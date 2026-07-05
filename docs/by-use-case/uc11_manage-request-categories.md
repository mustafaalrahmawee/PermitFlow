# UC-11 — Manage Request Categories

## Identity
- Domain: Administration · Primary actor: Administrator · Supporting actors: `_None_` · Level: User-Goal 🌊 [03_use-cases.md UC-11]

## Goal & trigger
The administrator needs to maintain the categories under which citizens file requests. [03_use-cases.md UC-11]

## Preconditions
- The administrator has access to category management. [03_use-cases.md UC-11]

## Main flow
1. The administrator opens request category management.
2. The administrator chooses to create or maintain a request category.
3. The administrator enters or reviews the category information.
4. The administrator chooses whether the category is active or inactive for future request filing.
5. The system validates that the actor is allowed to maintain categories.
6. The system validates that the category change keeps existing requests understandable.
7. The system saves the request category.
8. The system makes active categories available for new request filing and hides inactive categories from new request filing. [03_use-cases.md UC-11]

## Acceptance checklist (from extensions)
- ext 2a — selecting a category that cannot be found leaves categories unchanged and asks the administrator to choose another category. [03_use-cases.md UC-11]
- ext 5a — a non-administrator attempting category maintenance is denied. [03_use-cases.md UC-11] [02_business-rules.md BR-012] [02_business-rules.md BR-016]
- ext 6a — deleting a category that is already used by existing requests is blocked. [03_use-cases.md UC-11]
- ext 6b — semantically renaming a category that is already used by existing requests is blocked, because existing request history would become unclear. [03_use-cases.md UC-11] [02_business-rules.md BR-017]
- ext 6c — marking a used category inactive is allowed for future filing while existing requests keep their original category. [03_use-cases.md UC-11]
- ext 7a — if the category change cannot be saved, existing request categories remain unchanged. [03_use-cases.md UC-11]

## Authorization
- Category maintenance uses the `manage-categories` gate and is administrator-only. [docs/conventions.md Authorization] [02_business-rules.md BR-012]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]
- Category records themselves are not request-scoped, but the used-category checks read `requests` rows; `BR-016` governs any request information surfaced alongside. [02_business-rules.md BR-016]

## Data touched
- `Request category` → `request_categories`; fields: `name` (unique), `description` (nullable), `is_active`. [04_data-model.md §2.1]
- `Request` → `requests`; read via `requests.request_category_id` (restrict-on-delete) to detect whether a category is already used by existing requests. [04_data-model.md §2.1] [03_use-cases.md UC-11]

## Status transition(s)
_None._ UC-11 changes category availability, not request status. [03_use-cases.md UC-11]

## History events (written explicitly)
_None._ UC-11 defines no request-history event; it protects existing request understandability by blocking deletion and semantic rename of used categories instead. [03_use-cases.md UC-11] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ UC-11 defines no in-portal notification. [03_use-cases.md UC-11]

## Performance target
No dedicated per-UC target; UC-11 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Authorization must fail closed; a failed or unevaluable check denies category maintenance. [05_system-design.md §4]
- On any failure, existing request categories and existing request classifications remain unchanged. [03_use-cases.md UC-11]
- The database is the system of record for `request_categories` and `requests`; the restrict-on-delete FK from `requests.request_category_id` structurally prevents orphaning a used category. [05_system-design.md §1.1] [docs/conventions.md Data & schema]

## API seam  [derived — fragile]
- `GET /api/admin/request-categories` — `auth:sanctum` + `manage-categories`; returns the category list with `name`, `description`, `is_active`. [derived from 03_use-cases.md UC-11 steps 1–2 + docs/conventions.md Authorization]
- `GET /api/admin/request-categories/{request_category}` — `auth:sanctum` + `manage-categories`; returns one category for maintenance. [derived from 03_use-cases.md UC-11 steps 2–3]
- `POST /api/admin/request-categories` — `auth:sanctum` + `manage-categories`; request fields: `name`, `description`, `is_active`; creates a category; success returns the created resource in the `data` envelope. [derived from 03_use-cases.md UC-11 steps 2–8 + docs/conventions.md API success responses]
- `PATCH /api/admin/request-categories/{request_category}` — `auth:sanctum` + `manage-categories`; request fields: `name`, `description`, `is_active`; rejects a semantic `name` change when the category is used by existing requests; deactivation of a used category is allowed. [derived from 03_use-cases.md UC-11 ext 6b/6c]
- `DELETE /api/admin/request-categories/{request_category}` — `auth:sanctum` + `manage-categories`; deletes only a category no existing request uses; success returns `data: null` + `message`. [derived from 03_use-cases.md UC-11 ext 6a + docs/conventions.md API success responses]

## QA map  [derived — fragile]
- ext 2a → HTTP `404`; expected DB effect: no `request_categories` row is changed. [derived from 03_use-cases.md UC-11 ext 2a + docs/conventions.md API error responses]
- ext 5a → HTTP `403`; expected DB effect: no `request_categories` row changes. [derived from 03_use-cases.md UC-11 ext 5a + BR-012 + BR-016 + docs/conventions.md API error responses]
- ext 6a → HTTP `409`; expected DB effect: the used `request_categories` row still exists and every `requests.request_category_id` referencing it is unchanged. [derived from 03_use-cases.md UC-11 ext 6a + docs/conventions.md API error responses]
- ext 6b → HTTP `409`; expected DB effect: `request_categories.name` remains unchanged for a category used by existing requests. [derived from 03_use-cases.md UC-11 ext 6b + BR-017 + docs/conventions.md API error responses]
- ext 6c → HTTP `200`; expected DB effect: `request_categories.is_active` becomes `false` while every existing `requests.request_category_id` referencing the category is unchanged. [derived from 03_use-cases.md UC-11 ext 6c + docs/conventions.md API success responses]
- ext 7a → HTTP `500`; expected DB effect: transaction rollback leaves all `request_categories` rows unchanged. [derived from 03_use-cases.md UC-11 ext 7a + docs/conventions.md API error responses]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist before administrator category maintenance is usable through protected routes. [derived from 03_use-cases.md UC-11 preconditions + docs/conventions.md Auth]
- `manage-categories` gate registered in `AppServiceProvider`. [derived from docs/conventions.md Authorization]
- `request_categories` migration/model with the unique `name` constraint and `is_active` flag. [derived from 04_data-model.md §2.1]
- Cross-domain: the `requests` migration/model (with its restrict-on-delete `request_category_id` FK) must exist for the used-category checks in ext 6a/6b/6c; without it, only create and rename-of-unused paths are testable. [derived from 03_use-cases.md UC-11 ext 6a–6c + 04_data-model.md §2.1]
- Downstream note (not a prerequisite): UC-02 request filing requires at least one active category from this use case. [derived from 03_use-cases.md UC-02 preconditions]

## Notes
Used categories are historically protected in v1. Deactivation is the safe alternative to deletion when a category has already been used. [03_use-cases.md UC-11]
