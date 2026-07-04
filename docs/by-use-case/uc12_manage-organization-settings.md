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
- ext 3a — if the administrator cancels before saving, the settings remain unchanged. [03_use-cases.md UC-12]
- ext 4a — a non-administrator attempting settings maintenance is denied. [03_use-cases.md UC-12] [02_business-rules.md BR-014] [02_business-rules.md BR-016]
- ext 5a — a changed setting that would conflict with v1 hard constraints is rejected and the previous setting stays active. [03_use-cases.md UC-12]

## Authorization
- Settings maintenance uses the `manage-settings` gate and is administrator-only; it is a role gate, not request-scoped, so denial is a role-gate denial. [docs/conventions.md Authorization] [02_business-rules.md BR-014] [02_business-rules.md BR-016]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Organization settings` → `organization_settings`; fields: `singleton_key` (fixed `single_organization`), `organization_name`, `settings_payload` (whole JSON block), `updated_by_user_account_id`. [04_data-model.md §2.1] [04_data-model.md §3.1]
- `User account` → `user_accounts`; the administrator saved as the `last updater` via `updated_by_user_account_id`. [04_data-model.md §2.1]
- `Role` → no standalone table; stored as `user_accounts.role`; the actor must hold Administrator. [04_data-model.md §2.1] [02_business-rules.md BR-001]

## Status transition(s)
_None._ UC-12 configures organization settings and does not change any request status. [03_use-cases.md UC-12]

## History events (written explicitly)
_None._ UC-12 writes no request-history event; request history records request events, not organization configuration. [03_use-cases.md UC-12] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ UC-12 defines no in-portal notification. [03_use-cases.md UC-12]

## Performance target
No dedicated per-UC target; UC-12 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Authorization must fail closed; a failed or unevaluable check denies settings maintenance. [05_system-design.md §4]
- On failure, the existing organization settings remain active. [03_use-cases.md UC-12]
- The database is the system of record for `organization_settings`; `settings_payload` is read and written as one whole block. [05_system-design.md §1.1] [04_data-model.md §3.1]

## API seam  [derived — fragile]
- `GET /api/admin/organization-settings` — `auth:sanctum` + `manage-settings`; `200 OK`; returns the single organization's current settings `{ data: { organization_name, settings_payload } }`. [derived from 03_use-cases.md UC-12 steps 1–2 + docs/conventions.md Authorization]
- `PATCH /api/admin/organization-settings` — `auth:sanctum` + `manage-settings`; request fields: `organization_name`, `settings_payload`; updates the singleton and sets `updated_by_user_account_id`; rejects a change conflicting with v1 hard constraints; `200 OK`; returns updated `{ data: { organization_name, settings_payload, updated_by_user_account_id } }`. [derived from 03_use-cases.md UC-12 steps 3–6 + ext 5a]

## QA map  [derived — fragile]
- ext 3a → `frontend-only`; the cancel path sends no update request, so no `organization_settings` row changes. [derived from 03_use-cases.md UC-12 ext 3a]
- ext 4a → HTTP `403`; expected DB effect: no `organization_settings` row changes. [derived from 03_use-cases.md UC-12 ext 4a + BR-014 + BR-016 + docs/conventions.md API error responses]
- ext 5a → HTTP `422`; expected DB effect: the `organization_settings` row retains its previous values when a change conflicts with v1 hard constraints. Mapped as validation (`422`) rather than lifecycle conflict (`409`): a hard-constraint violation is rejected as an invalid `settings_payload` value — for example a payload that would contradict the fixed single-organization constraint (`singleton_key = single_organization`) — not a state-transition conflict on an existing record. The immutable `singleton_key` is not itself a `PATCH` request field. [derived from 03_use-cases.md UC-12 ext 5a + docs/conventions.md API error responses]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist before administrator settings maintenance is usable through protected routes. [derived from 03_use-cases.md UC-12 preconditions + docs/conventions.md Auth]
- `manage-settings` gate registered in `AppServiceProvider`. [derived from docs/conventions.md Authorization]
- `organization_settings` migration/model, including the `updated_by_user_account_id` FK → `user_accounts.id`. [derived from 04_data-model.md §2.1]
- A single-row (singleton) organization-settings record seeded with `singleton_key = single_organization`. [derived from 04_data-model.md §2.1]
- A bootstrap administrator account exists (seeded or manually created; self-service registration is out of v1). [derived from 03_use-cases.md UC-01 preconditions + UC-00 notes]

## Notes
The concrete settings are intentionally not expanded beyond the miniworld object; the payload is handled as one whole block. [03_use-cases.md UC-12] [04_data-model.md §3.1]
