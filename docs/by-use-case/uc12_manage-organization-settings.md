# UC-12 — Manage Organization Settings

## Identity
- Domain: Administration · Primary actor: Administrator · Supporting actors: `_None_` · Level: User-Goal 🌊 [03_use-cases.md UC-12]

## Goal & trigger
The administrator needs to configure institution-level settings for the single organization. [03_use-cases.md UC-12]

## Preconditions
- The administrator has access to organization settings. [03_use-cases.md UC-12]

## Main flow
1. The administrator opens organization settings.
2. The system shows the current organization settings.
3. The administrator changes the needed settings.
4. The system validates that the actor is allowed to maintain organization settings.
5. The system saves the changed settings.
6. The system makes the settings effective for the organization. [03_use-cases.md UC-12]

## Acceptance checklist (from extensions)
- ext 3a — cancelling before saving leaves the settings unchanged. [03_use-cases.md UC-12]
- ext 4a — a non-administrator attempting settings maintenance is denied. [03_use-cases.md UC-12] [02_business-rules.md BR-014] [02_business-rules.md BR-016]
- ext 5a — a changed setting that would conflict with v1 hard constraints is rejected and the previous setting remains active. [03_use-cases.md UC-12]

## Authorization
- Settings maintenance uses the `manage-settings` gate and is administrator-only. [docs/conventions.md Authorization] [02_business-rules.md BR-014]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]
- Role is a single fixed value on the account (`BR-001`), evaluated live, never from a cached copy. [02_business-rules.md BR-001] [05_system-design.md §4]

## Data touched
- `Organization settings` → `organization_settings`; fields: `singleton_key` (unique, fixed v1 value `single_organization`), `organization_name`, `settings_payload` (JSON, read and written as one whole block), `updated_by_user_account_id` (nullable FK → `user_accounts.id`). [04_data-model.md §2.1]
- `User account` → `user_accounts`; the acting administrator is recorded as the `last updater` via `updated_by_user_account_id`. [04_data-model.md §1.3, §2.1]
- `Role` → no standalone table; stored as `user_accounts.role`; value set: Citizen, Staff member, Administrator. [04_data-model.md §2.1] [02_business-rules.md BR-001]

## Status transition(s)
_None._ UC-12 changes organization configuration, not request status. [03_use-cases.md UC-12]

## History events (written explicitly)
_None._ UC-12 defines no request-history event; `BR-017` traceability covers request status changes and decisions, not organization settings. [03_use-cases.md UC-12] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ UC-12 defines no in-portal notification. [03_use-cases.md UC-12]

## Performance target
No dedicated per-UC target; UC-12 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Authorization must fail closed; a failed or unevaluable check denies settings maintenance. [05_system-design.md §4]
- On any failure, the existing organization settings remain active. [03_use-cases.md UC-12]
- The database is the system of record for `organization_settings`; `settings_payload` is a whole-block write, so a rejected or failed save leaves the previous block intact. [05_system-design.md §1.1] [04_data-model.md §3.1]

## API seam  [derived — fragile]
- `GET /api/admin/organization-settings` — `auth:sanctum` + `manage-settings`; returns the single organization-settings record (`organization_name`, `settings_payload`) in the `data` envelope; no list or id parameter because the record is a singleton. [derived from 03_use-cases.md UC-12 steps 1–2 + 04_data-model.md §2.1 singleton_key + docs/conventions.md API success responses]
- `PUT /api/admin/organization-settings` — `auth:sanctum` + `manage-settings`; request fields: `organization_name`, `settings_payload` (whole block); saves the settings, sets `updated_by_user_account_id` to the acting administrator, and returns the updated record. [derived from 03_use-cases.md UC-12 steps 3–6 + 04_data-model.md §2.1 + docs/conventions.md Data & schema]
- No create or delete endpoint: the singleton row exists from seeding and is never removed in v1. [derived from 04_data-model.md §2.1 singleton_key]

## QA map  [derived — fragile]
- ext 3a → `frontend-only`; cancelling is a client action that sends no request; expected DB effect: the `organization_settings` row is unchanged. [derived from 03_use-cases.md UC-12 ext 3a]
- ext 4a → HTTP `403`; expected DB effect: `organization_settings` row unchanged, including `updated_by_user_account_id`. [derived from 03_use-cases.md UC-12 ext 4a + BR-014 + BR-016 + docs/conventions.md API error responses]
- ext 5a → HTTP `422`; expected DB effect: the previous `organization_name` and `settings_payload` block remain active and unchanged. [derived from 03_use-cases.md UC-12 ext 5a + docs/conventions.md API error responses]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist before administrator settings maintenance is usable through protected routes. [derived from 03_use-cases.md UC-12 preconditions + docs/conventions.md Auth]
- `manage-settings` gate registered in `AppServiceProvider`. [derived from docs/conventions.md Authorization]
- `organization_settings` migration/model, plus a seeded singleton row with `singleton_key = single_organization` so the GET seam has a record to show before the first save. [derived from 04_data-model.md §2.1]
- `user_accounts` migration/model for the `updated_by_user_account_id` FK and an existing administrator account (UC-01 or seeding) to act. [derived from 04_data-model.md §2.1 + 03_use-cases.md UC-12 preconditions]

## Notes
The concrete settings are intentionally not expanded beyond the miniworld object. [03_use-cases.md UC-12]

## Open Questions
- The "v1 hard constraints" that ext 5a checks a changed setting against are not enumerated in any spec; the concrete settings are deliberately unexpanded (`03_use-cases.md UC-12` notes), so which values conflict — and therefore the exact 422 validation rules — cannot be compiled from the specs and must be decided at implementation time. [03_use-cases.md UC-12 ext 5a]
