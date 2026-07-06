# UC-09 — Record a Decision

## Identity
- Domain: Review Workflow · Primary actor: Staff member · Supporting actors: Citizen · Level: User-Goal 🌊 [03_use-cases.md UC-09]

## Goal & trigger
The responsible staff member has enough information to decide the request. [03_use-cases.md UC-09]

## Preconditions
- The request is assigned to the staff member. [03_use-cases.md UC-09] [02_business-rules.md BR-009]
- The request is Ready for Decision. [03_use-cases.md UC-09] [02_business-rules.md BR-004]
- The staff member has reviewed the submitted information. [03_use-cases.md UC-09]

## Main flow
1. The staff member opens the assigned request.
2. The staff member chooses to record a decision.
3. The staff member selects the decision outcome.
4. The staff member adds decision information or one decision document where needed.
5. The system validates that the decision is made by the responsible staff member.
6. The system validates the decision outcome and decision document.
7. The system records the decision on the request.
8. The system changes the request status to Decided.
9. The system records the decision and status change in the request history.
10. The system creates an in-portal notification for the citizen. [03_use-cases.md UC-09]

## Acceptance checklist (from extensions)
- ext 1a — a staff member the request is not assigned to is denied the action. [03_use-cases.md UC-09] [02_business-rules.md BR-009] [02_business-rules.md BR-016]
- ext 2a — a request that is not Ready for Decision blocks decision recording until it reaches the correct status. [03_use-cases.md UC-09] [02_business-rules.md BR-004]
- ext 3a — an outcome that is neither Approved nor Rejected is rejected. [03_use-cases.md UC-09] [02_business-rules.md BR-008]
- ext 4a — an attached document that is not a decision document for this request is rejected. [03_use-cases.md UC-09] [02_business-rules.md BR-006]
- ext 4b — if the system cannot accept or store the decision document, it rejects the document and does not record the decision as complete. [03_use-cases.md UC-09]
- ext 5a — an actor other than a staff member trying to make the decision is denied. [03_use-cases.md UC-09] [02_business-rules.md BR-007] [02_business-rules.md BR-016]
- ext 8a — if the request cannot move from Ready for Decision to Decided, no completed decision is recorded. [03_use-cases.md UC-09] [02_business-rules.md BR-004]
- ext 9a — if the decision cannot be recorded understandably in the request history, the decision does not complete. [03_use-cases.md UC-09] [02_business-rules.md BR-017]
- ext 10a — if the notification cannot be created, the decision remains recorded and visible to the citizen inside the request. [03_use-cases.md UC-09]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request is reachable only by its owning citizen, its responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- Deciding is `RequestPolicy@decide` — responsible staff member only; every decision is made by a staff member (human accountability). [docs/conventions.md Authorization] [02_business-rules.md BR-007] [02_business-rules.md BR-008] [02_business-rules.md BR-009]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Decision` → `decisions` + `DecisionOutcome` enum ({`approved`, `rejected`}); fields: `request_id` (unique — the single recorded outcome per request in v1), `decided_by_user_account_id` (the responsible staff member), `outcome`, `decision_text` (nullable), `decided_at`. [04_data-model.md §2.1] [02_business-rules.md BR-007] [02_business-rules.md BR-008]
- `Request` / `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}). [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Document` → `documents` + `DocumentKind` enum (`decision` here); fields: `request_id`, `uploaded_by_user_account_id`, `decision_id` (unique when non-null — at most one decision document per decision), `kind`, `file_reference` (S3/MinIO object key), file metadata, `description` (nullable). [04_data-model.md §2.1] [02_business-rules.md BR-006] [docs/conventions.md Storage]
- `Request history` → `request_history_entries` + `HistoryEventType` enum (`decision_recorded`); `decision_id` FK links the entry to the recorded decision. [04_data-model.md §2.1] [02_business-rules.md BR-017]
- `Notification` → `notifications` + `NotificationType` enum (`decision_recorded`); fields: `recipient_user_account_id` (owning citizen), `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]

## Status transition(s)
- `ready_for_decision` → `decided`, on step 8; validated by the `TransitionsRequestStatus` guard against the allowed v1 map; `decided` is terminal in v1. [03_use-cases.md UC-09] [02_business-rules.md BR-004] [docs/conventions.md Status transitions]

## History events (written explicitly)
- `decision_recorded` — linked to the decision via `decision_id` and carrying `from_status` = `ready_for_decision`, `to_status` = `decided`; written in the same transaction as the decision and the status change; the decision does not complete without it (ext 9a). [02_business-rules.md BR-017] [04_data-model.md §2.1] [derived — UC-09 step 9 says "records the decision and status change in the request history"; whether that is one `decision_recorded` entry carrying the status pair or a separate `status_changed` entry is an implementation choice; the single linked entry is assumed here — fragile]

## Notifications (best-effort)
- `decision_recorded` — created for the owning citizen (step 10). The decision completes even if the notification cannot be created (ext 10a); the decision stays visible to the citizen inside the request. [03_use-cases.md UC-09] [05_system-design.md §4]

## Performance target
- Response time p95 < 800 ms; p99 < 1500 ms — the decision + status + history write (steps 7–9). Closes the request path. [05_system-design.md §3]

## Reliability
- Must not fail silently: decision recording is named part of the durable-write path carrying legal and audit state — the decision row, the `ready_for_decision → decided` transition, and the history entry are saved together in one transaction, and no unapproved, untraceable, unsupported-document, or non-human decision is treated as final. [05_system-design.md §4] [03_use-cases.md UC-09] [02_business-rules.md BR-007] [02_business-rules.md BR-008] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- Authorization must fail closed; a failed or unevaluable check denies. [05_system-design.md §4]
- May degrade gracefully: the citizen notification (ext 10a); a file-store fault rejects the decision document — and with it the decision — while the request record stays intact (ext 4b). [05_system-design.md §4] [03_use-cases.md UC-09]

## API seam  [derived — fragile]
- `POST /api/requests/{request}/decision` — `auth:sanctum` + `RequestPolicy@decide` (responsible staff); request fields: `outcome` (a `DecisionOutcome` slug), optional `decision_text`, optional multipart decision-document file + `description`; records the decision, stores the decision document (kind `decision`, `decision_id` linked, bytes to the S3/MinIO disk with `file_reference` holding the object key), transitions `ready_for_decision → decided`, writes the history entry, creates the citizen notification; success `201` with the created decision in the `data` envelope. [derived from 03_use-cases.md UC-09 steps 2–10 + docs/conventions.md Authorization + Storage + Status transitions + API success responses]

## QA map  [derived — fragile]
- ext 1a → HTTP `404` when the caller is a staff member the request is not assigned to (out-of-scope records read as not found); expected DB effect: no change. [derived from 03_use-cases.md UC-09 ext 1a + BR-009 + BR-016 + docs/conventions.md API error responses]
- ext 2a → HTTP `409` when the request is not `ready_for_decision` (illegal transition); expected DB effect: no `decisions` row, `requests.status` unchanged. [derived from 03_use-cases.md UC-09 ext 2a + BR-004 + docs/conventions.md Status transitions + API error responses]
- ext 3a → HTTP `422` when `outcome` is not `approved` or `rejected`; expected DB effect: no `decisions` row. [derived from 03_use-cases.md UC-09 ext 3a + BR-008 + docs/conventions.md API error responses]
- ext 4a → HTTP `422` when the attached document is not a valid decision document for this request; expected DB effect: no `decisions` or `documents` row. [derived from 03_use-cases.md UC-09 ext 4a + BR-006 + docs/conventions.md API error responses]
- ext 4b → HTTP `500` on a file-store failure; expected DB effect: transaction rollback — no `decisions` or `documents` row, `requests.status` remains `ready_for_decision`. [derived from 03_use-cases.md UC-09 ext 4b + docs/conventions.md API error responses]
- ext 5a → HTTP `403` when an in-scope actor other than the responsible staff member (the owning citizen or an administrator) attempts the decision; an out-of-scope actor gets `404`; expected DB effect: no change. [derived from 03_use-cases.md UC-09 ext 5a + BR-007 + BR-016 + docs/conventions.md API error responses]
- ext 8a → HTTP `409` on an illegal transition, or HTTP `500` on a persistence failure inside the transaction; expected DB effect in both: no `decisions` row, `requests.status` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-09 ext 8a + BR-004 + docs/conventions.md Status transitions + API error responses]
- ext 9a → HTTP `500` when the history entry cannot be persisted; expected DB effect: transaction rollback — no `decisions` row, `requests.status` remains `ready_for_decision`, no `request_history_entries` or `notifications` row. [derived from 03_use-cases.md UC-09 ext 9a + BR-017 + docs/conventions.md API error responses]
- ext 10a → not triggerable through the API; verified by fault injection at test level: the call returns success, the `decisions` row and `request_history_entries` row exist, `requests.status` = `decided`, and no `notifications` row exists. [derived from 03_use-cases.md UC-09 ext 10a + 05_system-design.md §4]
- Happy path → HTTP `201`; expected DB effect: one `decisions` row (`outcome` ∈ {`approved`, `rejected`}, `decided_by_user_account_id` = the responsible staff member, unique `request_id`), `requests.status` = `decided`, one `request_history_entries` row (`event_type` = `decision_recorded`, `decision_id` set, `from_status` = `ready_for_decision`, `to_status` = `decided`), at most one `documents` row (`kind` = `decision`, `decision_id` set), one `notifications` row (`notification_type` = `decision_recorded`) for the owning citizen. [derived from 03_use-cases.md UC-09 steps 7–10]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the staff member acts through protected routes as an active account. [derived from 03_use-cases.md UC-09 preconditions + docs/conventions.md Auth]
- UC-05 assign-a-request must exist first: the request must have this staff member as its responsible handler. [derived from 03_use-cases.md UC-09 preconditions + BR-009]
- UC-06 review-an-assigned-request must exist first: the review path (`submitted → in_review`) precedes readiness for decision. [derived from 03_use-cases.md UC-06 step 5 + BR-004]
- UC-08 update-request-progress must exist first: `ready_for_decision` — this use case's required starting status — is reached through the staff progress move `in_review → ready_for_decision`. [derived from 03_use-cases.md UC-08 notes + UC-09 preconditions + BR-004]
- Cross-domain (Requests): UC-02 submit-a-request supplies the submitted request and its owning citizen, the notification recipient. [derived from 03_use-cases.md UC-09 step 10 + BR-003]
- Foundation: `requests`, `decisions`, `documents`, `request_history_entries`, `notifications` migrations/models; `RequestStatus`, `DecisionOutcome`, `DocumentKind`, `HistoryEventType`, `NotificationType` enums; the `TransitionsRequestStatus` guard trait; `RequestPolicy@decide` and the request-scope concern; the S3/MinIO document disk. [derived from docs/conventions.md Data & schema + Status transitions + Authorization + Storage]

## Notes
This is one of the deepest use cases because it closes the request path and must protect human accountability. Exact decision-document file limits are not defined in the current business rules and are therefore not specified here. [03_use-cases.md UC-09]
