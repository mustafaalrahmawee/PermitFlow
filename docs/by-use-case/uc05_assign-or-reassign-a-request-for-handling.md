# UC-05 — Assign or Reassign a Request for Handling

## Identity
- Domain: Review Workflow · Primary actor: Administrator · Supporting actors: Staff member · Level: User-Goal 🌊 [03_use-cases.md UC-05]

## Goal & trigger
A submitted or active request needs a responsible staff member, or an already assigned request needs a different responsible staff member. [03_use-cases.md UC-05]

## Preconditions
- The request has been submitted. [03_use-cases.md UC-05] [02_business-rules.md BR-004]
- At least one active staff member account exists. [03_use-cases.md UC-05] [02_business-rules.md BR-001]

## Main flow
1. The administrator opens the list of requests that need assignment or reassignment.
2. The administrator selects one eligible request.
3. The administrator selects one active staff member to become responsible for the request.
4. If the request is already assigned, the administrator enters a short reassignment reason.
5. The system validates that the assignment or reassignment is made by an administrator.
6. The system validates that the request status allows assignment or reassignment.
7. The system assigns the request to the selected staff member.
8. If this is a reassignment, the system records the previous responsible staff member, new responsible staff member, administrator, timestamp, and reason in the request history.
9. The system makes the request visible to the selected responsible staff member.
10. The system creates an in-portal notification for the selected responsible staff member.
11. If this is a reassignment, the system also creates an in-portal notification for the previous responsible staff member. [03_use-cases.md UC-05]

## Acceptance checklist (from extensions)
- ext 1a — with no requests needing assignment or reassignment, the system shows that there is nothing to assign. [03_use-cases.md UC-05]
- ext 2a — a Draft request cannot be assigned because it has not been submitted. [03_use-cases.md UC-05] [02_business-rules.md BR-004]
- ext 2b — a Decided request cannot be assigned or reassigned because it is closed in v1. [03_use-cases.md UC-05] [02_business-rules.md BR-004]
- ext 3a — selecting no staff member or more than one responsible staff member blocks the assignment until exactly one is selected. [03_use-cases.md UC-05] [02_business-rules.md BR-009]
- ext 3b — a staff account that is inactive or does not have the Staff member role cannot be assigned. [03_use-cases.md UC-05] [02_business-rules.md BR-001] [02_business-rules.md BR-009]
- ext 4a — a missing reassignment reason blocks reassignment until a short reason is entered. [03_use-cases.md UC-05]
- ext 5a — a non-administrator attempting assignment or reassignment is denied. [03_use-cases.md UC-05] [02_business-rules.md BR-010] [02_business-rules.md BR-016]
- ext 6a — a request whose status is not Submitted, In Review, Waiting for Citizen, or Ready for Decision cannot be assigned or reassigned. [03_use-cases.md UC-05] [02_business-rules.md BR-004]
- ext 8a — if the reassignment trace cannot be recorded, the reassignment does not complete. [03_use-cases.md UC-05] [02_business-rules.md BR-017]

## Authorization
- Assignment uses the `assign-requests` gate and is administrator-only. [docs/conventions.md Authorization] [02_business-rules.md BR-010]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]
- Request-scoped reach (`InteractsWithRequestScope`): administrators are among the authorized readers of the requests they assign. [docs/conventions.md Authorization] [02_business-rules.md BR-016]

## Data touched
- `Request` → `requests`; field written: `responsible_staff_user_account_id` (FK, nullable, restrict) — the single responsible staff member. [04_data-model.md §2.1] [02_business-rules.md BR-009]
- `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}); read to validate eligibility, never written here. [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Request history` → `request_history_entries` + `HistoryEventType` enum (`assignment_changed`); fields: `actor_user_account_id` (the administrator), `previous_staff_user_account_id`, `new_staff_user_account_id`, `summary`, `reason` (reassignment), `event_occurred_at`. [04_data-model.md §2.1] [02_business-rules.md BR-017]
- `User account` / `Role` → `user_accounts` (`role`, `account_state` value sets); the selected assignee must be an active account with the Staff member role. [04_data-model.md §2.1] [02_business-rules.md BR-001]
- `Notification` → `notifications` + `NotificationType` enum (`assigned`, `reassigned`); fields: `recipient_user_account_id`, `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]

## Status transition(s)
_None._ Assignment and reassignment change the responsible staff member, not the request status; the status is only validated against the eligible set (Submitted, In Review, Waiting for Citizen, Ready for Decision). [03_use-cases.md UC-05] [02_business-rules.md BR-004]

## History events (written explicitly)
- `assignment_changed` — on reassignment, records previous responsible staff member, new responsible staff member, administrator (actor), timestamp, and reason; written in the same transaction as the assignment, and the reassignment does not complete without it (ext 8a). [03_use-cases.md UC-05] [02_business-rules.md BR-017] [docs/conventions.md History & traceability]
- `assignment_changed` — on first assignment, records the new responsible staff member and administrator [derived — UC-05 step 8 states the trace only for reassignment, but 04_data-model.md §1.3 says "Assignment and reassignment facts are captured in Request history entry" and 03_use-cases.md UC-03 step 5 shows assignments in the citizen-visible history — fragile].

## Notifications (best-effort)
- `assigned` — created for the newly selected responsible staff member (step 10). [03_use-cases.md UC-05] [04_data-model.md §2.1]
- `reassigned` — on reassignment, also created for the previous responsible staff member (step 11). [03_use-cases.md UC-05] [04_data-model.md §2.1]
- In-portal notifications may degrade gracefully; the primary action completes without them [derived — UC-05 states no notification-failure extension of its own; 05_system-design.md §4 classifies in-portal notifications as may-degrade — fragile]. [05_system-design.md §4]

## Performance target
No dedicated per-UC target; UC-05 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Must not fail silently: the reassignment trace is part of the durable-write path — if the history entry cannot be recorded, the reassignment does not complete (ext 8a); assignment write and history entry are saved together in one transaction. [03_use-cases.md UC-05] [05_system-design.md §4] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- Authorization must fail closed; a failed or unevaluable check denies assignment. [05_system-design.md §4]
- The request is never assigned to an unauthorized, inactive, ambiguous, or untraceable responsible staff member. [03_use-cases.md UC-05]
- May degrade gracefully: the `assigned` / `reassigned` notifications. [05_system-design.md §4]

## API seam  [derived — fragile]
- `GET /api/admin/requests` — `auth:sanctum` + `assign-requests`; returns the requests eligible for assignment or reassignment (status in {`submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`}) with their current responsible staff member; empty `data` array when nothing needs assignment. [derived from 03_use-cases.md UC-05 steps 1–2 + ext 1a + docs/conventions.md API success responses]
- `GET /api/admin/assignable-staff` — `auth:sanctum` + `assign-requests`; returns active user accounts with the Staff member role, for the assignee selection. [derived from 03_use-cases.md UC-05 step 3 + BR-001 + BR-009]
- `PUT /api/requests/{request}/assignment` — `auth:sanctum` + `assign-requests`; request fields: `responsible_staff_user_account_id`, `reason` (required when the request is already assigned); assigns or reassigns, writes the history trace, creates the notification(s); success `200` with the updated request in the `data` envelope. [derived from 03_use-cases.md UC-05 steps 3–11 + docs/conventions.md API success responses]

## QA map  [derived — fragile]
- ext 1a → `GET /api/admin/requests` returns HTTP `200` with `data: []`; no DB effect. [derived from 03_use-cases.md UC-05 ext 1a + docs/conventions.md API success responses]
- ext 2a → HTTP `409` when assigning a Draft request; expected DB effect: `requests.responsible_staff_user_account_id` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-05 ext 2a + BR-004 + docs/conventions.md API error responses]
- ext 2b → HTTP `409` when assigning or reassigning a Decided request; expected DB effect: `requests.responsible_staff_user_account_id` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-05 ext 2b + BR-004 + docs/conventions.md API error responses]
- ext 3a → HTTP `422` when `responsible_staff_user_account_id` is missing or not a single id; expected DB effect: no change. [derived from 03_use-cases.md UC-05 ext 3a + BR-009 + docs/conventions.md API error responses]
- ext 3b → HTTP `422` when the selected account is inactive or not a Staff member; expected DB effect: no change. [derived from 03_use-cases.md UC-05 ext 3b + BR-001 + BR-009 + docs/conventions.md API error responses]
- ext 4a → HTTP `422` when reassigning without a `reason`; expected DB effect: no change. [derived from 03_use-cases.md UC-05 ext 4a + docs/conventions.md API error responses]
- ext 5a → HTTP `403` when a non-administrator calls the assignment seam (role gate, not request-scoped); expected DB effect: no change. [derived from 03_use-cases.md UC-05 ext 5a + BR-010 + BR-016 + docs/conventions.md API error responses]
- ext 6a → HTTP `409` for any status outside {`submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`}; expected DB effect: no change. [derived from 03_use-cases.md UC-05 ext 6a + BR-004 + docs/conventions.md API error responses]
- ext 8a → HTTP `500` on a history-write failure; expected DB effect: transaction rollback — `requests.responsible_staff_user_account_id` unchanged, no `request_history_entries` row, no `notifications` row. [derived from 03_use-cases.md UC-05 ext 8a + docs/conventions.md API error responses]
- Happy path (first assignment) → HTTP `200`; expected DB effect: `requests.responsible_staff_user_account_id` set, one `request_history_entries` row (`event_type` = `assignment_changed`, `new_staff_user_account_id` set), one `notifications` row (`notification_type` = `assigned`) for the assignee. [derived from 03_use-cases.md UC-05 steps 7–10]
- Happy path (reassignment) → HTTP `200`; expected DB effect: `requests.responsible_staff_user_account_id` replaced, one `request_history_entries` row (`event_type` = `assignment_changed`, `previous_staff_user_account_id` and `new_staff_user_account_id` set, `reason` set), two `notifications` rows (`assigned` for the new, `reassigned` for the previous staff member). [derived from 03_use-cases.md UC-05 steps 7–11]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the administrator acts through protected routes as an active account. [derived from 03_use-cases.md UC-05 preconditions + docs/conventions.md Auth]
- Cross-domain (Requests): UC-02 submit-a-request must exist so a Submitted request is available to assign; a Draft cannot be assigned. [derived from 03_use-cases.md UC-05 preconditions + ext 2a + BR-004]
- Cross-domain (Identity and Access): UC-01 account provisioning supplies the active Staff member accounts that can be assigned and the administrator performing the assignment. [derived from 03_use-cases.md UC-05 preconditions + BR-001 + BR-013]
- Foundation: `requests`, `user_accounts`, `request_history_entries`, `notifications` migrations/models; `RequestStatus`, `HistoryEventType`, `NotificationType` enums; the `assign-requests` gate in `AppServiceProvider`; the request-scope concern. [derived from docs/conventions.md Data & schema + Authorization]

## Notes
Reassignment is allowed in v1 before a request is Decided. Draft requests cannot be assigned, and Decided requests are terminal for v1. [03_use-cases.md UC-05]
