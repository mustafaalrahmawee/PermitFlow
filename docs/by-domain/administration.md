# Domain — Administration

## Purpose
Administration covers the configuration an administrator maintains for the single institution: the request-category set citizens file under, and the organization-level settings. It supplies the classification and configuration the request and review workflows depend on, without touching individual request handling. [00_project-context.md §5] [01_miniworld.md §3]

## Use cases (build order)
1. UC-11 Manage Request Categories → `docs/by-use-case/uc11_manage-request-categories.md`
2. UC-12 Manage Organization Settings → `docs/by-use-case/uc12_manage-organization-settings.md`

## Cross-cutting rules in force
- `BR-016` — request-scoped visibility; administration operations are role-gated rather than request-scoped, so denial is a role-gate denial. [02_business-rules.md BR-016]
- `BR-017` — UC-11 only: category maintenance must keep existing request classifications and history understandable. Not in force for UC-12, which writes no request history. [02_business-rules.md BR-017]
- `BR-002` — a request is filed under exactly one active category (UC-11). [02_business-rules.md BR-002]
- `BR-012` — request categories are created and maintained by administrators (UC-11). [02_business-rules.md BR-012]
- `BR-014` — organization settings are configured by administrators (UC-12). [02_business-rules.md BR-014]
- `BR-001` — every user account holds exactly one role (UC-12 last-updater). [02_business-rules.md BR-001]
- Role gates: `manage-categories` → Administrator (UC-11); `manage-settings` → Administrator (UC-12). [docs/conventions.md Authorization]

## Shared data
- `request_categories` — the maintained category set (`name`, `description`, `is_active`) used to classify requests (UC-11). [04_data-model.md §2.1]
- `requests` — read by UC-11 to detect whether a category is already used before deletion or semantic rename. [04_data-model.md §2.1] [03_use-cases.md UC-11]
- `organization_settings` — the single organization's configuration block (`singleton_key`, `organization_name`, `settings_payload`, `updated_by_user_account_id`) (UC-12). [04_data-model.md §2.1]
- `user_accounts` — read for the `last updater` link on organization settings and to gate both use cases by administrator role. [04_data-model.md §2.1] [02_business-rules.md BR-001]

## Build order & dependencies
1. Foundation first: authenticated context (UC-00 sign-in, Sanctum bearer-token auth) and the `manage-categories` / `manage-settings` role gates registered in `AppServiceProvider`. [derived from docs/conventions.md Auth + Authorization]
2. UC-11 before UC-02: request submission's precondition "at least one active request category" cannot be met until categories exist, so category management is built before the request pipeline is usable. [derived from 03_use-cases.md UC-02 preconditions]
3. UC-11's category CRUD can be built before UC-02; it needs only the `request_categories` table plus the `requests` table/model from the shared schema so the used-category delete/rename blockers can be implemented. Full integration testing of those blockers depends on either seeded requests or UC-02 — building all of UC-02 first is not required. [derived from 03_use-cases.md UC-11 extensions]
4. UC-12 is standalone: it needs only `organization_settings` and the `updated_by_user_account_id` FK to `user_accounts`; it does not depend on UC-11. [derived from 03_use-cases.md UC-12 preconditions + 04_data-model.md §2.1]
5. Both require an administrator account to exist first; self-service registration is out of v1. [derived from 03_use-cases.md UC-01 preconditions + UC-00 notes]

## Notes & open questions
- Used categories are historically protected in v1; deactivation is the safe alternative to deletion once a category has been used. [03_use-cases.md UC-11]
- Conservative-rename stance [derived — fragile]: UC-11 ext 6b blocks a *semantic* rename of a used category but the specs do not define how "semantic" is detected; v1 blocks any `name` change on a used category. See the UC-11 contract's conservative-rename note. [uc11_manage-request-categories.md Notes]
- The concrete organization settings are intentionally not expanded beyond the miniworld object; `settings_payload` is read and written as one whole JSON block. [03_use-cases.md UC-12] [04_data-model.md §3.1]
- _None open._
