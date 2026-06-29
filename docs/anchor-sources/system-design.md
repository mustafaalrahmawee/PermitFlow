# Anchor Source — System Design (Kleppmann, 2nd Edition)

> Derivation / reading notes behind `system-design-anchor.md`.
> **Scope of this summary:** only the concepts and sections that were _adopted_
> into the anchor. Topics that were read but deliberately rejected (stream/batch
> processing, OLAP/warehouse/lake, cloud-vs-self-hosting, distributed-vs-single-node,
> microservices/serverless, supercomputing, the Twitter case study, tail-latency
> amplification, SLA contracts, postmortems, chaos engineering, hardware failure
> rates) are intentionally **not** summarized here — see the anchor's Skip List for
> why each was excluded.

---

## Chapter 1 — Trade-Offs in Data Systems Architecture

### The three building blocks (→ SC-01)

Data-intensive applications are built from standard building blocks. The three that
this project uses:

- **Databases** — store data so that they, or another application, can find it again
  later.
- **Caches** — remember the result of an expensive operation, to speed up reads.
- **Search indexes** — allow users to search data by keyword or filter it in
  various ways.

Each block is a deliberate choice with trade-offs; no single approach is
fundamentally better than others. The anchor turns this into an explicit
yes/no/later decision per block.

### System of Record vs. Derived Data (→ SC-02, SC-03, SC-04)

This is the most important Chapter 1 idea, because it names the fundamental
pattern of any data system: authoritative source data vs. computed/cached state
derived from it.

- **System of record** (source of truth): holds the authoritative/canonical version
  of data. New data is written here first. Each fact is represented exactly once
  (typically normalized). On any discrepancy between systems, the system of record
  is correct **by definition**.
- **Derived data**: the result of transforming data from another system. **Rebuildable**: if lost, it can be re-created from the source. Common forms are **caches** and **search indexes**, and any rebuildable computed state (e.g., cached dashboards, search facets, or aggregated metrics) derived from the system of record. Kleppmann's full list also includes denormalized values, materialized views, and trained models; those are noted for completeness but are **out of scope** and are not bound in the anchor.
- **Key property — rebuildable:** if derived data is lost, it can be re-created from
  the source. This is what later decides what may be cached or indexed, and what
  must never be the only copy.
- **The tool is not the role:** most databases are neither inherently a system of
  record nor derived — it depends on _how you use them in your application_. Being
  explicit about which data is derived from which brings clarity to the architecture.

### Update propagation (→ SC-05)

When data in one system is derived from another, you need an explicit process to
update the derived data when the source changes. Many databases do not make this
cross-system propagation easy, so it must be designed deliberately.

### Privacy / right to be forgotten (→ SC-06)

From "Data Systems, Law, and Society": personal data may be collected only for a
specified, explicit purpose, not used for other purposes, and not kept longer than
necessary — the principle of **data minimization**. Regulations such as the GDPR
grant a **right to be forgotten** (deletion on request). The engineering challenge:
deletion must also reach **derived** datasets (caches, indexes), not
only the system of record.

---

## Chapter 2 — Defining Nonfunctional Requirements

Functional requirements say what the app must do; nonfunctional requirements say how
well it must behave (fast, reliable, scalable, maintainable). The four areas below
are the ones adopted.

### 2a. Performance

**Response time vs. throughput (→ NF-01)**

- _Response time_ = what the client sees: the elapsed time from making a request to
  receiving the answer, including all delays.
- _Throughput_ = requests (or data volume) per second the system processes.
  Name which metric a target refers to.

**Latency / service time / queueing delay (→ NF-02)**
The book separates terms that are often conflated:

- _Service time_ = the duration the service is actively processing the request.
- _Queueing delay_ = time waiting (e.g. for a free CPU) before processing.
- _Latency_ = a catchall for time during which the request is latent (not being
  actively processed); network latency = travel time through the network.
  Because queueing delay (e.g. head-of-line blocking) is a big part of variability,
  **measure response time on the client side.**

**Percentiles, not averages (→ NF-03)**
Response time is a _distribution_, not a single number. The average hides how many
users actually experienced a delay.

- Sort response times fastest → slowest.
- **p50 (median):** half the requests are faster. Tells you the _typical_ experience.
- **p95 / p99 / p999:** 95 / 99 / 99.9 percent of requests are faster; the rest are
  slower. These high percentiles are the **tail latencies**.
  Tail latencies matter because the slowest requests often belong to the most valuable
  users (most data). But optimizing the extreme tail (e.g. p9999) has diminishing
  returns. **Rule: every performance target is a percentile, never an average.**
  Averaging percentiles together is mathematically meaningless (add histograms instead).

**Service Level Objective (→ NF-03b)**
An SLO bundles percentile targets into a named, measurable promise over a time window
(e.g. "over 30 days, search holds p95 < 500 ms and 99.9% of requests are non-error").
An **SLA** is the contract version with penalties when the SLO is missed — named here
for clarity but **not used** in this project (no paying clients with refund terms).

### 2b. Reliability

**Reliability = continuing to work correctly when things go wrong (→ NF-04).**
"Working correctly" roughly means: performs the expected function, tolerates user
mistakes, performs well enough under expected load, prevents unauthorized access.

**Fault vs. failure (→ NF-05)**

- _Fault_ = one component stops working correctly (a disk dies, a machine crashes, a
  dependency has an outage).
- _Failure_ = the system as a whole stops providing the required service.
  A **fault-tolerant** system keeps serving users despite certain faults. The same
  event can be a fault at one level and a failure at another (a failed disk is a
  failure of the disk but only a fault for a system with redundancy).

**Single point of failure (→ NF-06)**
A part whose fault necessarily escalates into a system failure. Name what must not
fail silently vs. what may degrade.

**Hardware vs. software faults (→ NF-07)**
Hardware faults are mostly _independent_ (one disk failing doesn't make others fail).
Software faults are often _correlated_ — the same bug runs on every node — and are
therefore harder to anticipate and cause more system failures.

**Silent wrong responses (→ NF-07b)**
Kleppmann notes faults that don't crash: a CPU that occasionally returns the wrong
result, or a dependency that returns corrupted responses. In practice, this includes
any external API that returns HTTP 200 with stale or incorrect data, or a cache
serving outdated state — a fault that _looks like success_. Unlike a crash or timeout,
it cannot be caught by retry logic alone; it needs **validation of the business
state itself**. Marked `[K → P]` because it applies Kleppmann's "wrong result
without crashing" idea to the external-service case.

### 2c. Scalability

**"Scalable" is not a label (→ NF-08).** It's meaningless to say "X is scalable."
Instead ask concrete questions: if load grows in a particular way, what are our
options; how do we add resources; when do we hit the limit of the current
architecture? First understand current _load_ (throughput, peaks, read/write ratio).

**Vertical vs. horizontal (→ NF-09)**

- _Vertical (scaling up, shared-memory):_ a bigger machine — more CPU/RAM/disk on one
  node. Simple, but cost grows faster than linearly and bottlenecks limit the gain.
- _Horizontal (scaling out, shared-nothing):_ more independent machines coordinated
  over the network. Can scale near-linearly and tolerate failures, but brings the
  full complexity of distributed systems (sharding, network).
  **Project rule: vertical first, horizontal later** — don't rush into distribution.

### 2d. Maintainability

Most of the cost of software is ongoing maintenance, not initial development. Three
principles (→ NF-10):

- **Operability:** make it easy to keep the system running (monitoring, good defaults,
  predictable behavior, avoiding dependency on individual machines).
- **Simplicity:** make it easy to understand; avoid the "big ball of mud" and
  unnecessary complexity.
- **Evolvability:** make it easy to change for unanticipated future requirements;
  minimize irreversibility.

**Abstraction manages complexity (→ NF-11):** a good abstraction hides implementation
behind a clean interface and can be reused. (Essential vs. accidental complexity is a
useful but imperfect distinction, since the boundary shifts as tooling evolves.)

---

## Why these and not the rest

The project is a single-node portfolio SaaS or small-team business application in an early phase. The adopted
concepts give it a vocabulary for _which building blocks to use_, _how to state
performance and reliability targets_, and _how to keep the system simple and
maintainable_. Everything about distribution, analytics, and large-scale operations
was read and consciously left out as premature — to be revisited only if the system
grows into those needs.
