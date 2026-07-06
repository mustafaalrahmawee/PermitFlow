# UC-08 — Update Request Progress

## Identity
- Domain: Review Workflow · Primary actor: Staff member · Supporting actors: Citizen · Level: User-Goal 🌊 [03_use-cases.md UC-08]

## Goal & trigger
The responsible staff member needs to move a request to the next understandable status. [03_use-cases.md UC-08]

## Preconditions
- The request is assigned to the staff member. [03_use-cases.md UC-08] [02_business-rules.md BR-009]
- The intended status belongs to the defined status set. [03_use-cases.md UC-08] [02_business-rules.md BR-004]

## Main flow
1. The staff member opens the assigned request.
2. The staff member chooses the next appropriate request status.
3. The system validates the status against the defined status set.
4. The system validates the status change against the allowed v1 transition graph.
5. The system changes the request status.
6. The system records the important status change in the request history.
7. The system creates an in-portal notification for the citizen when the change is relevant to the citizen. [03_use-cases.md UC-08]

## Acceptance checklist (from extensions)
- ext 1a — a staff member the request is not assigned to is denied the action. [03_use-cases.md UC-08] [02_business-rules.md BR-009] [02_business-rules.md BR-016]
- ext 2a — a status outside the defined set is rejected. [03_use-cases.md UC-08] [02_business-rules.md BR-004]
- ext 4a — a transition not allowed in the v1 transition graph is blocked and the request stays unchanged. [03_use-cases.md UC-08] [02_business-rules.md BR-004]
- ext 5a — if the status change cannot be applied, the request status remains unchanged. [03_use-cases.md UC-08]
- ext 6a — if the status change cannot be recorded understandably, the progress change does not complete. [03_use-cases.md UC-08] [02_business-rules.md BR-017]
- ext 7a — if the notification cannot be created, the status change remains recorded and visible in the request. [03_use-cases.md UC-08]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request is reachable only by its owning citizen, its responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- Progress updates belong to the responsible staff member (`RequestPolicy@review`). [docs/conventions.md Authorization] [02_business-rules.md BR-009]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; written: `status`; read: `owner_user_account_id` (notification recipient), `responsible_staff_user_account_id` (authorization). [04_data-model.md §2.1]
- `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}). [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Request history` → `request_history_entries` + `HistoryEventType` enum (`status_changed`); fields: `actor_user_account_id` (the staff member), `from_status`, `to_status`, `summary`, `event_occurred_at`. [04_data-model.md §2.1] [02_business-rules.md BR-017]
- `Notification` → `notifications` + `NotificationType` enum (`status_changed`); fields: `recipient_user_account_id` (owning citizen), `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]

## Status transition(s)
- The staff member's chosen transition, validated by the `TransitionsRequestStatus` guard against the allowed v1 map: draft→submitted; submitted→in_review; in_review→{waiting_for_citizen, ready_for_decision}; waiting_for_citizen→in_review; ready_for_decision→decided; decided is terminal. [03_use-cases.md UC-08 notes] [02_business-rules.md BR-004] [docs/conventions.md Status transitions]
- Through this seam the characteristic staff-driven move is `in_review` → `ready_for_decision` [derived — the other transitions are owned by dedicated use cases: draft→submitted by UC-02, submitted→in_review by UC-06, in_review→waiting_for_citizen by UC-07, waiting_for_citizen→in_review by UC-04, ready_for_decision→decided by UC-09 — fragile].

## History events (written explicitly)
- `status_changed` (`from_status` = the prior status, `to_status` = the chosen status) — written in the same transaction as the status change; the progress change does not complete without it (ext 6a). [03_use-cases.md UC-08] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]

## Notifications (best-effort)
- `status_changed` — created for the owning citizen when the change is relevant to the citizen; the status change completes even if the notification cannot be created (ext 7a). [03_use-cases.md UC-08] [05_system-design.md §4] [derived — which changes count as "relevant to the citizen" is not enumerated by the specs; treating every status change through this seam as citizen-relevant is the assumed default — fragile]

## Performance target
No dedicated per-UC target; UC-08 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Must not fail silently: the status transition and its history entry are the durable-write path — the request never moves to an undefined, disallowed, or untraceable status; status change and history entry are saved together in one transaction. [03_use-cases.md UC-08] [05_system-design.md §4] [02_business-rules.md BR-004] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- Authorization must fail closed; a failed or unevaluable check denies. [05_system-design.md §4]
- May degrade gracefully: the citizen notification (ext 7a). [05_system-design.md §4]

## API seam  [derived — fragile]
- `PATCH /api/requests/{request}/status` — `auth:sanctum` + `RequestPolicy@review` (responsible staff); request field: `status` (a `RequestStatus` slug); validates against the enum set (422) and the transition guard (409), applies the change, writes the history entry, creates the citizen notification when relevant; success `200` with the updated request in the `data` envelope. [derived from 03_use-cases.md UC-08 steps 2–7 + docs/conventions.md Status transitions + API success responses]

## QA map  [derived — fragile]
- ext 1a → HTTP `404` when the caller is not the responsible staff member (out-of-scope records read as not found; an in-scope non-responsible actor such as the owning citizen or an administrator gets `403` from the policy); expected DB effect: no change. [derived from 03_use-cases.md UC-08 ext 1a + BR-009 + BR-016 + docs/conventions.md API error responses]
- ext 2a → HTTP `422` when `status` is not one of the six defined slugs; expected DB effect: `requests.status` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-08 ext 2a + BR-004 + docs/conventions.md API error responses]
- ext 4a → HTTP `409` when the requested transition is not in the allowed v1 graph (e.g. `in_review → decided`); expected DB effect: `requests.status` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-08 ext 4a + BR-004 + docs/conventions.md Status transitions + API error responses]
- ext 5a → HTTP `500` when the status change cannot be persisted; expected DB effect: transaction rollback — `requests.status` unchanged, no `request_history_entries` or `notifications` row. [derived from 03_use-cases.md UC-08 ext 5a + docs/conventions.md API error responses]
- ext 6a → HTTP `500` when the history entry cannot be persisted; expected DB effect: transaction rollback — `requests.status` unchanged, no `request_history_entries` or `notifications` row. [derived from 03_use-cases.md UC-08 ext 6a + BR-017 + docs/conventions.md API error responses]
- ext 7a → not triggerable through the API; verified by fault injection at test level: the call returns success, `requests.status` holds the new value with its `request_history_entries` row, and no `notifications` row exists. [derived from 03_use-cases.md UC-08 ext 7a + 05_system-design.md §4]
- Happy path → HTTP `200`; expected DB effect: `requests.status` = the chosen slug, one `request_history_entries` row (`event_type` = `status_changed`, `from_status`/`to_status` set), one `notifications` row (`notification_type` = `status_changed`) for the owning citizen when the change is citizen-relevant. [derived from 03_use-cases.md UC-08 steps 5–7]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the staff member acts through protected routes as an active account. [derived from 03_use-cases.md UC-08 preconditions + docs/conventions.md Auth]
- UC-05 assign-a-request must exist first: the request must have this staff member as its responsible handler. [derived from 03_use-cases.md UC-08 preconditions + BR-009]
- UC-06 review-an-assigned-request must exist first: the staff-driven progress moves start from In Review, which start-review produces. [derived from 03_use-cases.md UC-06 step 5 + BR-004]
- Cross-domain (Requests): UC-02 submit-a-request supplies the submitted request and its owning citizen, the notification recipient. [derived from 03_use-cases.md UC-08 step 7 + BR-003]
- Foundation: `requests`, `request_history_entries`, `notifications` migrations/models; `RequestStatus`, `HistoryEventType`, `NotificationType` enums; the `TransitionsRequestStatus` guard trait and `IllegalStatusTransitionException`; `RequestPolicy@review` and the request-scope concern. [derived from docs/conventions.md Data & schema + Status transitions + Authorization]

## Notes
The allowed v1 transition graph is: Draft → Submitted; Submitted → In Review; In Review → Waiting for Citizen; Waiting for Citizen → In Review; In Review → Ready for Decision; Ready for Decision → Decided. Decided is terminal in v1. [03_use-cases.md UC-08]
