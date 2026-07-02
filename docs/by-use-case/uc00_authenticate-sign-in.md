# UC-00 — Authenticate (Sign In)

## Identity
- Domain: Identity and Access · Primary actor: Registered user (Citizen, Staff member, or Administrator) · Supporting actors: `_None_` · Level: Subfunction 🐚 [03_use-cases.md UC-00]

## Goal & trigger
A person wants to access the portal under their user account. [03_use-cases.md UC-00]

## Preconditions
- A user account exists for the person. [03_use-cases.md UC-00]

## Main flow
1. The person provides account credentials.
2. The system verifies the credentials against a known user account.
3. The system confirms that the account is active.
4. The system establishes an authenticated bearer-token session for the account. [derived from docs/conventions.md Auth]
5. The system makes functions available according to the account's single role. [03_use-cases.md UC-00] [02_business-rules.md BR-001]

## Acceptance checklist (from extensions)
- ext 2a — credentials that do not match a known account are denied without revealing which part failed. [03_use-cases.md UC-00]
- ext 3a — an inactive account is denied and no session is established. [03_use-cases.md UC-00] [02_business-rules.md BR-018]
- ext 4a — a failed session/token establishment reports that sign-in did not complete and grants no access. [03_use-cases.md UC-00]

## Authorization
- The sign-in endpoint itself is public, because no authenticated context exists yet. [derived from 03_use-cases.md UC-00]
- The authenticated model is `App\Models\UserAccount` on `user_accounts`; login uses `email` + `password`, then issues a Sanctum personal access token sent later as `Authorization: Bearer <token>`. [docs/conventions.md Auth]
- Protected routes use `auth:sanctum`; inactive accounts are denied and role/ownership checks evaluate live, never from a cached copy. [docs/conventions.md Authorization] [02_business-rules.md BR-018]
- Role availability after sign-in follows the single-role account value. [02_business-rules.md BR-001]

## Data touched
- `User account` → `user_accounts`; fields used: `email`, implementation-only `password`, `role`, `account_state`; role/account-state value sets live on the table. [04_data-model.md §2.1] [docs/conventions.md Auth]
- `Role` → no standalone table; stored as `user_accounts.role`. [04_data-model.md §2.1]
- Auth token → `personal_access_tokens`; implementation table from Sanctum, not a domain table. [docs/conventions.md Auth]

## Status transition(s)
_None._ Request status is not changed by authentication. [03_use-cases.md UC-00]

## History events (written explicitly)
_None._ UC-00 does not write request history entries. [03_use-cases.md UC-00] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ UC-00 defines no in-portal notification. [03_use-cases.md UC-00]

## Performance target
No dedicated per-UC target; UC-00 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Authentication must fail closed: unverified or inactive accounts receive no session and no access. [03_use-cases.md UC-00] [02_business-rules.md BR-018]
- Access-control evaluation must fail closed; a check that cannot be evaluated denies rather than permits. [05_system-design.md §4]
- The single database is the system of record for `user_accounts`; token issuance must not imply access if account checks fail. [05_system-design.md §1.1] [docs/conventions.md Auth]

## API seam  [derived — fragile]
- `POST /api/auth/login` — public; request fields: `email`, `password`; success returns the authenticated account summary plus a Sanctum bearer token; access made available according to `user_accounts.role`. [derived from 03_use-cases.md UC-00 + docs/conventions.md Auth]
- `GET /api/auth/me` — `auth:sanctum`; returns the current active account and role used by the frontend to show available functions. [derived from 03_use-cases.md UC-00 step 5 + docs/conventions.md Auth]

## QA map  [derived — fragile]
- ext 2a → HTTP `401`; expected DB effect: no new `personal_access_tokens` row for the failed attempt; response must not reveal whether email or password failed. [derived from 03_use-cases.md UC-00 ext 2a + docs/conventions.md API error responses]
- ext 3a → HTTP `401`; expected DB effect: no new `personal_access_tokens` row; inactive account remains unchanged and its inactive state is not disclosed. [derived from 03_use-cases.md UC-00 ext 3a + BR-018 + docs/conventions.md API error responses]
- ext 4a → HTTP `500`; expected DB effect: no durable token/session usable for protected routes. [derived from 03_use-cases.md UC-00 ext 4a + docs/conventions.md API error responses]

## Dependencies  [derived — fragile]
- `user_accounts` migration and `App\Models\UserAccount` authenticatable model. [derived from 04_data-model.md §2.1 + docs/conventions.md Auth]
- String-backed role and account-state enum shape from project conventions. [derived from docs/conventions.md Data & schema]
- Laravel Sanctum and `personal_access_tokens` table. [derived from docs/conventions.md Auth]
- Bootstrap account provisioning (a user account exists for at least one person; self-service registration is out of v1) — see coordinator. [identity-and-access.md Build order & dependencies]

## Notes
Sign-in is a subfunction that user-goal use cases assume as a precondition. It documents authentication behavior for all registered roles and anchors rejection of inactive accounts. Registration and self-service account creation are out of v1; accounts are provisioned through UC-01. [03_use-cases.md UC-00]
