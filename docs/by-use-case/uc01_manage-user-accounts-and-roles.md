# UC-01 — Manage User Accounts and Roles

## Identity
- Domain: Identity and Access · Primary actor: Administrator · Supporting actors: `_None_` · Level: User-Goal 🌊 [03_use-cases.md UC-01]

## Goal & trigger
The administrator needs to create or maintain a user account, assign its role, or change its account state. [03_use-cases.md UC-01]

## Preconditions
- The administrator has access to administration functions. [03_use-cases.md UC-01]
- The person to be represented by the account is known to the institution. [03_use-cases.md UC-01]

## Main flow
1. The administrator opens user account management.
2. The administrator chooses whether to create a new account or maintain an existing account.
3. The administrator enters or reviews account details.
4. The administrator selects exactly one role.
5. The administrator chooses the account state where activation or deactivation is relevant.
6. The system validates account details, selected role, account state, and lifecycle impact.
7. The system saves the account, role, and account state.
8. The system makes the account available according to the assigned role and account state. [03_use-cases.md UC-01]

## Acceptance checklist (from extensions)
- ext 2a — selecting a non-existing account leaves existing accounts unchanged and asks the administrator to choose another account. [03_use-cases.md UC-01]
- ext 4a — selecting no role or more than one role is rejected; exactly one role is required. [03_use-cases.md UC-01] [02_business-rules.md BR-001]
- ext 5a — deactivating a citizen account that owns an undecided request is blocked until the request is decided. [03_use-cases.md UC-01]
- ext 5b — deactivating a staff account responsible for an undecided request is blocked until reassignment or decision. [03_use-cases.md UC-01]
- ext 5c — changing the role of an account connected to request ownership, responsibility, messages, or decisions is blocked in v1 to protect request history and responsibility. [03_use-cases.md UC-01] [02_business-rules.md BR-017]
- ext 6a — a non-authorized actor attempting account maintenance is denied. [03_use-cases.md UC-01] [02_business-rules.md BR-013] [02_business-rules.md BR-016]
- ext 7a — if the account change cannot be saved, existing user accounts, roles, and account states remain unchanged. [03_use-cases.md UC-01]

## Authorization
- Account maintenance uses the `manage-accounts` gate and is administrator-only. [docs/conventions.md Authorization] [02_business-rules.md BR-013]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]
- Request-scoped reach (`BR-016`) matters when lifecycle validation reads request information, messages, decisions, and history links. [02_business-rules.md BR-016]

## Data touched
- `User account` → `user_accounts`; fields: `display_name`, `email`, `role`, `account_state`; implementation-only `password` may be set for local login. [04_data-model.md §2.1] [docs/conventions.md Auth]
- `Role` → no standalone table; stored as `user_accounts.role`; value set: Citizen, Staff member, Administrator. [04_data-model.md §2.1] [02_business-rules.md BR-001]
- `Request` → `requests`; read for citizen ownership, staff responsibility, and undecided-request blockers. [04_data-model.md §2.1] [03_use-cases.md UC-01]
- `Message` → `messages`; read for role-change blockers on historical sender/recipient links. [04_data-model.md §2.1] [03_use-cases.md UC-01]
- `Decision` → `decisions`; read for role-change blockers and decided/undecided lifecycle checks. [04_data-model.md §2.1] [03_use-cases.md UC-01]
- `Request history` → `request_history_entries`; preserved, not regenerated, to protect traceability. [04_data-model.md §2.1] [02_business-rules.md BR-017]

## Status transition(s)
_None._ UC-01 changes account state, not request status. [03_use-cases.md UC-01]

## History events (written explicitly)
_None._ UC-01 preserves request history links; it does not define a new request-history event. [03_use-cases.md UC-01] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ UC-01 defines no in-portal notification. [03_use-cases.md UC-01]

## Performance target
No dedicated per-UC target; UC-01 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Authorization must fail closed; a failed or unevaluable check denies account maintenance. [05_system-design.md §4]
- Account changes must preserve existing request ownership, responsibility, decisions, messages, and historical links on failure. [03_use-cases.md UC-01]
- The database is the system of record for `user_accounts`, `requests`, `messages`, `decisions`, and `request_history_entries`; account maintenance should be saved atomically where lifecycle checks and account writes are combined. [05_system-design.md §1.1] [derived from 03_use-cases.md UC-01 guarantees]
- Deactivation is used instead of physical deletion for accounts tied to request history; hard deletion would conflict with traceability and referential-restrict rules. [docs/conventions.md Data & schema] [05_system-design.md §7]

## API seam  [derived — fragile]
- `GET /api/admin/user-accounts` — `auth:sanctum` + `manage-accounts`; returns account list with role and account state. [derived from 03_use-cases.md UC-01 steps 1–3 + docs/conventions.md Authorization]
- `GET /api/admin/user-accounts/{user_account}` — `auth:sanctum` + `manage-accounts`; returns one account for maintenance. [derived from 03_use-cases.md UC-01 steps 2–3]
- `POST /api/admin/user-accounts` — `auth:sanctum` + `manage-accounts`; request fields: `display_name`, `email`, `role`, `account_state`, implementation-only `password`; creates an account with exactly one role. [derived from 03_use-cases.md UC-01 steps 2–8 + docs/conventions.md Auth]
- `PATCH /api/admin/user-accounts/{user_account}` — `auth:sanctum` + `manage-accounts`; request fields: editable account details, one `role`, one `account_state`; validates lifecycle blockers before saving. [derived from 03_use-cases.md UC-01 steps 2–8]

## QA map  [derived — fragile]
- ext 2a → HTTP `404`; expected DB effect: no `user_accounts` row is changed. [derived from 03_use-cases.md UC-01 ext 2a + docs/conventions.md API error responses]
- ext 4a → HTTP `422`; expected DB effect: no `user_accounts.role` value is saved with none or multiple roles. [derived from 03_use-cases.md UC-01 ext 4a + BR-001 + docs/conventions.md API error responses]
- ext 5a → HTTP `409`; expected DB effect: citizen `user_accounts.account_state` remains unchanged while any owned request is undecided. [derived from 03_use-cases.md UC-01 ext 5a + docs/conventions.md API error responses]
- ext 5b → HTTP `409`; expected DB effect: staff `user_accounts.account_state` remains unchanged while any responsible request is undecided. [derived from 03_use-cases.md UC-01 ext 5b + docs/conventions.md API error responses]
- ext 5c → HTTP `409`; expected DB effect: `user_accounts.role` remains unchanged for accounts connected to ownership, responsibility, messages, or decisions. [derived from 03_use-cases.md UC-01 ext 5c + BR-017 + docs/conventions.md API error responses]
- ext 6a → HTTP `403`; expected DB effect: no `user_accounts` row changes. [derived from 03_use-cases.md UC-01 ext 6a + BR-013 + BR-016 + docs/conventions.md API error responses]
- ext 7a → HTTP `500`; expected DB effect: transaction rollback leaves accounts, roles, account states, request ownership, responsibility, and historical links unchanged. [derived from 03_use-cases.md UC-01 ext 7a + docs/conventions.md API error responses]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist before administrator account maintenance is usable through protected routes. [derived from 03_use-cases.md UC-01 preconditions + docs/conventions.md Auth]
- `manage-accounts` gate registered in `AppServiceProvider`. [derived from docs/conventions.md Authorization]
- `user_accounts` migration/model plus role and account-state enum/value-set validation. [derived from 04_data-model.md §2.1]
- Cross-domain migrations/models for `requests`, `messages`, `decisions`, and `request_history_entries` are needed for lifecycle blockers. [derived from 03_use-cases.md UC-01 extensions]
- Bootstrap administrator provisioning: a seeded or manually created administrator account exists before this use case is usable; self-service registration is out of v1. [derived from 03_use-cases.md UC-00 notes + UC-01 preconditions]

## Notes
v1 uses deactivation rather than physical deletion for accounts that have request history. Role changes are deliberately conservative because changing historical ownership or responsibility would weaken traceability. [03_use-cases.md UC-01]
