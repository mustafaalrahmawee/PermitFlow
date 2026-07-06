# UC-06 — Review an Assigned Request

## Identity
- Domain: Review Workflow · Primary actor: Staff member · Supporting actors: Citizen · Level: User-Goal 🌊 [03_use-cases.md UC-06]

## Goal & trigger
The responsible staff member starts reviewing a request assigned to them. [03_use-cases.md UC-06]

## Preconditions
- The request is assigned to the staff member. [03_use-cases.md UC-06] [02_business-rules.md BR-009]
- The request is visible to them under request-scoped access. [03_use-cases.md UC-06] [02_business-rules.md BR-016]

## Main flow
1. The staff member opens their assigned requests.
2. The system shows requests for which the staff member is responsible.
3. The staff member selects a request.
4. The system shows the submitted request information, supporting documents, current status, messages, and request history.
5. If the request is Submitted, the system allows the staff member to start review and changes the request status to In Review.
6. The staff member verifies the submitted information and supporting documents.
7. The staff member decides whether the request needs missing information, can move forward, or is ready for decision.
8. The system keeps the review state understandable through the request's status and history. [03_use-cases.md UC-06]

## Acceptance checklist (from extensions)
- ext 2a — a staff member with no assigned requests sees an empty assigned-request list. [03_use-cases.md UC-06]
- ext 3a — a staff member attempting to open a request assigned to someone else is denied access. [03_use-cases.md UC-06] [02_business-rules.md BR-009] [02_business-rules.md BR-016]
- ext 5a — if the request cannot move from Submitted to In Review, the review is not treated as started. [03_use-cases.md UC-06] [02_business-rules.md BR-004] [02_business-rules.md BR-017]
- ext 6a — incomplete submitted information leads the staff member to continue with UC-07 to request missing information. [03_use-cases.md UC-06]
- ext 6b — a supporting document that cannot be treated as a supporting or decision document cannot be relied on for review. [03_use-cases.md UC-06] [02_business-rules.md BR-006]
- ext 7a — a next step not supported by the request's current status is blocked. [03_use-cases.md UC-06] [02_business-rules.md BR-004]
- ext 8a — a review action that would leave no understandable trace for an important status change is blocked until the change is recorded. [03_use-cases.md UC-06] [02_business-rules.md BR-017]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request is reachable only by its owning citizen, its responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- Review is `RequestPolicy@review` — responsible staff member only. [docs/conventions.md Authorization] [02_business-rules.md BR-009]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies; role and responsibility are evaluated live, never from a cached copy. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; read: `title`, `request_details` (whole-block JSON), `responsible_staff_user_account_id`; written: `status` on start-review. [04_data-model.md §2.1]
- `Request status` → `requests.status` + `RequestStatus` enum ({`draft`, `submitted`, `in_review`, `waiting_for_citizen`, `ready_for_decision`, `decided`}). [04_data-model.md §2.1] [02_business-rules.md BR-004] [docs/conventions.md Data & schema]
- `Document` → `documents` + `DocumentKind` enum ({`supporting`, `decision`}); read for verification of supporting files. [04_data-model.md §2.1] [02_business-rules.md BR-006]
- `Message` → `messages`; read as part of the request detail. [04_data-model.md §2.1]
- `Request history` → `request_history_entries` + `HistoryEventType` enum (`status_changed` written on start-review; all entries read for the detail view). [04_data-model.md §2.1] [02_business-rules.md BR-017]
- `User account` / `Role` → `user_accounts` (`role`, `account_state` value sets); the actor must be an active account responsible for the request. [04_data-model.md §2.1] [02_business-rules.md BR-001]

## Status transition(s)
- `submitted` → `in_review`, on step 5 (start review); validated by the `TransitionsRequestStatus` guard against the allowed v1 map. [03_use-cases.md UC-06] [02_business-rules.md BR-004] [docs/conventions.md Status transitions]

## History events (written explicitly)
- `status_changed` (`from_status` = `submitted`, `to_status` = `in_review`) — written in the same transaction as the start-review status change; the review is not treated as started without it (ext 5a, ext 8a). [02_business-rules.md BR-017] [docs/conventions.md Status transitions] [derived — UC-06 step 8 requires the review state to stay understandable through status and history rather than naming the write; the event-type value comes from 04_data-model.md §2.1 — fragile]

## Notifications (best-effort)
_None._ UC-06 defines no in-portal notification; citizen-relevant status-change notifications are governed by UC-08. [03_use-cases.md UC-06] [03_use-cases.md UC-08]

## Performance target
- Response time p95 < 800 ms; p99 < 1500 ms — the request detail read (step 4: request + documents + messages + history), the heaviest read. [05_system-design.md §3]

## Reliability
- Must not fail silently: the `submitted → in_review` transition and its history entry — the review is not treated as started when the change cannot be applied or recorded; status change and history entry are saved together in one transaction. [05_system-design.md §4] [02_business-rules.md BR-004] [02_business-rules.md BR-017] [docs/conventions.md Status transitions]
- Authorization must fail closed; the staff member never gains access to requests outside their responsibility. [05_system-design.md §4] [03_use-cases.md UC-06]
- May degrade gracefully: document retrieval — a brief file-store fault hides the file while the request record stays intact; validate the `file_reference` belongs to the in-scope request before serving. [05_system-design.md §4] [docs/conventions.md Storage]

## API seam  [derived — fragile]
- `GET /api/requests` — `auth:sanctum` + request scope; for a staff caller the list contains the requests they are responsible for; empty `data` array when none. [derived from 03_use-cases.md UC-06 steps 1–2 + BR-016 + docs/conventions.md API success responses; shared seam with UC-03, where a citizen caller sees only owned requests]
- `GET /api/requests/{request}` — `auth:sanctum` + request scope (responsible staff); returns the request with its `request_details`, documents, messages, current status, and history. [derived from 03_use-cases.md UC-06 steps 3–4 + BR-016]
- `POST /api/requests/{request}/start-review` — `auth:sanctum` + `RequestPolicy@review`; no body; transitions `submitted → in_review` and writes the history entry; success `200` with the updated request in the `data` envelope. [derived from 03_use-cases.md UC-06 step 5 + docs/conventions.md Status transitions + API success responses]

## QA map  [derived — fragile]
- ext 2a → `GET /api/requests` returns HTTP `200` with `data: []` for a staff member with no assigned requests; no DB effect. [derived from 03_use-cases.md UC-06 ext 2a + docs/conventions.md API success responses]
- ext 3a → HTTP `404` when opening a request assigned to someone else (out-of-scope records read as not found); no DB effect. [derived from 03_use-cases.md UC-06 ext 3a + BR-009 + BR-016 + docs/conventions.md API error responses]
- ext 5a → illegal transition (start-review on a request not `submitted`) → HTTP `409`; persistence failure inside the transaction → HTTP `500` with rollback. Expected DB effect in both: `requests.status` unchanged, no new `request_history_entries` row. [derived from 03_use-cases.md UC-06 ext 5a + BR-004 + docs/conventions.md Status transitions + API error responses]
- ext 6a → `frontend-only` — the staff member's judgment to continue with UC-07; no API observation here. [derived from 03_use-cases.md UC-06 ext 6a]
- ext 6b → `frontend-only` at this seam — document kind is enforced at upload (`documents.kind` ∈ {`supporting`, `decision`}), so the detail read never contains a document of another kind. [derived from 03_use-cases.md UC-06 ext 6b + BR-006 + 04_data-model.md §2.1]
- ext 7a → HTTP `409` when a next-step action (UC-07, UC-08, UC-09 seams) is attempted at an unsupported status; expected DB effect: no change. [derived from 03_use-cases.md UC-06 ext 7a + BR-004 + docs/conventions.md API error responses]
- ext 8a → HTTP `500` on a history-write failure during start-review; expected DB effect: transaction rollback — `requests.status` remains `submitted`, no `request_history_entries` row. [derived from 03_use-cases.md UC-06 ext 8a + BR-017 + docs/conventions.md API error responses]
- Happy path → `GET` detail returns HTTP `200`; `POST /start-review` returns HTTP `200` with expected DB effect: `requests.status` = `in_review`, one `request_history_entries` row (`event_type` = `status_changed`, `from_status` = `submitted`, `to_status` = `in_review`). [derived from 03_use-cases.md UC-06 steps 4–5]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the staff member acts through protected routes as an active account. [derived from 03_use-cases.md UC-06 preconditions + docs/conventions.md Auth]
- UC-05 assign-a-request must exist first: only an assigned request is reachable and reviewable by its responsible staff member. [derived from 03_use-cases.md UC-06 preconditions + BR-009]
- Cross-domain (Requests): UC-02 submit-a-request must exist so a Submitted request with details and documents is available to review. [derived from 03_use-cases.md UC-06 step 4 + BR-004]
- Cross-domain (Identity and Access): UC-01 account provisioning supplies the active Staff member account. [derived from 03_use-cases.md UC-06 preconditions + BR-001]
- Foundation: `requests`, `documents`, `messages`, `request_history_entries` migrations/models; `RequestStatus`, `DocumentKind`, `HistoryEventType` enums; the `TransitionsRequestStatus` guard trait; `RequestPolicy@review` and the request-scope concern; the S3/MinIO document disk for document retrieval. [derived from docs/conventions.md Data & schema + Status transitions + Authorization + Storage]

## Notes
The process "Verify submitted information" is subsumed here because verification is part of reviewing an assigned request. [03_use-cases.md UC-06]
