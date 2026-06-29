# Curated Anchor — Data Model (Conceptual + Logical Schema)

## 1. Source Basis

- Elmasri / Navathe, _Fundamentals of Database Systems_ (7th Edition, Global Edition):
  - **Ch. 3** (ER modeling) — §3.3 Entities/Attributes/Keys, §3.4 Relationships/Roles/Structural Constraints, §3.4.4 Attributes of Relationship Types, §3.5 Weak Entity Types.
  - **Ch. 9** §9.1.1 — ER-to-Relational Mapping Algorithm, Steps 1–6.
  - **Ch. 14** — §14.2 Functional Dependencies (support concept only), §14.3.4 1NF, §14.3.5 2NF, §14.3.6 3NF.
- This anchor covers **two phases in one file**: the Conceptual Schema (Ch. 3) and the Logical Schema / mapping + normalization (Ch. 9, Ch. 14). It stops before implementation: **no migration code, no SQL/DDL syntax, no framework.** [P]

## 2. Provenance Legend

- `[K x.x]` = Direct concept / paraphrase from a book the author has read.
- `[K x.x → P]` = Book concept, sharpened or operationalized by a **Project decision** (applies to all my projects).
- `[K x.x → A]` = Book concept, interpreted or structured by an **Agent suggestion**.
- `[A]` = Agent suggestion (no read source).
- `[P]` = Pure Project decision (applies to all my projects, not just one).

## 3. Purpose

This anchor defines the **data-modeling phase** and binds the vocabulary for any
`04_data-model.md` (data model) file. It produces two consecutive, clearly separated blocks:

1. **Conceptual Schema** — entities, attributes, relationships, cardinality,
   participation, weak entities. Described **in prose**, not as a diagram. [P]
2. **Logical Schema** — the conceptual model mapped to tables (columns, keys,
   foreign keys, join tables) and normalized to 3NF. Described precisely enough
   that a migration follows trivially — but **described, not written as code**. [P]

The boundary: the data model ends at the table description. The migration itself
(the actual table-creation code) is the implementation phase and lives downstream. [P]

## 4. Conceptual Block — Accepted Concepts (Ch. 3)

| #     | Concept                                    | Provenance  | Rule for `04_data-model.md`                                                                                                                                                                                                                  |
| ----- | ------------------------------------------ | ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CC-01 | Entity                                     | [K 3.3]     | A thing of reality with independent existence; becomes a table candidate.                                                                                                                                                                    |
| CC-02 | Attribute                                  | [K 3.3]     | A property describing an entity; becomes a column candidate.                                                                                                                                                                                 |
| CC-03 | Attribute types                            | [K 3.3]     | simple vs. composite; single-valued vs. **multivalued**; stored vs. derived. Multivalued attributes drive the table-vs-JSON decision (see LC-06).                                                                                            |
| CC-04 | NULL values                                | [K 3.3]     | An attribute may be unknown or not applicable; becomes nullability.                                                                                                                                                                          |
| CC-05 | Key attribute                              | [K 3.3 → P] | Book: an attribute uniquely identifying an entity. Project rule: every table uses a surrogate `id` as primary key (bigint, auto-increment).                                                                                                  |
| CC-06 | Value set / domain                         | [K 3.3]     | The set of allowed values for an attribute; named here, enforced in the logical block.                                                                                                                                                       |
| CC-07 | Relationship type / set                    | [K 3.4]     | An association between entities. Stated generically (no project-specific entity names in the anchor).                                                                                                                                        |
| CC-08 | Roles                                      | [K 3.4]     | The function an entity plays in a relationship; required for **recursive** (self-referential) relationships where one entity appears twice.                                                                                                  |
| CC-09 | Structural constraints — cardinality ratio | [K 3.4]     | 1:1, 1:N, M:N — how many entities participate on each side.                                                                                                                                                                                  |
| CC-10 | Structural constraints — participation     | [K 3.4]     | total vs. partial participation (existence dependency); total participation later implies a non-null foreign key.                                                                                                                            |
| CC-11 | Relationship attributes + migration rules  | [K 3.4.4]   | A relationship may have its own attributes. Their location depends on cardinality: 1:1 → either side; 1:N → the N-side; M:N → stays on the relationship (becomes a join table). The formal execution is in the Logical Block (LC-04, LC-05). |
| CC-12 | Weak entity type                           | [K 3.5]     | An entity that cannot exist without its owner; identified by a partial key plus the owner's identity.                                                                                                                                        |

**Representation rule:** the conceptual schema is expressed **in prose** — e.g.
"Entity A has a 1:N relationship to B, with total participation on the B-side."
No diagram notation (no Mermaid, no Chen). Tool-neutral by deliberate choice. [P]

## 5. Logical Block — Accepted Concepts (Ch. 9 §9.1.1, Steps 1–6)

| #     | Step                            | Provenance    | Rule for `04_data-model.md`                                                                                                                                                                                                                           |
| ----- | ------------------------------- | ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| LC-01 | Step 1 — Regular entity → table | [K 9.1.1 → P] | Each strong entity becomes a table; simple attributes become columns. Book: a key attribute becomes the primary key. Project rule: use a surrogate `id` (see CC-05).                                                                                  |
| LC-02 | Step 2 — Weak entity → table    | [K 9.1.1 → P] | The weak entity becomes a table carrying the owner's key as a foreign key. Book: composite primary key (owner FK + partial key). Project rule: keep a surrogate `id` as PK and enforce the owner FK + partial-key combination as a unique constraint. |
| LC-03 | Step 3 — Binary 1:1             | [K 9.1.1]     | Place the foreign key on the side with total participation (the existence-dependent side), as a unique foreign key.                                                                                                                                   |
| LC-04 | Step 4 — Binary 1:N             | [K 9.1.1]     | The N-side carries a foreign key to the 1-side. Formal execution of migration rule CC-11 (1:N → N-side).                                                                                                                                              |
| LC-05 | Step 5 — Binary M:N             | [K 9.1.1]     | A separate join table with both foreign keys; relationship attributes become columns of that table. Formal execution of migration rule CC-11 (M:N → join table).                                                                                      |
| LC-06 | Step 6 — Multivalued attribute  | [K 9.1.1]     | **Default:** a multivalued attribute becomes its own table, foreign-keyed to the owner. The deliberate exception is governed by the 1NF trade-off (NC-02).                                                                                            |

**Foreign-key naming convention:** `<entity>_id`. Every table has `id`,
`created_at`, `updated_at`. [P]

## 6. Logical Block — Normalization (Ch. 14, up to 3NF only)

| #     | Concept                                 | Provenance     | Rule for `04_data-model.md`                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| ----- | --------------------------------------- | -------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NC-00 | Functional dependency (support concept) | [K 14.2]       | X determines Y if every X-value fixes exactly one Y-value. Minimal definition only — **no** formal apparatus (no Armstrong axioms, no closure, no inference proofs).                                                                                                                                                                                                                                                                                                             |
| NC-01 | First Normal Form (1NF)                 | [K 14.3.4]     | Every attribute value is atomic — no multivalued attributes, nested relations, or repeating groups in one column.                                                                                                                                                                                                                                                                                                                                                                |
| NC-02 | 1NF trade-off (the JSON exception)      | [K 14.3.4 → P] | **Access test.** If a value must be filtered, searched, grouped, or joined (`WHERE` / `JOIN` / aggregation), it gets its own table (LC-06) — JSON columns index and join poorly. If a value is only read/written as a whole block (raw-data snapshot, config, a list always shown in full and never queried per-element), a JSON array is allowed, with an explicit trade-off note at that column. Once a value is queried per-element, the exception lapses and Step 6 applies. |
| NC-03 | Second Normal Form (2NF)                | [K 14.3.5 → P] | Every non-key attribute fully depends on the whole primary key — no partial dependency on part of a composite key. Project scope note: because tables use a surrogate single-column `id`, 2NF is automatically satisfied in normal tables; it becomes relevant **only for join tables** (LC-05), which have composite keys.                                                                                                                                                      |
| NC-04 | Third Normal Form (3NF)                 | [K 14.3.6]     | No non-key attribute transitively depends on the key (no non-key attribute determines another non-key attribute). If two attributes belong to a different concept, they move to their own table. **3NF is the ceiling.**                                                                                                                                                                                                                                                         |

## 7. Rejected Concepts (Skip List)

| #     | Concept                                          | Provenance                   | Belongs to / Why excluded                                                                                                                                                                                                                                 |
| ----- | ------------------------------------------------ | ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RC-01 | Requirements / miniworld description             | [K 3.1]                      | `01_miniworld.md`                                                                                                                                                                                                                                  |
| RC-02 | Application-based business rules                 | [K 5.2]                      | `business-rules-anchor.md`                                                                                                                                                                                                                                |
| RC-03 | ER diagram notation (Chen / Mermaid / any)       | [P] excluded                 | Project decision (prose, tool-neutral) made up front; the book has a section on ER-diagram notation (§3.7), but it was never read or distilled because the decision made it irrelevant. Conceptual schema is expressed in prose (CC representation rule). |
| RC-04 | Higher-degree / ternary relationships (Step 7+)  | [K 3.8 / 9.1.1 → P] excluded | Decided during distillation: no conceptual counterpart (§3.8) was adopted, so the matching mapping step is excluded too.                                                                                                                                  |
| RC-05 | Refining ER design, naming conventions (§3.6)    | [P] excluded                 | Project decision; §3.6 was not read or distilled. Project naming conventions are `[P]` (see §5/§8).                                                                                                                                                       |
| RC-06 | FD formal apparatus (axioms, closure, inference) | [K 14] excluded              | Only the minimal FD definition (NC-00) is kept.                                                                                                                                                                                                           |
| RC-07 | BCNF, 4NF, 5NF                                   | [K 14] excluded              | Beyond the 3NF ceiling.                                                                                                                                                                                                                                   |
| RC-08 | Physical design (indexes, storage, access paths) | [K]                          | `system-design-anchor.md`                                                                                                                                                                                                                                 |
| RC-09 | Migration code / SQL-DDL syntax / framework      | [P]                          | Implementation phase, downstream.                                                                                                                                                                                                                         |

## 8. Application Rules (How to apply these concepts to any project)

- **Two ordered blocks.** Write the Conceptual Schema first (prose: entities,
  attributes, relationships, cardinality, participation, weak entities), then the
  Logical Schema (tables, columns, keys, foreign keys, join tables), then verify
  normalization to 3NF. [P]
- **Map by the algorithm.** Apply Steps 1–6 (LC-01…LC-06) in order. 1:N → FK on the
  N-side; M:N → join table; weak entity → owner FK + partial-key uniqueness. [K 9.1.1]
- **Run the access test on every multivalued attribute.** Queried per-element →
  own table. Read/written only as a whole block → JSON allowed with a trade-off
  note. (NC-02) [K 14.3.4 → P]
- **Check 2NF only on composite-key (join) tables.** Elsewhere the surrogate `id`
  makes it automatic. (NC-03) [K 14.3.5 → P]
- **Stop at 3NF.** No BCNF or higher. A deliberate denormalization (raw data, audit
  trail, read performance) is allowed only with a one-line reason at the table. [K 14.3.6 → P]
- **Describe, never code.** Columns with type and nullability, keys, constraints —
  all as description. No migration code, no DDL syntax. The migration is downstream. [P]

## 9. Binding Vocabulary

**Use these terms** — the controlled vocabulary of this anchor:

- Conceptual: `entity`, `attribute`, `multivalued attribute`, `relationship type`, `role`, `cardinality ratio` (1:1 / 1:N / M:N), `participation` (total / partial), `relationship attribute`, `weak entity`, `partial key` [K 3.x]
- Logical: `table`, `column`, `primary key`, `foreign key`, `join table`, `surrogate id`, `unique constraint`, `nullability` [K 9.1.1 / P]
- Normalization: `functional dependency`, `atomic value`, `1NF`, `2NF`, `3NF`, `transitive dependency`, `1NF trade-off` [K 14]

**Forbidden** — any occurrence means the file has drifted into implementation:

- DDL/migration syntax: `CREATE TABLE`, `$table->...`, `ALTER TABLE`, raw SQL statements
- ORM/framework terms: `Eloquent`, `migration`, `model class`, `Schema::`
- Normal forms beyond the ceiling: `BCNF`, `4NF`, `5NF`
- Diagram notation: `Mermaid`, `erDiagram`, Chen-notation symbols
- Physical-design terms: `index type`, `access path`, `storage engine` _(belong to `system-design-anchor.md`)_

_(Table and key concepts may be **described** in domain language — "this table has a
foreign key to users" is fine; writing the DDL for it is not. The boundary is
syntax and code, not topic.)_

## 10. Role Usage Rule

The role file (e.g. `04-data-modeler.md`) consumes this anchor as **binding
context**. The book chapters are background knowledge, not the direct prompt source.

Hard rules for every generated `04_data-model.md`:

1. Two ordered blocks — Conceptual (prose) then Logical (tables) — never merged.
2. Every relationship is mapped by Steps 1–6; the access test (NC-02) decides
   table-vs-JSON for every multivalued attribute.
3. Every table is in 3NF, or carries a one-line note justifying a deliberate
   trade-off. 2NF is checked on join tables only.
4. The file describes the schema; it never contains migration code or DDL syntax.
   If a line could be pasted into a migration file and run, it does not belong here. [K 9.1.1 / K 14 → P]

---

> **Cross-anchor note.** The migration rules in CC-11 (Ch. 3.4.4) and the mapping
> steps LC-04/LC-05 (Ch. 9) describe the same truth in two phases; the conceptual
> entry states the principle, the logical entry executes it. They are intentionally
> cross-referenced, not duplicated, to keep provenance clean.
