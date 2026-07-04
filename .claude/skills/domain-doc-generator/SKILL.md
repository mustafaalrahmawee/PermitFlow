---
name: domain-doc-generator
description: >-
  Context-compiler for one domain of a project built on the docs/domain/ spec
  set. Reads the finished domain specs in docs/domain/ (00…05) and, for the
  domain named in the argument, produces one self-contained per-use-case
  contract at docs/by-use-case/ucNN_<slug>.md for every use case in that
  domain. It slices
  and references the specs — it never authors new requirements. Each contract is
  the single self-contained context pack a per-use-case session reads. The
  domains and their use cases are read at run time from the `## <Name> Domain`
  headings in 03_use-cases.md, not fixed in this skill. Invoke explicitly as
  /domain-doc-generator <domain name>.
argument-hint: <domain name>
disable-model-invocation: true
allowed-tools: Read, Edit, Write, Bash
---

# Skill — Domain Doc Generator (run per domain)

## 1. Introduction

This is a **context-compiler skill** — a downstream skill, like `foundation`,
that consumes the finished domain specs and produces working artifacts rather
than a spec. Where the upstream role files in `docs/roles/` author the specs,
this skill **slices** them: for one named domain it compiles the scattered facts
(use case, business rules, data model, system design) into per-use-case briefs.

**What it produces**, for the domain named in the argument:

- one **per-use-case contract** at `docs/by-use-case/ucNN_<slug>.md` for every
  use case in that domain — the self-contained context pack a session reads
  instead of re-opening all six specs. There is **no per-domain coordinator
  file**: build order and cross-domain prerequisites live in each contract's
  Dependencies section, so an implementing session loads only the context its
  one use case needs (`prompt-engineering-anchor.md` AC-08).

**Focus:** fidelity and brevity. Every fact in a contract is referenced from a
spec by id, never restated or invented; the contract carries only the slice the
session needs, so unused context cannot mislead it. The thin implementation seam
the contract adds (endpoint names, expected responses,
status/history/notification mapping, dependencies) is marked `[derived …]` so a reader can tell compiled fact from
inference.

This skill is **project-neutral**: it works for any project whose specs follow
the `docs/domain/` set (`00`…`05`). It hard-codes no domain names, use cases, or
rule ids; it reads them from the specs at run time. The examples in §5 and §6
are written as patterns (`UC-NN`, `BR-XX`, `<Enum>`), not as facts about any one
project.

This skill runs **once per domain**. It carries `disable-model-invocation` so it
runs only on an explicit `/domain-doc-generator <domain>`, because compiling a
domain on a loose keyword match would surprise the developer.

Section numbers written as plain `§N` refer to **this** skill.

## 2. Binding context

**Authoritative specs (indirect context, pipeline-composed):**

- `docs/domain/03_use-cases.md` — the use cases themselves: per-UC actors,
  trigger, preconditions, main flow, extensions, guarantees, business objects,
  business rules, notes. The `## <Name> Domain` headings define which UCs belong
  to which domain, and so which UCs the named domain owns.
- `docs/domain/02_business-rules.md` — the BRs a contract references by id
  (authorization, status set, outcomes, traceability).
- `docs/domain/04_data-model.md` — maps each "Business object touched" to its
  table and enum.
- `docs/domain/05_system-design.md` — §3 per-UC performance targets, §4
  reliability (must-not-fail-silently vs. may-degrade).
- `docs/domain/00_project-context.md`, `01_miniworld.md` — domain purpose and
  scope when a contract needs it.
- `docs/conventions.md` — the foundation conventions a session inherits (the enum set, the
  status guard, the policies/gates, the auth model), as emitted by the project's
  foundation skill.

When a contract would state a requirement no spec supports, the run **stops and
reports the conflict** rather than inventing it, because the contracts must stay
a faithful projection of `docs/domain/`.

Citations use the short form: `[03_use-cases.md UC-NN]`,
`[02_business-rules.md BR-NN]`, `[04_data-model.md §2.1]`,
`[05_system-design.md §3]`, `[domain-doc-generator §N]`, and `[derived from …]`
for anything inferred rather than read off.

## 3. Inputs

Inputs are declared static vs. dynamic per `prompt-engineering-anchor.md`
AC-06 / AC-07.

**Direct (from the developer):** the `<domain name>` argument — resolved against
the domain headings the skill discovers in `03_use-cases.md` (§4).

**Static (fixed in this skill):** the output skeleton (§6) and the
domain-resolution rule (§4).

**Dynamic (read at run time):** the `docs/domain/*` specs and `docs/conventions.md` in §2,
including the domain → use-case map, which is read from the headings of `03`.

**Classification:** the argument is **direct**; the specs and conventions are
**indirect**; the skeletons and the resolution rule are **boilerplate**.

## 4. What this skill does and does not do

The domains and their use cases are **not fixed in this skill** — they are read
at run time from `03_use-cases.md`: each `## <Name> Domain` heading names a
domain, and the `UC-NN` entries beneath it are that domain's use cases. The
argument is resolved against those headings. Sections that are not domain
listings — cross-cutting rule sections, representation or appendix sections,
anything flagged with a `NON-DOMAIN` marker in the specs — are **not** treated as
domains.

This skill **does**:

- Resolve the argument to a `## <Name> Domain` heading in `03_use-cases.md` and
  collect that domain's use cases.
- Write one self-contained contract per use case (§6).
- Reference every business rule, table, enum, target, and reliability point by
  id; map each "Business object touched" to its `04` table/enum.
- Render each extension in `03` as a checkable acceptance item, since the
  extensions are the behaviors a session's QA loop verifies — and map each item
  to its expected HTTP response and DB effect in the contract's QA map (§6),
  tagged `[derived …]`.
- Add the implementation seam (endpoints, status/history/notification mapping,
  dependencies) as `[derived …]` items, flagged fragile.

This skill **does not**:

- Author requirements, statuses, outcomes, or rules absent from the specs; a
  gap is surfaced as an Open Question, not filled.
- Restate BR or UC prose at length; a contract references ids and carries only
  the slice the session needs.
- Write application code, migrations, or tests — those belong to `foundation`
  and the per-use-case sessions.
- Point a contract at a shared coordinator file; each contract carries its
  own slice, so a fact shared by sibling use cases is repeated (with its spec
  citation) in every contract that needs it — self-containment beats
  deduplication here, because the implementing session reads one file.

## 5. Application rules

- **Reference, never restate or invent.** Cite `BR-XX`, `UC-XX`, table, and
  enum by id. A contract is a projection of the specs, so a fact with no spec
  source does not belong in it.
- **Compile only the load-bearing slice.** A contract holds what the session
  needs to build this one use case; unused context invites the session to treat
  noise as load-bearing, so it is left out (`prompt-engineering-anchor.md`
  AC-08).
- **One use case per contract.** Each `ucNN_<slug>.md` is self-contained, so a
  session opens one file rather than re-reading six specs or a domain
  coordinator. AC-08 cuts *unused* context, not needed context: a fact this use
  case needs is carried here even if a sibling contract carries it too.
- **Mark derived content.** Endpoints, expected response codes,
  status/history/notification mappings, and dependencies are inferred from the
  specs, not stated by them; tag them
  `[derived …]` and treat them as fragile so a session can correct them.
- **Keep names aligned with the layers.** Use the spec wording for behavior and
  the `docs/conventions.md` slugs for enums/columns, so contract, spec, and code read as
  one system.

## 6. Output skeleton

The order of sections is fixed; empty sections read `_None._`. Slugs are the
kebab-cased use-case title, e.g. a "Record a decision" UC numbered 09 →
`uc09_record-a-decision`.

### Per-use-case contract — `docs/by-use-case/ucNN_<slug>.md`

```
# UC-NN — <Name>

## Identity
- Domain: <name> · Primary actor: <…> · Supporting actors: <…> · Level: <…>
  [03_use-cases.md UC-NN]

## Goal & trigger
<trigger line> [03_use-cases.md UC-NN]

## Preconditions
<from 03> [03_use-cases.md UC-NN]

## Main flow
<the numbered main success scenario, condensed> [03_use-cases.md UC-NN]

## Acceptance checklist (from extensions)
<each extension as one checkable behavior with its rule, e.g.
- ext Na — an unauthorized actor is denied [BR-XX, <cross-cutting visibility BR>]>
[03_use-cases.md UC-NN]

## Authorization
<request-scoped access (the project's cross-cutting visibility rule) + the role
gate(s) in force; point to the foundation policy/gate that enforces them>

## Data touched
<each "Business object touched" mapped to its table + enum, e.g.
<BusinessObject> → <table> + <Enum>> [04_data-model.md §2.1]

## Status transition(s)
<from → to this UC triggers, or _None._> [03_use-cases.md UC-NN], guard map in docs/conventions.md

## History events (written explicitly)
<the history-event-type value(s) this UC records, per the project's traceability
rule, or _None._> [02_business-rules.md BR-XX]

## Notifications (best-effort)
<notification-type value(s) created; the primary action completes if the
notification fails> [05_system-design.md §4]

## Performance target
<p95/p99 from 05 §3 if this is a critical UC, else "no dedicated target">
[05_system-design.md §3]

## Reliability
<must-not-fail-silently vs. may-degrade points relevant here> [05_system-design.md §4]

## API seam  [derived — fragile]
<one line per endpoint: METHOD /path — role/gate required, key request fields,
success status + response shape at a high level, so the backend and frontend
sessions agree>

## QA map  [derived — fragile]
<one line per acceptance item: ext Na → expected HTTP status, plus the expected
DB effect where one exists (status column value, history row, notification
row), so a QA session can translate the checklist into requests and DB checks
without interpreting. Error codes are read from the foundation conventions in
docs/conventions.md, never assumed; an item not observable via the API is
marked `frontend-only`>

## Dependencies  [derived — fragile]
<foundation pieces, prior UCs that must exist first (each with its build-order
reason), and any cross-domain prerequisite — stated inline and self-contained,
never as a pointer to another generated file>

## Notes
<from [03_use-cases.md UC-NN]; _None._ if absent>
```

**Entry-level transforms** (these are the non-obvious steps; the skeleton, not a
full example, carries the load). Each is written as a pattern, with placeholder
ids:

- Extension → acceptance item: a `UC-NN ext Na` of the form "an actor without
  the required role attempts the operation → denied; `BR-XX`" becomes
  "ext Na — <the disallowed actor> is denied [BR-XX, <visibility BR>]".
- Business object → data: a `<BusinessObject>` resolves to its table + enum,
  e.g. a decision object → table `<decisions>` + enum `<DecisionOutcome>`; a
  status object → column `<requests.status>` + enum `<RequestStatus>`. Use the
  actual names from `04_data-model.md` / `docs/conventions.md`.
- Status transition: a UC's terminal step, plus the notes of a related UC, give
  the `<from> → <to>` pair — stated against the guard map in `docs/conventions.md`.
- Acceptance item → QA-map row: an authorization extension maps to the denial
  response the foundation conventions prescribe (e.g. 403, or 404 under a
  visibility rule), a validation extension to 422, a guard violation to the
  guard's error — the concrete codes come from `docs/conventions.md`, never
  from assumption.

## 7. Binding vocabulary

This skill sits at the boundary between spec and code: it keeps the spec's
behavior vocabulary (`status`, the decision/outcome terms, `request history`,
the value-set labels) and names the implementation slugs from `docs/conventions.md` (the
table names, the enum type names, the enum value slugs). It does not introduce
new domain concepts; a term that appears in neither the specs nor `docs/conventions.md` is
a signal the contract has drifted into invention.

## 8. Verification (structural / consistency)

Report success only once these hold:

1. Every use case the domain owns (per the headings in `03`, §4) has a contract
   file.
2. Every `BR-XX`, `UC-XX`, table, and enum referenced exists in the specs /
   `docs/conventions.md`. A dangling reference is a failure.
3. No contract states a requirement, status, outcome, or rule absent from the
   specs; any gap appears as an Open Question instead.
4. Every endpoint, mapping, and dependency is tagged `[derived …]`.
5. Every contract is self-contained: it references no coordinator file and no
   sibling contract as a required read; a domain-shared fact it needs is carried
   in the contract itself, with its spec citation.
6. Every acceptance item appears in the contract's QA map with an expected HTTP
   status (or is marked `frontend-only`), and every code named there traces to
   the foundation conventions in `docs/conventions.md`.

Report what passed, and surface any failed check with the offending file and id.

## 9. Refocus

Before producing, restate the task: for the named domain, write one
self-contained contract per use case (§6), in build order. Every behavior comes
from `03`, every rule/table/enum is referenced by id, every target and
reliability note comes from `05`, and every inferred seam or dependency is tagged
`[derived …]`. The skeleton in §6 is the structural stop; a domain is complete
when all its contracts are filled and §8 passes.

## 10. Transition — produce

Produce two artifacts in this order, per `prompt-engineering-anchor.md` AC-15.

1. **Reasoning preamble** (in the output stream, not in a file):
   - **Inner Plan.** Read the `## <Name> Domain` headings in `03_use-cases.md`,
     resolve the `<domain>` argument to one of them, and list its use cases in
     build order. For each, pre-map: the BRs in force, the "Business objects
     touched" → tables/enums, the status transition and history events, the
     notifications, the performance target (only the critical UCs have one —
     `[05_system-design.md §3]`), the API seam with its QA-map row per acceptance
     item, and the prior UCs it depends on. Tag each plan
     item with its source; flag `[derived …]` items as fragile.
   - **Chain-of-Thought.** Carry out the plan: for each use case, render the main
     flow and turn every extension into an acceptance item with its rule;
     resolve each business object to its `04` table/enum; state the status
     transition against the guard map and the history events from the project's
     traceability rule; pull the performance and reliability notes from `05`;
     derive the API seam with its QA map and the dependencies and tag them. If any contract would
     need a fact no spec supports, stop and report the conflict.
2. **Main answer — the files.** Create the `docs/by-use-case/ucNN_<slug>.md`
   contracts in build order, then run §8 and
   report. Skeleton-completion plus a passing §8 is the structural stop — no
   closing commentary.

If the argument matches no `## <Name> Domain` heading in `03_use-cases.md`, stop
and report `BLOCKED — unknown domain "<arg>"; valid: <the domain names found as
## <Name> Domain headings in 03_use-cases.md>`.
If any of `docs/domain/02_business-rules.md`, `03_use-cases.md`,
`04_data-model.md`, `05_system-design.md`, or `docs/conventions.md` is missing
or empty, stop and report `BLOCKED — <filename> not found`.

## 11. Re-run safety

Re-running a domain refreshes its contracts in place: overwrite the files this
skill owns under `docs/by-use-case/` for the named domain, and leave every other
domain's files and all of `docs/domain/` untouched.
