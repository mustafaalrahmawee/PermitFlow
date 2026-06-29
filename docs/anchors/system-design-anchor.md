# Curated Anchor — System Design

## 1. Source Basis

- Kleppmann, Martin, _Designing Data-Intensive Applications_ (**2nd Edition**):
  - **Chapter 1 — Trade-Offs in Data Systems Architecture**: the data-intensive
    building blocks, System of Record vs. Derived Data, and data privacy / the
    right to be forgotten.
  - **Chapter 2 — Defining Nonfunctional Requirements**: performance (response
    time, throughput, percentiles), reliability (faults vs. failures), scalability,
    maintainability.
- Read by the author; concepts below are distilled, not summarized from memory.
- Full derivation / reading notes: `docs/anchor-sources/system-design.md`.

## 2. Provenance Legend

- `[K Kl 1.x]` / `[K Kl 2.x]` = Direct concept from Kleppmann, the named chapter.
- `[K Kl → P]` = Kleppmann concept, sharpened into a Project decision (applies to all my projects).
- `[A]` = Agent / author suggestion (no direct source).
- `[P]` = Pure Project decision.

## 3. Purpose

This anchor decides **which data-intensive building blocks a project needs** and
**how well the running system must behave**, and binds the vocabulary for any
`05_system-design.md` file. Philosophy: simple before fancy; derived data must be
rebuildable from its source; measure quality, never assert it. [K Kl 1/2 → P]

Every "yes" decision must reference a use case or business rule. Every quality
target must be measurable. "The system should be fast" is not allowed;
"List Active Items must respond within 500 ms at p95" is. [P]

## 4. Accepted Concepts — Building Blocks (Ch. 1)

| #     | Concept                         | Provenance   | Rule for `05_system-design.md`                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| ----- | ------------------------------- | ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| SC-01 | Three building blocks           | [K Kl 1]     | Databases (store data to find it later), caches (remember the result of an expensive operation to speed up reads), search indexes (search/filter by keyword). Each gets an explicit yes/no/later decision.                                                                                                                                                                                                                                                                                          |
| SC-02 | System of Record                | [K Kl 1]     | Holds the authoritative/canonical data; written first; each fact represented once (normalized). On any discrepancy, the system of record is correct by definition.                                                                                                                                                                                                                                                                                                                                  |
| SC-03 | Derived Data                    | [K Kl 1 → P] | Data transformed from another source. **Rebuildable**: if lost, it can be re-created from the source. Common forms: **caches** and **search indexes**, and any rebuildable computed state (e.g., cached dashboards, search facets, or aggregated metrics) derived from the system of record. (Kleppmann also lists other forms of derived data, such as denormalized values and materialized views; those forms are out of scope here and should not be introduced unless a use case demands them.) |
| SC-04 | Tool is not the role            | [K Kl 1]     | Whether something is a system of record or derived data depends on **how it is used**, not on the tool. State explicitly which data is derived from which.                                                                                                                                                                                                                                                                                                                                          |
| SC-05 | Update propagation              | [K Kl 1 → P] | When source data changes, the derived data must be updated by an explicit process (e.g. invalidating a cache or updating a search index when the underlying database record changes).                                                                                                                                                                                                                                                                                                               |
| SC-06 | Privacy / right to be forgotten | [K Kl 1]     | Personal data may be collected only for a specified purpose, not kept longer than necessary (data minimization). Deletion must also reach **derived** datasets, not only the system of record.                                                                                                                                                                                                                                                                                                      |

## 5. Accepted Concepts — Nonfunctional Requirements (Ch. 2)

### 5a. Performance

| #      | Concept                                 | Provenance   | Rule                                                                                                                                                                                                                                                                                                                                                          |
| ------ | --------------------------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NF-01  | Response time vs. throughput            | [K Kl 2]     | Response time = what the client sees (all delays included); throughput = requests/data per second. Name which metric a target refers to.                                                                                                                                                                                                                      |
| NF-02  | Latency / service time / queueing delay | [K Kl 2]     | Distinguish them: service time = active processing; queueing delay = waiting; latency = time the request is latent. Measure response time on the **client** side.                                                                                                                                                                                             |
| NF-03  | Percentiles, not averages               | [K Kl 2 → P] | Use p50 (median), p95, p99, p999 (tail latencies). The average hides how many users actually experienced a delay. **Every performance target is a percentile, never an average.** Averaging percentiles is meaningless.                                                                                                                                       |
| NF-03b | Service Level Objective (SLO)           | [K Kl 2 → P] | An SLO is an internal, measurable target that bundles percentile goals into a named promise over a time window (e.g. "over 30 days, search holds p95 < 500 ms and 99.9% of requests are non-error"). An **SLA** (a contract with penalties when the SLO is missed) is named but **not used** in this project — there are no paying clients with refund terms. |

### 5b. Reliability

| #      | Concept                                              | Provenance   | Rule                                                                                                                                                                                                                                                                                                                                                                                        |
| ------ | ---------------------------------------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NF-04  | Reliability = working correctly when things go wrong | [K Kl 2]     | The system keeps providing the required service despite adversity.                                                                                                                                                                                                                                                                                                                          |
| NF-05  | Fault vs. failure                                    | [K Kl 2]     | Fault = one component stops working correctly (a disk dies, a machine crashes, or an external service the system depends on has an outage). Failure = the whole system stops providing the service. A fault-tolerant system tolerates faults without failing.                                                                                                                               |
| NF-06  | Single point of failure (SPOF)                       | [K Kl 2]     | A part whose fault necessarily escalates into a system failure. Name what must not fail silently vs. what may degrade.                                                                                                                                                                                                                                                                      |
| NF-07  | Hardware vs. software faults                         | [K Kl 2]     | Hardware faults are mostly independent; software faults are often **correlated** (same bug on every node) and therefore harder, causing more failures.                                                                                                                                                                                                                                      |
| NF-07b | Silent wrong responses                               | [K Kl 2 → P] | A component can fail not only by crashing or timing out, but by returning a wrong result without raising an error (Kleppmann's "CPU that returns the wrong result"). Examples include an external API that returns HTTP 200 with stale or incorrect data, or a cache serving outdated state. Such silent faults need **validation of the business state**, not only timeout/retry handling. |

### 5c. Scalability

| #     | Concept                   | Provenance   | Rule                                                                                                                                                                                                                                                                |
| ----- | ------------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NF-08 | "Scalable" is not a label | [K Kl 2]     | Ask concrete questions instead: how does the system cope if load grows in a particular way; how do we add resources; when do we hit the limit of the current architecture?                                                                                          |
| NF-09 | Vertical vs. horizontal   | [K Kl 2 → P] | Vertical (scaling up, shared-memory): a bigger machine — simple, but cost grows faster than linearly. Horizontal (scaling out, shared-nothing): more machines — scales but adds distributed-systems complexity. **Project rule: vertical first, horizontal later.** |

### 5d. Maintainability

| #     | Concept                                                   | Provenance | Rule                                                                                                                                                                                  |
| ----- | --------------------------------------------------------- | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NF-10 | Maintainability = operability + simplicity + evolvability | [K Kl 2]   | Most software cost is ongoing maintenance, not initial build. Operability (easy to run), Simplicity (easy to understand; avoid the "big ball of mud"), Evolvability (easy to change). |
| NF-11 | Abstraction manages complexity                            | [K Kl 2]   | Hide implementation behind clean interfaces; distinguish essential from accidental complexity where useful.                                                                           |

## 6. Rejected Concepts (Skip List)

| #     | Concept                                                                        | Provenance   | Why excluded                                                                                                                                   |
| ----- | ------------------------------------------------------------------------------ | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| RC-01 | Stream processing, batch processing                                            | [K Kl 1 → P] | Not needed; the project uses manual commands, not an orchestrated pipeline.                                                                    |
| RC-02 | OLTP vs. OLAP, data warehouse, data lake                                       | [K Kl 1 → P] | No analytical/BI system in scope.                                                                                                              |
| RC-03 | Cloud vs. self-hosting trade-off                                               | [K Kl 1]     | Deployment choice, out of architecture scope.                                                                                                  |
| RC-04 | Distributed vs. single-node theory                                             | [K Kl 1 → P] | Single-node first; distribution is "later".                                                                                                    |
| RC-05 | Microservices, serverless                                                      | [K Kl 1 → P] | Monolith by choice; microservices = premature complexity.                                                                                      |
| RC-06 | Cloud computing vs. supercomputing (HPC)                                       | [K Kl 1]     | Irrelevant to a single-node portfolio SaaS or small-team business application.                                                                 |
| RC-07 | Tail latency amplification                                                     | [K Kl 2 → P] | Matters for many-call microservice requests; the monolith does not need it.                                                                    |
| RC-08 | SLA (service level agreement) contract                                         | [K Kl 2 → P] | The SLO is now accepted (NF-03b); only the SLA contract-with-penalties is excluded, since the project has no paying clients with refund terms. |
| RC-09 | Blameless postmortems, chaos engineering, retry storms, hardware failure rates | [K Kl 2 → P] | Operational depth beyond an architecture anchor.                                                                                               |
| RC-10 | Data model, tables, normalization detail                                       | [K]          | `data-model-anchor.md`                                                                                                                         |
| RC-11 | Business rules themselves                                                      | [K]          | `business-rules-anchor.md` — referenced, not redefined                                                                                         |

## 7. Application Rules (How to apply these concepts to any project)

- **Decide each building block explicitly.** For database, cache, and search index,
  write yes / no / later, each "yes" referencing a use case or rule. [K Kl 1 → P]
- **Mark system-of-record vs. derived.** State which data is authoritative and which
  is rebuildable, and the process that rebuilds the derived data. [K Kl 1]
- **State performance as percentiles.** Name the metric (response time / throughput)
  and give a p95/p99 target per critical use case; never an average. [K Kl 2 → P]
- **Name faults vs. failures.** Say what must not fail silently, what may degrade,
  and where the SPOFs are. [K Kl 2]
- **Scale vertical first.** Default to a bigger single machine; treat horizontal
  scaling as a later step triggered by a concrete limit. [K Kl 2 → P]
- **Keep it simple and rebuildable.** Avoid premature distribution, microservices,
  and caching; ensure all derived data can be regenerated from the source. [K Kl → P]
- **Deletion reaches derived data.** A deletion in the system of record must also
  remove the corresponding derived data (cache, index). [K Kl 1]

## 8. Binding Vocabulary

**Use these terms** — the controlled vocabulary of this anchor:

- Building blocks: `database`, `cache`, `search index`, `system of record`, `derived data`, `rebuildable` [K Kl 1]
- Performance: `response time`, `throughput`, `service time`, `queueing delay`, `latency`, `percentile` (`p50`/`p95`/`p99`/`p999`), `tail latency`, `service level objective (SLO)` [K Kl 2] _(`SLA` is named but not used — see RC-08)_
- Reliability: `fault`, `failure`, `fault-tolerant`, `single point of failure (SPOF)` [K Kl 2]
- Scalability: `vertical scaling` / `scaling up`, `horizontal scaling` / `scaling out`, `shared-memory`, `shared-nothing`, `load` [K Kl 2]
- Maintainability: `operability`, `simplicity`, `evolvability` [K Kl 2]

**Forbidden** — any occurrence means the file has drifted into another phase or into excluded scope:

- Implementation/code: `CREATE TABLE`, framework class names, migration code, raw SQL
- Excluded architecture: `Kafka`, `Kubernetes`, `microservice`, `serverless`, `data warehouse`, `data lake`, `stream processing`
- Averages where percentiles are correct: a bare "average response time" target
- Data-model design: `foreign key`, `normal form`, `join table` _(belong to `data-model-anchor.md`)_

_(A target may **name** a use case and a measurable number; it never contains the
implementing code. The boundary is: decide and measure, never implement.)_

## 9. Role Usage Rule

The role file (e.g. `05-system-design-architect.md`) consumes this anchor as
**binding context**. Kleppmann Chapters 1–2 are background knowledge, distilled in
`docs/anchor-sources/system-design.md`; the book is not the direct prompt source.

Hard rules for every generated `05_system-design.md`:

1. Database, cache, and search index each have an explicit yes/no/later decision,
   and every "yes" references a use case or business rule. [K Kl 1 → P]
2. Performance targets are percentiles (p95/p99) with a named metric, never
   averages. [K Kl 2 → P]
3. The file names faults vs. failures, what may degrade, and the SPOFs; it scales
   vertical-first. [K Kl 2 → P]
4. System-of-record and derived data are distinguished, derived data is rebuildable,
   and deletion reaches derived data. The file decides and measures; it never
   contains implementation code. [K Kl 1 / 2 → P]

---

> **Scope note.** This anchor covers Kleppmann's building blocks (Ch. 1) and the
> four nonfunctional-requirement areas (Ch. 2: performance, reliability, scalability,
> maintainability). Operational depth (chaos engineering, retry handling, hardware
> failure rates), analytical systems (OLAP, warehouses, lakes), and distributed
> architecture (microservices, stream processing) are deliberately excluded as
> premature for a single-node portfolio SaaS or small-team business application. They can be distilled later
> if the system grows into them.
