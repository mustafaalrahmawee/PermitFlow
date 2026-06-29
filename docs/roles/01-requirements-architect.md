# Role — Requirements Architect

## 1. Introduction

This is a **role file** in the project's methodology. It is consumed by a model
(single-pass chat or agentic runtime) together with **one anchor** and one
upstream spec, and produces a single named domain spec.

**Document type:** a structured-document role file
(per `prompt-engineering-anchor.md` AC-05). The deliverable is `01_miniworld.md`
— a practical **domain-discovery** document: a concise, business-language
description of the slice of reality the system supports, written **before** any
business-rule, data-model, use-case, system-design, or implementation decision
is made. It is a domain / business-requirements brief for a small product team.

**Focus:** name the **business objects** (the "nouns") the system must represent,
the **business processes** (the "verbs") it must support, and the **stakeholders**
(active users and external actors) involved — plus the assumptions, scope signals,
resolved tensions, and open questions the project context exposes — all in plain
language a non-technical reader can understand.

This role is the **second step** of the pipeline. Its input is
`00_project-context.md`; its output becomes indirect context for
`02-business-rules-architect` and every role file downstream
(`prompt-engineering-anchor.md` AC-07).

## 2. Binding Anchors (indirect context)

This role file is consumed together with **one anchor**:

- `prompt-engineering-anchor.md` — binds **how** this role file is written and
  how the model produces its output (four-part anatomy, section skeleton,
  reasoning preamble, vocabulary discipline). References look like
  `prompt-engineering-anchor.md AC-XX` or `prompt-engineering-anchor.md RC-XX`.

There is **no domain anchor** for this role. Instead, this role binds **what**
the deliverable contains through a practical method basis —
requirements-engineering and business-analysis principles — embedded as
application rules in §5. The deliverable name `miniworld` here means "the
real-world business slice the application supports."

This role does **not** rely on persona-priming ("You are an expert requirements
engineer"). Per `prompt-engineering-anchor.md` RC-01, output quality comes from
explicit inputs, the section skeleton, the rules and vocabulary, the validation
loop, and the reasoning preamble — not from a role label.

Section numbers written as plain `§N` always refer to **this** role file.

## 3. Inputs

Inputs are declared per `prompt-engineering-anchor.md` AC-06 / AC-07.

**Static (fixed in this role file):**

- The section skeleton for `01_miniworld.md` (§6).
- The application rules embedded from the practical method basis (§5).
- The vocabulary and forbidden-terms list reaffirmed for this stage (§7).

**Dynamic (filled at run time):**

- **Pre-loaded file:** `docs/domain/00_project-context.md` — the project context
  produced by `00-project-context-architect`. The model reads it whole and uses
  it as the seed for every section of the miniworld.

**Context classification (per `prompt-engineering-anchor.md` AC-07):**

- The prompt-engineering anchor is **indirect** (binding context).
- `00_project-context.md` is **indirect** (pipeline-composed upstream spec).
- The section skeleton and rules in this file are **boilerplate** (framing glue).

There is no direct user input at run time — the project context already captured
the user's intent.

## 4. What this role does and does not do

This role **does**:

- Describe the real-world **domain** the system supports, extracted from
  `00_project-context.md`: the slice of reality the system represents.
- Identify **stakeholders** — distinguishing **active users** (who log in and
  act) from **external actors** (who exist only as data sources or targets and
  never log in).
- Name the **business objects** (nouns) and **business processes** (verbs) at a
  high level — without fields, types, keys, scenarios, or use-case detail.
- Capture high-level **business rules and scope signals** that are already
  visible from the project context (decisions, limitations, invariants the
  context implies), without writing the formal `BR-XX` rules — those belong to
  `02-business-rules-architect`.
- Record **assumptions** that are reasonable for v1 but not confirmed by the
  project context, marked as such.
- Surface **tensions** in `00_project-context.md` (e.g. constraints that pull in
  opposite directions) and record explicit **Resolved Tensions** with one-line
  v1 reasons.
- Carry the user's **out-of-scope** items from `00_project-context.md` §7 into
  the final `Out of Scope` section, refining them with anything the analysis of
  the project context exposed.
- Keep **traceability**: state which content is directly derived, inferred, or
  assumed.

This role **does not**:

- Design data structures, schemas, storage, or technical architecture.
- Define entities, attributes, relationships, keys, tables, columns, indexes, or
  migrations — those belong to `04-data-modeler`.
- Define endpoints, routes, controllers, UI components, service classes, jobs,
  queues, or deployment.
- Write use cases, detailed scenarios, acceptance criteria, or UI flows — those
  belong to `03-use-case-architect`.
- Mention any technology, framework, library, deployment target, or product name
  (§7 Forbidden list).
- Invent objects or processes that are not implied by `00_project-context.md`.
  When something is implied but unclear, it goes into Assumptions or Open
  Questions, not into the body of the miniworld as fact.

## 5. Application Rules (embedded from the practical method basis)

This role is based on practical requirements-engineering and business-analysis
principles: understand stakeholders, separate business objects from business
processes, surface assumptions instead of hiding them, document scope boundaries
explicitly, detect and resolve tensions for v1, and stay
implementation-independent.

- **Practicality.** Write the document as if a small product team will use it
  before implementation starts. It should help developers understand the
  business domain.
- **Source discipline.** Every important statement is one of four kinds, and the
  kind is traceable (recorded in §6 Traceability Notes and surfaced in the
  reasoning preamble):
  - **Directly derived** — clearly stated in `00_project-context.md`.
  - **Inferred** — strongly implied by the project context.
  - **Assumption** — reasonable for v1, but not confirmed.
  - **Open question** — cannot be safely decided from available context.
- **Non-technical communication.** Use plain business language. A reader with no
  technical background must understand `01_miniworld.md` on a single read.
- **Scope control.** Do not expand v1. If a useful feature is not necessary for
  the first version, place it in `Out of Scope (v1)` or `Open Questions`.
- **No implementation leakage.** No technologies, frameworks, infrastructure,
  API design, data-design details, or code-level concepts.
- **Conflict detection.** When two requirements pull in opposite directions
  (e.g. "traceable decisions" vs. "single-developer portfolio scope"), surface
  the tension and record a v1 resolution.
- **Domain validation loop.** Before finalising, check: every business object is
  used by at least one business process; every business process acts on at least
  one business object; every active user has at least one reason to use the
  system; every scope cut is listed only once; every unresolved uncertainty
  appears in Open Questions.
- **Length discipline.** Concise, detailed, complete. No marketing prose, no
  repetition, no padding. Thinness here propagates to every later phase.

## 6. Section Skeleton (structural stop)

The deliverable `01_miniworld.md` is complete when the following section
sequence is filled (`prompt-engineering-anchor.md` AC-09 — four-part anatomy
with focus-opener recursion; AC-10 — structural stop; AC-11 — section-skeleton
template):

```markdown
# Miniworld — <Project Name>

## 1. Domain Overview

<2–4 paragraphs of plain language. The real-world domain, the problem being
handled, the purpose of the system, and for whom.>

## 2. Stakeholders and Actors

### Active Users

- **<role>** — <what they do in the system and why, one or two sentences>
- …

### External Actors

- **<actor>** — <how the system knows about them; never logs in>
- _None._ if not applicable.

## 3. Business Objects (the "nouns")

- **<object>** — <what it represents in the business domain, one sentence>
- …

## 4. Business Processes (the "verbs")

- **<process>** — <what happens and which business object(s) it acts on, one sentence>
- …

## 5. Business Rules and Scope Signals

- **<rule or signal>** — <high-level rule, limitation, or decision visible from the project context>
- …

## 6. Assumptions

- **<assumption>** — <why this is assumed for v1>
- _None._ if not applicable.

## 7. Resolved Tensions

- **<tension>** — <one-line v1 resolution>
- _None._ if not applicable.

## 8. Out of Scope (v1)

- <item>
- …

## 9. Open Questions

- <question that survived the analysis of the project context>
- _None._ if not applicable.

## 10. Traceability Notes

- **Primary source:** `00_project-context.md`
- **Directly derived:** <short list>
- **Inferred:** <short list>
- **Assumptions needing validation:** <short list>
```

The order is fixed. Empty sections must contain `_None._`.

## 7. Binding Vocabulary

Reaffirmed at production time:

**Use these terms:**

- `domain`, `miniworld`
- `stakeholder`, `actor`, `active user`, `external actor`
- `business object`, `business process`
- `business rule`, `scope signal`
- `assumption`, `resolved tension`, `tension`
- `scope boundary`, `out of scope`, `v1`
- `open question`, `traceability`

**Forbidden** — any occurrence means the file has drifted into a later phase:

- Conceptual-schema syntax: `entity`, `attribute`, `relationship`, `cardinality`,
  `weak entity`, `1:N`, `M:N`.
- Logical-schema syntax: `table`, `column`, `primary key`, `foreign key`, `index`,
  `migration`, `SQL`, `JSON`, normal forms.
- Application handles: `endpoint`, `route`, `controller`, `UI component`,
  `service class`, `job`, `queue`, `HTTP request`, `HTTP response`.
- Implementation: framework names, ORM terms (`Eloquent`, `model`, `migration`),
  product names (`Postgres`, `Redis`, `Meilisearch`, `Vue`, `Pinia`, `Kafka`),
  infrastructure / deployment product names.
- Performance: `p95`, `p99`, `latency`, `throughput`.
- Use-case-level detail: `precondition`, `trigger`, `main success scenario`,
  `extension`, `BR-XX`, `UC-XX`.

_(A line may **describe** a process — "citizens submit requests" — without ever
naming an endpoint, query, or framework. The boundary is syntax and product
names, not topic.)_

**Important exception — the word `request`.** The business word `request` is
allowed when it means a citizen request in the domain (e.g. PermitFlow). Only
technical phrases such as `HTTP request` are forbidden.

## 8. Refocus

Before producing the spec, restate the task:

The deliverable is `01_miniworld.md`, structured exactly per §6, derived from
`00_project-context.md`, written for a non-technical reader, and free of every
forbidden term in §7. Every named business object has a business process that
uses it; every named business process has a business object it acts on (the
validation loop — §5). Every important statement is directly derived, inferred,
or marked as an assumption.

No fact appears in more than one section: each item (object, process, tension,
scope cut) is stated once, in the section where it best belongs, and is
referenced — not repeated — elsewhere. A Resolved Tension states its resolution
without re-listing the Out-of-Scope items already in §8.

## 9. Transition — Produce

You will now produce two artifacts in this order, per
`prompt-engineering-anchor.md` AC-15:

1. **Reasoning preamble** (in your output stream, not in the file):
  - **Inner Plan.** First understand the input and devise a plan: identify
    the §6 sections to fill, and mark for each piece of content whether it is
    **directly derived** from `00_project-context.md`, **inferred** from it, or
    an **assumption** (and therefore potentially fragile). Name the
    validation-loop check you will run before finalising — every business object
    has a business process, every business process has a business object.
    Surface any apparent tensions in the project context that will need a
    Resolved Tensions entry.
    Tag every plan item with its source so the reader can audit where each
    decision came from. Use one of these forms:
    `[01-requirements-architect.md §N]` for this role file (e.g.
    `[01-requirements-architect.md §5]` when citing an application rule);
    `[00_project-context.md §N]` for the project context;
    `[derived from …]` for anything inferred rather than read off.
    Flag `[derived from …]` items as potentially fragile.
  - **Chain-of-Thought.** Carry out the plan step by step: walk the project
    context end to end. Each time you name a stakeholder, active user, external
    actor, business object, or business process, state which sentence of
    `00_project-context.md` it came from and classify it as directly derived,
    inferred, or assumption. When you promote a project-context "out of scope"
    item, paraphrase if needed. Run the validation loop explicitly: list each
    business object → name a business process that uses it; list each business
    process → name a business object it acts on. Note mismatches and either add
    a missing entry or surface an Open Question. Record each surfaced tension
    with its one-line v1 resolution.

2. **Main answer — the spec.** Write `01_miniworld.md` to the project's
   `docs/domain/` directory (or the equivalent location the runtime provides).
   Use the exact section skeleton from §6. Skeleton-completion is the structural
   stop (`prompt-engineering-anchor.md` AC-10); no closing remarks, no
   meta-commentary.

If `00_project-context.md` is missing or empty, stop and report
`BLOCKED — 00_project-context.md not found`.

---

> **Pipeline note.** `01_miniworld.md` is consumed by
> `02-business-rules-architect` as the **substance source for business
> rules** — its §3 Business Objects, §4 Business Processes, and §5 Business Rules
> and Scope Signals are the seedbed for every later phase, so thinness here
> propagates. `04-data-modeler` must read these section names — business objects as
> data-model inputs that may become entities/tables, attributes, enum/value
> sets, relationships, derived values, document/file references, audit/log
> concepts, or explicitly not persisted; business processes as relationship and
> constraint signals — so confirm it is aligned with this skeleton before
> `04_data-model.md` is generated.