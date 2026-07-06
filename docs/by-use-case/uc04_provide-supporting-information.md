# UC-04 — Provide Supporting Information

## Identity
- Domain: Documents · Primary actor: Citizen · Supporting actors: Staff member · Level: User-Goal 🌊 [03_use-cases.md UC-04]

## Goal & trigger
The citizen needs to provide documents or details for a request, either before submission or after staff requested missing information. [03_use-cases.md UC-04]

## Preconditions
- The citizen owns the request. [03_use-cases.md UC-04] [02_business-rules.md BR-003]
- The request is either still Draft or is Waiting for Citizen because staff requested missing information. [03_use-cases.md UC-04] [02_business-rules.md BR-004] [02_business-rules.md BR-005]

## Main flow
1. The citizen opens the relevant request.
2. The system confirms that the citizen may provide information at the request's current status.
3. The citizen enters the requested details or attaches supporting documents.
4. The citizen reviews the provided information.
5. The citizen sends the information to the system.
6. The system stores the information with the request.
7. If the request was Waiting for Citizen, the system changes the request status back to In Review.
8. If the status changed, the system records the status change in the request history.
9. The system makes the information visible to the responsible staff member.
10. The system creates an in-portal notification for the responsible staff member. [03_use-cases.md UC-04]

## Acceptance checklist (from extensions)
- ext 1a — a citizen attempting to open a request they do not own is denied access. [03_use-cases.md UC-04] [02_business-rules.md BR-003] [02_business-rules.md BR-016]
- ext 2a — a request that is already submitted with no open staff request for missing information blocks free post-submission input. [03_use-cases.md UC-04] [02_business-rules.md BR-005]
- ext 3a — a document that is not a supporting file for this request is rejected. [03_use-cases.md UC-04] [02_business-rules.md BR-006]
- ext 3b — when the system cannot accept or store a supporting document, it rejects that document, keeps the existing request content unchanged, and shows that the document was not attached. [03_use-cases.md UC-04]
- ext 5a — if the citizen cancels before sending, the request and existing documents remain unchanged. [03_use-cases.md UC-04]
- ext 7a — if the request cannot move from Waiting for Citizen back to In Review, the response is not completed as a returned review item. [03_use-cases.md UC-04] [02_business-rules.md BR-004] [02_business-rules.md BR-017]
- ext 9a — with no responsible staff member assigned yet, the information remains connected to the request and visibility waits for assignment. [03_use-cases.md UC-04] [02_business-rules.md BR-009] [02_business-rules.md BR-016]
- ext 10a — if the notification cannot be created, the information remains stored and visible to the responsible staff member. [03_use-cases.md UC-04]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request is reachable only by its owning citizen, its responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- Providing information is `RequestPolicy@provideInformation` — the owning citizen, and only while the request is Draft or Waiting for Citizen; this policy carries the BR-005 controlled post-submission boundary. [docs/conventions.md Authorization] [02_business-rules.md BR-003] [02_business-rules.md BR-005]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; written: `status` (Waiting-for-Citizen path only); read: `owner_user_account_id` (ownership check), `responsible_staff_user_account_id` (visibility and notification recipient). [04_data-model.md §2.1]
- `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}). [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Document` → `documents` + `DocumentKind` enum (`supporting` here); fields: `request_id`, `uploaded_by_user_account_id` (the citizen), `kind`, `file_reference` (S3/MinIO object key), `original_filename`, `mime_type`, `size_bytes`, `uploaded_at`, `description` (nullable). [04_data-model.md §2.1] [02_business-rules.md BR-006] [docs/conventions.md Storage]
- `Message` → `messages` + `MessageKind` enum (`citizen_reply` here); fields: `request_id`, `sender_user_account_id` (owning citizen), `recipient_user_account_id` (responsible staff member), `body`, `sent_at` — carries the entered details on the Waiting-for-Citizen path. [04_data-model.md §2.1]
- `Request history` → `request_history_entries` + `HistoryEventType` enum (`information_provided`); `message_id` and `document_id` FKs link the entry to the provided information. [04_data-model.md §2.1] [02_business-rules.md BR-017]
- `Notification` → `notifications` + `NotificationType` enum (`information_provided`); fields: `recipient_user_account_id` (responsible staff member), `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]

## Status transition(s)
- `waiting_for_citizen` → `in_review`, on step 7, only when the request was Waiting for Citizen; validated by the `TransitionsRequestStatus` guard against the allowed v1 map. [03_use-cases.md UC-04] [02_business-rules.md BR-004] [docs/conventions.md Status transitions]
- The Draft path performs no status transition; the request stays `draft` until UC-02 submission. [03_use-cases.md UC-04] [derived — the main flow changes status only "if the request was Waiting for Citizen"]

## History events (written explicitly)
- `information_provided` — written on the Waiting-for-Citizen path, linked to the provided information via `message_id` / `document_id` and carrying `from_status` = `waiting_for_citizen`, `to_status` = `in_review`; written in the same transaction as the stored information and the status change. [02_business-rules.md BR-017] [04_data-model.md §2.1] [derived — UC-04 step 8 says the status change is recorded "if the status changed"; whether that is one `information_provided` entry carrying the status pair or a separate `status_changed` entry is an implementation choice; the single linked entry is assumed here, mirroring UC-07 — fragile]
- Draft-path input (editing an unsubmitted draft, attaching documents to it) writes no history entry. [derived — step 8 is conditional on a status change, and BR-017 covers important status changes and decisions, neither of which occurs on the Draft path — fragile]

## Notifications (best-effort)
- `information_provided` — created for the responsible staff member (step 10). The provided information remains stored even if the notification cannot be created (ext 10a). On the Draft path no responsible staff member exists yet, so no notification is created and visibility waits for assignment (ext 9a). [03_use-cases.md UC-04] [05_system-design.md §4]

## Performance target
No dedicated per-UC target; UC-04 is not listed among the critical UCs in `05_system-design.md §3`. Document byte upload is throughput-bound on file size, not a percentile target in v1. [05_system-design.md §3]

## Reliability
- Must not fail silently: the `waiting_for_citizen → in_review` transition and its history entry — the response is not completed as a returned review item when the transition or its trace cannot be recorded (ext 7a); stored information, status change, and history entry are saved together in one transaction. [03_use-cases.md UC-04] [05_system-design.md §4] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- Authorization must fail closed; a failed or unevaluable check denies. [05_system-design.md §4]
- May degrade gracefully: the staff notification (ext 10a); a file-store fault rejects the document while the existing request content stays unchanged (ext 3b). [05_system-design.md §4] [03_use-cases.md UC-04]
- Submitted documents remain connected to the request history and cannot be silently removed by the citizen after submission. [03_use-cases.md UC-04]

## API seam  [derived — fragile]
- `POST /api/requests/{request}/documents` — `auth:sanctum` + `RequestPolicy@provideInformation` (owning citizen, Draft or Waiting for Citizen); multipart file + optional `description`; stores a `supporting` document against the request; file bytes go to the S3/MinIO disk, `file_reference` holds the object key; success `201` with the created document in the `data` envelope. [derived from 03_use-cases.md UC-04 step 3 + BR-005 + BR-006 + docs/conventions.md Authorization + Storage + API success responses; shared seam with UC-02, which uses it at draft time]
- `POST /api/requests/{request}/provide-information` — `auth:sanctum` + `RequestPolicy@provideInformation` (owning citizen); request field: `body` (the requested details, recorded as a `citizen_reply` message to the responsible staff member); on a Waiting-for-Citizen request: stores the reply, transitions `waiting_for_citizen → in_review`, writes the `information_provided` history entry, and creates the staff notification; success `200` with the updated request in the `data` envelope. [derived from 03_use-cases.md UC-04 steps 5–10 + docs/conventions.md Authorization + Status transitions + API success responses]
- Draft-path detail edits go through the UC-02 draft seam (`PATCH /api/requests/{request}`), not through `provide-information`. [derived from 03_use-cases.md UC-02 ext 6a + UC-04 trigger — fragile]

## QA map  [derived — fragile]
- ext 1a → HTTP `404` when the caller does not own the request (out-of-scope records read as not found); expected DB effect: no change. [derived from 03_use-cases.md UC-04 ext 1a + BR-003 + BR-016 + docs/conventions.md API error responses]
- ext 2a → HTTP `403` when the request is neither `draft` nor `waiting_for_citizen` (e.g. `submitted`, `in_review`, `decided`): `RequestPolicy@provideInformation` denies because the status condition lives in the policy; expected DB effect: no `documents`, `messages`, `request_history_entries`, or `notifications` row, `requests.status` unchanged. [derived from 03_use-cases.md UC-04 ext 2a + BR-005 + docs/conventions.md Authorization + API error responses]
- ext 3a → HTTP `422` on document attach with an invalid/non-supporting file; expected DB effect: no `documents` row. [derived from 03_use-cases.md UC-04 ext 3a + BR-006 + docs/conventions.md API error responses]
- ext 3b → HTTP `500` on a file-store failure; expected DB effect: no `documents` row, the `requests` row unchanged. [derived from 03_use-cases.md UC-04 ext 3b + docs/conventions.md API error responses]
- ext 5a → `frontend-only`; cancelling before sending never issues the API call, so the request and existing documents are untouched. [derived from 03_use-cases.md UC-04 ext 5a]
- ext 7a → HTTP `500` when the transition or its history entry cannot be persisted; expected DB effect: transaction rollback — `requests.status` remains `waiting_for_citizen`, no `messages`, `request_history_entries`, or `notifications` row. [derived from 03_use-cases.md UC-04 ext 7a + BR-004 + BR-017 + docs/conventions.md API error responses]
- ext 9a → HTTP `201` on a Draft-path document attach with `responsible_staff_user_account_id` null; expected DB effect: the `documents` row exists linked to the request, and no `notifications` row is created. [derived from 03_use-cases.md UC-04 ext 9a + BR-009 + BR-016]
- ext 10a → not triggerable through the API; verified by fault injection at test level: the call returns success, the stored information and its `request_history_entries` row exist, `requests.status` = `in_review`, and no `notifications` row exists. [derived from 03_use-cases.md UC-04 ext 10a + 05_system-design.md §4]
- Happy path (Waiting for Citizen) → HTTP `200`; expected DB effect: one `messages` row (`message_kind` = `citizen_reply`, sender = owning citizen, recipient = responsible staff) and/or the attached `documents` row(s) (`kind` = `supporting`), `requests.status` = `in_review`, one `request_history_entries` row (`event_type` = `information_provided`, `from_status` = `waiting_for_citizen`, `to_status` = `in_review`, linked via `message_id`/`document_id`), one `notifications` row (`notification_type` = `information_provided`) for the responsible staff member. [derived from 03_use-cases.md UC-04 steps 6–10]
- Happy path (Draft) → HTTP `201` on document attach; expected DB effect: one `documents` row (`kind` = `supporting`), `requests.status` remains `draft`, no `request_history_entries` or `notifications` row. [derived from 03_use-cases.md UC-04 steps 3 + 6 + the conditional steps 7–8]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the citizen acts through protected routes as an active account. [derived from 03_use-cases.md UC-04 preconditions + docs/conventions.md Auth]
- Cross-domain (Requests): UC-02 submit-a-request must exist first — it creates the owned draft the Draft path feeds, and its submission starts the path that leads to Waiting for Citizen. [derived from 03_use-cases.md UC-04 preconditions + BR-003]
- Cross-domain (Review Workflow), for the Waiting-for-Citizen path: UC-05 assign-a-request supplies the responsible staff member (message recipient and notification target), and UC-07 request-missing-information produces the `waiting_for_citizen` status this path answers. [derived from 03_use-cases.md UC-04 preconditions + BR-005 + BR-009]
- Foundation: `requests`, `documents`, `messages`, `request_history_entries`, `notifications` migrations/models; `RequestStatus`, `DocumentKind`, `MessageKind`, `HistoryEventType`, `NotificationType` enums; the `TransitionsRequestStatus` guard trait; `RequestPolicy@provideInformation` and the request-scope concern; the S3/MinIO document disk. [derived from docs/conventions.md Data & schema + Status transitions + Authorization + Storage]

## Notes
Exact supporting-document file limits are not defined in the current business rules and are therefore not specified here. Submitted documents remain connected to the request history and cannot be silently removed by the citizen after submission. [03_use-cases.md UC-04]
