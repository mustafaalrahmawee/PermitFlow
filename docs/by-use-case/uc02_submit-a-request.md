# UC-02 — Submit a Request

## Identity
- Domain: Requests · Primary actor: Citizen · Supporting actors: Administrator · Level: User-Goal 🌊 [03_use-cases.md UC-02]

## Goal & trigger
The citizen wants to send a formal application or permit request to the institution. [03_use-cases.md UC-02]

## Preconditions
- The citizen has an active user account with the Citizen role. [03_use-cases.md UC-02] [02_business-rules.md BR-001] [02_business-rules.md BR-018]
- At least one active request category is available. [03_use-cases.md UC-02] [02_business-rules.md BR-002]

## Main flow
1. The citizen starts a new request.
2. The system creates the request as a draft owned by the citizen.
3. The citizen selects one active request category.
4. The citizen enters the required request information.
5. The citizen attaches supporting documents when needed.
6. The citizen reviews the request before submission.
7. The citizen submits the request.
8. The system changes the request status from Draft to Submitted.
9. The system makes the submitted request visible to authorized institution users for handling.
10. The system creates an in-portal notification for administrators that a submitted request needs assignment. [03_use-cases.md UC-02]

## Acceptance checklist (from extensions)
- ext 3a — selecting no category or attempting multiple categories blocks submission until exactly one active category is selected. [03_use-cases.md UC-02] [02_business-rules.md BR-002]
- ext 3b — an inactive category cannot be used for a new request. [03_use-cases.md UC-02]
- ext 3c — with no active request category available, the citizen cannot select a category and the request cannot be submitted until an administrator makes one available. [03_use-cases.md UC-02] [02_business-rules.md BR-002]
- ext 5a — a document that is not a supporting file for the request is rejected for this use case. [03_use-cases.md UC-02] [02_business-rules.md BR-006]
- ext 5b — when the system cannot accept or store a supporting document, it rejects that document, keeps the request editable, and shows the document was not attached. [03_use-cases.md UC-02]
- ext 6a — wrong or missing information found during review leaves the request Draft; the citizen may correct it before submission. [03_use-cases.md UC-02]
- ext 7a — a citizen attempting to submit a request that belongs to someone else is denied. [03_use-cases.md UC-02] [02_business-rules.md BR-003] [02_business-rules.md BR-016]
- ext 8a — if the request cannot move to Submitted, it remains Draft and the citizen sees that submission did not complete. [03_use-cases.md UC-02] [02_business-rules.md BR-004]
- ext 10a — if the administrator notification cannot be created, the request remains Submitted and authorized administrators can still find it in the submitted-request list. [03_use-cases.md UC-02]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request is reachable only by its owning citizen, its responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- Submission is `RequestPolicy@submit` — owning citizen only. [docs/conventions.md Authorization] [02_business-rules.md BR-003]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; fields: `owner_user_account_id` (FK, restrict), `request_category_id` (FK, restrict), `title`, `request_details` (whole-block JSON), `status`, `submitted_at` (nullable). [04_data-model.md §2.1]
- `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}). [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Request category` → `request_categories`; read for selection; only `is_active = true` categories are selectable. [04_data-model.md §2.1] [02_business-rules.md BR-002]
- `Document` → `documents` + `DocumentKind` enum (`supporting` here); fields: `request_id`, `uploaded_by_user_account_id`, `kind`, `file_reference` (S3/MinIO object key), file metadata. [04_data-model.md §2.1] [02_business-rules.md BR-006] [docs/conventions.md Storage]
- `Notification` → `notifications` + `NotificationType` enum (`request_submitted`); fields: `recipient_user_account_id`, `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]
- `User account` / `Role` → `user_accounts` (`role`, `account_state` value sets); the owner must be an active Citizen; notification recipients are administrators. [04_data-model.md §2.1] [02_business-rules.md BR-001]

## Status transition(s)
- `draft` → `submitted`, on step 8; validated by the `TransitionsRequestStatus` guard against the allowed v1 map; `submitted_at` is set. [03_use-cases.md UC-02] [02_business-rules.md BR-004] [docs/conventions.md Status transitions]

## History events (written explicitly)
- `status_changed` (`from_status` = `draft`, `to_status` = `submitted`) — written in the same transaction as the status change. [02_business-rules.md BR-017] [docs/conventions.md Status transitions] [derived — UC-02's main flow does not state this write; BR-017 requires important status changes to be recorded and UC-03 step 5 shows submission in the citizen-visible history — fragile]

## Notifications (best-effort)
- `request_submitted` — created for administrators so a submitted request gets assigned; one `notifications` row per active administrator account [derived from 03_use-cases.md UC-02 step 10 + 04_data-model.md §2.1 recipient FK — fragile]. The submission completes even if this notification cannot be created (ext 10a). [03_use-cases.md UC-02] [05_system-design.md §4]

## Performance target
- Response time p95 < 800 ms; p99 < 1500 ms — the submission transaction (steps 7–10). Excludes document byte upload, which is throughput-bound on file size, not a percentile target in v1. [05_system-design.md §3]

## Reliability
- Must not fail silently: the submission's durable-write path — the `draft → submitted` transition and its history entry — refuses to report success when it cannot be recorded; status change and history entry are saved together in one transaction. [05_system-design.md §4] [02_business-rules.md BR-004] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- May degrade gracefully: the administrator notification (ext 10a); a file-store fault rejects the document while the draft request record stays intact (ext 5b). [05_system-design.md §4] [03_use-cases.md UC-02]
- No incomplete submission is treated as submitted; the citizen's draft remains available where possible. [03_use-cases.md UC-02]

## API seam  [derived — fragile]
- `GET /api/request-categories` — `auth:sanctum`; returns active categories for selection (`is_active = true` only). [derived from 03_use-cases.md UC-02 step 3 + 02_business-rules.md BR-002]
- `POST /api/requests` — `auth:sanctum`; request fields: `request_category_id`, `title`, `request_details`; creates the request as a Draft owned by the caller; success `201` with the created resource in the `data` envelope. [derived from 03_use-cases.md UC-02 steps 1–4 + docs/conventions.md API success responses]
- `PATCH /api/requests/{request}` — `auth:sanctum` + request scope (owner); request fields: `request_category_id`, `title`, `request_details`; edits a Draft before submission. [derived from 03_use-cases.md UC-02 steps 3–6 + ext 6a]
- `POST /api/requests/{request}/documents` — `auth:sanctum` + request scope (owner); multipart file + optional `description`; stores a `supporting` document against the draft; file bytes go to the S3/MinIO disk, `file_reference` holds the object key. [derived from 03_use-cases.md UC-02 step 5 + 02_business-rules.md BR-006 + docs/conventions.md Storage; shared seam with UC-04, which governs post-submission input]
- `POST /api/requests/{request}/submit` — `auth:sanctum` + `RequestPolicy@submit` (owning citizen); no body; transitions `draft → submitted`, sets `submitted_at`, writes the history entry, creates administrator notifications; success `200` with the updated request in the `data` envelope. [derived from 03_use-cases.md UC-02 steps 7–10 + docs/conventions.md Status transitions + API success responses]

## QA map  [derived — fragile]
- ext 3a → HTTP `422` on create/update with no or a non-scalar `request_category_id`; expected DB effect: no `requests` row created/changed. [derived from 03_use-cases.md UC-02 ext 3a + BR-002 + docs/conventions.md API error responses]
- ext 3b → HTTP `422` on create/update naming an inactive category; expected DB effect: `requests.request_category_id` never references a category with `is_active = false` via this seam. [derived from 03_use-cases.md UC-02 ext 3b + docs/conventions.md API error responses]
- ext 3c → `GET /api/request-categories` returns HTTP `200` with `data: []`; any create attempt then fails ext 3a/3b with `422`. The "citizen cannot select" experience itself is frontend-only. [derived from 03_use-cases.md UC-02 ext 3c + BR-002]
- ext 5a → HTTP `422` on document attach with an invalid/non-supporting file; expected DB effect: no `documents` row. [derived from 03_use-cases.md UC-02 ext 5a + BR-006 + docs/conventions.md API error responses]
- ext 5b → HTTP `500` on a file-store failure; expected DB effect: no `documents` row, `requests.status` remains `draft` and the row is unchanged. [derived from 03_use-cases.md UC-02 ext 5b + docs/conventions.md API error responses]
- ext 6a → HTTP `200` on `PATCH` of a Draft; expected DB effect: `requests` row updated, `status` still `draft`. [derived from 03_use-cases.md UC-02 ext 6a + docs/conventions.md API success responses]
- ext 7a → HTTP `404` when submitting another citizen's request (out-of-scope records read as not found); expected DB effect: the target `requests` row is unchanged. [derived from 03_use-cases.md UC-02 ext 7a + BR-003 + BR-016 + docs/conventions.md API error responses]
- ext 8a → illegal transition (e.g. submitting an already-submitted request) → HTTP `409`; persistence failure inside the transaction → HTTP `500` with rollback. Expected DB effect in both: `requests.status` unchanged, no new `request_history_entries` row. [derived from 03_use-cases.md UC-02 ext 8a + BR-004 + docs/conventions.md Status transitions + API error responses]
- ext 10a → not triggerable through the API; verified by fault injection at test level: submission returns success, `requests.status` = `submitted` with its history row, and no `notifications` row exists. [derived from 03_use-cases.md UC-02 ext 10a + 05_system-design.md §4]
- Happy path → HTTP `200` on submit; expected DB effect: `requests.status` = `submitted`, `submitted_at` set, one `request_history_entries` row (`event_type` = `status_changed`, `from_status` = `draft`, `to_status` = `submitted`), one `notifications` row (`notification_type` = `request_submitted`) per active administrator. [derived from 03_use-cases.md UC-02 steps 7–10]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the citizen acts through protected routes as an active account. [derived from 03_use-cases.md UC-02 preconditions + docs/conventions.md Auth]
- Cross-domain (Administration): UC-11 request-category management must exist so at least one active category is available for selection; without it the precondition cannot be met. [derived from 03_use-cases.md UC-02 preconditions + BR-002]
- Cross-domain (Identity and Access): UC-01 account provisioning supplies the citizen account and the administrator accounts that receive the `request_submitted` notification. [derived from 03_use-cases.md UC-02 preconditions + step 10]
- Foundation: `requests`, `documents`, `notifications`, `request_history_entries` migrations/models; `RequestStatus`, `DocumentKind`, `NotificationType`, `HistoryEventType` enums; the `TransitionsRequestStatus` guard trait; `RequestPolicy@submit` and the request-scope concern; the S3/MinIO document disk. [derived from docs/conventions.md Data & schema + Status transitions + Authorization + Storage]

## Notes
The process "Review a request before submission" is subsumed into steps 6–7 because it serves the submit goal. Exact upload limits are not defined in the current business rules and are therefore not specified here. [03_use-cases.md UC-02]
