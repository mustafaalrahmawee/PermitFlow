---
name: use-case-implementer
description: >-
  Implement one use case end-to-end from its compiled contract in
  docs/by-use-case/. The main session reads the contract (never the six domain
  specs), builds the Laravel backend slice, and has it verified by a read-only
  backend QA subagent that replays the contract's QA map with curl and tinker
  (max 3 attempts). Only after a backend PASS does it build the Nuxt frontend
  slice, verified by a frontend QA subagent (build + typecheck + API-seam
  conformance, max 3 attempts). It never commits; it ends with a per-phase
  change report so the developer reviews the diff and commits manually. Invoke
  explicitly as /use-case-implementer <UC-NN>.
argument-hint: <UC-NN>
disable-model-invocation: true
allowed-tools: Read, Edit, Write, Bash, Task
---

# Skill — Use-Case Implementer (run per use case)

## 1. Introduction

This is the **implementation skill** — the third stage of the pipeline, after
`foundation` (the scaffold) and `domain-doc-generator` (the contracts). Where
those produce the base and the context packs, this skill **consumes** one
contract and turns it into working code: the backend slice first, then the
frontend slice, each gated by an independent QA verdict.

**The workflow shape** (fixed): implementation happens **in the main session**,
which holds the cumulative context a fix loop needs; verification happens in
**fresh, read-only QA subagents**, which do not share the implementer's blind
spots. A phase advances only on a QA report whose verdict is `PASS`; the main
session never passes itself. Each QA loop allows **three attempts**; a third
`FAIL` stops the run with a `BLOCKED` report instead of a fourth guess.

It is **skeleton-complete** when the backend and frontend QA reports are both
`PASS` and the final report (§6c) is emitted. This skill **never commits**: the
developer reviews the uncommitted diff in the IDE and commits manually, so a
clean working tree at start is a precondition — it keeps each use case's diff
isolated and reviewable.

Section numbers written as plain `§N` refer to **this** skill.

## 2. Binding context

**Authoritative inputs (indirect context, pipeline-composed):**

- `docs/by-use-case/ucNN_<slug>.md` — **the contract**: the single context pack
  for this use case. Its Acceptance checklist, QA map, API seam, data, status,
  history, notification, and Dependencies sections (the latter carrying build
  order and cross-domain prerequisites inline) are the whole behavioral spec of
  this run. There is no per-domain coordinator file; the contract is
  self-contained by design (`prompt-engineering-anchor.md` AC-08).
- `docs/conventions.md` — the foundation conventions every artifact obeys (enum
  shape, status guard, policies/gates, auth model, error responses, storage).

The session does **not** re-open `docs/domain/*`; the contract is its faithful
projection, and re-reading six specs is exactly the context load the contract
exists to remove. Two conflict rules keep that safe:

- A contract item tagged `[derived — fragile]` that proves wrong at
  implementation time (an endpoint shape, a status code, a dependency) is
  **corrected in the contract file** and flagged in the final report — derived
  items are the contract's seam for exactly this correction.
- A conflict with an **untagged** (spec-sourced) contract item is never patched
  around: the run stops and reports it, because the fix belongs upstream in
  `docs/domain/` and a re-run of `/domain-doc-generator`.

## 3. Inputs

Inputs are declared static vs. dynamic per `prompt-engineering-anchor.md`
AC-06 / AC-07.

**Direct (from the developer):** the `<UC-NN>` argument — a use-case id
(`UC-02`, `uc02`, or `02`), resolved against `docs/by-use-case/`.

**Static (fixed in this skill):** the workflow (§5), the QA subagent briefs and
report skeletons (§6), the application rules (§7).

**Dynamic (read at run time):** the contract and `docs/conventions.md`.

**Classification:** the argument is **direct**; contract and conventions are
**indirect**; the workflow and skeletons are **boilerplate**.

## 4. What this skill does and does not do

This skill **does**:

- Implement the backend slice the contract describes: routes, controller,
  form-request validation, policy/gate wiring, the status transition through
  the guard, explicit history writes, best-effort notifications — per
  `docs/conventions.md`.
- Delegate verification to fresh read-only QA subagents (§6) and fix findings
  in the main session, up to three attempts per phase.
- Implement the frontend slice against the contract's API seam only.
- End with the final report (§6c) listing per-phase changed files for manual
  review and commit.

This skill **does not**:

- Re-read `docs/domain/*` or author behavior absent from the contract; a gap is
  an Open Question in the final report, not an invention.
- Implement other use cases: an unmet dependency (§5.0) is `BLOCKED`, with the
  contract's Dependencies section as the pointer — not an excuse to build the
  prerequisite inline.
- Let a QA subagent edit code, or advance a phase without a `PASS` verdict.
- Commit, stage, or otherwise alter git state; the diff belongs to the
  developer's review.

## 5. Session workflow

The phases run strictly in order; each names its gate.

### 5.0 Preflight

1. Resolve `<UC-NN>` to `docs/by-use-case/ucNN_<slug>.md`. Missing contract →
   `BLOCKED — no contract for <arg>; run /domain-doc-generator <domain> first`.
2. Read the contract and `docs/conventions.md` — nothing else.
3. Check the contract's Dependencies: each prerequisite UC's endpoints appear
   in `php artisan route:list`. An absent prerequisite →
   `BLOCKED — dependency UC-NN not implemented; see the contract's Dependencies
   section`.
4. `git status --porcelain` is empty (see §11 for the re-run exception),
   because the diff of this run must be reviewable in isolation.
5. The local stack answers: `docker compose ps` services up, migrations and
   seeds applied, the API responds to an unauthenticated smoke request with the
   conventions' expected status.

### 5.1 Phase A — backend implementation (main session)

Implement exactly the slice the contract names: the API-seam endpoints, the
authorization the contract's Authorization section states (request-scoped
policy + role gate, fail closed), validation per the contract's data section,
the status transition through the foundation guard, explicit history writes
with the contract's event types, and best-effort notifications (the primary
action completes if the notification write fails). Reuse foundation pieces;
touch only files this use case needs.

### 5.2 Phase B — backend QA loop (subagent; max 3 attempts)

Spawn a **fresh** backend QA subagent with the §6a brief. On `FAIL`: fix the
failing items in the main session using the report's exact requests and
observations, then spawn a **new** subagent (attempt 2, then 3) — a fresh agent
re-verifies everything, not just the previous failures. A third `FAIL` →
`BLOCKED — backend QA failed after 3 attempts` plus the last report, and the
run stops; the frontend is never started on a red backend.

### 5.3 Gate

Frontend work begins only after a backend report whose verdict is `PASS`.

### 5.4 Phase C — frontend implementation (main session)

Implement the Nuxt slice against the contract's **API seam only**: pages,
components, and composables that call the seam's endpoints with the seam's
fields, render the states the Acceptance checklist implies (success, denial,
validation error), and cover the QA-map items marked `frontend-only`.
No endpoint or field that the seam does not name.

### 5.5 Phase D — frontend QA loop (subagent; max 3 attempts)

Same loop discipline as §5.2, with the §6b brief: build + typecheck + seam
conformance. Three `FAIL`s →
`BLOCKED — frontend QA failed after 3 attempts` plus the last report. The
backend work stays in place (uncommitted) for the developer to inspect.

### 5.6 Wrap-up

Emit the final report (§6c). No commit, no closing commentary beyond it.

## 6. QA subagent briefs

Both subagents are **read-only**: they may read code and the contract, run
commands, and query the database, but never edit a file. Each attempt gets a
fresh subagent. The report skeletons are fixed; a QA subagent that cannot fill
its skeleton reports `FAIL` with the reason, never an ad-hoc format.

### 6a. Backend QA brief

**The subagent receives:** the contract path (it reads the Acceptance
checklist, QA map, API seam, Status transition, History events, Notifications
sections itself), the API base URL, the seeded actors per role, and the tinker
recipe for minting a Sanctum bearer token for a seeded account.

**The subagent does:** for the main flow and every QA-map row that is not
`frontend-only`, issue the curl request the API seam implies (correct actor,
correct payload), compare observed HTTP status and response shape against the
QA map, then verify the stated DB effect via tinker — status column value,
history row with the contract's event type, notification row. Authorization
rows are probed with the *disallowed* actor's token.

**Report skeleton (fixed):**

```
# QA report — UC-NN backend — attempt N/3

| item | request | expected | observed | result |
|------|---------|----------|----------|--------|
<one row per main-flow step probe and per QA-map row; result pass/fail>

## DB effects
<per checked effect: expected vs. observed tinker result>

## Verdict
PASS | FAIL
<if FAIL: the failing items, each with the exact curl command and the full
observed response, so the fix needs no re-probing>
```

### 6b. Frontend QA brief

**The subagent receives:** the contract path (API seam + QA map), and the list
of changed `app/` files from `git status`.

**The subagent does:** run the Nuxt production build and the typecheck (both
must exit 0); then extract every API call (`$fetch`/`useFetch`/composables) in
the changed files and compare method, path, and payload/response fields against
the API seam — an endpoint or field the seam does not name is a `FAIL` finding;
confirm the `frontend-only` QA-map items have a rendered state.

**Report skeleton (fixed):**

```
# QA report — UC-NN frontend — attempt N/3

## Build & typecheck
<command, exit code, first error if any>

## Seam conformance
| call site | method+path | seam match | fields match | result |

## frontend-only items
| item | rendered state found | result |

## Verdict
PASS | FAIL
<if FAIL: each finding with file:line and the seam line it violates>
```

### 6c. Final report skeleton (main session)

```
# UC-NN — implementation report

## Backend — <attempts used>/3 — PASS
<changed files under api/ (from git status), one line each>

## Frontend — <attempts used>/3 — PASS
<changed files under app/>

## Contract corrections
<[derived] items corrected in the contract, with reason; _None._ if absent>

## Open questions
<gaps the contract did not answer; _None._ if absent>

Review the diff in your IDE, then commit manually (suggested message:
"UC-NN <slug>: backend + frontend").
```

## 7. Application rules

- **Context hygiene.** The main session's inputs are the contract,
  `docs/conventions.md`, and the files it edits — it does not
  browse the specs or unrelated code, because the contract carries the whole
  slice and extra context invites drift (`prompt-engineering-anchor.md` AC-08).
- **The QA verdict binds.** Only a subagent report with `PASS` advances a
  phase. The main session never marks its own work verified, because the value
  of the check is its independence.
- **Fresh subagent per attempt.** A re-used QA agent inherits its previous
  framing; a fresh one re-verifies the full checklist.
- **Fix from the report, not from memory.** A fix addresses the report's
  observed request/response pairs; if the report is too vague to act on, the
  report format (§6) was violated — tighten the brief, don't guess.
- **Smallest slice.** Touch only files this use case needs; a change to a
  shared helper or foundation piece is listed under Contract corrections /
  Open questions in the final report so the review sees it.
- **Derived is corrigible, spec-sourced is not.** §2's two conflict rules.

## 8. Verification (structural)

Report success only once these hold:

1. A backend QA report with verdict `PASS` exists, produced by a subagent, with
   every non-`frontend-only` QA-map row `pass`, within 3 attempts.
2. A frontend QA report with verdict `PASS` exists: build and typecheck exit 0,
   every seam-conformance row and `frontend-only` item `pass`, within 3
   attempts.
3. `git status` shows changes only under `api/`, `app/`, and (if corrected)
   this use case's contract file; git history is untouched.
4. The final report (§6c) is emitted and complete.

## 9. Refocus

Before producing, restate the task: implement the one use case the argument
names, from its contract alone. Preflight (§5.0), backend in the main session
(§5.1), fresh backend QA subagent until `PASS` or 3 attempts (§5.2), gate
(§5.3), frontend against the seam (§5.4), fresh frontend QA subagent until
`PASS` or 3 attempts (§5.5), final report and stop — no commit (§5.6). The QA
verdict binds; derived contract items are corrigible, spec-sourced ones are
not.

## 10. Transition — produce

Produce in this order, per `prompt-engineering-anchor.md` AC-15.

1. **Reasoning preamble** (in the output stream, not in a file):
   - **Inner Plan.** Resolve the argument, run preflight, and lay out the
     backend slice from the contract: endpoints, authorization, validation,
     status transition, history events, notifications — each traced to its
     contract section; then the frontend slice from the seam. Note which
     QA-map rows the backend QA must probe and which are `frontend-only`.
   - **Chain-of-Thought.** Execute phase by phase, surfacing each QA verdict
     and each fix decision explicitly.
2. **Main answer — the code and the reports.** The phases of §5 with the
   briefs of §6. Skeleton-completion per §8 is the structural stop.

`BLOCKED` lines this skill can emit, verbatim forms:
`BLOCKED — no contract for "<arg>"; run /domain-doc-generator <domain> first`,
`BLOCKED — dependency UC-NN not implemented; see the contract's Dependencies section`,
`BLOCKED — working tree not clean; commit or stash first`,
`BLOCKED — stack not running / not seeded`,
`BLOCKED — backend QA failed after 3 attempts`,
`BLOCKED — frontend QA failed after 3 attempts`,
`BLOCKED — contract conflicts with a spec-sourced item: <item>`.

## 11. Re-run safety

A re-run of the same use case continues in place. The §5.0 clean-tree check
admits exactly one exception: the dirty files are the ones a previous run of
**this same use case** touched (they match the contract's slice); any other
dirty state stays `BLOCKED`. A re-run restarts the QA attempt counters, spawns
fresh subagents, and overwrites nothing outside the use case's slice. Files of
other use cases, `docs/domain/*`, other contracts, and git history are never
touched.
