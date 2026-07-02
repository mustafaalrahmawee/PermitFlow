# Data Model — PermitFlow

## 1. Conceptual Schema (prose)

### 1.1 Entities
- **User account** — the persisted account through which a citizen, staff member, or administrator is known to PermitFlow and acts inside the portal.
  - Attributes: `display_name` (simple, single-valued, stored), `email` (simple, single-valued, stored), `role` (value set: {Citizen, Staff member, Administrator}), `account_state` (value set: {Active, Inactive}).
  - Key: surrogate `id` _(see §2.1)_

- **Request category** — the classification maintained by administrators and selected by a citizen when filing a request.
  - Attributes: `name` (simple, single-valued, stored), `description` (simple, single-valued, stored, nullable), `is_active` (simple, single-valued, stored).
  - Key: surrogate `id` _(see §2.1)_

- **Request** — the formal application or permit request submitted by one citizen and handled by the institution.
  - Attributes: `title` (simple, single-valued, stored), `request_details` (composite stored attribute represented as a whole request-information block), `status` (value set: {Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, Decided}), `submitted_at` (simple, single-valued, stored, nullable).
  - Key: surrogate `id` _(see §2.1)_

- **Document** — a file connected to a request, either as supporting information or as a decision document.
  - Attributes: `kind` (value set: {Supporting, Decision}), `file_reference` (simple, single-valued, stored), `original_filename` (simple, single-valued, stored), `mime_type` (simple, single-valued, stored), `size_bytes` (simple, single-valued, stored), `uploaded_at` (simple, single-valued, stored), `description` (simple, single-valued, stored, nullable).
  - Key: surrogate `id` _(see §2.1)_

- **Decision** — the recorded outcome reached by a staff member for a request.
  - Attributes: `outcome` (value set: {Approved, Rejected}), `decision_text` (simple, single-valued, stored, nullable), `decided_at` (simple, single-valued, stored).
  - Key: surrogate `id` _(see §2.1)_

- **Message** — request-scoped communication between the owning citizen and the responsible staff member.
  - Attributes: `message_kind` (value set: {General, Missing information request, Citizen reply}), `body` (simple, single-valued, stored), `sent_at` (simple, single-valued, stored).
  - Key: surrogate `id` _(see §2.1)_

- **Notification** — an in-portal alert for a user when something relevant happens on a request.
  - Attributes: `notification_type` (value set: {Request submitted, Assigned, Reassigned, Missing information requested, Information provided, Status changed, Decision recorded, Message received}), `body` (simple, single-valued, stored), `read_at` (simple, single-valued, stored, nullable).
  - Key: surrogate `id` _(see §2.1)_

- **Organization settings** — the institution-level configuration for the single organization served by v1.
  - Attributes: `singleton_key` (simple, single-valued, stored), `organization_name` (simple, single-valued, stored), `settings_payload` (composite stored attribute read and written as a whole block).
  - Key: surrogate `id` _(see §2.1)_

- **Role** — accounted as the `role` value set on **User account**, not as a separate entity, because v1 has a fixed set of roles and no process for maintaining role records independently.

- **Request status** — accounted as the `status` value set on **Request** and as status-related attributes on **Request history entry**, not as a separate entity, because v1 has a fixed status path and no process for maintaining status records independently.

### 1.2 Weak Entities
- **Request history entry** owned by **Request** — partial key `sequence_number`.
  - Attributes: `sequence_number` (simple, single-valued, stored), `event_type` (value set: {Status changed, Assignment changed, Decision recorded, Information requested, Information provided, Message recorded}), `from_status` (value set: {Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, Decided}, nullable), `to_status` (value set: {Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, Decided}, nullable), `summary` (simple, single-valued, stored), `reason` (simple, single-valued, stored, nullable), `event_occurred_at` (simple, single-valued, stored).
  - Key: surrogate `id` plus unique ownership identity through `request_id` and `sequence_number` _(see §2.1)_

### 1.3 Relationships
- **User account — Request**: 1:N, total on the Request side, partial on the User account side.
  - Relationship role: the User account side plays the `owning citizen` role; the Request side plays the `owned request` role.
  - Relationship attributes: _None._

- **Request category — Request**: 1:N, total on the Request side, partial on the Request category side.
  - Relationship attributes: _None._

- **User account — Request**: 1:N, partial on both sides.
  - Relationship role: the User account side plays the `responsible staff member` role; the Request side plays the `assigned request` role.
  - Relationship attributes: _None._ Assignment and reassignment facts are captured in **Request history entry**.

- **Request — Document**: 1:N, total on the Document side, partial on the Request side.
  - Relationship attributes: _None._

- **User account — Document**: 1:N, total on the Document side, partial on the User account side.
  - Relationship role: the User account side plays the `uploader` role.
  - Relationship attributes: _None._

- **Request — Decision**: 1:1, total on the Decision side, partial on the Request side.
  - Relationship attributes: _None._

- **User account — Decision**: 1:N, total on the Decision side, partial on the User account side.
  - Relationship role: the User account side plays the `deciding staff member` role.
  - Relationship attributes: _None._

- **Decision — Document**: 1:1 for decision documents, total on the Document side when `kind` is Decision, partial on the Decision side.
  - Relationship attributes: _None._ Supporting documents do not participate in this relationship.

- **Request — Request history entry**: 1:N, total on the Request history entry side, partial on the Request side.
  - Relationship attributes: _None._

- **User account — Request history entry**: 1:N, partial on the Request history entry side, partial on the User account side.
  - Relationship role: the User account side plays the `actor` role.
  - Relationship attributes: _None._ Some system-created history entries may have no actor.

- **Decision — Request history entry**: 1:N, partial on the Request history entry side, partial on the Decision side.
  - Relationship role: the Decision side plays the `related decision` role for history entries that record a decision event.
  - Relationship attributes: _None._ Only decision-related history entries participate in this relationship.

- **Message — Request history entry**: 1:N, partial on the Request history entry side, partial on the Message side.
  - Relationship role: the Message side plays the `related message` role for history entries that record a message, missing-information request, or citizen reply.
  - Relationship attributes: _None._ Only message-related history entries participate in this relationship.

- **Document — Request history entry**: 1:N, partial on the Request history entry side, partial on the Document side.
  - Relationship role: the Document side plays the `related document` role for history entries that record provided information or a decision document.
  - Relationship attributes: _None._ Only document-related history entries participate in this relationship.

- **User account — Request history entry**: 1:N, partial on the Request history entry side, partial on the User account side.
  - Relationship role: the User account side plays the `previous responsible staff member` role for reassignment history entries.
  - Relationship attributes: _None._ Only reassignment history entries participate in this relationship.

- **User account — Request history entry**: 1:N, partial on the Request history entry side, partial on the User account side.
  - Relationship role: the User account side plays the `new responsible staff member` role for assignment or reassignment history entries.
  - Relationship attributes: _None._ Only assignment-related history entries participate in this relationship.

- **Request — Message**: 1:N, total on the Message side, partial on the Request side.
  - Relationship attributes: _None._

- **User account — Message**: 1:N for the sender role, total on the Message side, partial on the User account side.
  - Relationship role: the User account side plays the `sender` role.
  - Relationship attributes: _None._

- **User account — Message**: 1:N for the recipient role, total on the Message side, partial on the User account side.
  - Relationship role: the User account side plays the `recipient` role.
  - Relationship attributes: _None._

- **Request — Notification**: 1:N, total on the Notification side, partial on the Request side.
  - Relationship attributes: _None._

- **User account — Notification**: 1:N, total on the Notification side, partial on the User account side.
  - Relationship role: the User account side plays the `recipient` role.
  - Relationship attributes: _None._

- **Request history entry — Notification**: 1:N, partial on the Notification side, partial on the Request history entry side.
  - Relationship attributes: _None._

- **User account — Organization settings**: 1:N, partial on both sides.
  - Relationship role: the User account side plays the `last updater` role.
  - Relationship attributes: _None._

## 2. Logical Schema (tables, described)

### 2.1 Tables

#### `user_accounts` — stores the account and fixed v1 role of each person using PermitFlow.
- `id` — bigint, auto-increment, PK.
- `display_name` — varchar, not null. _(**Derived from:** `01_miniworld.md §3`)_
- `email` — varchar, not null, unique. _(**Derived from:** structural)_
- `role` — varchar, not null, allowed values: {Citizen, Staff member, Administrator}. _(**Derived from:** `BR-001`)_
- `account_state` — varchar, not null, allowed values: {Active, Inactive}. _(**Derived from:** `03_use-cases.md UC-01`; access effect of `Inactive` governed by `BR-018`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** Role is a value set rather than a separate table because v1 has no independent role-maintenance process.

#### `request_categories` — stores the maintained category set used to classify requests.
- `id` — bigint, auto-increment, PK.
- `name` — varchar, not null, unique. _(**Derived from:** structural)_
- `description` — text, nullable. _(**Derived from:** `03_use-cases.md UC-11`)_
- `is_active` — boolean, not null. _(**Derived from:** `03_use-cases.md UC-11`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** Category maintenance authority is a semantic constraint from `BR-012`; this table carries the category facts, while authorization is enforced around changes.

#### `organization_settings` — stores the single organization's current configuration block.
- `id` — bigint, auto-increment, PK.
- `singleton_key` — varchar, not null, unique, fixed v1 value: `single_organization`. _(**Derived from:** `00_project-context.md §6`)_
- `organization_name` — varchar, not null. _(**Derived from:** `01_miniworld.md §3`)_
- `settings_payload` — JSON, not null; read and written as one settings block. _(**Derived from:** `03_use-cases.md UC-12`)_
- `updated_by_user_account_id` — bigint, nullable, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `BR-014`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** `settings_payload` is a deliberate 1NF trade-off because concrete settings are not expanded in the current use cases and are handled as a whole block.

#### `requests` — stores each citizen request and its current handling state.
- `id` — bigint, auto-increment, PK.
- `owner_user_account_id` — bigint, not null, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `BR-003`)_
- `request_category_id` — bigint, not null, FK → `request_categories.id`, on delete restrict. _(**Derived from:** `BR-002`)_
- `responsible_staff_user_account_id` — bigint, nullable, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `BR-009`)_
- `title` — varchar, not null. _(**Derived from:** `03_use-cases.md UC-02`)_
- `request_details` — JSON, not null; read and written as one request-information block. _(**Derived from:** `03_use-cases.md UC-02`)_
- `status` — varchar, not null, allowed values: {Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, Decided}. _(**Derived from:** `BR-004`)_
- `submitted_at` — timestamp, nullable. _(**Derived from:** `03_use-cases.md UC-02`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** `request_details` is a deliberate 1NF trade-off because the current use cases review and store the submitted information as a request block, with no per-field filtering, grouping, or joining requirement.

#### `documents` — stores request-connected supporting files and decision documents.
- `id` — bigint, auto-increment, PK.
- `request_id` — bigint, not null, FK → `requests.id`, on delete restrict. _(**Derived from:** `01_miniworld.md §3`)_
- `uploaded_by_user_account_id` — bigint, not null, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-04`)_
- `decision_id` — bigint, nullable, FK → `decisions.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-09`)_
- `kind` — varchar, not null, allowed values: {Supporting, Decision}. _(**Derived from:** `BR-006`)_
- `file_reference` — varchar, not null. _(**Derived from:** `01_miniworld.md §3`)_
- `original_filename` — varchar, not null. _(**Derived from:** structural)_
- `mime_type` — varchar, not null. _(**Derived from:** structural)_
- `size_bytes` — bigint, not null. _(**Derived from:** structural)_
- `uploaded_at` — timestamp, not null. _(**Derived from:** `03_use-cases.md UC-04`)_
- `description` — text, nullable. _(**Derived from:** `03_use-cases.md UC-09`)_
- **Unique constraint:** non-null `decision_id` appears at most once. _(**Derived from:** `03_use-cases.md UC-09`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** The file itself is represented by `file_reference`; the data model stores the request-scoped document record and not the file bytes.

#### `decisions` — stores the final recorded outcome for a request.
- `id` — bigint, auto-increment, PK.
- `request_id` — bigint, not null, unique, FK → `requests.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-09`)_
- `decided_by_user_account_id` — bigint, not null, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `BR-007`)_
- `outcome` — varchar, not null, allowed values: {Approved, Rejected}. _(**Derived from:** `BR-008`)_
- `decision_text` — text, nullable. _(**Derived from:** `03_use-cases.md UC-09`)_
- `decided_at` — timestamp, not null. _(**Derived from:** `03_use-cases.md UC-09`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** A request may have no decision until it reaches the decision step; once a decision exists, the unique request reference makes it the single recorded outcome for that request in v1.

#### `request_history_entries` — stores the lightweight, understandable history of important status, assignment, and decision events for a request.
- `id` — bigint, auto-increment, PK.
- `request_id` — bigint, not null, FK → `requests.id`, on delete restrict. _(**Derived from:** `BR-017`)_
- `sequence_number` — integer, not null. _(**Derived from:** structural)_
- `actor_user_account_id` — bigint, nullable, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-05`)_
- `decision_id` — bigint, nullable, FK → `decisions.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-09`)_
- `message_id` — bigint, nullable, FK → `messages.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-07`)_
- `document_id` — bigint, nullable, FK → `documents.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-04`)_
- `previous_staff_user_account_id` — bigint, nullable, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-05`)_
- `new_staff_user_account_id` — bigint, nullable, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `03_use-cases.md UC-05`)_
- `event_type` — varchar, not null, allowed values: {Status changed, Assignment changed, Decision recorded, Information requested, Information provided, Message recorded}. _(**Derived from:** `BR-017`)_
- `from_status` — varchar, nullable, allowed values: {Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, Decided}. _(**Derived from:** `BR-004`)_
- `to_status` — varchar, nullable, allowed values: {Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, Decided}. _(**Derived from:** `BR-004`)_
- `summary` — text, not null. _(**Derived from:** `BR-017`)_
- `reason` — text, nullable. _(**Derived from:** `03_use-cases.md UC-05`)_
- `event_occurred_at` — timestamp, not null. _(**Derived from:** `BR-017`)_
- **Unique constraint:** (`request_id`, `sequence_number`). _(**Derived from:** structural weak-entity mapping)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** `summary` is a deliberate audit-trail denormalization so progress remains understandable after the event even if related labels or wording later change.

#### `messages` — stores the request message thread between the owning citizen and responsible staff member.
- `id` — bigint, auto-increment, PK.
- `request_id` — bigint, not null, FK → `requests.id`, on delete restrict. _(**Derived from:** `BR-011`)_
- `sender_user_account_id` — bigint, not null, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `BR-011`)_
- `recipient_user_account_id` — bigint, not null, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `BR-011`)_
- `message_kind` — varchar, not null, allowed values: {General, Missing information request, Citizen reply}. _(**Derived from:** `03_use-cases.md UC-07` and `03_use-cases.md UC-10`)_
- `body` — text, not null. _(**Derived from:** `03_use-cases.md UC-10`)_
- `sent_at` — timestamp, not null. _(**Derived from:** `03_use-cases.md UC-10`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** The participant rule depends on the request's owner and responsible staff member and is therefore a semantic constraint around message creation, while the table keeps the historical sender and recipient.

#### `notifications` — stores in-portal request-related alerts for users.
- `id` — bigint, auto-increment, PK.
- `recipient_user_account_id` — bigint, not null, FK → `user_accounts.id`, on delete restrict. _(**Derived from:** `01_miniworld.md §3`)_
- `request_id` — bigint, not null, FK → `requests.id`, on delete restrict. _(**Derived from:** `01_miniworld.md §3`)_
- `request_history_entry_id` — bigint, nullable, FK → `request_history_entries.id`, on delete restrict. _(**Derived from:** `BR-017`)_
- `notification_type` — varchar, not null, allowed values: {Request submitted, Assigned, Reassigned, Missing information requested, Information provided, Status changed, Decision recorded, Message received}. _(**Derived from:** `01_miniworld.md §5`)_
- `body` — text, not null. _(**Derived from:** `01_miniworld.md §3`)_
- `read_at` — timestamp, nullable. _(**Derived from:** `01_miniworld.md §5`)_
- `created_at`, `updated_at` — timestamp, not null.
- **Indexes:** only those implied by the primary key and unique constraints. Secondary / performance indexes are deliberately deferred in v1 — not planned in this phase.
- **Notes:** There are no external-delivery columns in v1 because notifications are in-portal only.

#### Accounted business objects not persisted as standalone tables
- `Role` — persisted as `user_accounts.role`, a fixed value set. Reason: no independent role-maintenance process exists in v1.
- `Request status` — persisted as `requests.status` and status attributes on `request_history_entries`. Reason: the status set is fixed for v1.
- `Reporting summaries` — not persisted. Reason: staff and administrative summaries are derived from authorized request, status, assignment, and decision data at read time.

## 3. Normalization

### 3.1 1NF
All scalar columns hold atomic values. JSON columns are listed here with their access-test rationale:
- `organization_settings.settings_payload` — JSON; read and written as a whole organization-settings block in UC-12; not queried per element in the current use cases.
- `requests.request_details` — JSON; read, reviewed, and stored as a whole request-information block in UC-02, UC-04, and UC-06; not queried per element in the current use cases.

### 3.2 2NF (join tables only)
- _None._ No M:N relationship in the v1 conceptual schema produced a join table.

### 3.3 3NF
- `user_accounts`: `display_name`, `email`, `role`, and `account_state` depend directly on `user_accounts.id`; no non-key attribute determines another non-key attribute.
- `request_categories`: `name`, `description`, and `is_active` depend directly on `request_categories.id`; no non-key attribute determines another non-key attribute.
- `organization_settings`: `singleton_key`, `organization_name`, `settings_payload`, and `updated_by_user_account_id` depend directly on `organization_settings.id`; no non-key attribute determines another non-key attribute.
- `requests`: ownership, category, responsible staff, current status, submitted timestamp, title, and request-information block depend directly on `requests.id`; category details and user details remain in their own tables.
- `documents`: file metadata, document kind, request reference, uploader reference, and optional decision reference depend directly on `documents.id`; request, user, and decision details remain in their own tables.
- `decisions`: outcome, decision text, deciding staff reference, request reference, and decided timestamp depend directly on `decisions.id`; staff and request details remain in their own tables.
- `request_history_entries`: event data, related object references, status values, summary, reason, and timestamp depend directly on `request_history_entries.id`; related object details remain in their own tables.
- `messages`: request reference, sender, recipient, kind, body, and sent timestamp depend directly on `messages.id`; user and request details remain in their own tables.
- `notifications`: recipient, request reference, optional history reference, type, body, and read timestamp depend directly on `notifications.id`; request and user details remain in their own tables.
- Listed deliberate exceptions:
  - `request_history_entries.summary` — denormalized for an understandable audit trail after status, assignment, or decision events.
  - `requests.request_details` — denormalized as a whole request-information block because category-specific request fields are not queried individually in the current use cases.
  - `organization_settings.settings_payload` — denormalized as a whole settings block because concrete settings are not expanded or queried individually in the current use cases.

## 4. Open Questions
- _None._
