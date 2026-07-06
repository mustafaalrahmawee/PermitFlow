# UC-10 — Exchange Request Messages

## Identity
- Domain: Communication · Primary actor: Citizen or Staff member · Supporting actors: Citizen or Staff member · Level: User-Goal 🌊 [03_use-cases.md UC-10]

## Goal & trigger
An authorized request participant wants to ask or answer a request-related question inside the portal. [03_use-cases.md UC-10]

## Preconditions
- The request exists. [03_use-cases.md UC-10]
- The citizen owns the request. [03_use-cases.md UC-10] [02_business-rules.md BR-003]
- The request has exactly one responsible staff member for direct staff-citizen communication. [03_use-cases.md UC-10] [02_business-rules.md BR-009]

## Main flow
1. The primary actor opens an authorized request.
2. The primary actor opens the request message thread.
3. The primary actor writes a request-related message.
4. The system validates that the message is between the request's citizen and the responsible staff member.
5. The system records the message on the request.
6. The system makes the message visible to the other authorized participant.
7. The system creates an in-portal notification for the other authorized participant. [03_use-cases.md UC-10]

## Acceptance checklist (from extensions)
- ext 1a — a citizen attempting to open another citizen's request is denied access. [03_use-cases.md UC-10] [02_business-rules.md BR-003] [02_business-rules.md BR-016]
- ext 1b — a staff member attempting to open a request for which they are not responsible is denied access. [03_use-cases.md UC-10] [02_business-rules.md BR-009] [02_business-rules.md BR-016]
- ext 3a — an empty message is not recorded, and the primary actor is asked to enter message content. [03_use-cases.md UC-10]
- ext 4a — with no responsible staff member assigned yet, direct staff-citizen exchange cannot complete and the actor is asked to wait until assignment. [03_use-cases.md UC-10] [02_business-rules.md BR-009] [02_business-rules.md BR-011]
- ext 4b — a participant outside the request's citizen/responsible-staff relationship is denied the message. [03_use-cases.md UC-10] [02_business-rules.md BR-011] [02_business-rules.md BR-016]
- ext 5a — when the message cannot be recorded, no notification is created and the primary actor sees that the message was not sent. [03_use-cases.md UC-10]
- ext 7a — if the notification cannot be created, the message remains recorded and visible in the request thread. [03_use-cases.md UC-10]

## Authorization
- Request-scoped reach (`InteractsWithRequestScope`): the request and its message thread are reachable only by the owning citizen, the responsible staff member, or an administrator; a record outside the actor's scope is reported as not found. [docs/conventions.md Authorization] [02_business-rules.md BR-016]
- Sending a message is `MessagePolicy@create` — only the request's citizen or its responsible staff member; this policy carries the BR-011 participant relationship. Reading a message follows request-scoped reach (who may *read* is governed by BR-016, not BR-011). [docs/conventions.md Authorization] [02_business-rules.md BR-011] [02_business-rules.md BR-016]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-001] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Request` → `requests`; read only: `owner_user_account_id` (participant check, BR-003/BR-011) and `responsible_staff_user_account_id` (participant check and recipient resolution, BR-009/BR-011); no column written. [04_data-model.md §2.1]
- `Message` → `messages` + `MessageKind` enum (`general` here; the set is {`general`, `missing_information_request`, `citizen_reply`}); fields: `request_id`, `sender_user_account_id` (the primary actor), `recipient_user_account_id` (the other authorized participant), `message_kind`, `body`, `sent_at`. [04_data-model.md §2.1] [02_business-rules.md BR-011]
- `Notification` → `notifications` + `NotificationType` enum (`message_received`); fields: `recipient_user_account_id` (the other authorized participant), `request_id`, `body`, `read_at` (nullable). [04_data-model.md §2.1]
- `User account` / `Role` → `user_accounts` (+ `role` value set); read for participant identity and role. [04_data-model.md §2.1] [02_business-rules.md BR-001]

## Status transition(s)
_None._ UC-10 records a message and never changes `requests.status`; the `TransitionsRequestStatus` guard is not involved. [03_use-cases.md UC-10] [docs/conventions.md Status transitions]

## History events (written explicitly)
_None required by the specs._ The UC-10 main flow records the message on the request (step 5) but states no request-history write, and BR-017 covers important status changes and decisions, neither of which occurs here. [03_use-cases.md UC-10] [02_business-rules.md BR-017]
- **Open Question:** `04_data-model.md` defines the `message_recorded` history event type and a Message → Request history entry relationship ("history entries that record a message"), yet no use-case flow states that write. Whether general messages also write a `message_recorded` history entry is unresolved; this contract assumes no history write, per the UC-10 flow. [04_data-model.md §1.2, §1.3] [derived — fragile]

## Notifications (best-effort)
- `message_received` — created for the other authorized participant (step 7): the responsible staff member when the citizen sends, the owning citizen when the staff member sends. The message remains recorded and visible in the thread even if the notification cannot be created (ext 7a). If the message itself cannot be recorded, no notification is created (ext 5a). [03_use-cases.md UC-10] [04_data-model.md §2.1] [05_system-design.md §4]

## Performance target
No dedicated per-UC target; UC-10 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Must not fail silently: a message that cannot be recorded is reported as not sent, and no notification is created for it (ext 5a); no message is silently delivered to the wrong participant (minimal guarantee) — the BR-011 participant validation runs before the write. [03_use-cases.md UC-10] [02_business-rules.md BR-011]
- Authorization must fail closed; a failed or unevaluable check denies. [05_system-design.md §4]
- May degrade gracefully: the in-portal notification — the message write completes without it (ext 7a). [03_use-cases.md UC-10] [05_system-design.md §4]

## API seam  [derived — fragile]
- `GET /api/requests/{request}/messages` — `auth:sanctum` + request-scoped reach (owning citizen, responsible staff member, or administrator); returns the request's message thread; success `200` with the message list in the `data` envelope (paginated lists add a sibling `meta` cursor). [derived from 03_use-cases.md UC-10 step 2 + BR-016 + docs/conventions.md Authorization + API success responses]
- `POST /api/requests/{request}/messages` — `auth:sanctum` + `MessagePolicy@create` (request's citizen or responsible staff member); request field: `body` (required, non-empty); records a `general` message with sender = the actor and recipient = the other authorized participant, then creates the `message_received` notification best-effort; success `201` with the created message in the `data` envelope. [derived from 03_use-cases.md UC-10 steps 3–7 + BR-011 + docs/conventions.md Authorization + API success responses]

## QA map  [derived — fragile]
- ext 1a → HTTP `404` when a citizen targets another citizen's request (out-of-scope records read as not found); expected DB effect: no change. [derived from 03_use-cases.md UC-10 ext 1a + BR-003 + BR-016 + docs/conventions.md API error responses]
- ext 1b → HTTP `404` when a staff member targets a request they are not responsible for; expected DB effect: no change. [derived from 03_use-cases.md UC-10 ext 1b + BR-009 + BR-016 + docs/conventions.md API error responses]
- ext 3a → HTTP `422` on an empty or missing `body`; expected DB effect: no `messages` row, no `notifications` row. [derived from 03_use-cases.md UC-10 ext 3a + docs/conventions.md API error responses]
- ext 4a → HTTP `409` when the request has no responsible staff member yet (`responsible_staff_user_account_id` null) — the send is blocked by current state, not by identity, matching the conventions' lifecycle-conflict code; expected DB effect: no `messages` row, no `notifications` row. [derived from 03_use-cases.md UC-10 ext 4a + BR-009 + BR-011 + docs/conventions.md API error responses — fragile: an implementation placing this check inside `MessagePolicy@create` would return `403` instead]
- ext 4b → HTTP `403` for an in-scope actor outside the citizen/responsible-staff pair (e.g. an administrator, who can reach the request under BR-016 but is not a BR-011 participant): `MessagePolicy@create` denies; an out-of-scope actor is already covered by ext 1a/1b as `404`; expected DB effect: no `messages` row. [derived from 03_use-cases.md UC-10 ext 4b + BR-011 + BR-016 + docs/conventions.md API error responses]
- ext 5a → HTTP `500` when the message write fails; expected DB effect: transaction rollback — no `messages` row and no `notifications` row. [derived from 03_use-cases.md UC-10 ext 5a + docs/conventions.md API error responses]
- ext 7a → not triggerable through the API; verified by fault injection at test level: the call returns `201`, the `messages` row exists, and no `notifications` row exists. [derived from 03_use-cases.md UC-10 ext 7a + 05_system-design.md §4]
- Happy path (citizen sends) → HTTP `201`; expected DB effect: one `messages` row (`message_kind` = `general`, `sender_user_account_id` = owning citizen, `recipient_user_account_id` = responsible staff member), one `notifications` row (`notification_type` = `message_received`, recipient = responsible staff member), `requests.status` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-10 steps 4–7]
- Happy path (staff sends) → HTTP `201`; expected DB effect: one `messages` row (`message_kind` = `general`, `sender_user_account_id` = responsible staff member, `recipient_user_account_id` = owning citizen), one `notifications` row (`notification_type` = `message_received`, recipient = owning citizen), `requests.status` unchanged, no `request_history_entries` row. [derived from 03_use-cases.md UC-10 steps 4–7]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist first: both participants act through protected routes as active accounts. [derived from 03_use-cases.md UC-10 preconditions + docs/conventions.md Auth]
- Cross-domain (Requests): UC-02 submit-a-request must exist first — it creates the citizen-owned request the thread hangs off (BR-003) and submits it into institutional handling. [derived from 03_use-cases.md UC-10 preconditions + BR-003]
- Cross-domain (Review Workflow): UC-05 assign-a-request must exist first — it supplies the exactly-one responsible staff member that BR-011 requires as the second participant; until assignment, sending is blocked (ext 4a). [derived from 03_use-cases.md UC-10 preconditions + BR-009 + BR-011]
- Foundation: `requests`, `messages`, `notifications` migrations/models; `MessageKind` and `NotificationType` enums; `MessagePolicy@create` and the `InteractsWithRequestScope` concern; the API success/error envelopes. [derived from docs/conventions.md Data & schema + Authorization + API success responses + API error responses]

## Notes
Staff-initiated missing-information communication is handled by UC-07. This use case covers general request-related exchange in both directions. [03_use-cases.md UC-10]
