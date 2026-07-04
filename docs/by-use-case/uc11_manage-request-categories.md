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
- ext 6a — deleting a category already used by existing requests is blocked. [03_use-cases.md UC-11] [02_business-rules.md BR-017]
- ext 6b — semantically renaming a category already used by existing requests is blocked because existing request history would become unclear. [03_use-cases.md UC-11] [02_business-rules.md BR-017]
- ext 6c — marking a used category inactive is allowed for future filing; existing requests keep their original category. [03_use-cases.md UC-11] [02_business-rules.md BR-002]
- ext 7a — if the category change cannot be saved, existing request categories remain unchanged. [03_use-cases.md UC-11]

## Authorization
- Category maintenance uses the `manage-categories` gate and is administrator-only; it is a role gate, not request-scoped, so denial is a role-gate denial. [docs/conventions.md Authorization] [02_business-rules.md BR-012] [02_business-rules.md BR-016]
- The action is protected by `auth:sanctum`; inactive accounts cannot pass protected actions regardless of role. [docs/conventions.md Auth] [02_business-rules.md BR-018]
- Access checks fail closed; a check that cannot be evaluated denies. [05_system-design.md §4]

## Data touched
- `Request category` → `request_categories`; fields: `name`, `description` (nullable), `is_active`. [04_data-model.md §2.1]
- `Request` → `requests`; read to detect whether a category is already used before a delete or semantic rename. [04_data-model.md §2.1] [03_use-cases.md UC-11]

## Status transition(s)
_None._ UC-11 maintains categories and does not change any request status. [03_use-cases.md UC-11]

## History events (written explicitly)
_None._ UC-11 writes no request-history event; category changes preserve existing request classifications rather than recording a request event. [03_use-cases.md UC-11] [02_business-rules.md BR-017]

## Notifications (best-effort)
_None._ UC-11 defines no in-portal notification. [03_use-cases.md UC-11]

## Performance target
No dedicated per-UC target; UC-11 is not listed among the critical UCs in `05_system-design.md §3`. [05_system-design.md §3]

## Reliability
- Authorization must fail closed; a failed or unevaluable check denies category maintenance. [05_system-design.md §4]
- On failure, existing request categories and request classifications remain unchanged. [03_use-cases.md UC-11]
- The database is the system of record for `request_categories` and `requests`; the used-category check and the write must be atomic (one transaction / row lock) so a category cannot become used between the check and a delete or rename. `restrictOnDelete` guards the delete path, but the rename block is an application-level check that needs transactional protection. [05_system-design.md §1.1] [docs/conventions.md Status transitions] [derived from 03_use-cases.md UC-11 guarantees]
- Deactivation is the safe alternative to deletion for a used category; `restrictOnDelete` FKs prevent orphaning a category referenced by requests. [docs/conventions.md Data & schema] [04_data-model.md §2.1]

## API seam  [derived — fragile]
- `GET /api/admin/request-categories` — `auth:sanctum` + `manage-categories`; `200 OK`; returns a category list `{ data: [{ id, name, description, is_active }] }`. [derived from 03_use-cases.md UC-11 steps 1–3 + docs/conventions.md Authorization]
- `GET /api/admin/request-categories/{request_category}` — `auth:sanctum` + `manage-categories`; `200 OK`; returns one category `{ data: { id, name, description, is_active } }`. [derived from 03_use-cases.md UC-11 steps 2–3]
- `POST /api/admin/request-categories` — `auth:sanctum` + `manage-categories`; request fields: `name`, `description`, `is_active`; `201 Created`; returns `{ data: { id, name, description, is_active } }`. [derived from 03_use-cases.md UC-11 steps 2–8]
- `PATCH /api/admin/request-categories/{request_category}` — `auth:sanctum` + `manage-categories`; request fields: `name`, `description`, `is_active`; blocks a name change on a used category (see conservative-rename note) and allows marking a used category inactive; `200 OK`; returns updated `{ data: { id, name, description, is_active } }`. [derived from 03_use-cases.md UC-11 steps 2–8 + ext 6b/6c]
- `DELETE /api/admin/request-categories/{request_category}` — `auth:sanctum` + `manage-categories`; `204 No Content` on success, blocked (`409`) when the category is already used by existing requests. [derived from 03_use-cases.md UC-11 ext 6a]

## QA map  [derived — fragile]
- ext 2a → HTTP `404`; expected DB effect: no `request_categories` row is changed. [derived from 03_use-cases.md UC-11 ext 2a + docs/conventions.md API error responses]
- ext 5a → HTTP `403`; expected DB effect: no `request_categories` row changes. [derived from 03_use-cases.md UC-11 ext 5a + BR-012 + BR-016 + docs/conventions.md API error responses]
- ext 6a → HTTP `409`; expected DB effect: the `request_categories` row referenced by existing `requests.request_category_id` is not deleted. [derived from 03_use-cases.md UC-11 ext 6a + docs/conventions.md API error responses]
- ext 6b → HTTP `409`; expected DB effect: `request_categories.name` is unchanged for a category referenced by existing requests (conservative v1 stance — see conservative-rename note). [derived from 03_use-cases.md UC-11 ext 6b + BR-017 + docs/conventions.md API error responses]
- ext 6c → HTTP `200`; expected DB effect: `request_categories.is_active` becomes false while existing `requests.request_category_id` references remain unchanged. [derived from 03_use-cases.md UC-11 ext 6c + BR-002]
- ext 7a → HTTP `500`; expected DB effect: transaction rollback leaves existing `request_categories` rows unchanged. [derived from 03_use-cases.md UC-11 ext 7a + docs/conventions.md API error responses]

## Dependencies  [derived — fragile]
- UC-00 sign-in and Sanctum bearer-token auth must exist before administrator category maintenance is usable through protected routes. [derived from 03_use-cases.md UC-11 preconditions + docs/conventions.md Auth]
- `manage-categories` gate registered in `AppServiceProvider`. [derived from docs/conventions.md Authorization]
- `request_categories` migration/model. [derived from 04_data-model.md §2.1]
- `requests` migration/model, for the used-category delete/rename blockers. [derived from 03_use-cases.md UC-11 extensions]
- A bootstrap administrator account exists (seeded or manually created; self-service registration is out of v1). [derived from 03_use-cases.md UC-01 preconditions + UC-00 notes]

## Notes
- Used categories are historically protected in v1. Deactivation is the safe alternative to deletion when a category has already been used. [03_use-cases.md UC-11]
- Conservative-rename note [derived — fragile]: UC-11 ext 6b specifies blocking a *semantic* rename of a used category but does not define how "semantic" is detected. v1 therefore treats any `name` change on a category referenced by existing requests as a blocked semantic rename; only `description` and `is_active` may change on a used category. Revisit if a non-semantic-rename allowance is later specified. [derived from 03_use-cases.md UC-11 ext 6b]
