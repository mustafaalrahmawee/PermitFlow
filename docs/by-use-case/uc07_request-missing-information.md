# UC-07 — Request Missing Information

## Identity
- Domain: Review Workflow · Primary actor: Staff member · Supporting actors: Citizen · Level: User-Goal 🌊 [03_use-cases.md UC-07]

## Goal & trigger
The responsible staff member finds that a request lacks information needed for review. [03_use-cases.md UC-07]

## Preconditions
- The request is assigned to the staff member. [03_use-cases.md UC-07] [02_business-rules.md BR-009]
- The request is In Review and missing information may be requested. [03_use-cases.md UC-07] [02_business-rules.md BR-004]

## Main flow
1. The staff member opens the assigned request.
2. The staff member chooses to request missing information.
3. The staff member writes a message that explains what the citizen must provide.
4. The system records the message on the request.
5. The system changes the request status to Waiting for Citizen.
6. The system records the status change in the request history.
7. The system creates an in-portal notification for the citizen. [03_use-cases.md UC-07]

## Acceptance checklist (from extensions)
- ext 1a — a staff member the request is not assigned to is denied the action. [03_use-cases.md UC-07] [02_business-rules.md BR-009] [02_business-rules.md BR-016]
- ext 2a — a request whose status does not allow a missing-information request blocks the action. [03_use-cases.md UC-07] [02_business-rules.md BR-004]
- ext 3a — an empty message is not sent; the system asks for a clear message. [03_use-cases.md UC-07]
- ext 4a — an invalid message participant relationship blocks the message. [03_use-cases.md UC-07] [02_business-rules.md BR-011]
- ext 5a — if the status cannot be changed to Waiting for Citizen, the request keeps its prior status and the citizen is not notified. [03_use-cases.md UC-07] [02_business-rules.md BR-004] [02_business-rules.md BR-017]
- ext 7a — if the notification cannot be created, the missing-information request remains recorded and visible to the citizen inside the request. [03_use-cases.md UC-07]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request is reachable only by its owning citizen, its responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- The action belongs to the responsible staff member (`RequestPolicy@review`); the message itself is guarded by `MessagePolicy@create` — the request's citizen or responsible staff only. [docs/conventions.md Authorization] [02_business-rules.md BR-009] [02_business-rules.md BR-011]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; written: `status`; read: `owner_user_account_id` (notification and message recipient), `responsible_staff_user_account_id` (sender validation). [04_data-model.md §2.1]
- `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}). [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Message` → `messages` + `MessageKind` enum (`missing_information_request`); fields: `request_id`, `sender_user_account_id` (responsible staff), `recipient_user_account_id` (owning citizen), `body`, `sent_at`. [04_data-model.md §2.1] [02_business-rules.md BR-011]
- `Request history` → `request_history_entries` + `HistoryEventType` enum (`information_requested`); `message_id` FK links the entry to the recorded message. [04_data-model.md §2.1] [02_business-rules.md BR-017]
- `Notification` → `notifications` + `NotificationType` enum (`missing_information_requested`); fields: `recipient_user_account_id` (owning citizen), `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]

## Status transition(s)
- `in_review` → `waiting_for_citizen`, on step 5; validated by the `TransitionsRequestStatus` guard against the allowed v1 map. [03_use-cases.md UC-07] [02_business-rules.md BR-004] [docs/conventions.md Status transitions]

## History events (written explicitly)
- `information_requested` — linked to the recorded message via `message_id` and carrying `from_status` = `in_review`, `to_status` = `waiting_for_citizen`; written in the same transaction as the message and the status change. [02_business-rules.md BR-017] [04_data-model.md §2.1] [derived — UC-07 step 6 says "records the status change in the request history"; whether that is one `information_requested` entry carrying the status pair or a separate `status_changed` entry is an implementation choice; the single linked entry is assumed here — fragile]

## Notifications (best-effort)
- `missing_information_requested` — created for the owning citizen (step 7). The missing-information request completes even if the notification cannot be created (ext 7a); the message stays visible inside the request. [03_use-cases.md UC-07] [05_system-design.md §4]

## Performance target
No dedicated per-UC target; UC-07 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Must not fail silently: the message record, the `in_review → waiting_for_citizen` transition, and the history entry — the citizen is never notified of a missing-information request that was not recorded; message, status change, and history entry are saved together in one transaction. [03_use-cases.md UC-07] [05_system-design.md §4] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- Authorization must fail closed; a failed or unevaluable check denies. [05_system-design.md §4]
- May degrade gracefully: the citizen notification (ext 7a). [05_system-design.md §4]

## API seam  [derived — fragile]
- `POST /api/requests/{request}/request-information` — `auth:sanctum` + `RequestPolicy@review` (responsible staff) with the message participant relationship validated per `MessagePolicy@create`; request field: `body` (the message explaining what the citizen must provide); records the `missing_information_request` message, transitions `in_review → waiting_for_citizen`, writes the history entry, creates the citizen notification; success `200` with the updated request in the `data` envelope. [derived from 03_use-cases.md UC-07 steps 2–7 + docs/conventions.md Authorization + Status transitions + API success responses]

## QA map  [derived — fragile]
- ext 1a → HTTP `404` when the caller is not the responsible staff member (out-of-scope records read as not found; an in-scope non-responsible actor such as the owning citizen or an administrator gets `403` from the policy); expected DB effect: no change. [derived from 03_use-cases.md UC-07 ext 1a + BR-009 + BR-016 + docs/conventions.md API error responses]
- ext 2a → HTTP `409` when the request is not `in_review` (illegal transition); expected DB effect: `requests.status` unchanged, no `messages`, `request_history_entries`, or `notifications` row. [derived from 03_use-cases.md UC-07 ext 2a + BR-004 + docs/conventions.md Status transitions + API error responses]
- ext 3a → HTTP `422` on an empty `body`; expected DB effect: no `messages` row. [derived from 03_use-cases.md UC-07 ext 3a + docs/conventions.md API error responses]
- ext 4a → HTTP `403` when the message participant relationship is invalid (sender is not the responsible staff member or recipient is not the owning citizen); expected DB effect: no `messages` row. [derived from 03_use-cases.md UC-07 ext 4a + BR-011 + docs/conventions.md API error responses]
- ext 5a → HTTP `500` when the status change or its history entry cannot be persisted; expected DB effect: transaction rollback — `requests.status` remains `in_review`, no `messages`, `request_history_entries`, or `notifications` row. [derived from 03_use-cases.md UC-07 ext 5a + BR-004 + BR-017 + docs/conventions.md API error responses]
- ext 7a → not triggerable through the API; verified by fault injection at test level: the call returns success, the `messages` row and `request_history_entries` row exist, `requests.status` = `waiting_for_citizen`, and no `notifications` row exists. [derived from 03_use-cases.md UC-07 ext 7a + 05_system-design.md §4]
- Happy path → HTTP `200`; expected DB effect: one `messages` row (`message_kind` = `missing_information_request`, sender = responsible staff, recipient = owning citizen), `requests.status` = `waiting_for_citizen`, one `request_history_entries` row (`event_type` = `information_requested`, `message_id` set, `from_status` = `in_review`, `to_status` = `waiting_for_citizen`), one `notifications` row (`notification_type` = `missing_information_requested`) for the owning citizen. [derived from 03_use-cases.md UC-07 steps 4–7]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the staff member acts through protected routes as an active account. [derived from 03_use-cases.md UC-07 preconditions + docs/conventions.md Auth]
- UC-05 assign-a-request must exist first: the request must have this staff member as its responsible handler. [derived from 03_use-cases.md UC-07 preconditions + BR-009]
- UC-06 review-an-assigned-request must exist first: the request must be In Review, which start-review (`submitted → in_review`) produces. [derived from 03_use-cases.md UC-07 preconditions + BR-004]
- Cross-domain (Requests): UC-02 submit-a-request supplies the submitted request and its owning citizen, the message recipient. [derived from 03_use-cases.md UC-07 step 7 + BR-003]
- Foundation: `requests`, `messages`, `request_history_entries`, `notifications` migrations/models; `RequestStatus`, `MessageKind`, `HistoryEventType`, `NotificationType` enums; the `TransitionsRequestStatus` guard trait; `RequestPolicy@review`, `MessagePolicy@create`, and the request-scope concern. [derived from docs/conventions.md Data & schema + Status transitions + Authorization]

## Notes
This use case is high-risk because it opens controlled post-submission input; the extension set keeps that boundary explicit. [03_use-cases.md UC-07]
