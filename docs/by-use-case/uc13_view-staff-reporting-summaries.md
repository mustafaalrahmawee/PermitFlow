# UC-13 — View Staff Reporting Summaries

## Identity
- Domain: Reporting · Primary actor: Staff member · Supporting actors: _None_ · Level: User-Goal 🌊 [03_use-cases.md UC-13]

## Goal & trigger
The staff member wants a basic summary of request volume, status, or processing progress for work planning. [03_use-cases.md UC-13]

## Preconditions
- The actor has the Staff member role. [03_use-cases.md UC-13] [02_business-rules.md BR-001]
- Requests exist, or the actor accepts an empty summary. [03_use-cases.md UC-13]

## Main flow
1. The staff member opens reporting summaries.
2. The system validates that the actor may access reporting summaries.
3. The staff member selects the summary view they need.
4. The system shows authorized request volume, request status, or processing-progress information.
5. The staff member reviews the summary for work planning. [03_use-cases.md UC-13]

## Acceptance checklist (from extensions)
- ext 1a — a citizen attempting to open reporting summaries is denied access. [03_use-cases.md UC-13] [02_business-rules.md BR-015] [02_business-rules.md BR-016]
- ext 2a — a staff member attempting to view request information beyond their authorized scope has the view limited or denied. [03_use-cases.md UC-13] [02_business-rules.md BR-015] [02_business-rules.md BR-016]
- ext 3a — a selected summary with no matching requests shows an empty summary rather than an error. [03_use-cases.md UC-13]
- ext 4a — a summary that would reveal request information outside the staff member's permitted scope does not show that information. [03_use-cases.md UC-13] [02_business-rules.md BR-016]

## Authorization
- Reporting summaries are behind the `view-reporting` role gate — staff member or administrator; citizens are not among the authorized viewers. Gate denial is `403` because reporting is a role gate, not request-scoped reach. [docs/conventions.md Authorization] [02_business-rules.md BR-015] [Table BR-016]
- Within the gate, the *content* of the staff summary is bounded by request-scoped access: a staff member may see request information only for requests they are responsible for. [02_business-rules.md BR-016]
- **[derived — fragile]** The specs do not state what a staff member's summary scope is: BR-016 limits per-request information to the requests they are responsible for, but whether org-wide *anonymous counts* (no per-request information) are permitted for work planning is unstated. This contract takes the conservative reading — the staff summary aggregates only over requests where the actor is the responsible staff member. **Open Question:** may a staff summary include organization-wide aggregate counts that expose no individual request information, or is it strictly scoped to the actor's assigned requests? [derived from 03_use-cases.md UC-13 ext 2a/4a + BR-016]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-001] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. Role is evaluated live, never from a cached copy. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; read only: `status`, `responsible_staff_user_account_id` (scope filter [derived], per the Authorization reading above), `submitted_at`; no column written. [04_data-model.md §2.1]
- `Request status` → `requests.status` + `RequestStatus` enum (set: {`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}); read for the per-status counts. [04_data-model.md §2.1] [02_business-rules.md BR-004]
- `User account` / `Role` → `user_accounts` (+ `role` value set); read for the actor's identity, role gate, and the responsible-staff scope filter. [04_data-model.md §2.1] [02_business-rules.md BR-001]
- Reporting summaries are **not persisted**: they are derived data recomputed on every read from `requests`, `decisions`, and status + assignment data — always fresh, no stored copy, no rebuild job. [04_data-model.md §2.1 "Accounted business objects not persisted"] [05_system-design.md §2]

## Status transition(s)
_None._ UC-13 is a pure read; `requests.status` is aggregated, never changed, and the `TransitionsRequestStatus` guard is not involved. [03_use-cases.md UC-13] [docs/conventions.md Status transitions]

## History events (written explicitly)
_None._ BR-017 covers important status changes and decisions; a summary read is neither, and the UC-13 flow states no history write. [03_use-cases.md UC-13] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ The UC-13 flow creates no notification and `Notification` is not among its business objects touched. [03_use-cases.md UC-13]

## Performance target
Response time p95 < 1000 ms; p99 < 2000 ms — read-time aggregation (step 4); latency-tolerant work-planning, the loosest bound among the critical UCs. SLO over 30 days: reporting p95 ≤ 1000 ms. [05_system-design.md §3, §3.1]

## Reliability
- May degrade gracefully: a summary with no matching requests shows an empty view over an error (ext 3a). [03_use-cases.md UC-13] [05_system-design.md §4]
- Access-control evaluation must fail closed: a check that cannot be evaluated denies rather than permits. [05_system-design.md §4]
- Silent-wrong-response watch: a reporting summary must never be read off a partially-applied transaction — read committed state only. [05_system-design.md §4]
- Evolvability note: the summary is a recomputed view; if the §3 target is breached at grown volume, the `later` trigger is a cache derived from `requests` + `decisions` + status/assignment data — swap without touching the system of record. [05_system-design.md §1.2, §6]

## API seam  [derived — fragile]
- `GET /api/reporting/staff-summary` — `auth:sanctum` + `view-reporting` gate (staff member or administrator); no request body; returns one summary object in the `data` envelope carrying request-volume, per-status counts, and processing-progress aggregates computed over the actor's responsible requests (scope per the Authorization reading), from which the client selects the view (step 3); success `200`. [derived from 03_use-cases.md UC-13 steps 1–4 + BR-015 + BR-016 + docs/conventions.md Authorization + API success responses]

## QA map  [derived — fragile]
- ext 1a → HTTP `403` when an authenticated citizen calls the staff summary (`view-reporting` gate denies; role gate, not request-scoped, so not `404`); expected DB effect: none (read-only). [derived from 03_use-cases.md UC-13 ext 1a + BR-015 + docs/conventions.md API error responses]
- ext 2a → the staff summary is *limited*, not denied: HTTP `200` whose aggregates are verified by response-body assertion to cover only requests where the actor is the responsible staff member (e.g. two different staff members get different volume/status totals matching their own assigned requests). The administrative summary seam (`GET /api/reporting/admin-summary`) is the **UC-14** endpoint and is out of UC-13's slice; it is not implemented here and is not a UC-13 dependency, so a staff member calling it is denied by an absent route (`404`) rather than a `403` role-gate — both are a denial that satisfies ext 2a ("view limited or denied"). The substantive UC-13 check for ext 2a is therefore the staff-summary scope limitation, not the admin-summary status code; expected DB effect: none. [derived from 03_use-cases.md UC-13 ext 2a + BR-015 + BR-016 + docs/conventions.md API error responses — corrected: original expected a `403` from an admin-summary endpoint that belongs to UC-14; rests on the scope reading flagged in Authorization]
- ext 3a → HTTP `200` with a zero-count / empty summary object in the `data` envelope, not an error status; expected DB effect: none. [derived from 03_use-cases.md UC-13 ext 3a]
- ext 4a → HTTP `200` + response-body assertion: the payload contains only aggregates over the actor's authorized scope and no per-request information from outside it (e.g. counts equal the actor's assigned-request counts, no foreign request ids or titles in the body); expected DB effect: none. [derived from 03_use-cases.md UC-13 ext 4a + BR-016 — fragile: rests on the scope reading flagged in Authorization]
- Happy path (staff member) → HTTP `200`; `data` envelope with volume, per-status, and processing-progress aggregates; expected DB effect: no row written in any table (pure read; no `request_history_entries`, no `notifications`). [derived from 03_use-cases.md UC-13 steps 1–4 + docs/conventions.md API success responses]
- Unauthenticated or inactive account → HTTP `401` on the protected route. [derived from docs/conventions.md Auth + API error responses + BR-018]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the staff member acts through a protected route as an active account. [derived from 03_use-cases.md UC-13 preconditions + docs/conventions.md Auth]
- Foundation: `user_accounts` (+ `role` value set) and `requests` migrations/models; the `RequestStatus` enum; the `view-reporting` gate; the API success/error envelopes. [derived from docs/conventions.md Data & schema + Authorization + API success responses + API error responses]
- Data-population (soft) dependencies — the endpoint is buildable and testable without them because ext 3a mandates an empty summary, but a meaningful summary needs: cross-domain (Requests) UC-02 submit-a-request (creates the requests being counted); cross-domain (Review Workflow) UC-05 assignment (supplies the responsible-staff scope the staff summary filters on), UC-08 update-request-progress (moves statuses the per-status counts reflect), and UC-09 record-a-decision (populates `decisions`, a named source of the read-time aggregation). [derived from 05_system-design.md §2 + 03_use-cases.md UC-13 ext 3a]

## Notes
This use case is one half of the miniworld process "View reporting summaries"; the administrator variant is UC-14. [03_use-cases.md UC-13]
