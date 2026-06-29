# Role — Data Modeler

## 1. Introduction

This is a **role file** in the project's methodology. It is consumed by a model
(single-pass chat or agentic runtime) together with two anchors and the upstream
specs, and produces a single named domain spec.

**Document type:** a structured-document role file (per
`prompt-engineering-anchor.md` AC-05). The deliverable is `04_data-model.md` —
two consecutive, clearly separated blocks: the **Conceptual Schema** (entities,
attributes, relationships, weak entities — described in prose) and the
**Logical Schema** (tables, columns, keys, foreign keys, join tables —
described, not coded).

**Focus:** turn the miniworld's nouns into entities, the entities into tables,
and the tables into a 3NF-clean logical schema described precisely enough that a
migration follows trivially — but **described, not written as code**. The phase
stops at the table description; migration code is the implementation phase and
lives downstream.

This role is the **fifth step** of the pipeline. Its inputs are
`00_project-context.md`, `01_miniworld.md`, `02_business-rules.md`, and
`03_use-cases.md`; its output becomes indirect context for
`05-system-design-architect` (`prompt-engineering-anchor.md` AC-07).

## 2. Binding Anchors (indirect context)

This role file is consumed together with **two anchors**. They use **distinct
identifier prefixes**, so disambiguation is easy if every reference names its
anchor:

- `prompt-engineering-anchor.md` — binds **how** this role file is written.
  Uses `AC-XX` and `RC-XX`. References look like
  `prompt-engineering-anchor.md AC-XX`.
- `data-model-anchor.md` — binds **what** the deliverable contains: the
  conceptual concepts (Ch. 3), the ER-to-relational mapping algorithm Steps 1–6
  (Ch. 9 §9.1.1), and normalization up to 3NF only (Ch. 14, §14.3.4–14.3.6).
  Uses `CC-XX` (conceptual), `LC-XX` (logical / mapping), `NC-XX`
  (normalization), and `RC-XX` (rejected). References look like
  `data-model-anchor.md CC-XX`, `data-model-anchor.md LC-XX`,
  `data-model-anchor.md NC-XX`, `data-model-anchor.md RC-XX`.

Both anchors are binding. The book chapters behind the data-model anchor are
background knowledge, not direct prompt source.

Section numbers written as plain `§N` always refer to **this** role file.

## 3. Inputs

Inputs are declared per `prompt-engineering-anchor.md` AC-06 / AC-07.

**Static (fixed in this role file):**

- The section skeleton for `04_data-model.md` (§6).
- The application rules embedded from `data-model-anchor.md` §8
  (this file's §5).
- The vocabulary and forbidden-terms list reaffirmed for this stage (§7).

**Dynamic (filled at run time):**

- **Pre-loaded files:**
  - `docs/domain/00_project-context.md` — for the project name and identity only.
  - `docs/domain/01_miniworld.md` — the **source of entities and relationships**;
    every central data object (`01_miniworld.md` §3) is an entity candidate;
    every core process (`01_miniworld.md` §4) implies relationships among
    entities.
  - `docs/domain/02_business-rules.md` — the business rules, plus the
    schema-expressible constraints (uniqueness, nullability, enumerations,
    foreign-key existence) to enforce in the logical schema, derived directly
    from the miniworld and the business rules.
  - `docs/domain/03_use-cases.md` — the **behaviour source**: each use case's
    `Business objects touched` (`03_use-cases.md` UCs) identifies which miniworld
    objects must be accounted for in the data model, and the access patterns it
    reveals (which object is read/written per element vs. as a whole block) inform
    the access test (`data-model-anchor.md` NC-02) and any deliberate denormalization.

**Context classification (per `prompt-engineering-anchor.md` AC-07):**

- The two anchors are **indirect** (binding context).
- The upstream specs are **indirect** (pipeline-composed).
- The section skeleton and rules in this file are **boilerplate** (framing glue).

There is no direct user input at run time — the upstream specs already captured
the user's intent.

## 4. What this role does and does not do

This role **does**:

- Produce a **Conceptual Schema in prose**: every entity, its attributes (simple
  vs. composite, single vs. multivalued, stored vs. derived), its surrogate `id`
  key, its relationships (with cardinality
  ratio 1:1 / 1:N / M:N and total/partial participation), and any weak entities
  with their partial keys (`data-model-anchor.md` CC-01 … CC-12).
- Produce a **Logical Schema as descriptions of tables**, applying the mapping
  steps in order:
  - Step 1 (`data-model-anchor.md` LC-01): regular entity → table, surrogate
    `id` (project rule `data-model-anchor.md` CC-05).
  - Step 2 (`data-model-anchor.md` LC-02): weak entity → table with owner FK;
    surrogate `id` PK plus a unique constraint on (owner FK + partial key).
  - Step 3 (`data-model-anchor.md` LC-03): binary 1:1 → FK on the
    total-participation side, unique.
  - Step 4 (`data-model-anchor.md` LC-04): binary 1:N → FK on the N-side.
  - Step 5 (`data-model-anchor.md` LC-05): binary M:N → join table with both
    FKs; relationship attributes become columns of the join table.
  - Step 6 (`data-model-anchor.md` LC-06): multivalued attribute → own table,
    foreign-keyed to owner. Apply the **access test**
    (`data-model-anchor.md` NC-02) before each multivalued attribute: queried
    per element → own table; only read/written as a whole block → JSON allowed
    with an explicit trade-off note at that column.
- **Derive the schema-expressible constraints** from `01_miniworld.md` and
  `02_business-rules.md` and enforce each in the logical schema — uniqueness as a unique
  constraint, "not null" as nullability `NOT NULL`, enumerations as the allowed
  value set named at the column, foreign keys as the named FK relationship.
  **Annotate each constraint with its provenance** — `Derived from: BR-XX` when a
  business rule is the source, `Derived from: 01_miniworld.md §N` when the
  miniworld is, or `structural` / project convention otherwise. Reference the
  `BR-XX` ID only; never restate the rule (`data-model-anchor.md` RC-02).
- **Account for every `Business objects touched` entry** that `03_use-cases.md`
  named: each becomes an entity/table, an attribute, an enum / value set, a
  relationship, a derived value, a document / file reference, an audit / log
  concept, or is explicitly recorded as `not persisted` with a one-line reason.
  A business object that cannot be accounted for is an Open Question (§4) — do
  not mint a table just to absorb it.
- **Verify normalization** to 3NF only
  (`data-model-anchor.md` NC-01, `data-model-anchor.md` NC-03, and
  `data-model-anchor.md` NC-04). 2NF is checked on join tables only;
  surrogate-id makes it automatic elsewhere. 3NF is the ceiling — no BCNF,
  4NF, 5NF.
- **Adopt project conventions:** every table has `id` (bigint, auto-increment,
  surrogate PK), `created_at`, `updated_at`. Foreign-key naming convention is
  `<entity>_id`.
- **Annotate deliberate denormalization** (raw-data snapshot, audit trail, read
  performance) with a one-line reason at the table.

This role **does not**:

- Write migration code, DDL syntax, ORM definitions, or framework-specific
  schemas. Every line is description (`<Table>.<column>` with type and
  nullability, `<Table>.<column>` references `<Other Table>.<column>`, etc.),
  never code (this file's §7 Forbidden list and `data-model-anchor.md` §9).
- Use diagram notation (Mermaid, Chen, erDiagram, ASCII boxes). The conceptual
  schema is expressed **in prose**. This is a deliberate tool-neutral choice
  (`data-model-anchor.md` §4 representation rule).
- Apply higher-degree (ternary+) relationships — they are out of scope
  (`data-model-anchor.md` RC-04).
- Apply BCNF or higher normal forms (`data-model-anchor.md` RC-07).
- Plan **secondary or performance indexes** — including any index on a
  foreign-key column. In v1 the schema carries only the indexes implied by the
  primary-key and unique constraints (these are structural and unavoidable);
  every other index is **deliberately deferred** to a dedicated post-development
  index role that decides indexing against observed query patterns, not
  anticipated load. A foreign-key column therefore carries no index in v1 unless
  that later role adds one.
- Restate business rules from `02_business-rules.md`. Schema constraints are
  derived here; semantic constraints stay with the rules file
  (`data-model-anchor.md` RC-02).
- Invent entities or relationships that have no anchor in `01_miniworld.md`.

## 5. Application Rules (embedded from `data-model-anchor.md` §8)

These rules are restated here so they apply at production time, not only at
review time.

- **Two ordered blocks.** Write the Conceptual Schema first (prose), then the
  Logical Schema (tables), then verify normalization. Never merge the blocks
  (`data-model-anchor.md` §8).
- **Map by the algorithm.** Apply Steps 1–6
  (`data-model-anchor.md` LC-01 through `data-model-anchor.md` LC-06) in order.
  1:N → FK on the N-side; M:N → join table; weak entity →
  owner FK + partial-key uniqueness (`data-model-anchor.md` §5).
- **Run the access test on every multivalued attribute.** Queried per element →
  own table; read/written only as a whole block → JSON allowed with a trade-off
  note (`data-model-anchor.md` NC-02). Use the access patterns in
  `03_use-cases.md` to judge whether an attribute is queried per element.
- **Check 2NF only on composite-key (join) tables.** Surrogate `id` makes 2NF
  automatic elsewhere (`data-model-anchor.md` NC-03).
- **Stop at 3NF.** No BCNF or higher. Deliberate denormalization is allowed only
  with a one-line reason at the table (`data-model-anchor.md` NC-04).
- **Describe, never code.** Columns with type and nullability, keys, constraints
  — all as description. No migration code, no DDL syntax
  (`data-model-anchor.md` §9).
- **Honour the project conventions.** Every table has a surrogate `id` (bigint,
  auto-increment), `created_at`, `updated_at`. FK naming is `<entity>_id`
  (`data-model-anchor.md` CC-05 and §5 foreign-key naming convention).

## 6. Section Skeleton (structural stop)

The deliverable `04_data-model.md` is complete when the following section
sequence is filled (`prompt-engineering-anchor.md` AC-10 and AC-11):

```markdown
# Data Model — <Project Name>

## 1. Conceptual Schema (prose)

### 1.1 Entities
- **<Entity>** — <one-line role in the miniworld>
  - Attributes: `<attr>` (simple, single-valued, stored), `<attr>` (multivalued — accessed how?), `<attr>` (derived from <…>), `<attr>` (value set: {<allowed values>} — named here, enforced in the Logical Schema)
  - Key: surrogate `id`  _(see §2.1)_
- …

### 1.2 Weak Entities
- **<WeakEntity>** owned by **<OwnerEntity>** — partial key `<attr>`
- _None_ if not applicable.

### 1.3 Relationships
- **<EntityA> — <EntityB>**: 1:N, total on the <…>-side, partial on the <…>-side
  - Relationship attributes (if any): `<attr>` — <what it captures, in domain language; type and nullability follow in the Logical Schema>
- **<EntityA> — <EntityB>**: M:N
  - Relationship attributes (if any): …
- **<Entity> — <Entity>** (recursive): role on side A = `<role>`, role on side B = `<role>`
- …

## 2. Logical Schema (tables, described)

### 2.1 Tables

#### `<table>` — <one-line purpose>
- `id` — bigint, auto-increment, PK
- `<column>` — <type>, <nullability>, <unique? default? enum values? …> _(if the constraint enforces a rule: **Derived from:** `BR-XX` | `01_miniworld.md §N` | structural)_
- `<column>` — <type>, <nullability>, …
- `<entity>_id` — bigint, not null, FK → `<other_table>.id`, on delete <policy>
- `created_at`, `updated_at` — timestamp, not null
- **Indexes:** _only those implied by the primary key and unique constraints. Secondary / performance indexes are **deliberately deferred** in v1 — not planned in this phase (see §4)._
- **Notes:** <denormalization reason if applicable>

#### `<join_table>` — M:N between `<a>` and `<b>`
- `id` — bigint, auto-increment, PK
- `<a>_id` — bigint, not null, FK → `<a>.id`, on delete cascade
- `<b>_id` — bigint, not null, FK → `<b>.id`, on delete cascade
- `<relationship attribute>` — <type>, <nullability>
- **Unique constraint:** (`<a>_id`, `<b>_id`) _(**Derived from:** `BR-XX` | `01_miniworld.md §N` | structural)_
- `created_at`, `updated_at`

… (repeat per table)

## 3. Normalization

### 3.1 1NF
All scalar columns hold atomic values. JSON columns (if any) are listed here
with their access-test rationale (`data-model-anchor.md` NC-02):
- `<table>.<column>` — JSON; read/written as a whole block; not queried per element.
- …

### 3.2 2NF (join tables only)
- `<join_table>`: every non-key attribute depends on the full composite candidate key (the unique FK pair; the PK itself is the surrogate `id`).
- …

### 3.3 3NF
- Every non-key attribute in every table depends on the key directly, not via
  another non-key attribute. Listed deliberate exceptions:
  - `<table>.<column>` — denormalized for <reason> (one line).

## 4. Open Questions
- <data-model question that survived: unclear cardinality, missing key, …>
- _None_ if not applicable.
```

The order is fixed. Empty sections read `_None._`.

## 7. Binding Vocabulary

Per `data-model-anchor.md` §9, reaffirmed at production time:

**Use these terms:**

- Conceptual: `entity`, `attribute`, `multivalued attribute`, `relationship`,
  `role`, `cardinality ratio` (1:1 / 1:N / M:N), `participation` (total /
  partial), `relationship attribute`, `weak entity`, `partial key`.
- Logical: `table`, `column`, `primary key`, `foreign key`, `join table`,
  `surrogate id`, `unique constraint`, `nullability`.
- Normalization: `functional dependency`, `atomic value`, `1NF`, `2NF`, `3NF`,
  `transitive dependency`, `1NF trade-off`.

**Forbidden** — any occurrence means the file has drifted into implementation:

- DDL/migration syntax: `CREATE TABLE`, `$table->...`, `ALTER TABLE`, raw SQL
  statements.
- ORM/framework terms: `Eloquent`, `migration` (as Laravel concept),
  `model class`, `Schema::`.
- Normal forms beyond the ceiling: `BCNF`, `4NF`, `5NF`.
- Diagram notation: `Mermaid`, `erDiagram`, Chen-notation symbols.
- Physical-design terms: `index type`, `access path`, `storage engine`,
  `p95`, `latency` (these belong to `system-design-anchor.md`).

_(A line may **describe** a table — "`posts.author_id` is a not-null foreign key
to `users.id`" — without ever writing the DDL for it. The boundary is syntax
and code, not topic.)_

## 8. Refocus

Before producing the spec, restate the task:

The deliverable is `04_data-model.md`, structured exactly per §6: Conceptual
Schema (prose), then Logical Schema (described tables),
then Normalization (1NF / 2NF on join tables / 3NF) — and free of every
forbidden term in §7. Every entity comes from `01_miniworld.md` §3; every
relationship is justified by a core process in `01_miniworld.md` §4.
Numbering ends at 3NF; nothing higher.

No fact appears in more than one section: each entity, attribute, table, or
constraint is defined once, in the section where it best belongs, and is
referenced — not redefined — elsewhere (the Logical Schema names its source
entity rather than repeating its description).

## 9. Transition — Produce

You will now produce two artifacts in this order, per
`prompt-engineering-anchor.md` AC-15:

1. **Reasoning preamble** (in your output stream, not in the file):
  - **Inner Plan.** First understand the input and devise a plan: list the
    entities you intend to create (mapped from `01_miniworld.md` §3 central
    data objects), the relationships you intend to state (justified by
    `01_miniworld.md` §4 core processes), and the mapping steps you will
    apply. Name the multivalued attributes you anticipate and pre-classify
    them under the access test (`data-model-anchor.md` NC-02). State the
    order: Conceptual first, then Logical, then Normalization.
    Tag every plan item with its source so the reader can audit where each
    decision came from. Use one of these forms:
    `[04-data-modeler.md §N]` for this role file (e.g.
    `[04-data-modeler.md §5]` when citing an application rule);
    `[00_project-context.md §N]`, `[01_miniworld.md §N]`,
    `[02_business-rules.md §N]`, and `[03_use-cases.md §N]`
    for upstream specs; `[derived from …]` for anything inferred rather than read off.
    Flag `[derived from …]` items as potentially fragile.
  - **Chain-of-Thought.** Carry out the plan step by step: walk the miniworld
    end to end. For each central
    object → name the entity, list attributes (simple / composite / multivalued
    / stored / derived); the key is the surrogate `id`. For each pair of entities → name
    the relationship, decide cardinality and participation (justify each side),
    decide whether relationship attributes exist. Apply Steps 1–6 in order;
    each step's choice cites its rule (`data-model-anchor.md` LC-01 … LC-06).
    For every multivalued attribute, run the access test
    (`data-model-anchor.md` NC-02) and state the outcome explicitly. Run the normalization checks: 1NF over all
    columns, 2NF over join tables only, 3NF over all tables (state
    transitive-dependency checks where a candidate attribute looked
    suspicious). Surface unresolved items as Open Questions.

2. **Main answer — the spec.** Write `04_data-model.md` to the project's
   `docs/domain/` directory (or the equivalent location the runtime provides).
   Use the exact section skeleton from §6. Skeleton-completion is the structural
   stop (`prompt-engineering-anchor.md` AC-10); no closing remarks, no
   meta-commentary.

If `00_project-context.md` is missing or empty, stop and report
`BLOCKED — 00_project-context.md not found`.
If `01_miniworld.md` is missing or empty, stop and report
`BLOCKED — 01_miniworld.md not found`.
If `02_business-rules.md` is missing, stop and report
`BLOCKED — 02_business-rules.md not found`.
If `03_use-cases.md` is missing, stop and report
`BLOCKED — 03_use-cases.md not found`.
If a schema-expressible constraint cannot be mapped (an item has no entity to
attach to), surface it as an Open Question in §4 — do not invent the missing
entity.

---

> **Pipeline note.** Every `Business objects touched` entry that `03_use-cases.md`
> named must be **accounted for** in this file's §6 → §2.1 — but not necessarily
> as a table. A business object may become an entity/table, an attribute, an
> enum / value set, a relationship, a derived value, a document / file reference,
> an audit / log concept, or be explicitly recorded as `not persisted` with a
> one-line reason. A missing accounting decision is an Open Question (§4), not a
> silent gap, and not an automatic new table. The table list is the data-flow
> basis for `05-system-design-architect`'s system-of-record vs. derived-data
> decisions. Renaming a table downstream forces an update here first — names are
> stable from this point forward.