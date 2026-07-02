---
name: foundation
description: >-
  Scaffold the PermitFlow Laravel backend the domain specs describe: eight
  string-backed enums, nine migrations and Eloquent models with restrict-on-delete
  foreign keys, Sanctum API bearer-token auth on the user_accounts model, request-scoped
  policies plus role gates that fail closed, an explicit request status guard,
  S3/MinIO storage, dev factories and seeders, a docker-compose stack, and a
  project conventions file. Every artifact traces to docs/domain/00..05 or a named
  implementation-only convention. Run this once at the start of the project,
  before any per-domain or per-use-case work. Invoke explicitly with /foundation.
disable-model-invocation: true
allowed-tools: Read, Edit, Write, Bash
---

# Skill — Foundation (run once)

## 1. Introduction

This skill scaffolds the runnable PermitFlow backend that the project's domain
specs describe. When you run it, it produces the deterministic base every later
per-domain and per-use-case session depends on: the composer dependency set, the
enums, the migrations and models, auth and authorization, the status guard, dev
fixtures, storage, the local stack, and a project conventions file.

It is **skeleton-complete** when every artifact in §5 exists in the project under
its real path. It **succeeds** only when, on top of skeleton-completion, the §7
verification is green — a fresh migrate + seed, the tinker data-layer checks, and
the curl boot-and-auth smoke test all pass. Skeleton-completion plus green
verification is the structural stop; there is no closing commentary after it.

Every artifact is derived from a `docs/domain/*` line or is a named
implementation-only convention recorded in §5.10. When generated code would
contradict a spec, the run **stops and reports the conflict** rather than
guessing — silent divergence from `docs/domain/` would break the pipeline's
single source of truth.

## 2. Binding context

The authoritative sources are the project's domain specs, consumed as **indirect**
context. Cite them in short form so each generated artifact stays auditable:

- `[04_data-model.md §2.1]` — every table, column, type, foreign-key rule, and
  unique constraint (the literal source for migrations and models).
- `[04_data-model.md §1.1 / §1.2 / §1.3]` — the entities and their attribute value
  sets, the weak entity, and the relations.
- `[02_business-rules.md BR-001..BR-017]` — the enum value sets and the
  authorization rules; the roles / permissions matrix (Table BR-016).
- `[03_use-cases.md UC-00..UC-14]` — the legal status transitions confirmed for
  the guard (UC-08 graph) and the column-justifying use-case lines.
- `[05_system-design.md]` — fail-closed authorization (§4), the single-node
  synchronous v1 stance (§1.2–§1.4), S3 storage, and the reliability checks.
- `[00_project-context.md]`, `[01_miniworld.md]` — project identity (this skill's
  frontmatter) and the v1 scope cuts that justify the rejected non-goals (§4).

## 3. Inputs

**Static** (fixed in this skill): the §4 application rules — including the prose
descriptions of the enum shape and the status-guard structure — and the
dependency-ordered §5 inventory that those rules govern.

**Dynamic** (read at run time): the `docs/domain/*` specs named in §2. There is no
direct user input; the upstream specs already captured the owner's intent.

## 4. Application rules (project conventions)

These are the Laravel-stack conventions every artifact obeys. Each states what to
do and why, so the reason survives into the generated code. Patterns are
described in prose; no code-example files are bundled.

- **Bigint auto-increment keys.** Primary keys are `bigint` auto-increment
  everywhere (plain `id()`), following the data model `[04_data-model.md §2.1]`.
  ULIDs / UUIDs are a deliberate v1 non-goal.
- **String-backed enums (the one enum shape).** Each enum is a string-backed PHP
  enum whose backing value is a stable snake_case slug decoupled from wording. It
  carries a `label()` method returning the human label verbatim from the specs,
  plus two static helpers — one returning the list of backing values (for
  validation rules) and one returning a slug-to-label map (for select controls).
  The stored database value is the slug; columns stay `varchar` with application
  casts, because native database enum types are costly to alter as a value set
  evolves `[06-foundation-architect.md §5]`. The concrete location, case-naming,
  and helper names are fixed in §5.2. Every enum in §5.2 follows this shape.
- **Filtered relations are query-constrained.** A relation that names a subset of a
  related table applies its filter inside the relation definition as a query
  constraint, so only the matching rows load from the database rather than being
  narrowed in memory by the caller. When the filtered column is enum-backed, the
  constraint compares against the enum's stored backing value (the slug), matching
  the `varchar` the column holds `[06-foundation-architect.md §5]`. §5.4 marks each
  filtered relation.
- **Native authorization, fail closed.** Authorization is native Laravel policies
  and gates `[02_business-rules.md BR-016, BR-010..BR-015]`. Role is a fixed value
  set on the user table with no role-maintenance process, so a permissions package
  would add tables the model does not define and is left out. A check that cannot
  be evaluated — current user, role, owner, responsible staff, recipient, or
  related request unresolved — **denies** rather than permits, evaluated live and
  never from a cached copy `[05_system-design.md §4]`. There is no global
  administrator allow-all (§5.5).
- **Restrict-on-delete with deactivation.** Every foreign key uses
  `restrictOnDelete()` so referenced records cannot be orphaned
  `[04_data-model.md §2.1]`. Accounts tied to request history are retired via the
  `inactive` account state rather than removed, preserving the history traceability
  requires `[03_use-cases.md UC-01; 02_business-rules.md BR-017]`.
- **Explicit history writes.** History is written explicitly in use-case logic. A
  history `summary` is a frozen audit snapshot whose value depends on **not** being
  regenerated `[04_data-model.md §2.1 notes; 02_business-rules.md BR-017]`, so a
  model-event auto-logger and an activity-log package are both left out.
- **Status transitions via a guard (the one guard structure).** Status changes go
  through a guard with two parts: an exception type for an illegal transition,
  carrying the from-status and to-status; and a trait on the request model holding
  an explicit allowed-transition map (each status mapped to its legal targets), a
  predicate that tests whether a target is legal, and a transition method that
  validates against the map and sets the status **in memory only**, raising the
  exception on an illegal target. Persistence is the caller's responsibility, so
  the status change and its history entry are saved together in one transaction and
  the durable-write path stays atomic `[06-foundation-architect.md §5;
05_system-design.md §4]`. The map is filled in §5.6 from the confirmed transition
  set; a state-machine package is left out because this guard covers the v1 set.
- **Sanctum API bearer-token auth.** Auth is Laravel Sanctum using API personal
  access tokens only (bearer tokens in the `Authorization: Bearer <token>` header),
  not a stateful SPA session. The authenticatable model is the project's
  `user_accounts` table, not the framework-default `users`
  `[06-foundation-architect.md §5]`.
- **Implementation-only auth columns.** `user_accounts` gains a `password` column
  and a nullable `remember_token` as implementation-only columns beyond the
  conceptual data model, because local login needs a credential the conceptual
  model does not carry. Recorded as such in §5.10.
- **S3 disk against MinIO.** File attachments use the S3 disk against local MinIO
  (`use_path_style_endpoint`). The `documents.file_reference` column holds the
  object key, not the bytes `[04_data-model.md §2.1; 05_system-design.md §4]`.

## 5. Artifacts to produce

Generate in dependency order — enums → migrations → models → auth →
authorization → status guard → factories/seeders → storage → docker-compose →
project conventions file. Each layer depends on the ones before it. Emit each
like artifact from its one §4 description so the generated code is uniform.

### 5.1 Composer packages

Only the packages a convention requires: `laravel/sanctum` (API bearer-token auth, §4) and the
Flysystem S3 adapter (`league/flysystem-aws-s3-v3`) for the S3/MinIO disk (§4). No
permissions, activity-log, state-machine, or v1 test-suite package
`[06-foundation-architect.md §5 "Minimal dependency surface"]`.

### 5.2 Enums

Emit one PHP enum under `app/Enums` for each value set below. Every enum follows
the §4 enum shape: string-backed PHP enum, stable `snake_case` stored slug,
`label()` returning the spec label verbatim, `values()` returning all stored
slugs, and `options()` returning a slug-to-label map for select controls. Use
PascalCase enum case names derived from the spec labels. Do not create duplicate
enums for the same value set.

`RequestStatus` is reused for `requests.status`,
`request_history_entries.from_status`, and `request_history_entries.to_status` —
do not generate a second status enum.

| Enum               | Stored slugs → spec labels                                                                                                                                                                                                                                                                                                   | Source                                                                                                                                       |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `Role`             | citizen → "Citizen"; staff_member → "Staff member"; administrator → "Administrator"                                                                                                                                                                                                                                          | `[02_business-rules.md BR-001]` / `[04_data-model.md §1.1 User account; §2.1 user_accounts]`                                                 |
| `AccountState`     | active → "Active"; inactive → "Inactive"                                                                                                                                                                                                                                                                                     | `[03_use-cases.md UC-01]` / `[04_data-model.md §1.1 User account; §2.1 user_accounts]`                                                       |
| `RequestStatus`    | draft → "Draft"; submitted → "Submitted"; in_review → "In Review"; waiting_for_citizen → "Waiting for Citizen"; ready_for_decision → "Ready for Decision"; decided → "Decided"                                                                                                                                               | `[02_business-rules.md BR-004]` / `[04_data-model.md §1.1 Request; §1.2 Request history entry; §2.1 requests; §2.1 request_history_entries]` |
| `DocumentKind`     | supporting → "Supporting"; decision → "Decision"                                                                                                                                                                                                                                                                             | `[02_business-rules.md BR-006]` / `[04_data-model.md §1.1 Document; §2.1 documents]`                                                         |
| `DecisionOutcome`  | approved → "Approved"; rejected → "Rejected"                                                                                                                                                                                                                                                                                 | `[02_business-rules.md BR-008]` / `[04_data-model.md §1.1 Decision; §2.1 decisions]`                                                         |
| `MessageKind`      | general → "General"; missing_information_request → "Missing information request"; citizen_reply → "Citizen reply"                                                                                                                                                                                                            | `[03_use-cases.md UC-07, UC-10]` / `[04_data-model.md §1.1 Message; §2.1 messages]`                                                          |
| `NotificationType` | request_submitted → "Request submitted"; assigned → "Assigned"; reassigned → "Reassigned"; missing_information_requested → "Missing information requested"; information_provided → "Information provided"; status_changed → "Status changed"; decision_recorded → "Decision recorded"; message_received → "Message received" | `[01_miniworld.md §5]` / `[04_data-model.md §1.1 Notification; §2.1 notifications]`                                                          |
| `HistoryEventType` | status_changed → "Status changed"; assignment_changed → "Assignment changed"; decision_recorded → "Decision recorded"; information_requested → "Information requested"; information_provided → "Information provided"; message_recorded → "Message recorded"                                                                 | `[02_business-rules.md BR-017]` / `[04_data-model.md §1.2 Request history entry; §2.1 request_history_entries]`                              |

### 5.3 Migrations

Emit one Laravel migration per persisted table, in FK-safe order. Columns, types,
nullability, foreign-key rules, and unique constraints are derived from
`04_data-model.md §2.1`. All primary keys are bigint auto-increment `id()`. All
columns are `not null` unless explicitly marked nullable below. Every foreign key
uses `restrictOnDelete()` per §4. Enum-backed columns are `varchar`, validated and
cast in the application layer, not stored as native database enum types.

Do not add secondary / performance indexes in v1. Create only the indexes required
by primary keys, unique constraints, and foreign-key implementation — secondary /
performance indexes are deliberately deferred `[04_data-model.md §2.1]`.

1. **`user_accounts`** — `display_name` varchar; `email` varchar unique; `role`
   varchar; `account_state` varchar; **implementation-only** `password` varchar and
   nullable `remember_token` (§4, §5.10); timestamps. Source:
   `[04_data-model.md §2.1 user_accounts]` plus implementation-only auth convention.
2. **`request_categories`** — `name` varchar unique; nullable `description` text;
   `is_active` boolean; timestamps. Source:
   `[04_data-model.md §2.1 request_categories]`.
3. **`organization_settings`** — `singleton_key` varchar unique, fixed v1 value
   `single_organization`; `organization_name` varchar; `settings_payload` JSON;
   nullable `updated_by_user_account_id` FK → `user_accounts.id`, restrict;
   timestamps. Source: `[04_data-model.md §2.1 organization_settings]`.
4. **`requests`** — `owner_user_account_id` FK → `user_accounts.id`, restrict;
   `request_category_id` FK → `request_categories.id`, restrict; nullable
   `responsible_staff_user_account_id` FK → `user_accounts.id`, restrict; `title`
   varchar; `request_details` JSON; `status` varchar; nullable `submitted_at`
   timestamp; timestamps. Source: `[04_data-model.md §2.1 requests]`.
5. **`decisions`** — `request_id` FK → `requests.id`, restrict, unique;
   `decided_by_user_account_id` FK → `user_accounts.id`, restrict; `outcome`
   varchar; nullable `decision_text` text; `decided_at` timestamp; timestamps.
   Source: `[04_data-model.md §2.1 decisions]`. Generate before `documents` because
   `documents.decision_id` references it.
6. **`documents`** — `request_id` FK → `requests.id`, restrict;
   `uploaded_by_user_account_id` FK → `user_accounts.id`, restrict; nullable
   `decision_id` FK → `decisions.id`, restrict; `kind` varchar; `file_reference`
   varchar; `original_filename` varchar; `mime_type` varchar; `size_bytes` bigint;
   `uploaded_at` timestamp; nullable `description` text; timestamps. Source:
   `[04_data-model.md §2.1 documents]`. Enforce that each non-null `decision_id`
   appears at most once, implemented the database-appropriate way for the
   configured database (a nullable unique column or a partial unique index): the
   invariant is one decision document per decision, while supporting documents
   carry no `decision_id`.
7. **`messages`** — `request_id` FK → `requests.id`, restrict;
   `sender_user_account_id` FK → `user_accounts.id`, restrict;
   `recipient_user_account_id` FK → `user_accounts.id`, restrict; `message_kind`
   varchar; `body` text; `sent_at` timestamp; timestamps. Source:
   `[04_data-model.md §2.1 messages]`. Generate before `request_history_entries`
   because `request_history_entries.message_id` references it.
8. **`request_history_entries`** — `request_id` FK → `requests.id`, restrict;
   `sequence_number` integer; nullable `actor_user_account_id`,
   `previous_staff_user_account_id`, `new_staff_user_account_id` FK →
   `user_accounts.id`, restrict; nullable `decision_id` FK → `decisions.id`,
   restrict; nullable `message_id` FK → `messages.id`, restrict; nullable
   `document_id` FK → `documents.id`, restrict; `event_type` varchar; nullable
   `from_status` varchar; nullable `to_status` varchar; `summary` text; nullable
   `reason` text; `event_occurred_at` timestamp; unique (`request_id`,
   `sequence_number`); timestamps. Source:
   `[04_data-model.md §2.1 request_history_entries; §1.2]`. Generated after
   `decisions`, `messages`, and `documents` because it references all three.
9. **`notifications`** — `recipient_user_account_id` FK → `user_accounts.id`,
   restrict; `request_id` FK → `requests.id`, restrict; nullable
   `request_history_entry_id` FK → `request_history_entries.id`, restrict;
   `notification_type` varchar; `body` text; nullable `read_at` timestamp;
   timestamps. Source: `[04_data-model.md §2.1 notifications]`.

### 5.4 Models

Emit one Eloquent model under `app/Models` for each persisted table. Each model
uses the table and foreign-key names from `04_data-model.md §2.1`; no model
introduces a table, role, relation, or derived object absent from
`04_data-model.md §1.3 / §2.1`.

`UserAccount` is the authenticatable model used by §5.5: it extends Laravel's
authenticatable base model rather than a plain Eloquent model, hides `password`
and `remember_token` from serialization, casts every enum-backed column to its
§5.2 enum, casts `requests.request_details` and
`organization_settings.settings_payload` to array/JSON, and casts all `*_at`
columns to datetime `[04_data-model.md §3.1]`. Use one mass-assignment convention
consistently across all models and record it in §5.10.

Relations, read off `04_data-model.md §1.3`:

- **UserAccount** — authenticatable account model. Has many requests as
  `ownedRequests` (`owner_user_account_id`); has many requests as
  `assignedRequests` (`responsible_staff_user_account_id`); has many documents as
  `uploadedDocuments` (`uploaded_by_user_account_id`); has many decisions as
  `decisionsMade` (`decided_by_user_account_id`); has many messages as
  `sentMessages` (`sender_user_account_id`); has many messages as
  `receivedMessages` (`recipient_user_account_id`); has many notifications
  (`recipient_user_account_id`); has many request history entries as
  `actedHistoryEntries` (`actor_user_account_id`),
  `previousStaffHistoryEntries` (`previous_staff_user_account_id`), and
  `newStaffHistoryEntries` (`new_staff_user_account_id`); has many organization
  settings rows as `updatedOrganizationSettings` (`updated_by_user_account_id`).
- **RequestCategory** — has many requests (`request_category_id`).
- **Request** — belongs to owner (`owner_user_account_id`), category
  (`request_category_id`), and nullable responsible staff member
  (`responsible_staff_user_account_id`); has many documents, messages, history
  entries, and notifications (each on `request_id`); has one decision
  (`request_id`). Uses the status-guard trait from §5.6.
- **Decision** — belongs to request (`request_id`) and deciding staff member
  (`decided_by_user_account_id`); has one `decisionDocument` (`decision_id`), a
  query-constrained relation filtered to the `DocumentKind::Decision` backing value
  (§4); has many history entries (`decision_id`).
- **Document** — belongs to request (`request_id`), uploader
  (`uploaded_by_user_account_id`), and nullable decision (`decision_id`); has many
  history entries (`document_id`).
- **Message** — belongs to request (`request_id`), sender (`sender_user_account_id`),
  and recipient (`recipient_user_account_id`); has many history entries
  (`message_id`).
- **RequestHistoryEntry** — belongs to request (`request_id`); belongs to nullable
  actor (`actor_user_account_id`), previous staff member
  (`previous_staff_user_account_id`), new staff member (`new_staff_user_account_id`),
  decision (`decision_id`), message (`message_id`), and document (`document_id`); has
  many notifications (`request_history_entry_id`).
- **Notification** — belongs to recipient (`recipient_user_account_id`), request
  (`request_id`), and nullable history entry (`request_history_entry_id`).
- **OrganizationSettings** — belongs to nullable `updatedBy` user account
  (`updated_by_user_account_id`).

When importing the PermitFlow `Request` model in files that also use
`Illuminate\Http\Request`, alias one of the two names so the generated code stays
unambiguous.

### 5.5 Auth + authorization

**Auth mode.** Wire Laravel Sanctum as **API bearer-token authentication only**, using Sanctum personal access tokens sent by clients in the `Authorization: Bearer <token>` header.

**Authenticatable model.** The authenticatable model is `App\Models\UserAccount` for the `user_accounts` table, not Laravel's default `App\Models\User` model and not the framework-default `users` table. All protected API routes authenticate through Sanctum token authentication against `UserAccount`.

**Login.** Login uses `email` and the implementation-only `password` column from §5.3 / §5.10. A successful login for an active account creates a Sanctum personal access token and returns the plain-text token once to the API client. The client must store and send that token as `Authorization: Bearer <token>` on later requests. The backend must not rely on a browser session to remember the user.

**Inactive accounts.** Inactive accounts do not authenticate successfully and do not pass protected ability checks. If credentials match an inactive account, the login attempt fails and no token is created. If an account becomes inactive after tokens already exist, protected routes and policy checks must still deny access for that account, and token revocation may be performed as part of account deactivation. This implements `[03_use-cases.md UC-00; 02_business-rules.md BR-018]`.

**Logout and token lifecycle.** Logout revokes the current access token used for the request. Optional administrative account deactivation may revoke all tokens belonging to that user account.

**Authorization base.** Use native Laravel policies and gates only; do not use a permissions package. All checks fail closed and evaluate live (§4). Do not implement a global administrator allow-all: no `Gate::before` administrator bypass. Administrators may view request-scoped records only through explicit authorized oversight and may perform the administrator operations listed below, but the administrator role alone does not permit actions reserved for the owning citizen or responsible staff member. Decisions stay with responsible staff `[02_business-rules.md BR-007]`, and messaging stays with the request participants `[02_business-rules.md BR-011]`.

**Request-scoped viewing policies `[02_business-rules.md BR-016]`.** These records are reachable only by the owning citizen, the responsible staff member, or authorized administrator oversight:

- `RequestPolicy@view`
- `DocumentPolicy@view` through the document's request
- `MessagePolicy@view` through the message's request
- `DecisionPolicy@view` through the decision's request
- `RequestHistoryEntryPolicy@view` through the history entry's request

**Notification policy.** Notifications are user-targeted alerts, not request-scoped records. A notification is viewable by its `recipient_user_account_id` only. Administrator oversight is added only as an explicit `NotificationPolicy@viewForOversight` ability. The default notification view must not leak one participant's notification to another participant on the same request `[derived from 04_data-model.md §2.1 notifications.recipient_user_account_id; bounded by BR-016]`.

**Operation-specific policy abilities — the v1 authorization surface.** Per-use-case sessions wire the controllers and transactions that invoke these abilities:

- `RequestPolicy@submit` — owning citizen only `[BR-003; UC-02]`.
- `RequestPolicy@provideInformation` — owning citizen only, request `Draft` or `Waiting for Citizen` `[BR-005; UC-04]`.
- `RequestPolicy@review` — responsible staff member only `[BR-009; UC-06]`.
- `RequestPolicy@requestMissingInformation` — responsible staff member only, request `In Review` `[BR-009; UC-07]`.
- `RequestPolicy@markReadyForDecision` — responsible staff member only, request `In Review` `[BR-009; UC-06/UC-08]`.
- `RequestPolicy@decide` — responsible staff member only, request `Ready for Decision` `[BR-007, BR-008, BR-009; UC-09]`.
- `MessagePolicy@create` — only the request's owning citizen or responsible staff member, and only when the sender/recipient pair matches the request's citizen and responsible staff member `[BR-011; UC-10]`.
- `DocumentPolicy@createSupporting` — owning citizen, before submission (`Draft`) or when the request is `Waiting for Citizen` `[BR-005, BR-006; UC-02, UC-04]`.
- `DocumentPolicy@createDecisionDocument` — responsible staff member only, as part of recording a decision `[BR-006, BR-007; UC-09]`.

**Role gates for operations not tied to one existing request record.**

- `assign-requests` → administrator `[BR-010]`.
- `manage-categories` → administrator `[BR-012]`.
- `manage-accounts` → administrator `[BR-013]`.
- `manage-settings` → administrator `[BR-014]`.
- `view-reporting` → staff member or administrator `[BR-015]`.

Reporting still applies request-scope limits at query time: staff reporting summarizes only records within the staff member's authorized scope; administrator reporting uses the administrator oversight scope `[BR-015, BR-016; UC-13, UC-14]`.

**Policy registration.** Register policies for `Request`, `Document`, `Message`, `Decision`, `RequestHistoryEntry`, and `Notification`, and register the role gates in the auth service provider. Policy and gate registration must not introduce a global administrator bypass.

**Bearer-token smoke test.** The §7 smoke test proves all of the following:

- unauthenticated API access without a bearer token is rejected;
- login with valid credentials for an active account returns a bearer token;
- login with valid credentials for an inactive account returns no token;
- authenticated access with `Authorization: Bearer <token>` is accepted only on an allowed protected route;
- authenticated access with a valid token still fails when the policy or gate denies the requested ability.

### 5.6 Status guard

Emit the request status guard from the §4 guard structure as two artifacts:
`app/Exceptions/IllegalRequestStatusTransition.php` and
`app/Models/Concerns/HasRequestStatusTransitions.php`.

The exception carries the from-status and to-status as `RequestStatus` enum values
(nullable where needed for error reporting). The trait, used by the `Request`
model, contains an explicit allowed-transition map keyed by `RequestStatus`, a
predicate `canTransitionTo(RequestStatus $target): bool`, and a transition method
`transitionTo(RequestStatus $target): void`. The transition method validates the
target against the map and sets the model's `status` **in memory only** — it does
not call `save()` and does not create a `RequestHistoryEntry`. The caller saves the
status change and the matching history entry together in one transaction, so legal
state and audit state cannot drift apart.

Allowed v1 transition map:

| From                  | Legal targets                               | Source                                         |
| --------------------- | ------------------------------------------- | ---------------------------------------------- |
| `draft`               | `submitted`                                 | `[03_use-cases.md UC-02 step 8]`               |
| `submitted`           | `in_review`                                 | `[03_use-cases.md UC-06 step 5]`               |
| `in_review`           | `waiting_for_citizen`, `ready_for_decision` | `[03_use-cases.md UC-07 step 5; UC-08 notes]`  |
| `waiting_for_citizen` | `in_review`                                 | `[03_use-cases.md UC-04 step 7]`               |
| `ready_for_decision`  | `decided`                                   | `[03_use-cases.md UC-09 step 8]`               |
| `decided`             | no targets; terminal in v1                  | `[03_use-cases.md UC-05 ext. 2b; UC-08 notes]` |

Do not add reopen, withdrawal, appeal, cancellation, or direct approval/rejection
transitions — they are outside v1 `[00_project-context.md §7]`. Do not install a
state-machine package; this small explicit guard is the v1 mechanism.

### 5.7 Factories + seeders

Emit factories and seeders that create a deterministic, **relationally valid** dev
fixture that passes a fresh migrate + seed and supports the §7 tinker checks.

Create at minimum: one active administrator, one active staff member, one active
citizen, and one inactive account (to prove `AccountState::Inactive`); one active
and one inactive request category; the singleton `organization_settings` row with
`singleton_key = single_organization`; requests covering every `RequestStatus`,
each in a valid relationship state; supporting documents and decision documents;
one approved and one rejected decision; messages covering every `MessageKind`;
history entries covering every `HistoryEventType`; notifications covering every
`NotificationType`.

Keep fixture integrity explicit so no invalid combination is seeded:

- a `draft` request is owned by a citizen and has no `submitted_at`;
- a `submitted` request is owned by a citizen, has a category, and may still be
  unassigned `[BR-009]`;
- an `in_review` request has a responsible staff member;
- a `waiting_for_citizen` request has a responsible staff member plus a
  missing-information message and matching history entry;
- a `ready_for_decision` request has a responsible staff member and review history;
- a `decided` request has exactly one decision and decision history;
- a decision document is attached only to a decision; supporting documents carry no
  `decision_id`;
- notification recipients match the event they are meant to receive.

Seed deterministically — stable emails, category names, request titles, and
timestamps relative to a fixed seed-time value — so the §7 verification finds known
records. Do not use random data where verification expects specific rows.

### 5.8 Storage

Configure Laravel's S3 disk for local MinIO with path-style endpoints and
environment variables for endpoint, bucket, key, secret, region, and URL. Generate
or update the filesystem configuration and the matching `.env.example` entries for
local development. `documents.file_reference` stores the object key only; file
bytes live in the bucket, not the database
`[04_data-model.md §2.1; 05_system-design.md §4]`.

The generated conventions must state that serving or downloading a file first
checks request-scoped authorization and verifies the document belongs to the
in-scope request before reading the object from storage
`[05_system-design.md §4 silent-wrong-response watch; BR-016]`. Do not add external
delivery, virus scanning, signed-document workflows, or archival rules — those are
outside v1 unless a later domain session introduces them.

### 5.9 docker-compose

Emit a local `docker-compose.yml` for the foundation stack: the Laravel app
service, a relational database service, a MinIO service, and the MinIO console for
local inspection. Provide the environment §5.8 needs and the database connection
the app uses, with persistent volumes for database and MinIO data.

Do not add Redis, a queue worker, a scheduler, a search engine, a cache service, a
mail service, or an external notification worker in v1 — the system-design stance
has no cache, no search index, and no async worker for the foundation phase, and
notifications are in-portal and created synchronously
`[05_system-design.md §1.2–§1.4; 01_miniworld.md §5]`. When the file already
exists, extend it safely rather than overwrite unrelated developer changes (§10).

### 5.10 Project conventions file

Write `docs/implementation/conventions.md` — the implementation memory later
per-domain and per-use-case sessions treat as binding unless an upstream spec is
intentionally changed first. Each item carries either a `docs/domain/*` source
reference or the label `implementation-only convention`:

- bigint auto-increment primary keys `[04 §2.1]`;
- string-backed enum shape under `app/Enums`: stored slug, `label()`, `values()`,
  `options()`, PascalCase cases `[§4, §5.2]`;
- enum columns stored as `varchar` and cast in Eloquent `[§4]`;
- filtered relations apply their filter as a query constraint inside the relation,
  comparing enum-backed columns against the stored backing value `[§4, §5.4]`;
- native Laravel policies and gates only `[BR-010..BR-016]`;
- authorization fails closed and is evaluated live `[05 §4]`;
- no global administrator allow-all `[BR-007, BR-011, Table BR-016]`;
- request-scoped visibility = owner, responsible staff member, authorized
  administrator oversight `[BR-016]`;
- notifications are recipient-only, not request-scoped `[implementation-only
convention, bounded by BR-016]`;
- every foreign key uses restrict-on-delete `[04 §2.1]`;
- accounts with history are deactivated, not physically deleted `[UC-01, BR-017]`;
- inactive accounts cannot authenticate or pass protected checks
  `[03_use-cases.md UC-00; 02_business-rules.md BR-018]`;
- request history is written explicitly, not by model-event auto logging
  `[BR-017]`;
- `request_history_entries.summary` is a frozen audit snapshot `[BR-017, 04 §2.1]`;
- status changes go through the request status guard, which sets status in memory
  only; status persistence and history writing share one transaction `[§4, §5.6,
05 §4]`;
- Sanctum API bearer-token auth uses `UserAccount` / `user_accounts`, not the default `User` /
  `users` `[§4]`;
- implementation-only auth columns `user_accounts.password` and nullable
  `remember_token` `[implementation-only convention]`;
- one consistent mass-assignment convention across models `[implementation-only
convention]`;
- file bytes live in MinIO/S3; `documents.file_reference` stores the object key;
  file access is authorized and request-verified before serving `[04 §2.1, 05 §4]`;
- no cache, search index, queue worker, scheduler, permissions package,
  activity-log package, state-machine package, or external notification delivery in
  v1 `[05 §1.2–§1.4]`;
- factories and seeders stay deterministic and relationally valid `[§5.7]`.

## 6. Binding vocabulary

This is an implementation skill, so it names framework terms directly:
`migration`, `model`, `relation`, `cast`, `enum`, `policy`, `gate`, `Sanctum`,
`S3 disk`, `factory`, `seeder`, `docker-compose`, `status guard`,
`allowed-transition map`. **Layer boundary:** the skill realises the specs and
therefore names the framework; it never authors a new domain or architecture
decision. A genuine gap is a conflict to report or an Open Question, not a free
choice.

## 7. Verification

Report success only after all of the following pass against this project's tables
and enums:

- **Fresh migrate + seed** completes with no error (all nine tables build; the
  §5.7 fixture loads and is relationally valid).
- **Tinker data-layer checks:** an enum column resolves to its enum cast and
  `label()` returns the spec label; a representative relation loads (a request's
  owner, responsible staff, decision, history entries; a user's owned vs. assigned
  requests; a notification's recipient); a **legal** transition (submitted →
  in_review) sets the status while an **illegal** one (draft → decided) raises the
  guard exception without saving; a `restrictOnDelete` blocks deleting a referenced
  record (a user_account that owns a request).
- **Authorization checks:** a request-scoped view denies a non-participant; a
  notification view denies a user who is not its recipient (no cross-participant
  leak); an administrator is denied the `decide` and message-create abilities (no
  global allow-all); an inactive account fails authentication.
- **Curl smoke test:** the app boots and Sanctum auth is wired — an unauthenticated
  request to a protected route is rejected; an authenticated one on an allowed
  route is accepted.

## 8. Refocus

Produce the §5 inventory in dependency order, each artifact held with its source
and emitted from its one §4 description. No fact is stated in more than one place:
each enum, table, relation, policy, and convention lives in its §5 slot and is
referenced elsewhere, not repeated (§5.10 consolidates the conventions as the
generated memory file's content, by design). The structural stop is §5 complete and
§7 green. Stop on any spec/code conflict instead of guessing.

## 9. Transition — produce

Produce two artifacts in this order:

1. **Reasoning preamble** (in the gather-context / output stream, not in files): an
   Inner Plan listing the §5 artifacts with their sources and the one decision
   confirmed at run time (the §5.6 transition map against `03_use-cases.md`),
   followed by a Chain-of-Thought that walks the data model table by table —
   deriving each enum, migration, model, relation, policy, and gate with its source
   tag — and surfaces any spec/code conflict instead of resolving it silently. Tag
   plan items `[04_data-model.md §N]`, `[02_business-rules.md BR-N]`,
   `[03_use-cases.md UC-N]`, `[05_system-design.md §N]`, or `[derived from …]`
   (flagging derived items as fragile).
2. **Main answer — the artifacts.** Write the §5 inventory to the project under its
   real paths in §5 dependency order, then run §7. Skeleton-completion plus green
   verification is the structural stop; no closing remarks.

If any of `docs/domain/00_project-context.md`, `01_miniworld.md`,
`02_business-rules.md`, `03_use-cases.md`, `04_data-model.md`, or
`05_system-design.md` is missing or empty, stop and report
`BLOCKED — <filename> not found`.

## 10. Re-run safety

The skill runs once but stays safe to re-run: detect an existing artifact and edit
or skip it rather than duplicate; leave seeded data intact unless a reset is
explicitly confirmed; extend config and `docker-compose` services rather than
overwrite them. Add only the §5.1 packages if absent.
