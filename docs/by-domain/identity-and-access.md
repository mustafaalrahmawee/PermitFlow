# Domain — Identity and Access

## Purpose
Identity and Access covers how PermitFlow knows a person as a user account, how that account authenticates, and how its single v1 role and active/inactive state control what the person may do. It anchors the project-wide access model used by the request, review, communication, administration, and reporting workflows. [00_project-context.md §5] [01_miniworld.md §3]

## Use cases (build order)
1. UC-00 Authenticate (Sign In) → `docs/by-use-case/uc00_authenticate-sign-in.md`
2. UC-01 Manage User Accounts and Roles → `docs/by-use-case/uc01_manage-user-accounts-and-roles.md`

## Cross-cutting rules in force
- `BR-001` — every user account holds exactly one role. [02_business-rules.md BR-001]
- `BR-018` — inactive accounts cannot authenticate or pass protected actions. [02_business-rules.md BR-018]
- `BR-013` — user accounts and roles are maintained by administrators. [02_business-rules.md BR-013]
- `BR-016` — request-scoped visibility is enforced wherever account actions touch request information, documents, messages, or decisions. [02_business-rules.md BR-016]
- `BR-017` — existing request history and responsibility links must remain understandable after account maintenance. [02_business-rules.md BR-017]
- Role gate: `manage-accounts` → Administrator. [docs/conventions.md Authorization]

## Shared data
- `user_accounts` — account identity, email, role, and active/inactive state. [04_data-model.md §2.1]
- `user_accounts.role` — role value set: Citizen, Staff member, Administrator. [04_data-model.md §2.1] [02_business-rules.md BR-001]
- `user_accounts.account_state` — account-state value set: Active, Inactive. [04_data-model.md §2.1] [02_business-rules.md BR-018]
- `requests`, `messages`, `decisions`, `request_history_entries` — read by account-maintenance checks that protect ownership, responsibility, messages, decisions, and historical links. [03_use-cases.md UC-01] [04_data-model.md §2.1]
- `personal_access_tokens` — Sanctum implementation table, not a domain table. [docs/conventions.md Auth]

## Build order & dependencies
1. Foundation first: create `user_accounts`, the `App\Models\UserAccount` authenticatable model, string-backed role/account-state enums, Sanctum bearer-token auth, and fail-closed authorization conventions. [derived from docs/conventions.md Auth + Authorization]
2. UC-00 before protected user-goal UCs: sign-in creates the authenticated context that later routes assume. [derived from 03_use-cases.md UC-00 notes]
3. UC-01 after UC-00 and the `manage-accounts` gate: account maintenance requires an authenticated administrator. [derived from 03_use-cases.md UC-01 preconditions + docs/conventions.md Authorization]
4. UC-01 lifecycle blockers depend on cross-domain tables/models for `requests`, `messages`, `decisions`, and `request_history_entries`, because the use case checks ownership, responsibility, messages, decisions, and history links before deactivation or role change. [derived from 03_use-cases.md UC-01 extensions]
5. A seed/manual bootstrap administrator is needed before UC-01 can be used from the UI/API; self-service registration is out of v1. [derived from 03_use-cases.md UC-00 notes + UC-01 preconditions]

## Notes & open questions
- Registration and self-service account creation are out of v1; accounts are provisioned through UC-01. [03_use-cases.md UC-00]
- v1 uses deactivation rather than physical deletion for accounts that have request history. [03_use-cases.md UC-01]
- Role changes are deliberately conservative because changing historical ownership or responsibility would weaken traceability. [03_use-cases.md UC-01]
- Resolved (was open): `docs/conventions.md` now defines the API error-response convention — 401 authentication denial, 403 authorization denial, 404 not-found/visibility, 422 validation, 409 lifecycle conflict, 500 persistence failure. Both contracts' QA maps cite it instead of flagging HTTP status as `OPEN`. [docs/conventions.md API error responses]
