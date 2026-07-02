# System Design — PermitFlow

## 1. Building Blocks

### 1.1 Database
- **Decision:** yes
- **Justification:** `04_data-model.md §2.1` — the nine-table model needs a persisted store; every fact in the workflow (`03_use-cases.md UC-01`…`UC-14`) is read back later.
- **Role:** system of record for `user_accounts`, `request_categories`, `organization_settings`, `requests`, `documents`, `decisions`, `request_history_entries`, `messages`, `notifications`.
- **Notes:** One normalized relational database. The two whole-block JSON columns (`requests.request_details`, `organization_settings.settings_payload`, `04_data-model.md §3.1`) live inside this store; they are not a separate building block. Choice is relational at the category level only.

### 1.2 Cache
- **Decision:** no (reporting is a `later` candidate)
- **Justification:** No UC has a read path whose recompute cost exceeds invalidation cost at single-institution scale. The list/detail reads (`03_use-cases.md UC-03`, `UC-06`, `UC-05`) are direct owner / responsible-staff / status filters, and the only aggregation — reporting summaries (`UC-13`, `UC-14`) — is cheap to recompute here. A cache would add invalidation logic against the single-developer constraint (`00_project-context.md §6`).
- **System of record:** _Not applicable in v1._
- **Update propagation:** _Not applicable in v1._
- **Rebuild path:** _Not applicable in v1._
- **Notes:** `later` trigger — if the reporting target in §3 is breached at grown volume, introduce a cache derived from `requests` + `decisions` + status/assignment data, invalidated on write to those tables.

### 1.3 Search Index
- **Decision:** no (`later` if free-text request search is added)
- **Justification:** No UC requires full-text or faceted search. Request bodies are read as a whole block and not queried per element (`04_data-model.md §3.1`); the staff and citizen views (`03_use-cases.md UC-03`, `UC-06`) are simple status/ownership filters relational querying serves directly.
- **System of record:** _Not applicable in v1._
- **Update propagation:** _Not applicable in v1._
- **Rebuild path:** _Not applicable in v1._
- **Notes:** `later` trigger — if a free-text-search-across-requests UC is introduced, index from `requests` and `messages`, synchronized by `updated_at` propagation or a rebuild job.

### 1.4 Other Building Blocks
- **Decision:** queue / scheduler / async worker — no (`later` when external notification delivery arrives)
- **Justification:** Notifications are in-portal only (`01_miniworld.md §5`) and are created synchronously inside each UC, with the primary action completing even when the notification cannot be created (`03_use-cases.md UC-02` ext. 10a, `UC-04` ext. 10a, `UC-07` ext. 7a, `UC-09` ext. 10a). No scheduled exists in v1. `later` trigger — external delivery (a later-phase scope item) needs an async worker.

## 2. System of Record vs. Derived Data

| Dataset | Role | Rebuildable from |
| --- | --- | --- |
| `user_accounts` (`04_data-model.md §2.1`) | system of record | _(canonical)_ |
| `request_categories` | system of record | _(canonical)_ |
| `organization_settings` | system of record | _(canonical)_ |
| `requests` | system of record | _(canonical)_ |
| `documents` | system of record | _(canonical)_ |
| `decisions` | system of record | _(canonical)_ |
| `request_history_entries` | system of record | _(canonical — see note)_ |
| `messages` | system of record | _(canonical)_ |
| `notifications` | system of record | _(canonical)_ |
| Reporting summaries (read-time) | derived data (computed aggregation) | `requests`, `decisions`, status + assignment data |

Every derived entry is rebuildable from a system-of-record entry. The reporting summary holds no stored copy: it is recomputed on every read (`03_use-cases.md UC-13`, `UC-14`; `04_data-model.md §2` "Reporting summaries — not persisted"), so it is always fresh and needs no separate rebuild job.

**Note — denormalizations that are system of record, not derived data:** `request_history_entries.summary` is a frozen audit snapshot whose value depends on *not* being regenerated (`BR-017`); the two JSON blocks are whole-block storage (`04_data-model.md §3.1`). None of these are rebuildable derived data, and rebuilding the history summary would defeat its traceability purpose.

## 3. Performance Targets (per critical UC)

Response time is measured on the client side.

- **`UC-03` — Track Request Progress:** response time p95 < 500 ms; p99 < 1000 ms. Source: steps 4–6 (status + history + messages read). The most frequent citizen read; tightest bound.
- **`UC-06` — Review an Assigned Request:** response time p95 < 800 ms; p99 < 1500 ms. Source: step 4 (request + documents + messages + history read). Heaviest read; looser bound.
- **`UC-02` — Submit a Request:** response time p95 < 800 ms; p99 < 1500 ms. Source: steps 7–10 (the submission transaction). Excludes document byte upload, which is throughput-bound on file size, not a percentile target in v1.
- **`UC-09` — Record a Decision:** response time p95 < 800 ms; p99 < 1500 ms. Source: steps 7–9 (decision + status + history write). Closes the request path.
- **`UC-13` / `UC-14` — Reporting summaries:** response time p95 < 1000 ms; p99 < 2000 ms. Source: step 4 (read-time aggregation). Latency-tolerant work-planning; loosest bound.

p95 bounds the case the user routinely experiences; p99 bounds the tail so a small fraction never hits multi-second hangs. The reporting bound is looser because it is heavier aggregation and not on an interactive critical path.

### 3.1 Service Level Objective (SLO)
- Over 30 days: response-time p95 ≤ 800 ms on the critical interactive UCs (`UC-02`, `UC-03`, `UC-06`, `UC-09`); reporting (`UC-13` / `UC-14`) p95 ≤ 1000 ms; ≥ 99.9% of requests are non-error. The targets above are assumed against the modest load profile in §5; if that profile is revised the thresholds are revisited.
- _(No SLA. SLAs are out of scope — the project has no paying clients with refund terms.)_

## 4. Reliability

- **Must not fail silently:** the durable-write path carrying legal and audit state — status transitions (`BR-004`), decision recording (`BR-007`, `BR-008`), and every history entry (`BR-017`). The use cases already refuse to report success when the history write fails (`03_use-cases.md UC-08` ext. 6a, `UC-09` ext. 9a). Access-control evaluation (`BR-016`) must fail closed: a check that cannot be evaluated denies rather than permits.
- **May degrade gracefully:** in-portal notifications (the notifying action completes without them — `03_use-cases.md UC-02` ext. 10a, `UC-07` ext. 7a, `UC-09` ext. 10a); reporting summaries (empty view over error — `UC-13` ext. 3a); document retrieval (a brief file-store fault hides the file while the request record stays intact).
- **Single points of failure (SPOFs):** the single-node database (system of record) — accepted v1 risk, mitigated by backup/restore plus a trivially rebuildable derived layer. The external file store referenced by `documents.file_reference` — its fault degrades document access but must not corrupt request records; the referential-restrict rules in `04_data-model.md §2.1` prevent orphaning. Accepted v1 risk with backups.
- **Silent-wrong-response watch (`system-design-anchor.md NF-07b`):** the file store returning a stale or wrong file for a `file_reference` — validate the reference belongs to the in-scope request (`BR-016`) before serving. A reporting summary read off a partially-applied transaction — read committed state only. A stale cached permission — evaluate role and ownership live, never from a cached copy.

## 5. Scalability

- **Current load profile (assumed; `00_project-context.md §3`–`§4` name the actors but no volumes):** one institution; three roles (Citizen, Staff member, Administrator); request and notification volume consistent with a single public office. Concrete counts are not stated upstream and are not invented here — see Open Questions.
- **Scaling stance:** vertical first. Horizontal scaling is `later`, triggered by a concrete limit — for example single-node database CPU or I/O saturation under sustained write load.
- **Concrete next limits to watch:** sustained write throughput on the append-heavy `request_history_entries`; database connection ceiling under concurrent staff/citizen reads; file-store capacity growth from accumulated `documents`.

## 6. Maintainability

- **Operability:** single-developer, single-node operation (`00_project-context.md §6`) — a monolith whose main operational task is backup and restore. No mail server is run because notifications are in-portal only (`01_miniworld.md §5`), keeping the operational surface small.
- **Simplicity:** deliberately kept out — cache, search index, queues, and the excluded architecture in `system-design-anchor.md RC-01`…`RC-05`; secondary / performance indexes are also deferred (`04_data-model.md §2.1`).
- **Evolvability:** reporting and notification delivery are expected to change first and are isolated. Reporting is a recomputed view that can be swapped for a cache without touching the system of record (§1.2); notifications are already best-effort and decoupled from the primary action (§4), so adding external delivery (§1.4 `later`) does not disturb the core workflow.

## 7. Privacy / Right to be Forgotten

- **Personal data inventory (`04_data-model.md §2.1`, anchored to `BR-016`):** `user_accounts.display_name`, `user_accounts.email`; `requests.title`, `requests.request_details`; `documents.file_reference`, `documents.original_filename`; `decisions.decision_text`; `messages.body`; `request_history_entries.summary`, `request_history_entries.reason`; `notifications.body`. The external privacy basis named by `BR-016` is EU GDPR / DSGVO; visibility is request-scoped, never public.
- **Retention purpose:** account fields — identify and authenticate a portal user (`03_use-cases.md UC-00`; inactive accounts are denied access per `BR-018`). Request title/details and documents — the substance the institution must hold to decide the request. Decision text and history — the traceable outcome and progress record (`BR-017`). Messages — the request-scoped citizen–staff exchange. Notifications — in-portal alerting about the recipient's own requests.
- **Deletion behaviour:** on a deletion request, personal data is removed from the listed tables; because the only derived dataset is the read-time reporting summary, recomputation after deletion already excludes the removed records and no separate purge is needed. The unresolved part is the erasure rule on the source tables themselves: v1 uses deactivation rather than physical deletion for accounts tied to request history (`03_use-cases.md UC-01` notes; the inactive state and its access effect are governed by `BR-018`), and the referential-restrict rules in `04_data-model.md §2.1` block hard deletion, which collides with `BR-017` traceability. See Open Questions.

## 8. Open Questions
- No upstream rule defines retention periods or an erasure mechanism for personal data; `BR-016` governs access, not deletion, and `BR-017` keeps history undeletable. The right-to-be-forgotten path on the source tables is therefore unresolved for v1.
- The load profile in §5 is assumed: `00_project-context.md §3`–`§4` state the actors and single organization but no request, user, or notification volumes, so the §3 percentile targets rest on that assumption and should be confirmed before they are treated as committed.