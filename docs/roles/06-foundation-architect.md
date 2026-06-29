# Role — Foundation Architect

## 1. Introduction

This is a **role file** in the project's methodology. It is consumed by a model
(single-pass chat or agentic runtime) together with **one anchor** and the
upstream domain specs, and produces a single named deliverable.

**Document type:** a structured-document role file (per
`prompt-engineering-anchor.md` AC-05). The deliverable is a project-specific
**foundation skill** — a `foundation/SKILL.md` that, when run, scaffolds the
runnable backend the project's domain specs describe. This role is a **skill
generator**: where `01`…`05` produce `docs/domain/*` specs, this role produces
the implementation skill that consumes those specs.

**Focus:** turn the finished domain specs into a foundation skill whose dynamic
sections (enum table, model relations, verification checks, frontmatter) are
**derived from the specs**, and whose static sections (dependency order,
conventions, generation discipline) are **embedded in this role file** (§5),
described in prose rather than as concrete code. The generated skill must trace
every artifact to a spec line or a named implementation-only convention; it must
stop on any spec/code conflict rather than guess.

This role is the **bridge step**: the last upstream specs feed in, and the first
implementation artifact comes out. Its inputs are `00_project-context.md`
through `05_system-design.md`. Its output, the foundation skill, becomes the
deterministic base every later per-domain and per-use-case session depends on.

## 2. Binding Anchors (indirect context)

This role file is consumed together with **one anchor**:

- `prompt-engineering-anchor.md` — binds **how** this role file is written and
  how the model produces its output (four-part anatomy, section skeleton,
  reasoning preamble, vocabulary discipline). References look like
  `prompt-engineering-anchor.md AC-XX` or `prompt-engineering-anchor.md RC-XX`.

There is **no domain anchor** for this role. Instead, this role binds **what**
the deliverable contains through a practical method basis — Laravel-backend
scaffolding conventions (Eloquent models and migrations, string-backed enums,
Sanctum SPA auth, native policies / gates, an explicit status guard, Flysystem
S3 storage against MinIO, `docker-compose` for local dev) — embedded as
application rules in §5 and stated **in descriptive prose, not in code**. The
deliverable is an **implementation skill**, so this role file legitimately names
framework classes, migration code, and column types: that is the layer it
operates in, not a drift (§7).

Because the method basis is the Laravel stack, this role file is **stack-
specific**. A different stack family (e.g. a Django backend) is a sibling role
file (`06-foundation-architect-django.md`) with its own embedded conventions,
the same way `01-requirements-architect` is the concrete requirements role
rather than a generic one.

This role does **not** rely on persona-priming
(`prompt-engineering-anchor.md` RC-01). Output quality comes from the explicit
inputs, the section skeleton, the embedded rules in §5, and the reasoning
preamble — not from a role label.

Section numbers written as plain `§N` always refer to **this** role file.

## 3. Inputs

Inputs are declared per `prompt-engineering-anchor.md` AC-06 / AC-07.

**Static (fixed in this role file):**

- The section skeleton for the generated `foundation/SKILL.md` (§6).
- The application rules embedded from the Laravel-stack method basis (§5),
  including the prose descriptions of the enum shape and the status-guard
  structure the generated skill must emit.
- The vocabulary and layer-boundary note reaffirmed for this stage (§7).

**Dynamic (filled at run time):**

- **Pre-loaded files** (the authoritative source for every generated artifact):
  - `docs/domain/04_data-model.md` — §2.1 gives every table, column, type,
    foreign-key rule, and unique constraint; §1.2 / §1.3 give the value sets and
    the relations. The literal source for migrations and model relations.
  - `docs/domain/02_business-rules.md` — BR-001…BR-NNN and the roles /
    permissions matrix give the enum value sets and the authorization rules.
  - `docs/domain/03_use-cases.md` — consulted to confirm the legal status
    transitions for the guard (§5).
  - `docs/domain/05_system-design.md` — consulted for the reliability and
    storage decisions the skill encodes (fail-closed authorization, S3 disk, the
    synchronous v1 stance).
  - `docs/domain/00_project-context.md`, `docs/domain/01_miniworld.md` —
    consulted for project identity (the generated skill's frontmatter name /
    description) and the v1 scope cuts that justify the rejected non-goals.

**Context classification (per `prompt-engineering-anchor.md` AC-07):**

- The prompt-engineering anchor is **indirect** (binding context).
- The six upstream specs are **indirect** (pipeline-composed).
- The section skeleton and the embedded rules in this file are **boilerplate**
  (framing glue).

There is no direct user input at run time — the upstream specs already captured
the project owner's intent, refined through the conventions in §5.

## 4. What this role does and does not do

This role **does**:

- Produce a foundation skill whose **frontmatter** (name, description,
  `disable-model-invocation`, `allowed-tools`) is filled from project identity
  (`00_project-context.md`) and the §6 artifact inventory.
- Derive the **enum inventory** from the value sets in `04_data-model.md` §1.2
  and `02_business-rules.md`, each enum following the enum-shape convention
  described in §5.
- Derive the **migration and model inventory** one-per-table from
  `04_data-model.md` §2.1, with columns, types, foreign-key rules, and relations
  read off the data model (§5).
- Encode the **authorization base** — request-scoped policies and role gates —
  from the cross-cutting visibility rule and the role-restricted rules in
  `02_business-rules.md`, with checks that **fail closed**
  (`05_system-design.md` reliability; §5).
- Wire the **status guard** described in §5 to the transition map confirmed
  against `03_use-cases.md`.
- Encode **auth, storage, the compose stack, factories / seeders, and the
  project conventions file** per the embedded conventions (§5).
- State the **verification** the generated skill must pass before reporting
  success (§5), referencing the project's own tables and enums.
- Carry forward **v1 scope cuts** from `00_project-context.md` §7 /
  `05_system-design.md` as the justification for the generated skill's rejected
  non-goals (§5).

This role **does not**:

- Re-decide domain content. It never invents a business rule, renames or extends
  an enum value set away from the specs, or adds a table the data model does not
  define. A genuine gap is a conflict to report or an Open Question, not a free
  choice (§7 Forbidden).
- Re-open architecture the system-design spec settled (a cache, search index,
  queue, or worker marked `no` / `later` stays out).
- Ship concrete code examples or bundled reference files. It describes each
  pattern in prose and lets the generated skill emit the code from that
  description.
- Write the per-use-case endpoint logic, request validation, or controllers —
  the generated skill scaffolds the **base**; per-use-case sessions extend it.

## 5. Application Rules (embedded from the Laravel-stack method basis)

This role is based on a small set of Laravel-backend scaffolding conventions,
decided with the project owner. Each states what to do and **why**, so the reason
survives into every generated foundation skill. They are stated **descriptively**
— no concrete code, no bundled example files; the generated skill emits the code
from these descriptions. The conventions split into **framework conventions**
(what the generated code looks like) and **generation discipline** (how the run
proceeds).

**Framework conventions:**

- **Bigint auto-increment keys.** Primary keys are `bigint` auto-increment
  everywhere — plain `id()` — following the data model. ULIDs / UUIDs are a
  deliberate non-goal for v1.
- **String-backed enums (shape described).** Each enum is a string-backed PHP
  enum whose backing value is a stable snake_case slug, decoupled from wording.
  It carries a `label()` method returning the human label verbatim from the
  specs, and two static helpers: one returning the list of backing values (for
  validation rules) and one returning a slug-to-label map (for select controls).
  The stored database value is the slug; columns stay `varchar` with application
  casts, because native database enum types are costly to alter as a value set
  evolves. Every enum in the inventory follows this one shape.
- **Native authorization.** Authorization is native Laravel policies / gates.
  Role is a fixed value set on the user table with no role-maintenance process,
  so a permissions package would introduce tables the model does not define and
  is left out for that reason.
- **Restrict-on-delete with deactivation.** Every foreign key uses
  `restrictOnDelete()`, so referenced records cannot be orphaned. Accounts tied
  to request history are retired via an `inactive` account state rather than
  removed, which preserves the history the traceability rule requires.
- **Explicit history writes.** History is written explicitly in the use-case
  logic. A history summary is a frozen audit snapshot whose value depends on
  **not** being regenerated, so a model-event auto-logger is a poor fit and an
  activity-log package is left out.
- **Status transitions via a guard (structure described).** Status changes go
  through a guard with two parts: an exception type for an illegal transition,
  carrying the from-status and to-status; and a trait added to the request model
  that holds an explicit allowed-transition map (each status mapped to its list
  of legal target statuses), a predicate that tests whether a target is legal,
  and a transition method that validates against the map and sets the status
  **in memory only**, raising the exception on an illegal target. Persistence is
  the caller's responsibility, so the status change and its history entry are
  saved together in one transaction and the durable-write path stays atomic. The
  map is the v1 default derived from the status value set and the use-case
  transitions; a state-machine package is left out because this guard covers the
  v1 transition set.
- **Sanctum SPA auth.** Auth is Laravel Sanctum (stateful SPA session). The
  authenticatable model is the project's user table, not the framework-default
  `users`.
- **Implementation-only auth columns.** The user table gains a `password` column
  (plus nullable `remember_token`) as implementation-only columns beyond the
  conceptual data model, because local login needs a credential the conceptual
  model does not carry. Record this in the project conventions file.
- **S3 disk against MinIO.** File attachments use the S3 disk against local MinIO
  (`use_path_style_endpoint`). The file-reference column holds the object key,
  not the bytes.

**Generation discipline:**

- **Derive every artifact from a spec.** Each enum value set, table, column,
  relation, and policy traces to a `docs/domain/` line, or it is a named
  implementation-only convention recorded in the project conventions file, or it
  is a conflict to report.
- **Generate in dependency order.** Enums → migrations → models → auth →
  authorization → status guard → factories / seeders → storage → docker-compose
  → project conventions file. Each layer depends on the ones before it.
- **One described pattern per artifact kind.** Every enum follows the single
  enum shape above; the status guard follows the single structure above. Emit
  each like artifact from its one description so the generated code is uniform.
- **Stop on spec conflict.** When generated code would contradict a spec, the
  run **stops and reports the conflict** rather than guessing, because silent
  divergence from `docs/domain/` breaks the pipeline's single source of truth.
- **Prove it runs.** The generated skill reports success only after a fresh
  migrate + seed completes, tinker data-layer checks pass (casts resolve, a
  relation loads, a legal vs. illegal transition sets vs. throws,
  restrict-on-delete blocks), and a curl smoke test confirms the app boots and
  auth is wired.
- **Minimal dependency surface, safe re-runs.** Add only the composer packages a
  convention requires (SPA auth, S3 disk); no permissions, activity-log,
  state-machine, or v1 test-suite package. The skill runs once but stays safe to
  re-run: detect existing files and edit or skip rather than duplicate; leave
  data intact unless a reset is confirmed; extend config and compose rather than
  overwrite.

## 6. Section Skeleton (structural stop)

The deliverable `foundation/SKILL.md` is complete when the following section
sequence is filled (`prompt-engineering-anchor.md` AC-09 — four-part anatomy
with focus-opener recursion; AC-10 — structural stop; AC-11 — section-skeleton
template). The skeleton below is the **shape of the generated skill**; the
angle-bracket slots are filled from the specs and the §5 conventions. The
generated skill states its enum and guard patterns **in prose**, the same way
§5 does — it carries no bundled code example files.

````markdown
---
name: foundation
description: >-
  <one-paragraph scaffold summary naming the project and the artifact set —
  derived from 00_project-context.md identity + the §5 inventory below.>
  Run this once at the start of the project, before any per-domain or
  per-use-case work. Invoke explicitly with /foundation.
disable-model-invocation: true
allowed-tools: Read, Edit, Write, Bash
---

# Skill — Foundation (run once)

## 1. Introduction

<what the skill produces and its structural stop — skeleton-complete plus a
passing §7 verification.>

## 2. Binding context

<the authoritative `docs/domain/*` specs (indirect), with the short-form
citation convention.>

## 3. Inputs

<static: the §4 conventions and the §5 inventory, including the prose
descriptions of the enum shape and the status-guard structure; dynamic: the
`docs/domain/*` specs.>

## 4. Application rules (project conventions)

<the framework conventions from this role file's §5, each with its source —
bigint keys, the described enum shape, native authorization, restrict-on-delete +
deactivate, explicit history, the described status guard, Sanctum, the
implementation-only auth columns, the S3/MinIO disk.>

## 5. Artifacts to produce

<the dependency-ordered inventory (§5 generation discipline), filled from the
specs:>

- **5.1 Composer packages** — <minimal set.>
- **5.2 Enums** — <one row per value set from `04_data-model.md` §1.2 +
  `02_business-rules.md`: enum name, stored slugs, spec labels — each emitted
  from the described enum shape.>
- **5.3 Migrations** — <one per table from `04_data-model.md` §2.1: columns,
  types, FK rules, unique constraints.>
- **5.4 Models** — <one per table: casts, relations from `04_data-model.md`
  §1.3.>
- **5.5 Auth + authorization** — <Sanctum wiring; the request-scoped policies
  and role gates from the visibility + role-restricted business rules,
  fail-closed.>
- **5.6 Status guard** — <emitted from the described guard structure; the
  transition map confirmed against `03_use-cases.md`.>
- **5.7 Factories + seeders** — <a usable dev fixture across the value sets.>
- **5.8 Storage** — <the S3/MinIO disk; the file-reference object key.>
- **5.9 docker-compose** — <the services for this stack; no async worker in a
  synchronous v1.>
- **5.10 Project conventions file** — <always-true conventions for later
  sessions, including every implementation-only decision.>

## 6. Binding vocabulary

<the implementation-layer vocabulary and the layer-boundary note.>

## 7. Verification

<the checks against this project's tables and enums: a fresh migrate + seed;
tinker data-layer checks (casts resolve, a relation loads, a legal vs. illegal
transition sets vs. throws, restrict-on-delete blocks); a curl smoke test that
the app boots and auth is wired.>

## 8. Refocus

<restate the task: generate the §5 inventory in dependency order, each
convention held with its source, structural stop = §5 complete and §7 green.>

## 9. Transition — produce

<the two-artifact order per `prompt-engineering-anchor.md` AC-15: reasoning
preamble (Inner Plan + Chain-of-Thought with source tags), then the files in §5
order under their real paths, then §7. Plus the BLOCKED guard if any
`docs/domain/*` spec is missing.>

## 10. Re-run safety

<detect existing files and edit or skip; leave data intact unless a reset is
confirmed; extend config and compose rather than overwrite.>
````

The order is fixed. The generated skill describes its enum and guard patterns in
prose; it bundles no code example files.

## 7. Binding Vocabulary

This role file straddles two layers. When it **describes the deliverable**, it
names framework terms (`migration`, `policy`, `Sanctum`) — that naming is
correct, because the deliverable is an implementation skill. When it **describes
itself as a role file**, it uses the methodology vocabulary of
`prompt-engineering-anchor.md`. This inversion is the layer boundary: the
upstream spec role files forbid framework names; this role implements those
specs and therefore names them.

**Use these terms:**

- Methodology: `role file`, `anchor`, `section skeleton`, `reasoning preamble`,
  `structural stop`, `pipeline composition`, `skill generator`.
- Deliverable layer: `foundation skill`, `dependency order`, `enum-shape
  convention`, `status-guard structure`, `enum inventory`, `migration`, `model`,
  `relation`, `policy`, `gate`, `factory`, `seeder`, `S3 disk`,
  `docker-compose`, `project conventions file`, `verification`.

**Forbidden** — any occurrence means this role file has drifted out of its job:

- Concrete code examples or bundled reference code files: this role file
  **describes** each pattern in prose; emitting the actual PHP is the generated
  skill's job at run time.
- Re-deciding domain content: stating a business rule, value set, or table that
  the specs do not contain (the role file **reads** these from `docs/domain/`;
  it never authors them). An implementation-only column (the `password` case,
  §5) is permitted only when named as such.
- Re-opening settled architecture: introducing a cache, search index, queue, or
  worker the `05_system-design.md` decision excluded.
- Persona priming as a quality mechanism
  (`prompt-engineering-anchor.md` RC-01).

_(This role file **authors a generator** that **realises** the specs. It names
framework terms and describes patterns in prose; it never makes a new domain or
architecture decision of its own, and never ships concrete code. A genuine gap
is a conflict to report or an Open Question, not a free choice.)_

## 8. Refocus

Before producing the deliverable, restate the task:

The deliverable is a project-specific `foundation/SKILL.md`, structured exactly
per §6, with every dynamic slot filled from `docs/domain/00`…`05` and every
static slot drawn from the §5 conventions, which are stated in prose. Every
generated artifact named in the skill's §5 traces to a spec line or a named
implementation-only convention. The generated skill generates in the §5
dependency order, stops on any spec/code conflict, and reaches a green
verification before reporting success. No concrete code example files are
bundled — the enum shape and the status guard are carried as descriptions.

No fact appears in more than one section: each convention, enum, table, or
policy is stated once, in the section where it best belongs, and is referenced —
not repeated — elsewhere. Justifying specs and rules are cited by id, not
re-described.

## 9. Transition — Produce

You will now produce two artifacts in this order, per
`prompt-engineering-anchor.md` AC-15:

1. **Reasoning preamble** (in your output stream, not in the file):
  - **Inner Plan.** First understand the input and devise a plan: list the §6
    skeleton sections you will fill; pre-decide the enum inventory (one per
    value set), the table inventory (one migration + one model per table), and
    which business rules become policies vs. gates. Name the one decision to
    confirm at generation time — the status-transition map against
    `03_use-cases.md`. Name the validation you will run before finalising: every
    enum value set, table, relation, and policy traces to a spec line.
    Tag every plan item with its source so the reader can audit where each
    decision came from. Use one of these forms:
    `[06-foundation-architect.md §N]` for this role file (e.g.
    `[06-foundation-architect.md §5]` when citing an embedded convention);
    `[04_data-model.md §N]`, `[02_business-rules.md BR-N]`,
    `[03_use-cases.md UC-N]`, `[05_system-design.md §N]`,
    `[00_project-context.md §N]`, `[01_miniworld.md §N]` for upstream specs;
    `[derived from …]` for anything inferred rather than read off.
    Flag `[derived from …]` items as potentially fragile.
  - **Chain-of-Thought.** Carry out the plan step by step: walk the data model
    table by table, deriving each enum, migration, model, and relation with its
    source; derive each policy / gate from the visibility and role-restricted
    rules; reconcile the transition map against `03_use-cases.md` and record any
    transition the use cases require that the described guard does not yet list.
    Each time a generated artifact would contradict a spec, surface the conflict
    instead of resolving it silently. Confirm no domain or architecture
    decision is being newly made — only realised.

2. **Main answer — the deliverable.** Write `foundation/SKILL.md` to the
   project's skills directory (or the equivalent location the runtime provides),
   using the exact section skeleton from §6, with the enum shape and the status
   guard carried as prose descriptions. Skeleton-completion is the structural
   stop (`prompt-engineering-anchor.md` AC-10); no closing remarks, no
   meta-commentary.

If any of `00_project-context.md`, `01_miniworld.md`, `02_business-rules.md`,
`03_use-cases.md`, `04_data-model.md`, or `05_system-design.md` is missing or
empty, stop and report `BLOCKED — <filename> not found`.

---

> **Pipeline note.** `06-foundation-architect.md` is the seam between the
> upstream spec pipeline (`00`…`05`) and the implementation layer. Its output —
> the project's `foundation/SKILL.md` — is itself a generated artifact, just as
> `01_miniworld.md` is: re-running this role after the specs change re-derives
> the skill. The generated foundation skill then runs once (`/foundation`) to
> scaffold the backend that every per-domain and per-use-case session builds on.
> A project on a different stack family is served by a sibling foundation role
> file with its own embedded conventions, not by re-binding this one.