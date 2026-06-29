# Curated Anchor — Use Cases

## 1. Source Basis

- Cockburn, Alistair, _Writing Effective Use Cases_ (2001), **Chapter 1**
  (§1.1–§1.6). The single source for this anchor.
- **Not** based on Elmasri/Navathe. An earlier plan considered Ch. 6 (Basic SQL)
  as a data-operation grid, but the use-case file is narrative, not SQL; the only
  domain link is the `Business objects touched` field, kept as a `[P]` convention.
- Full derivation / reading notes: `docs/anchor-sources/use-cases.md`.

## 2. Provenance Legend

- `[K Cb 1.x]` = Direct concept from Cockburn, Chapter 1, section x.
- `[A]` = Agent / author suggestion (no direct source).
- `[P]` = Pure Project decision (applies to all my projects).

## 3. Purpose

This anchor defines what a **use case** is and binds the vocabulary, structure,
stance, and **domain-grouped organization** for any `03_use-cases.md` file. A use
case is a **contract between stakeholders** about how the system under discussion
(SuD) behaves in response to a primary actor's goal, while protecting the
interests of _all_ stakeholders. [K Cb 1.1]

It is the phase that describes **what actually happens** (and whose interests must
be protected) — distinct from the earlier phases that describe what merely _exists_. [P]

## 4. Accepted Concepts — Cockburn Core (Ch. 1) + Project Organization

| #     | Concept                            | Provenance | Rule for `03_use-cases.md`                                                                                                                                                                                                                                                                                                                                      |
| ----- | ---------------------------------- | ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| UC-01 | Use case = stakeholder contract    | [K Cb 1.1] | A use case describes how the SuD responds to a primary actor's goal while protecting all stakeholders' interests. It bundles several possible scenarios.                                                                                                                                                                                                        |
| UC-02 | The three things to keep separate  | [K Cb 1.1] | Every use case must state its **Scope**, **Primary Actor**, and **Level**. These three are the hardest part and must never be left implicit.                                                                                                                                                                                                                    |
| UC-03 | Actor                              | [K Cb 1.1] | Anyone or anything that exhibits behavior (person, external system, hardware).                                                                                                                                                                                                                                                                                  |
| UC-04 | Stakeholder                        | [K Cb 1.1] | Someone/something with a legitimate interest in the SuD's behavior.                                                                                                                                                                                                                                                                                             |
| UC-05 | Primary Actor                      | [K Cb 1.1] | The stakeholder who initiates the interaction to achieve a goal.                                                                                                                                                                                                                                                                                                |
| UC-06 | Scope                              | [K Cb 1.1] | Defines which system we are talking about (where the boundary is drawn).                                                                                                                                                                                                                                                                                        |
| UC-07 | Level                              | [K Cb 1.1] | The goal's altitude: Summary (☁️ above sea level), User-Goal (🌊 sea level), Subfunction (🤿 below). A user goal is achieved in one sitting.                                                                                                                                                                                                                    |
| UC-08 | Preconditions                      | [K Cb 1.1] | What must be true _before_ the use case starts.                                                                                                                                                                                                                                                                                                                 |
| UC-09 | Guarantees                         | [K Cb 1.1] | What is true _after_: minimal guarantee on failure, success guarantee on success.                                                                                                                                                                                                                                                                               |
| UC-10 | Trigger                            | [K Cb 1.1] | The event that starts the use case.                                                                                                                                                                                                                                                                                                                             |
| UC-11 | Main Success Scenario              | [K Cb 1.1] | The ideal path: nothing fails, the goal is reached.                                                                                                                                                                                                                                                                                                             |
| UC-12 | Extensions                         | [K Cb 1.1] | Alternative paths, errors, special cases. Numbered against the main-scenario step where the deviation occurs (e.g. `4a`).                                                                                                                                                                                                                                       |
| UC-13 | One template, variable depth       | [K Cb 1.2] | Keep one project-wide template; fill it deeply for high-risk use cases, shallowly for low-risk ones.                                                                                                                                                                                                                                                            |
| UC-14 | Value moment 1 — the list          | [K Cb 1.4] | Simply naming and listing the use cases (user goals) defines scope and enables estimation and prioritization.                                                                                                                                                                                                                                                   |
| UC-15 | Value moment 2 — failure scenarios | [K Cb 1.4] | The greatest value comes from brainstorming what can go wrong — this is where hidden business rules and missing stakeholders are discovered.                                                                                                                                                                                                                    |
| UC-16 | Usage narrative                    | [K Cb 1.6] | A situated, highly specific example of one (fictional but concrete) actor using the system, capturing their motive/mental state. It is **not** a use case; the use case is its "dried-out", generic form. The narrative **anchors** the use case. It need not survive into the final document.                                                                  |
| UC-17 | Domain-grouped organization        | [P]        | The main body of the file is grouped by **domain**, never by actor. Domain names match `02_business-rules.md` exactly and reflect the domains implied by `01_miniworld.md`. Actor information lives **inside** each UC as `Primary Actor` / `Supporting Actors` metadata. UC IDs are globally sequential across all sections and do **not** restart per domain. |

## 5. Rejected / Out-of-Scope

| #     | Concept                                                                                     | Provenance      | Why excluded                                                                                                                                                              |
| ----- | ------------------------------------------------------------------------------------------- | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RC-01 | Elmasri Ch. 6 SQL operation grid                                                            | [P]             | The file is narrative; `Business objects touched` is enough to link to the domain.                                                                                              |
| RC-02 | Actual SQL (SELECT/INSERT/UPDATE/DELETE text)                                               | [P]             | Describe the operation; the SQL is downstream.                                                                                                                            |
| RC-03 | Miniworld / actors-at-large description                                                     | [K]             | `01_miniworld.md`                                                                                                                                                  |
| RC-04 | Data model / tables / keys                                                                  | [K]             | `data-model-anchor.md`                                                                                                                                                    |
| RC-05 | Application business rules themselves                                                       | [K]             | `business-rules-anchor.md` — referenced by ID in notes, not redefined                                                                                                     |
| RC-06 | Cockburn's deeper chapters (goal-level craft, extension-writing technique, scope mechanics) | [K Cb] **debt** | **Deliberately not yet distilled.** Ch. 1 carries the concepts; later chapters deepen the craft. To be added on demand if the generated output shows a concrete weakness. |
| RC-07 | Actor-based grouping (`Use Cases for Candidate` etc.)                                       | [P]             | Replaced by domain grouping (UC-17). Actor information lives as metadata inside each UC, not as a section heading.                                                        |

## 6. Application Rules (How to apply these concepts to any project)

- **State the three always.** Every use case names Scope, Primary Actor, Level —
  never implicit. [K Cb 1.1]
- **Separate the success path from the extensions.** A use case has one Main
  Success Scenario plus numbered Extensions; failure cases live in the extensions,
  not tangled into the happy path. [K Cb 1.1]
- **One template, variable depth.** Same fields everywhere; depth scales with the
  use case's risk. [K Cb 1.2]
- **Mine the extensions.** Treat failure scenarios as the place to surface hidden
  business rules; reference them by `BR-XX`, do not invent them here. [K Cb 1.4]
- **Warm up with a usage narrative.** Before drafting a high-risk or unclear use
  case, sketch a brief vignette: one fictional but specific actor, their motive /
  mental state, the situation from start to end. Brevity matters (the story at a
  glance); motive matters (shows what the system must optimize for). The
  narrative anchors the use case — the use case is its dried-out, generic form —
  and need not survive into the document. [K Cb 1.6]
- **Group by domain, not by actor.** The file is organized into domain sections
  matching `02_business-rules.md`. Actor information lives inside each UC as
  metadata. UC IDs stay globally sequential across sections. (UC-17) [P]
- **Link to the domain, don't write SQL.** Each use case ends with
  `Business objects touched` naming the business objects from `01_miniworld.md` it
  reads or writes; `04_data-model.md` later accounts for these. [P]

## 7. Binding Vocabulary

**Use these terms** — the controlled vocabulary of this anchor:

- `use case`, `scenario`, `system under discussion (SuD)` [K Cb 1.1]
- `actor`, `stakeholder`, `primary actor`, `supporting actor` [K Cb 1.1]
- `scope`, `level` (summary / user-goal / subfunction) [K Cb 1.1]
- `precondition`, `guarantee` (minimal / success), `trigger` [K Cb 1.1]
- `main success scenario`, `extension` [K Cb 1.1]
- `usage narrative` [K Cb 1.6] _(the concept — a situated example that anchors a use case)_
- `UC-XX`, `Business objects touched` [P]
- `domain`, `domain-grouped`, `<Domain Name> Domain` (section heading) [P]

**Forbidden** — any occurrence means the file has drifted into another phase:

- SQL/DDL syntax: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `JOIN`, `WHERE`
- Data-model design: `column`, `foreign key`, `index`, normal forms
- Implementation: framework names, function/class names, endpoints
- Actor-based section headings: `Use Cases for Candidate`, `Use Cases for Admin`
  _(actors live inside each UC as metadata; see UC-17)_

_(A use case may **name** a business object in `Business objects touched` and may **reference** a rule
by `BR-XX`; it never writes the SQL or restates the rule. The boundary is: describe
the behavior and what it touches, never implement it.)_

## 8. Role Usage Rule

The role file (e.g. `03-use-case-architect.md`) consumes this anchor as **binding
context**. Cockburn Chapter 1 is background knowledge, distilled in
`docs/anchor-sources/use-cases.md`; the book itself is not the direct prompt source.

Hard rules for every generated `03_use-cases.md`:

1. Every use case states Scope, Primary Actor, and Level explicitly. [K Cb 1.1]
2. Every use case has one Main Success Scenario plus numbered Extensions; failure
   cases live in the extensions, not in the happy path. [K Cb 1.1]
3. One template, depth scaled to risk; "sufficient" beats "perfect". [K Cb 1.2]
4. Each use case links to the domain via `Business objects touched` (business objects
   from `01_miniworld.md`, later accounted for in `04_data-model.md`) and references
   business rules by `BR-XX`; it writes no SQL and restates no rule. [P]
5. The file is grouped by domain (UC-17), with domain names matching
   `02_business-rules.md` exactly. Actors appear inside each UC as metadata,
   never as section headings. UC IDs stay globally sequential across sections. [P]

---

> **Honest-debt note.** This anchor rests on Cockburn **Chapter 1 only** (§1.1–1.6),
> which carries the use-case concepts. The deeper chapters (goal-level craft,
> extension-writing technique, scope mechanics) are **deliberately not yet
> distilled** — they deepen the craft rather than add concepts. If generated output
> later shows a concrete weakness (e.g. thin extensions), read the matching chapter
> and extend this anchor. The marker stays until then.
