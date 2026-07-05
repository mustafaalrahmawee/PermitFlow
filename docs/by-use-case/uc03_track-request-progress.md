# UC-03 — Track Request Progress

## Identity
- Domain: Requests · Primary actor: Citizen · Supporting actors: `_None_` · Level: User-Goal 🌊 [03_use-cases.md UC-03]

## Goal & trigger
The citizen wants to know where their submitted request stands. [03_use-cases.md UC-03]

## Preconditions
- The citizen has at least one request in the system. [03_use-cases.md UC-03]

## Main flow
1. The citizen opens their request list.
2. The system shows only requests the citizen owns.
3. The citizen selects a request.
4. The system shows the current request status.
5. The system shows the understandable request history for important status changes, assignments, reassignments, and decisions.
6. The citizen reviews any visible messages, requested information, documents, or decision information connected to the request. [03_use-cases.md UC-03]

## Acceptance checklist (from extensions)
- ext 2a — a citizen with no requests sees an empty request list and no request detail is opened. [03_use-cases.md UC-03]
- ext 3a — a citizen attempting to open a request they do not own is denied access. [03_use-cases.md UC-03] [02_business-rules.md BR-003] [02_business-rules.md BR-016]
- ext 5a — a status change or decision that is not understandable to the citizen is not presented as complete progress. [03_use-cases.md UC-03] [02_business-rules.md BR-017]
- ext 6a — while a requested response is pending, the request is shown as Waiting for Citizen. [03_use-cases.md UC-03] [02_business-rules.md BR-004] [02_business-rules.md BR-005]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the list shows only requests the citizen owns, and a request outside the citizen's scope is reported as not found rather than forbidden, so existence is not revealed. [docs/conventions.md Authorization] [02_business-rules.md BR-003] [02_business-rules.md BR-016]
- The connected documents, messages, history entries, decision, and notifications are covered by the same request-scoped policies (Document, Message, Decision, RequestHistoryEntry, Notification). [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies; role and ownership are evaluated live, never from a cached copy. [05_system-design.md §4]

## Data touched
All reads — this use case writes nothing.
- `Request` → `requests`; filtered by `owner_user_account_id` = the caller. [04_data-model.md §2.1] [02_business-rules.md BR-003]
- `Request status` → `requests.status` + `RequestStatus` enum. [04_data-model.md §2.1] [02_business-rules.md BR-004]
- `Request history` → `request_history_entries` + `HistoryEventType` enum; ordered by (`request_id`, `sequence_number`); the frozen `summary` carries the understandable wording. [04_data-model.md §1.2, §2.1] [02_business-rules.md BR-017]
- `Message` → `messages` + `MessageKind` enum. [04_data-model.md §2.1]
- `Document` → `documents` + `DocumentKind` enum. [04_data-model.md §2.1] [02_business-rules.md BR-006]
- `Decision` → `decisions` + `DecisionOutcome` enum (at most one per request). [04_data-model.md §2.1] [02_business-rules.md BR-008]
- `Notification` → `notifications` + `NotificationType` enum; the citizen's own request-related alerts. [04_data-model.md §2.1]

## Status transition(s)
_None._ UC-03 is a read; it displays `requests.status` and changes nothing. [03_use-cases.md UC-03]

## History events (written explicitly)
_None written._ UC-03 reads the existing `request_history_entries` (status changes, assignments, reassignments, decisions) recorded by the writing use cases. [03_use-cases.md UC-03] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None created._ Existing notifications connected to the request are among the visible items the citizen reviews in step 6. [03_use-cases.md UC-03]

## Performance target
- Response time p95 < 500 ms; p99 < 1000 ms — the status + history + messages read (steps 4–6). The most frequent citizen read; the tightest bound in the system. [05_system-design.md §3]

## Reliability
- Access-control evaluation must fail closed: a check that cannot be evaluated denies rather than permits — the minimal guarantee is that the citizen never gains access to another person's request information. [05_system-design.md §4] [03_use-cases.md UC-03]
- May degrade gracefully: document retrieval — a brief file-store fault hides the file while the request record stays intact. [05_system-design.md §4]
- Reads see committed state only (no summary or detail off a partially-applied transaction). [05_system-design.md §4]

## API seam  [derived — fragile]
- `GET /api/requests` — `auth:sanctum`; returns only requests owned by the calling citizen, paginated (`data` array + sibling `meta` cursor), each row with its current `status` slug. [derived from 03_use-cases.md UC-03 steps 1–2 + docs/conventions.md API success responses + Authorization]
- `GET /api/requests/{request}` — `auth:sanctum` + request scope (owner); returns the request detail: `status`, `submitted_at`, category, `request_details`, plus the connected history entries (ordered by `sequence_number`), messages, documents, and decision where one exists, in the `data` envelope. [derived from 03_use-cases.md UC-03 steps 3–6 + 04_data-model.md §2.1; whether connected collections ride on this response or on sibling sub-resource GETs is an implementation choice — fragile]

## QA map  [derived — fragile]
- ext 2a → HTTP `200` with `data: []` (and a `meta` cursor with `total: 0`) for a citizen owning no requests; expected DB effect: none (read-only). [derived from 03_use-cases.md UC-03 ext 2a + docs/conventions.md API success responses]
- ext 3a → HTTP `404` when requesting another citizen's request by id — not found rather than forbidden, so existence is not revealed; expected DB effect: none. [derived from 03_use-cases.md UC-03 ext 3a + BR-003 + BR-016 + docs/conventions.md API error responses]
- ext 5a → DB-side check: every `request_history_entries` row returned carries a non-empty frozen `summary`; the "not presented as complete" rendering itself is frontend-only. [derived from 03_use-cases.md UC-03 ext 5a + BR-017 + 04_data-model.md §2.1]
- ext 6a → HTTP `200` on the detail read with `data.status` = `waiting_for_citizen` while a missing-information request is pending; expected DB effect: none. [derived from 03_use-cases.md UC-03 ext 6a + BR-004 + BR-005]
- List scoping (step 2) → HTTP `200`; the `data` array contains only rows whose `owner_user_account_id` is the caller's account. [derived from 03_use-cases.md UC-03 step 2 + BR-016]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: the citizen reads through protected routes. [derived from docs/conventions.md Auth]
- UC-02 (this domain) must exist first: the list and detail read the `requests` rows submission creates; without it the precondition "at least one request" cannot be produced. [derived from 03_use-cases.md UC-03 preconditions + UC-02]
- Foundation: the `requests`, `request_history_entries`, `messages`, `documents`, `decisions`, `notifications` migrations/models with their enums, and the request-scoped policies (Request, Document, Message, Decision, RequestHistoryEntry, Notification). [derived from docs/conventions.md Authorization + Data & schema]
- Cross-domain enrichers, not prerequisites: assignment/reassignment history (UC-05), missing-information messages and Waiting for Citizen status (UC-07), status progress (UC-08), and decisions (UC-09) populate what steps 5–6 display; UC-03 renders whatever exists, so it is buildable and testable with UC-02 data alone (ext 6a's full path needs UC-07). [derived from 03_use-cases.md UC-03 steps 5–6 + UC-05/UC-07/UC-08/UC-09]

## Notes
This use case protects transparency without making request information public. [03_use-cases.md UC-03]
