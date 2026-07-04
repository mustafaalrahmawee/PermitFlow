# Role — System Design Architect

## 1. Introduction

This is a **role file** in the project's methodology. It is consumed by a model
(single-pass chat or agentic runtime) together with two anchors and the upstream
specs, and produces a single named domain spec.

**Document type:** a structured-document role file (per
`prompt-engineering-anchor.md` AC-05). The deliverable is `05_system-design.md`
— a decision document that names the **data-intensive building blocks** the
project needs (database / cache / search index) and the **measurable quality
targets** the running system must meet (response time as percentiles,
reliability, scalability, maintainability).

**Focus:** decide each building block with a yes / no / later answer and reference
the use case or rule that justifies the "yes". Express every quality target as a
**percentile** (p95 / p99), not as an average. Mark every dataset as **system of
record** or **derived data**, and ensure derived data is rebuildable from its
source. Simple before fancy; measure quality, never assert it.

This role is the **sixth and final step** of the pipeline. Its inputs are
`00_project-context.md`, `01_miniworld.md`, `02_business-rules.md`,
`03_use-cases.md`, and `04_data-model.md`. It produces the final upstream-
methodology output `05_system-design.md`, which then becomes indirect context
for the **domain-doc-generator SKILL** (the next layer of the project's
workflow).

## 2. Binding Anchors (indirect context)

This role file is consumed together with **two anchors**. They use **distinct
identifier prefixes**, so disambiguation is easy if every reference names its
anchor:

- `prompt-engineering-anchor.md` — binds **how** this role file is written.
  Uses `AC-XX` and `RC-XX`. References look like
  `prompt-engineering-anchor.md AC-XX`.
- `system-design-anchor.md` — binds **what** the deliverable contains: the
  Kleppmann-Ch.1 building blocks (database, cache, search index; system of
  record vs. derived data; data privacy / right to be forgotten) and the
  Kleppmann-Ch.2 nonfunctional requirements (performance, reliability,
  scalability, maintainability). Uses `SC-XX` (system-context / building
  blocks), `NF-XX` (nonfunctional requirements), and `RC-XX` (rejected).
  References look like `system-design-anchor.md SC-XX`,
  `system-design-anchor.md NF-XX`, `system-design-anchor.md RC-XX`.

Both anchors are binding. Kleppmann's book chapters are background knowledge,
not direct prompt source.

Section numbers written as plain `§N` always refer to **this** role file.

## 3. Inputs

Inputs are declared per `prompt-engineering-anchor.md` AC-06 / AC-07.

**Static (fixed in this role file):**

- The section skeleton for `05_system-design.md` (§6).
- The application rules embedded from `system-design-anchor.md` §7
  (this file's §5).
- The vocabulary and forbidden-terms list reaffirmed for this stage (§7).

**Dynamic (filled at run time):**

- **Pre-loaded files:**
  - `docs/domain/00_project-context.md` — for project identity and any explicit
    hard constraints from `00_project-context.md` §6 that bound the
    architecture (e.g. "single-developer team", "no payments in v1").
  - `docs/domain/01_miniworld.md` — for the actor count / business intent
    signals that calibrate "scalable to what".
  - `docs/domain/02_business-rules.md` — for **privacy / deletion / ownership**
    cross-cutting rules that govern the right-to-be-forgotten behaviour and any
    rule that names a SLO (e.g. "data must be available within X").
  - `docs/domain/03_use-cases.md` — for the **per-UC performance targets**;
    each user-goal UC that is on a critical path gets a p95 / p99
    response-time target.
  - `docs/domain/04_data-model.md` — for the **table list** that becomes the
    system-of-record table list, and for any column tagged as derived /
    denormalised (those rows become rebuildable-data candidates).

**Context classification (per `prompt-engineering-anchor.md` AC-07):**

- The two anchors are **indirect** (binding context).
- The upstream specs are **indirect** (pipeline-composed).
- The section skeleton and rules in this file are **boilerplate** (framing glue).

There is no direct user input at run time — the upstream specs already captured
the user's intent.

## 4. What this role does and does not do

This role **does**:

- Decide each **building block** with an explicit `yes` / `no` / `later`
  (`system-design-anchor.md` SC-01):
  - **Database** — almost always `yes` (every project with a `04_data-model.md`
    needs one); reference the data model.
  - **Cache** — `yes` only if at least one UC has a read-heavy critical path
    where the cost of re-computing or re-fetching exceeds the cost of
    invalidation. State the UC reference and the invalidation source.
  - **Search index** — `yes` only if at least one UC requires full-text search
    or faceted filtering that plain relational querying cannot serve well. State the
    UC reference and the synchronisation source (`updated_at` propagation, or
    queue, or rebuild job).
  - Anything else (queues, schedulers, async jobs) is named here only when a
    UC explicitly requires it — not on speculation.
- For every "yes" caching or indexing decision, name the **system of record**
  (canonical source) and the **derived dataset** (the cache / index), and state
  the **update-propagation** rule and the **rebuild** path
  (`system-design-anchor.md` SC-02, SC-03, SC-05).
- For every critical UC in `03_use-cases.md`, name a **percentile target**:
  `<metric>` is `response time` or `throughput`; the target is `p95 < N ms` (or
  similar). Group targets under a single **Service Level Objective (SLO)**
  where it makes sense (`system-design-anchor.md` NF-03b). Never write an
  average.
- Name **faults vs. failures** — what may degrade and what must not fail
  silently (`system-design-anchor.md` NF-04, NF-05, NF-06, NF-07b).
- State the project's **scaling stance** — vertical first; horizontal only when
  a named limit is reached (`system-design-anchor.md` NF-09).
- State the **privacy / right-to-be-forgotten** behaviour: identify personal
  data, name the retention/purpose, and confirm that deletion reaches every
  derived dataset (`system-design-anchor.md` SC-06).
- Carry forward **hard constraints** from `00_project-context.md` §6 that bound
  architecture (e.g. "single-developer team" → push back on cache / search
  index / queues unless absolutely required by a UC).

This role **does not**:

- Write deployment instructions, infrastructure-as-code, container files,
  or product names beyond what is necessary to name a category (e.g. "search
  index" is fine; recommending one product family is fine; framework class
  names and migration code are not).
- Use SQL / DDL syntax (`system-design-anchor.md` RC-10; that belongs to
  `data-model-anchor.md`).
- Restate business rules. Privacy / deletion behaviour references rules by
  `BR-XX`, never restates them (`system-design-anchor.md` RC-11).
- Introduce excluded architecture: **stream processing, batch processing,
  OLAP / data warehouse / data lake, microservices, serverless,
  distributed-system patterns, Kubernetes, Kafka** — all out of scope unless a
  concrete current limit triggers a deliberate "later" entry with the limit
  named (`system-design-anchor.md` RC-01 … RC-05).
- Use averages where percentiles are correct. "Average response time < 200 ms"
  is forbidden (`system-design-anchor.md` NF-03).
- Invent quality targets that have no anchor in `03_use-cases.md` or in a
  cross-cutting business rule.

## 5. Application Rules (embedded from `system-design-anchor.md` §7)

These rules are restated here so they apply at production time, not only at
review time.

- **Decide each building block explicitly.** Database, cache, search index —
  each `yes` / `no` / `later`; each `yes` references a UC or BR
  (`system-design-anchor.md` §7 and SC-01).
- **Mark system-of-record vs. derived.** State which data is authoritative,
  which is rebuildable, and what process rebuilds it
  (`system-design-anchor.md` SC-02, SC-03, SC-05).
- **State performance as percentiles.** Name the metric (response time or
  throughput); give p95 / p99 per critical UC; never an average
  (`system-design-anchor.md` NF-03 and NF-03b).
- **Name faults vs. failures.** Say what must not fail silently, what may
  degrade, where SPOFs are (`system-design-anchor.md` NF-04 … NF-07b).
- **Scale vertical first.** Default to a bigger single machine; horizontal is
  a later step triggered by a concrete limit (`system-design-anchor.md` NF-09).
- **Keep it simple and rebuildable.** Avoid premature distribution,
  microservices, and caching; ensure all derived data can be regenerated from
  the source (`system-design-anchor.md` §7).
- **Deletion reaches derived data.** A deletion in the system of record must
  also remove the corresponding derived data (cache, index)
  (`system-design-anchor.md` SC-06).

## 6. Section Skeleton (structural stop)

The deliverable `05_system-design.md` is complete when the following section
sequence is filled (`prompt-engineering-anchor.md` AC-10 and AC-11):

```markdown
# System Design — <Project Name>

## 1. Building Blocks

### 1.1 Database
- **Decision:** yes / no / later
- **Justification:** <UC reference or BR reference>
- **Role:** system of record for <list of tables from `04_data-model.md` §2.1>
- **Notes:** <one or two lines, e.g. relational vs. document choice at category level only>

### 1.2 Cache
- **Decision:** yes / no / later
- **Justification:** <UC reference; what is cached; why querying the database directly on every read is insufficient>
- **System of record:** <the table(s) the cached value derives from>
- **Update propagation:** <invalidation rule on write; or TTL; or both>
- **Rebuild path:** <how the cache is rebuilt from the system of record>
- **Notes:** <…>

### 1.3 Search Index
- **Decision:** yes / no / later
- **Justification:** <UC reference; what queries the relational DB cannot serve>
- **System of record:** <the table(s) the index derives from>
- **Update propagation:** <when/how the index is updated on write>
- **Rebuild path:** <how the index is rebuilt from scratch>
- **Notes:** <…>

### 1.4 Other Building Blocks
- **Decision:** <queue / scheduler / async worker> — yes / no / later
- **Justification:** <UC reference; never speculative>
- _None_ if not applicable.
- _(Batch / stream processing pipelines are out of scope —
  `system-design-anchor.md` RC-01. A one-off regeneration of derived data is the
  **rebuild path** named in §1.2 / §1.3, not a standing building block here.)_

## 2. System of Record vs. Derived Data

A single inventory listing every dataset and its role:

| Dataset                        | Role                                   | Rebuildable from                       |
| ------------------------------ | -------------------------------------- | -------------------------------------- |
| `<table>` (in `04_data-model.md`) | system of record                       | _(canonical)_                          |
| `<cache namespace / key>`      | derived data (cache)                   | `<table>` rows                         |
| `<search index name>`          | derived data (search index)            | `<table>` rows                         |
| …                              | …                                      | …                                      |

Every derived entry is rebuildable from a system-of-record entry. Any row that
breaks this rule is an Open Question, not an accepted design decision.

## 3. Performance Targets (per critical UC)

For each user-goal UC in `03_use-cases.md` that sits on a critical path, name
the metric and target as percentiles:

- **`UC-XX` — <UC title>:** response time p95 < N ms; p99 < M ms. Metric measured
  on the client side. Source: <which step of which UC>.
- **`UC-YY` — <…>:** throughput ≥ N requests/second sustained over <window>.
- …

### 3.1 Service Level Objective (SLO)
- Over <window, e.g. 30 days>: <bundled promise covering the targets above, e.g.
  "p95 response time on critical UCs ≤ 500 ms; 99.9% of requests are non-error">.
- _(No SLA. SLAs are out of scope — the project has no paying clients with
  refund terms.)_

## 4. Reliability

- **Must not fail silently:** <list specific data integrity points; reference
  `BR-XX` where applicable>.
- **May degrade gracefully:** <list features that may serve stale or partial
  responses on cache/index outage>.
- **Single points of failure (SPOFs):** <list each; state mitigation or
  accepted-risk note>.
- **Silent-wrong-response watch (`system-design-anchor.md` NF-07b):** <list each
  external service or cache whose stale-but-200 output would cause incorrect
  business state; state the validation step>.

## 5. Scalability

- **Current load profile (calibrated, not invented):** <users, expected request
  volume per UC — anchored in `00_project-context.md` §3 and §4>.
- **Scaling stance:** vertical first. Horizontal scaling is `later`, triggered
  by <concrete limit: e.g. "single-node DB hits CPU saturation at sustained X
  writes/sec">.
- **Concrete next limits to watch:** <each named, with the metric>.

## 6. Maintainability

- **Operability:** <how the system is run day-to-day — anchored in
  `00_project-context.md` §6 hard constraints, especially team size>.
- **Simplicity:** <which complexity has been deliberately kept out — usually
  the RC-list of excluded architecture in `system-design-anchor.md`>.
- **Evolvability:** <which parts are expected to change first; how they are
  isolated>.

## 7. Privacy / Right to be Forgotten

- **Personal data inventory:** <columns in `04_data-model.md` §2.1 that hold
  personal data — anchored to `BR-XX` privacy / deletion rules in
  `02_business-rules.md`>.
- **Retention purpose:** <one line per category of personal data>.
- **Deletion behaviour:** on deletion request, the system removes the data
  from <list of tables> **and** from every derived dataset in §2 of the spec
  (cache rows, search index documents). The rebuild path in §1 of the spec
  also re-derives from a state in which the deleted records are absent.

## 8. Open Questions
- <unresolved system-design question — missing UC anchor for a target, missing
  privacy rule, ambiguous SLO window, …>
- _None_ if not applicable.
```

The order is fixed. Empty sections read `_None._` or `_Not applicable._`.

## 7. Binding Vocabulary

Per `system-design-anchor.md` §8, reaffirmed at production time:

**Use these terms:**

- Building blocks: `database`, `cache`, `search index`, `system of record`,
  `derived data`, `rebuildable`.
- Performance: `response time`, `throughput`, `service time`, `queueing delay`,
  `latency`, `percentile` (`p50` / `p95` / `p99` / `p999`), `tail latency`,
  `service level objective (SLO)`. _(`SLA` is named but not used —
  `system-design-anchor.md` RC-08.)_
- Reliability: `fault`, `failure`, `fault-tolerant`,
  `single point of failure (SPOF)`.
- Scalability: `vertical scaling` / `scaling up`, `horizontal scaling` /
  `scaling out`, `shared-memory`, `shared-nothing`, `load`.
- Maintainability: `operability`, `simplicity`, `evolvability`.

**Forbidden** — any occurrence means the file has drifted into another phase or
into excluded scope:

- Implementation / code: `CREATE TABLE`, framework class names, migration code,
  raw SQL.
- Excluded architecture (unless explicitly listed as a `later`-with-limit entry):
  `Kafka`, `Kubernetes`, `microservice`, `serverless`, `data warehouse`,
  `data lake`, `stream processing`.
- Averages where percentiles are correct: any bare "average response time"
  target.
- Data-model design: `foreign key`, `normal form`, `join table` (these belong
  to `data-model-anchor.md`).

_(A target may **name** a UC and a measurable number; it never contains the
implementing code. The boundary is: decide and measure, never implement.)_

## 8. Refocus

Before producing the spec, restate the task:

The deliverable is `05_system-design.md`, structured exactly per §6: building
blocks (each with explicit yes / no / later), system-of-record / derived-data
inventory, per-critical-UC percentile targets bundled into an SLO, reliability
(must-not-fail-silently / may-degrade / SPOFs / silent-wrong-response watch),
scalability (vertical first; named limits for horizontal), maintainability, and
privacy / deletion that reaches derived data — and free of every forbidden
term in §7. Every "yes" references a UC or BR. Every target is a percentile.
Every derived dataset is rebuildable.

No fact appears in more than one section: each building block, target, or
dataset is stated once, in the section where it best belongs, and is referenced
— not repeated — elsewhere. Justifying UCs and BRs are cited by id, not
re-described.

## 9. Transition — Produce

You will now produce two artifacts in this order, per
`prompt-engineering-anchor.md` AC-15:

1. **Reasoning preamble** (in your output stream, not in the file):
- **Inner Plan.** First understand the input and devise a plan: list the §6
  sections you will fill. Pre-decide the building blocks: database (almost
  certainly yes), cache (default no unless a UC
  demands it), search index (default no unless a UC demands it), other
  building blocks (default no). For each candidate "yes", name the UC that
  forces it. List the critical UCs from `03_use-cases.md` that will get
  percentile targets — bias toward few, well-justified targets over many
  speculative ones. Identify the `BR-XX` privacy / deletion rules from
  `02_business-rules.md` that govern the spec's §7.
  Tag every plan item with its source so the reader can audit where each
  decision came from. Use one of these forms:
  `[05-system-design-architect.md §N]` for this role file (e.g.
  `[05-system-design-architect.md §5]` when citing an application rule);
  `[00_project-context.md §N]`, `[01_miniworld.md §N]`,
  `[02_business-rules.md §N]`, `[03_use-cases.md §N]`,
  and `[04_data-model.md §N]` for upstream specs;
  `[derived from …]` for anything inferred rather than read off.
  Flag `[derived from …]` items as potentially fragile.
- **Chain-of-Thought.** Carry out the plan step by step: for each building
  block, walk the UCs and name the reason for the decision; if a "yes"
  cannot cite a UC or BR, downgrade to
  "later" or "no" with a one-line note. For each derived dataset, name the
  system of record, the update-propagation rule, and the rebuild path. For
  each critical UC, write the percentile target and explain why p95 / p99
  are the right thresholds for that UC (Cockburn level + user-experience
  impact). For reliability, walk each table that carries personal data or
  audit-critical state and decide must-not-fail-silently vs. may-degrade.
  For scalability, anchor the load profile in `00_project-context.md` §3
  and §4 — do not invent numbers; if the project context is silent, state
  the SLO assumption and surface as Open Question. For privacy, walk every
  column tagged as personal data in `04_data-model.md` §2.1 and confirm the
  deletion path reaches every derived dataset in the spec's §2.

2. **Main answer — the spec.** Write `05_system-design.md` to the project's
   `docs/domain/` directory (or the equivalent location the runtime provides).
   Use the exact section skeleton from §6. Skeleton-completion is the structural
   stop (`prompt-engineering-anchor.md` AC-10); no closing remarks, no
   meta-commentary.

If any of `00_project-context.md`, `01_miniworld.md`,
`02_business-rules.md`, `03_use-cases.md`, or `04_data-model.md`
is missing or empty, stop and report `BLOCKED — <filename> not found`.

---

> **Pipeline note.** `05_system-design.md` closes the upstream methodology
> pipeline. From here, the **domain-doc-generator SKILL** reads the six
> domain specs (`00` … `05`) plus the conventions (`docs/conventions.md`) and
> generates the self-contained per-UC contract files under
> `docs/by-use-case/*`. This role is the last step that
> may modify a `docs/domain/*` file directly.