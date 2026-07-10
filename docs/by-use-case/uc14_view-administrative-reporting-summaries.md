# UC-14 — View Administrative Reporting Summaries

## Identity
- Domain: Reporting · Primary actor: Administrator · Supporting actors: _None_ · Level: User-Goal 🌊 [03_use-cases.md UC-14]

## Goal & trigger
The administrator wants a basic organization-level summary of request volume, status, or processing progress. [03_use-cases.md UC-14]

## Preconditions
- The actor has the Administrator role. [03_use-cases.md UC-14] [02_business-rules.md BR-001]
- Requests exist, or the actor accepts an empty summary. [03_use-cases.md UC-14]

## Main flow
1. The administrator opens reporting summaries.
2. The system validates that the actor may access reporting summaries.
3. The administrator selects the summary view they need.
4. The system shows authorized request volume, request status, or processing-progress information.
5. The administrator reviews the summary for oversight. [03_use-cases.md UC-14]

## Acceptance checklist (from extensions)
- ext 1a — a citizen attempting to open reporting summaries is denied access. [03_use-cases.md UC-14] [02_business-rules.md BR-015] [02_business-rules.md BR-016]
- ext 2a — a non-authorized actor attempting to view administrative reporting summaries is denied access. [03_use-cases.md UC-14] [02_business-rules.md BR-015] [02_business-rules.md BR-016]
- ext 3a — a selected summary with no matching requests shows an empty summary rather than an error. [03_use-cases.md UC-14]
- ext 4a — a summary that would reveal request information outside authorized oversight does not show that information. [03_use-cases.md UC-14] [02_business-rules.md BR-016]

## Authorization
- Reporting summaries are behind the `view-reporting` role gate — staff member or administrator; citizens are not among the authorized viewers. Gate denial is `403` because reporting is a role gate, not request-scoped reach. [docs/conventions.md Authorization] [02_business-rules.md BR-015] [Table BR-016]
- **[derived — fragile]** The *administrative* variant is narrower than the shared gate: ext 2a denies a non-authorized actor (e.g. a staff member) the administrative summaries, so this seam additionally requires the Administrator role. The conventions define no dedicated admin-reporting gate — only `view-reporting` (staff or admin) — so the extra check is derived and an implementation may realize it as a second gate, an ability, or an in-controller role check. [derived from 03_use-cases.md UC-14 ext 2a + BR-015 + docs/conventions.md Authorization]
- The administrator's content scope is what oversight requires: administrators see request information "only what oversight requires", and visibility is never public. Organization-level aggregates for oversight fall within that scope. [02_business-rules.md BR-016 notes]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-001] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. Role is evaluated live, never from a cached copy. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; read only: `status`, `responsible_staff_user_account_id` (assignment data feeding processing-progress aggregation), `submitted_at`; organization-wide (single institution); no column written. [04_data-model.md §2.1] [05_system-design.md §2]
- `Request status` → `requests.status` + `RequestStatus` enum (set: {`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}); read for the per-status counts. [04_data-model.md §2.1] [02_business-rules.md BR-004]
- `User account` / `Role` → `user_accounts` (+ `role` value set); read for the actor's identity and role check. [04_data-model.md §2.1] [02_business-rules.md BR-001]
- Reporting summaries are **not persisted**: they are derived data recomputed on every read from `requests`, `decisions`, and status + assignment data — always fresh, no stored copy, no rebuild job. [04_data-model.md §2.1 "Accounted business objects not persisted"] [05_system-design.md §2]

## Status transition(s)
_None._ UC-14 is a pure read; `requests.status` is aggregated, never changed, and the `TransitionsRequestStatus` guard is not involved. [03_use-cases.md UC-14] [docs/conventions.md Status transitions]

## History events (written explicitly)
_None._ BR-017 covers important status changes and decisions; a summary read is neither, and the UC-14 flow states no history write. [03_use-cases.md UC-14] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ The UC-14 flow creates no notification and `Notification` is not among its business objects touched. [03_use-cases.md UC-14]

## Performance target
Response time p95 < 1000 ms; p99 < 2000 ms — read-time aggregation (step 4); latency-tolerant, the loosest bound among the critical UCs. SLO over 30 days: reporting p95 ≤ 1000 ms. [05_system-design.md §3, §3.1]

## Reliability
- May degrade gracefully: a summary with no matching requests shows an empty view over an error (ext 3a). [03_use-cases.md UC-14] [05_system-design.md §4]
- Access-control evaluation must fail closed: a check that cannot be evaluated denies rather than permits. [05_system-design.md §4]
- Silent-wrong-response watch: a reporting summary must never be read off a partially-applied transaction — read committed state only. [05_system-design.md §4]
- Evolvability note: the summary is a recomputed view; if the §3 target is breached at grown volume, the `later` trigger is a cache derived from `requests` + `decisions` + status/assignment data — swap without touching the system of record. [05_system-design.md §1.2, §6]

## API seam  [derived — fragile]
- `GET /api/reporting/admin-summary` — `auth:sanctum` + `view-reporting` gate + Administrator role (the derived narrowing flagged in Authorization); no request body; returns one organization-level summary object in the `data` envelope carrying request-volume, per-status counts, and processing-progress aggregates over all requests of the single organization, from which the client selects the view (step 3); success `200`. [derived from 03_use-cases.md UC-14 steps 1–4 + BR-015 + BR-016 + docs/conventions.md Authorization + API success responses]

## QA map  [derived — fragile]
- ext 1a → HTTP `403` when an authenticated citizen calls the administrative summary (`view-reporting` gate denies; role gate, not request-scoped, so not `404`); expected DB effect: none (read-only). [derived from 03_use-cases.md UC-14 ext 1a + BR-015 + docs/conventions.md API error responses]
- ext 2a → HTTP `403` when an authenticated staff member (in the shared gate but not an administrator) calls the administrative summary; expected DB effect: none. [derived from 03_use-cases.md UC-14 ext 2a + BR-015 + docs/conventions.md API error responses — fragile: rests on the derived admin-only narrowing flagged in Authorization]
- ext 3a → HTTP `200` with a zero-count / empty summary object in the `data` envelope, not an error status; expected DB effect: none. [derived from 03_use-cases.md UC-14 ext 3a]
- ext 4a → HTTP `200` + response-body assertion: the payload contains only organization-level aggregates needed for oversight and no request information beyond that scope (e.g. no request bodies or message content in the summary payload); expected DB effect: none. [derived from 03_use-cases.md UC-14 ext 4a + BR-016]
- Happy path (administrator) → HTTP `200`; `data` envelope with organization-level volume, per-status, and processing-progress aggregates; expected DB effect: no row written in any table (pure read; no `request_history_entries`, no `notifications`). [derived from 03_use-cases.md UC-14 steps 1–4 + docs/conventions.md API success responses]
- Unauthenticated (no / invalid bearer token) → HTTP `401` on the protected route. [derived from docs/conventions.md Auth + API error responses + BR-018]
- Authenticated-but-inactive account (reachable only via a *stale* token — login itself 401s an inactive account and issues no token) → HTTP `403`: the `view-reporting` gate denies fail-closed on its `isActive()` check before the controller runs, so this seam returns the gate's 403 rather than a 401. This is the actual foundation behavior for every gated route (identical on the shipped UC-13 `view-reporting` route); `docs/conventions.md` (401 clause) states an inactive account on a protected route should be 401, so a convention/foundation gap remains — closing it requires a global Sanctum-layer rejection of inactive-account tokens, outside this use case's slice. [derived — corrected at UC-14 implementation to match observed fail-closed foundation behavior; convention gap flagged as an Open Question]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the administrator acts through a protected route as an active account. [derived from 03_use-cases.md UC-14 preconditions + docs/conventions.md Auth]
- Foundation: `user_accounts` (+ `role` value set) and `requests` migrations/models; the `RequestStatus` enum; the `view-reporting` gate plus the derived administrator narrowing; the API success/error envelopes. [derived from docs/conventions.md Data & schema + Authorization + API success responses + API error responses]
- Data-population (soft) dependencies — the endpoint is buildable and testable without them because ext 3a mandates an empty summary, but a meaningful summary needs: cross-domain (Requests) UC-02 submit-a-request (creates the requests being counted); cross-domain (Review Workflow) UC-05 assignment (assignment data in the aggregation), UC-08 update-request-progress (moves statuses the per-status counts reflect), and UC-09 record-a-decision (populates `decisions`, a named source of the read-time aggregation). [derived from 05_system-design.md §2 + 03_use-cases.md UC-14 ext 3a]

## Notes
This use case keeps the same reporting process but separates the administrator as a distinct primary actor. [03_use-cases.md UC-14]
