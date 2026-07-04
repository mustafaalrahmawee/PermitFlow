# PermitFlow — Project Conventions

Always-true conventions for later per-domain and per-use-case sessions. Each
traces to a domain spec or is a named implementation-only decision. Generated
code that would contradict a spec is a conflict to report, not a free choice.

## Stack
- Laravel 13 (PHP 8.4), backend lives in `api/`.
- Frontend SPA in `app/`: Nuxt 4, Vue 3, TypeScript. UI via shadcn-vue +
  Tailwind CSS 3; state via Pinia; composables via VueUse; forms validated with
  Vee-Validate + Zod; icons from Lucide.
- Database: PostgreSQL (system of record) `[05_system-design.md §1.1]`.
- Single-node, synchronous v1: no queue, scheduler, or async worker
  `[05_system-design.md §1.4; 01_miniworld.md §5]`.

## Data & schema
- **Bigint auto-increment keys** everywhere (plain `id()`); ULIDs/UUIDs are a v1
  non-goal `[04_data-model.md §2.1]`.
- **String-backed enums (one enum shape).** Each enum (`app/Enums`) is a
  string-backed PHP enum whose backing value is a stable snake_case slug
  decoupled from wording. It carries `label()` (spec label verbatim),
  `values()` (backing slugs, for validation), and `options()` (slug→label map,
  for selects). Enum columns are `varchar` with application casts — native DB
  enum types are costly to alter as a value set evolves `[§4]`.
- **Restrict-on-delete with deactivation.** Every FK uses `restrictOnDelete()`
  so referenced records cannot be orphaned. Accounts tied to request history are
  retired via the `inactive` account state, never deleted
  `[04_data-model.md §2.1; 03_use-cases.md UC-01; BR-017]`.
- **Whole-block JSON.** `requests.request_details` and
  `organization_settings.settings_payload` are read/written as one block and
  cast to array `[04_data-model.md §3.1]`.
- Only PK/unique indexes in v1; secondary/performance indexes are deferred
  `[04_data-model.md §2.1]`.

## History & traceability
- **Explicit history writes.** History entries are written explicitly in
  use-case logic. A history `summary` is a frozen audit snapshot whose value
  depends on *not* being regenerated — no model-event auto-logger, no
  activity-log package `[04_data-model.md §2.1 notes; BR-017]`.
- `request_history_entries` is a weak entity owned by `requests`; the pair
  (`request_id`, `sequence_number`) is unique.

## Status transitions (the one guard structure)
- Status changes go through the guard: `App\Exceptions\IllegalStatusTransitionException`
  (carries from/to status) + the `App\Concerns\TransitionsRequestStatus` trait on
  the `Request` model. The trait holds the explicit allowed-transition map, a
  predicate (`canTransitionTo`), and `transitionTo`, which validates against the
  map and sets the status **in memory only**, raising the exception on an illegal
  target.
- Persistence is the caller's responsibility: a use case saves the status change
  and its history entry together in **one transaction** so the durable-write path
  stays atomic `[§4; 05_system-design.md §4]`.
- Allowed v1 transitions `[03_use-cases.md UC-08]`: draft→submitted;
  submitted→in_review; in_review→{waiting_for_citizen, ready_for_decision};
  waiting_for_citizen→in_review; ready_for_decision→decided; decided is terminal.

## Authorization (native, fail closed)
- Native Laravel policies + gates, registered in `AppServiceProvider`. No
  permissions package — role is a fixed value set on `user_accounts` with no
  role-maintenance process `[BR-016; §4]`.
- **Fail closed:** a check that cannot be evaluated denies. Inactive accounts are
  denied. Role and ownership are evaluated live, never from a cached copy
  `[05_system-design.md §4]`.
- **Request-scoped reach** (`InteractsWithRequestScope`): a record is reachable
  only by its owning citizen, its responsible staff member, or an administrator
  `[BR-016]`. Policies: Request, Document, Message, Decision,
  RequestHistoryEntry, Notification.
- Role-restricted abilities: `RequestPolicy@submit` (owning citizen `[BR-003]`),
  `@provideInformation` (owner, Draft/Waiting for Citizen `[BR-005]`),
  `@review` (responsible staff `[BR-009]`), `@decide` (responsible staff
  `[BR-007/008/009]`), `MessagePolicy@create` (request's citizen or responsible
  staff `[BR-011]`).
- Role gates (Table BR-016): `assign-requests`, `manage-categories`,
  `manage-accounts`, `manage-settings` → administrator `[BR-010/012/013/014]`;
  `view-reporting` → staff member or administrator `[BR-015]`.

## Auth
- **Laravel Sanctum, API bearer-token auth only.** The authenticatable model is
  `App\Models\UserAccount` (table `user_accounts`), set via `AUTH_MODEL`, not the
  framework-default `users` `[§4]`. Login issues a Sanctum personal access token
  that clients send as `Authorization: Bearer <token>`; there is no stateful SPA
  session and the backend never relies on a browser session. Protected routes use
  `auth:sanctum`. Login uses `email` + the `password` column.

## API success responses (one envelope)
Every successful JSON response uses a single envelope so clients read one shape
regardless of controller (implementation-only decision):
- **`data`** — the payload: a resource object, a list of resources, or a
  structured object for a composite result (e.g. login returns the token and the
  account). `null` when there is no payload (e.g. logout).
- **`message`** — a short human-readable summary of the outcome.

The envelope keys are fixed; a resource is never returned under an ad-hoc key
(no `user`, `user_account`, `user_accounts`). Success always carries `data`;
errors never do (see below), so the two shapes never collide and a caller can
tell them apart without inspecting the status code.

**Paginated lists add a sibling `meta`, never a nested paginator.** A list seam
paginates server-side (`paginate()`, fixed page size, `page` read from the
`?page=` query parameter) and returns the rows as the `data` array plus a
`meta` cursor: `current_page`, `last_page`, `per_page`, `total`. The raw Laravel
paginator is never placed under `data` (which would nest `data.data` and leak
the framework's `links` shape); `data` stays the flat array of resources and the
cursor rides alongside in `meta`. Page size is a fixed constant, not a
client-tunable parameter (secondary/performance tuning is a v1 non-goal).

## API error responses (fail closed)
Concrete HTTP status codes for denied or failed API actions, so per-use-case QA
maps translate acceptance items into requests and DB checks without inventing
codes. Standard REST mapping; errors deny rather than leak. Errors use Laravel's
native error envelope — `{ message }`, plus `{ errors }` (field → messages) on a
422 — and never include a `data` key.
- **401 Unauthorized** — authentication denial: credentials do not match a known
  account, or an inactive account attempts sign-in or a protected route. The
  login response does not reveal which part failed, nor that an account exists
  but is inactive `[03_use-cases.md UC-00; BR-018]`.
- **403 Forbidden** — authorization denial: an authenticated actor lacks the
  required policy or role gate (e.g. `manage-accounts`). Account management is a
  role gate, not request-scoped, so denial is 403 `[BR-013; BR-016]`.
- **404 Not Found** — a targeted record does not exist; and, under request-scoped
  visibility, a record outside the actor's scope is reported as not found rather
  than forbidden, so existence is not revealed `[BR-016]`.
- **422 Unprocessable Entity** — validation failure (Laravel default): e.g. no
  role or more than one role selected `[BR-001]`.
- **409 Conflict** — lifecycle conflict: an operation blocked by current state,
  e.g. deactivating an account tied to an undecided request, or a v1 role change
  on an account connected to request history `[03_use-cases.md UC-01]`.
- **500 Internal Server Error** — an unexpected persistence failure; the write
  transaction rolls back and no partial change is durable
  `[§ Status transitions; 05_system-design.md §4]`.

## Storage
- File attachments use the **S3 disk against local MinIO**
  (`use_path_style_endpoint`). `documents.file_reference` holds the **object key**,
  not the bytes `[04_data-model.md §2.1; 05_system-design.md §4]`. Validate the
  reference belongs to the in-scope request before serving `[BR-016]`.

## Dependencies (minimal surface)
- Only `laravel/sanctum` (API bearer-token auth) and `league/flysystem-aws-s3-v3` (S3/MinIO
  disk) added beyond the skeleton. No permissions, activity-log, or state-machine
  package `[§5.1]`.

## Frontend routing (guards & layout)
- **Auth is a global allowlist, not per-page.** One global route middleware
  protects every page and redirects to sign-in when there is no token; public
  pages are named in its allowlist. The default layout auto-applies. An
  authenticated page therefore declares neither layout nor middleware — the
  defaults cover it (implementation-only decision).
- **A public/guest page is registered in two coupled places:** its name is added
  to the global middleware's allowlist, and the page is given the guest layout
  plus the guest middleware (which bounces an already-authenticated session away
  from the form). Omitting either is the routing bug to avoid.

## Frontend data access (API client, stores, types)
- **One global API client.** A single Nuxt plugin (`app/app/plugins/api.ts`)
  configures `$fetch` to default the API base URL from runtime config and attach
  the Sanctum bearer token from the auth store. Callers pass only a path; no
  per-call base URL or `Authorization` header, and no fetch-wrapping composable
  (implementation-only decision).
- **Fetching lives in Pinia stores, not in pages.** Each domain has a setup-style
  Pinia store that owns its API calls and the resulting state. Pages are
  presentational: they call store actions and read store state (via
  `storeToRefs`), and never call `$fetch` directly.
- **Mutations patch local state in place.** After a create or update, the store
  updates its own collection — append the created record, replace the changed one
  by id — rather than refetching the whole list.
- **Types are declared first in `app/app/types`.** Domain interfaces and select
  option maps (enum slug→label) live there and are imported where needed; stores
  and pages depend on them instead of redefining shapes inline.

## Implementation-only decisions (beyond the conceptual data model)
- `user_accounts.password` (varchar) and `user_accounts.remember_token` (varchar,
  nullable) exist solely for local login; they are not in the conceptual data
  model `[§4, §5.10]`.
- `personal_access_tokens` table is the standard Sanctum schema, added for the
  auth package; not a domain table.
- The framework-default `users`, `password_reset_tokens`, `sessions`, `cache`,
  and `jobs` tables remain from the skeleton for framework integrity (session and
  cache drivers); they are not part of the domain model and are unused for auth.
