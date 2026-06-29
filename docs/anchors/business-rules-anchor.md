# Curated Anchor — Business Rules

## 1. Source Basis

- Wiegers, Karl and Beatty, Joy, _Software Requirements, Third Edition_ (2013),
  **Chapter 9 — Business Rules** (§9.1–§9.7): the definition of a business rule,
  the five-type taxonomy (Figure 9-1), atomic rules, the business rules catalog
  (Table 9-4), discovering rules (Figure 9-3), the rule ↔ requirement relationship,
  and linking rules to requirements without duplication. The single **external**
  source for the business-rules concepts in this anchor; project-specific
  adaptations and agent rules are explicitly tagged `[K W → P]`, `[P]`, or `[A]`.
- Read by the author; concepts below are distilled, not summarized from memory.
- Full derivation / reading notes: `docs/anchor-sources/business-rules.md`.

## 2. Provenance Legend

- `[K W 9.x]` = Direct concept from Wiegers & Beatty, Chapter 9, section x.
- `[K W → P]` = Wiegers/Beatty concept, sharpened into a Project decision (applies to all my projects).
- `[A]` = Agent / author suggestion (no direct source).
- `[P]` = Pure Project decision.

## 3. Purpose

This anchor defines what a **business rule** is and binds the vocabulary, the
five-type taxonomy, the atomic rule schema, the discovery procedure, and the
impact/trace model for two artifacts: **`02_business-rules.md`** (the clean rule
catalog) and **`02a_business-rules-discovery.md`** (the discovery/audit artifact).

A business rule is an **external statement of policy, law, regulation, industry
standard, or governing principle** under which the organization operates. It
**exists independently of any software application** and would hold even if the
work were done by hand. The system merely **enforces** a rule — it does not own
or create it. [K W 9.1]

Because a rule is a property of the business, it is **not itself a requirement**.
It is the **origin** of requirements: it dictates properties the system must have
to comply. [K W 9.1] The catalog states the rule, its source, and its impact —
it never implements the rule, and never freezes the *how* of enforcement, because
the same rule may yield different functionality in different environments. [K W 9.6 → P]

`02_business-rules.md` is the **canonical home** of business rules: `BR-XX` IDs
are minted here; `00`/`01`/`03`/`04`/`05` reference rules by ID and never redefine
them. [K W 9.4 / 9.7 → P]

## 4. Accepted Concepts — Foundations & Pipeline Position (§9.1)

| #       | Concept                                  | Provenance      | Rule for `02_business-rules.md`                                                                                                                                                                                                                                                                                                                                                              |
| ------- | ---------------------------------------- | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-01   | Business rule = governing principle      | [K W 9.1]       | A business rule is a policy, law, regulation, industry standard, or governing principle the organization operates under (= business logic). The file documents such principles, not application features.                                                                                                                                                                                  |
| AC-02   | Rules exist outside software; software only enforces | [K W 9.1] | Every rule exists independently of any application and would hold under a manual process. The system *enforces* a rule; it does not own or create it. Rules are phrased as properties of the business, never as system behavior.                                                                                                                            |
| AC-03   | Not requirements, but their origin       | [K W 9.1]       | A rule is not a software requirement; it is a *source*. It dictates properties the system must have to comply. The file holds the rule; the derived requirement lives in the downstream spec.                                                                                                                                                                                              |
| AC-04   | Pipeline Impact Map / Artifact Ownership Map | [K W 9.1 → P] | Each rule classifies which requirement types / concerns it influences and which artifact *owns* the concern. The map is split by direction relative to `02`. **Downstream** (recorded as `Downstream impact`, referenced by `BR-XX`): User/Functional requirement → `03`; Data-model decision → `04`; Quality attribute / External interface → `05`. **Upstream** (no direct edit; triggers `Upstream Impact Review`, AC-09): Business requirement / Business Goal / Scope → `00`; Domain understanding / business object / process → `01`. The rule itself stays in `02`. |
| AC-04-R1 | Multiple impacts allowed                | [P]             | A single rule may hit upstream **and** downstream at once; it is never forced into one artifact. Canonical case: a certification rule sharpens `01`, triggers an Upstream Review for `00`, influences `03`, touches `04`, and raises security/auditability/compliance in `05`. Record all relevant impacts, respecting direction per impact.                                                  |
| AC-04-R2 | Constraint impact routing                | [P]             | When a rule affects a constraint, route by effect: a *scope/hard* constraint impacts `00` (upstream); a *technical/quality* constraint impacts `05` (downstream). The classification of the constraint itself is governed by AC-15 / AC-15-R1.                                                                                                                                              |
| AC-05   | Three-way separation: rule vs business requirement vs business process | [K W 9.1] | Keep three things apart. *Business requirement* = high-level goal / project justification (→ `00`). *Business process* = activity sequence input→output (→ `01`/`03`). *Business rule* = governing principle that constrains/drives both. The rule file contains only rules — it justifies no project and narrates no process.                                                |
| AC-07   | One rule, many processes → single documented source | [K W 9.1 → P] | Because the same rule touches many processes/applications, document it once as a separate information set. `02_business-rules.md` is the project's canonical single source; `BR-XX` IDs are minted here; `03`/`04`/`05` reference by ID, never redefine. No "corporate folklore": every rule the project depends on is written down.                                          |
| AC-08   | Traceability — where, not how            | [K W 9.1 / 9.6 → P] | Each rule records where it is enforced downstream so a rule change has a known blast radius. The trace names the **affected downstream artifact** (the *where*) — a use case / functional requirement in `03`, a constraint/decision in `04`, a quality attribute in `05` — **never the concrete enforcement solution** (the *how*, AC-40) and never code (RC-06).                       |
| AC-09   | Upstream Impact Review (`02 → 00/01`)    | [P]             | Because rules are discovered *after* `00`/`01`, a new/clarified rule may change, contradict, or sharpen upstream understanding. A review is required when a rule affects: Business Goal, Scope, Out of Scope, Hard Constraints, Anticipated Domains/Product Areas, Assumptions, Open Questions, domain understanding, business objects, business processes. The role **never** silently rewrites `00`/`01` — it records the review note only. |

## 5. Accepted Concepts — Rule Taxonomy (§9.2)

### 5a. Classification discipline

| #       | Concept                                  | Provenance      | Rule                                                                                                                                                                                                                                                                                                                       |
| ------- | ---------------------------------------- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-10   | Five-type taxonomy + required `Primary Type` | [K W 9.2]   | Each rule has exactly one `Primary Type`: Fact, Constraint, Action Enabler, Inference, Computation (Figure 9-1). `Primary Type` is a mandatory field. The single type does **not** limit the rule to one impact (AC-04-R1 stands). *(Replaces the retired AC-06; its four "ways rules affect processes" map onto the types.)* |
| AC-11   | Type suggests impact, never replaces the map | [K W 9.2 → P] | The type gives a *default hint* only: Fact→`01`/`04`; Constraint→`03`/`04`/`05`; Action Enabler→`03`; Inference→derived state/`04`/functional; Computation→functional+`04`. The hint never replaces explicit classification: `Influences` / `Downstream impact` / `Upstream Impact Review` are always filled (AC-04).         |
| AC-12   | Classification discipline: consistent > perfect | [K W 9.2 → P] | Consistent capture beats heated classification debates. Tie-breaker (Inference vs Action Enabler): a "then" clause yielding a *fact* → Inference; an *action* → Action Enabler. On genuine ambiguity: pick one type, note it in `Notes`/`Open questions`, continue.                                                          |

### 5b. The five types

| #       | Concept           | Provenance | Rule                                                                                                                                                                                                                                                                                                              |
| ------- | ----------------- | ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-13   | Facts             | [K W 9.2]  | True statements about relationships between business terms. Discipline: project-relevant facts only; bind each fact to a business object, process, event, or known domain relationship (from `01`), or to a forward impact note for `03`/`04` when the later artifact does not exist yet — never presuppose a finished `03`/`04`. Facts often surface in the data model → strong `04` link (terms → `01`). Facts *name* entities/relationships; the model is built in `04` (RC-11). |
| AC-14   | Constraints       | [K W 9.2]  | A statement restricting allowed actions of system/users (must / must not / may not / only role X). Each constraint rule names its origin (organizational policy / government regulation / industry standard). May carry *structural implications without direct functionality* (e.g. privilege levels) — recorded as impacts (AC-04-R1). |
| AC-15   | "So many constraints": constraint intake filter | [K W 9.2 → P] | Not every constraint is a business rule. (1) *Project-level* (schedule/staff/budget) → not a rule (RC-09). (2) *Product-scope/hard product* → `00` (Upstream Review if found during analysis). (3) *Design/implementation, not business-derived* → `05` (RC-10). (4) *Business-rule constraint* → minted in `02` as a Constraint `BR-XX`; if mirrored in software requirements, cited as the rationale of each derived requirement. **A business policy is not automatically a `00` constraint — it lives in `02`.** |
| AC-15-R1 | Origin before Ownership | [P]   | Classify a constraint by **origin** first, then assign **ownership** — never sort by effect. Project/product boundary → `00`. Policy/law/regulation/standard/operating rule restricting users/roles/business → first `02` as Constraint `BR-XX`; affects `00` only if it changes scope/goal/assumptions/hard constraints/product boundaries → then Upstream Review, the rule itself is not moved. Technical/design, not business-derived → `05`. PM limit → out of scope (RC-09). |
| AC-16   | Action Enablers   | [K W 9.2]  | Trigger an activity when conditions hold. Recognition pattern: "If `<condition/event>`, then `<action>`." For complex condition logic → decision table (AC-19).                                                                                                                                                    |
| AC-17   | Inferences        | [K W 9.2]  | Derive new knowledge from facts. Syntactically "if/then" like an Action Enabler, but the "then" clause yields a *fact*, not an action (tie-breaker: AC-12).                                                                                                                                                        |
| AC-18   | Computations      | [K W 9.2 → P] | Transform data via formula/algorithm; often externally mandated (e.g. tax formulas). Prefer symbolic form (math expression) or a rule table (Table 9-2) over wordy prose (AC-19).                                                                                                                               |

### 5c. Representation & validation

| #     | Concept                              | Provenance   | Rule                                                                                                                                                                                                                                                                                                                                              |
| ----- | ------------------------------------ | ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-19 | Compact representations; prose is fallback | [K W 9.2 → P] | Three compact forms: **roles & permissions matrix** for role-restricted constraints (Figure 9-2), **decision table** for complex action enablers, **rule table / formula** for computations (Table 9-2). These document the **business-rule layer only** — never UI screens, controllers, endpoints, framework mechanisms, or whole use cases. Roles/operations are understood in `01`, refined in `03`; the authorization mechanism + security quality attributes belong to `05`. |
| AC-20 | Trap: boundary value overlaps        | [K W 9.2 → P] | For value ranges (computations and any range-based rule), forbid overlapping boundaries (not 1–5, 5–10, 10–20). Enforce non-overlapping, gap-free ranges. Becomes a validation rule (§10/§12).                                                                                                                                                  |

## 6. Accepted Concepts — Atomicity, Identity & Schema (§9.3–§9.4)

| #     | Concept                                  | Provenance   | Rule                                                                                                                                                                                                                                                                                                                  |
| ----- | ---------------------------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-22 | Atomic business rules                    | [K W 9.3]    | Each `BR-XX` carries one short, clear, non-decomposable statement. Composite rules (several facts/constraints/conditions/actions/exceptions/computations in one) are forbidden. Split a multi-detail candidate into atomic rules, linking via `Related rules`. **Validation rule.**                                    |
| AC-23 | Atomicity mechanics for if/then rules    | [K W 9.3 → P] | For Action Enabler & Inference rules: left side with *alternative business conditions* → split; right side with *multiple independent outcomes* → split. A **semantic test, not a blind grammar test** — split business logic (multiple triggers / multiple consequences), not harmless wording.                       |
| AC-24 | Local change & blast-radius control      | [K W 9.3 → P] | Atomic rules keep change local: one business detail changes → only the affected atomic rule changes; downstream identifies affected `BR-XX` and updates only the relevant items. Couples atomicity (AC-22) with traceability (AC-08).                                                                                  |
| AC-25 | Functional requirements combine rules (n:m) | [K W 9.3 → P] | The rule ↔ functional-requirement relationship is many-to-many: a requirement/use case may depend on several atomic rules; a rule may influence several requirements/artifacts. Downstream combines and references by `BR-XX`, never redefines.                                                                       |
| AC-26 | Stable ID + human-readable Name (Option C) | [K W 9.3 → P] | Hybrid schema: `ID` = stable canonical pipeline reference (`BR-001`); `Name` = short business-readable slug (`Video.Media.Types`); `Statement` = atomic statement. Cross-references from `03`/`04`/`05` always point to `BR-XX`, never the Name (names drift with domain vocabulary; IDs must stay stable). The Name aids grouping/readability, never replaces the reference ID. |
| AC-27 | Unique identifier = pointer to master instance | [K W 9.4] | Each rule has a unique ID; downstream carries **only the ID**, never the definition. The spec does not go obsolete on rule change because it points, not duplicates. Book confirmation of AC-07 + AC-25.                                                                                                            |
| AC-28 | Required field `Type`                    | [K W 9.4]    | The catalog's "Type of rule" column = `Primary Type` (AC-10). Mandatory schema column.                                                                                                                                                                                                                                |
| AC-29 | Required field `Source`                  | [K W 9.4]    | Every rule names its origin (corporate/management policy, SME/person, law/regulation/document). One must know whom to contact on questions/changes. `Source` anchors the rule outside software (AC-02) and is the entry point of any Upstream Review (AC-09).                                                          |
| AC-30 | Required field `Static/Dynamic`          | [K W 9.4 → P] | Mark each rule **static** (rarely changes) or **dynamic** (changes periodically). Heuristic: *laws of nature* → static; *laws of humans* → dynamic. Pipeline use: primarily a downstream signal to `05` (dynamic ⇒ keep configurable/data-driven, do not hard-code). `dynamic` + safety-critical auto-raises a `05` impact (ATC cautionary tale). |
| AC-31 | Final rule schema: Table 9-4 baseline + pipeline fields | [K W 9.4 → P] | Wiegers' Table 9-4 is the **accepted schema baseline**, extended for traceability/review/impact/atomicity/readability. **Required:** `ID`, `Name`, `Statement`, `Type`, `Source`, `Static/Dynamic`, `Influences`. **Optional/as-needed:** `Downstream impact` (AC-08), `Upstream Impact Review` (AC-09), `Related rules` (AC-22), `Representation` (AC-19, e.g. "see Table BR-060"), `Notes/Rationale`. |

**Example `BR-XX` entry:**

```markdown
### BR-001 — Video.Media.Types
- **Statement:** DVD discs and Blu-ray discs are video items.
- **Type:** Fact
- **Source:** Library lending policy
- **Static/Dynamic:** Dynamic
- **Influences:** 01_miniworld.md, 03_use-cases.md, 04_data-model.md
- **Downstream impact:** 03_use-cases.md, 04_data-model.md
- **Related rules:** BR-002, BR-003
- **Notes/Rationale:** Determines which lending rules apply to video items.
```

## 7. Accepted Concepts — Discovery (§9.5)

| #       | Concept                                  | Provenance   | Rule (mostly `02a_business-rules-discovery.md`)                                                                                                                                                                                                                                                                       |
| ------- | ---------------------------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-32   | Discovery, not direct asking             | [K W 9.5]    | Rules are not obtained by asking "what are your business rules?"; they are actively discovered. Motivates the discovery procedure (AC-33–AC-38).                                                                                                                                                                       |
| AC-33   | Two primary discovery sources            | [K W 9.5 → P] | (1) **Business Process Modeling** — inspect each process step in `01_miniworld.md` for constraints, triggering events, computations, decisions, exceptions, facts. (2) **Analysis of Data** — inspect business objects, known states, transitions, events, ownership, permissions, relationships **in `01`**. Because `04` is generated *after* `02`, the role must not assume a finished data model; it may emit `04` impact notes (AC-04) but designs no model here (RC-11). |
| AC-34   | BA probes rationale during elicitation   | [K W 9.5]    | Rules surface when the BA probes the *rationale* behind requirements, constraints, process steps, decisions, or exceptions — asking not only *what* the system should do but *why* the business requires it.                                                                                                          |
| AC-34-R1 | Agent-based self-elicitation            | [A]          | The human pattern is simulated by two personas: a **BA persona** (asks rationale-oriented questions) and a **stakeholder persona** (answers only from available project knowledge). The stakeholder persona is **not** an authoritative real stakeholder — it may surface candidates/assumptions/open questions but **must not invent confirmed rules** (AC-36). |
| AC-35   | Figure-9-3 coverage sweep (mandatory relevance check) | [K W 9.5 → P] | Run all eight Figure-9-3 perspectives (Policies, Regulations, Computations, Data Models, User Decisions, Events, System Decisions, Object Life Cycles) with their lead questions. **All eight are always briefly checked** — none silently skipped. Relevant ones are deepened into candidates; non-relevant ones marked `N/A` with a reason. **No rule is invented just to fill a perspective.** |
| AC-36   | Three-tier confidence                    | [K W 9.5 → P] | `00`+`01` (plus tagged assumptions) are ground truth. Each candidate is classified: **Confirmed** (backed by `00`/`01` or a named `Source`) → becomes `BR-XX`; **Candidate** (plausible, unbacked) → marked, **no** `BR-XX` yet; **Open Question** → recorded, no invented knowledge. Only `Confirmed` reaches the AC-31 schema. Operationalizes Wiegers' validity/currency/scope check. |
| AC-37   | Scope of applicability check             | [K W 9.5]    | Assess each discovered rule for reach (**local / domain-wide / enterprise-wide**) and currency (stale formulas/policies). Only project-relevant rules (AC-13 discipline) enter `02`; enterprise-wide rules are noted, not all absorbed.                                                                                |
| AC-38   | Interview as separate discovery/audit artifact | [P]    | The self-interview lives in `02a_business-rules-discovery.md` (Figure-9-3 questions, Q&A notes, candidate rules, assumptions, open questions, source notes, confidence status). `02_business-rules.md` stays the **clean catalog** — confirmed `BR-XX` plus only the minimal discovery references needed for traceability. The full transcript is **not** embedded in `02`, and is **persistent** (audit trail), not throwaway. |

## 8. Accepted Concepts — Rules ↔ Requirements & Traceability (§9.6–§9.7)

| #     | Concept                                  | Provenance   | Rule                                                                                                                                                                                                                                                                                                                  |
| ----- | ---------------------------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-39 | Rule ≠ functional requirement            | [K W 9.6]    | Rules are external statements of policy; functional requirements are the system functionality that enforces/supports/checks/operationalizes them. `02` documents the rule; `03` defines the derived functionality. A rule may *influence* requirements but is never rewritten as a requirement inside `02`.            |
| AC-40 | Same rule, different functionality        | [K W 9.6 → P] | The same rule may yield different functional requirements depending on environment/data/integration/manual fallback/design. Therefore `02` never defines the concrete functionality or enforcement mechanism. **Allowed in `02`:** the rule, the affected object/process, `Type`, `Source`, likely downstream impact, affected artifact. **Not allowed:** full functional requirements, `shall`-statements, UI behavior, notification-channel decisions, API behavior, implementation mechanisms, architecture decisions. A *business action* may be the "then" of an Action Enabler; concrete *system functionality* → `03`; the technical enforcement *mechanism* → `05`. |
| AC-41 | No duplication downstream                | [K W 9.7]    | Rules must not be duplicated in downstream requirements/data-model/design artifacts. `02` holds the master instance; downstream references by stable `BR-XX`. Downstream may explain *how* it uses/enforces a rule but never restate the rule statement. Consolidates AC-25 + AC-27 from the downstream side.          |
| AC-42 | Lightweight trace model                  | [K W 9.7 → P] | Use stable `BR-XX` references instead of a heavy RM tool. Two directions: (1) in `02`, each rule records `Influences` and, when known, `Downstream impact`; (2) downstream artifacts reference the originating `BR-XX`. Recommended downstream fields: `Business rules`, `Origin`, `Derived from`, `Rule references`. The field name may vary; the reference must use the stable `BR-XX`. |
| AC-43 | Rule change impact search                | [K W 9.7 → P] | When a rule changes, search its `BR-XX` references across `03`/`04`/`05` to find requirements, use cases, data-model decisions, quality attributes, constraints, or design decisions needing revision. Operational form of AC-24 + AC-08.                                                                              |
| AC-44 | Rule-discovery feedback loop (`03 → 02a`) | [K W 9.7 → P] | A *second*, distinct loop from AC-09. When functional requirements are created/reviewed (in the `03` role), inspect each requirement's rationale; if the rationale is really a policy/regulation/constraint/computation/inference/fact/action trigger, create/update a **candidate** in `02a`. Only after confirmation + source tagging does it become a `BR-XX` in `02`. (AC-09 = `02 → 00/01`; AC-44 = `03 → 02a`.) |
| AC-45 | Taxonomy coverage check, not a quota     | [K W 9.7 → P] | Use the five types (Fact, Constraint, Action Enabler, Inference, Computation) as a discovery checklist — check whether each is relevant. The type-axis companion to AC-35's perspective-axis. **Never invent a rule of a type just to fill the taxonomy**; if none is found, mark `N/A` with a short reason in `02a`. |

## 9. Rejected Concepts (Skip List)

| #     | Concept                                                              | Provenance      | Why excluded                                                                                                                                                              |
| ----- | ------------------------------------------------------------------- | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RC-01 | Figure 1-1 as a full SRS / document model                           | [K W 9.1 → P]   | Only the useful relationship is kept (lives in AC-04 + AC-09). The full document architecture, "system requirements" as a heavy area, and the academic hierarchy are not reproduced. |
| RC-02 | Enterprise-wide governance program / corporate master repository as an org effort | [K W 9.1 → P] | Cross-department unification, repository administration, org politics, BRMS tooling = organizational process, not an artifact constraint. Only the *single-source* principle is kept, project-scaled (AC-07). |
| RC-03 | Business requirements themselves (project goals/justification)      | [K]             | `00_project-context.md` — referenced as impact (AC-04), never written here.                                                                                               |
| RC-04 | Business processes themselves (process narrative)                   | [K]             | `01_miniworld.md` / `03_use-cases.md` — a rule may *influence* a process; the process is described there.                                                                  |
| RC-05 | Downstream requirements as written content                          | [K]             | `00` / `03` / `05` — the file names impact and references forward; it does not write the requirement.                                                                      |
| RC-06 | Code concerns: tracing-to-code, code reuse                          | [K W 9.1 → P]   | These specs contain no code; the trace target is the downstream spec element (AC-08), not implementation code. Code reuse is an engineering benefit outside the artifact.  |
| RC-07 | Full rules-driven methodologies / automated BRMS                    | [K W 9.2]       | (von Halle, Ross, Ross & Lam.) Heavy methodology is only needed for strongly rules-driven systems; we identify / document / link. Sharpens RC-02 on the methodology axis.  |
| RC-08 | Terms as a separate minted rule category                            | [K W 9.2 → P]   | `01_miniworld.md` — domain vocabulary/glossary lives in `01`. Facts may *reference* terms; `02` keeps no glossary.                                                         |
| RC-09 | Project-level constraints (schedule/staff/budget)                   | [K W 9.2]       | Not business rules. Product-related hard constraints remain `00` material (AC-15 bucket 2) and are *not* covered by RC-09. No PM-plan artifact exists in this pipeline → PM constraints simply have no home in `02`. |
| RC-10 | Pure design/implementation constraints (not business-derived)       | [K W 9.2 → P]   | `05_system-design.md` / SRS — conditions imposed on developers that are not "how the business operates" are not business rules.                                            |
| RC-11 | The data model itself (keys, entity structure)                      | [K]             | `04_data-model.md` — facts *name* entities/relationships; the model is built in `04`.                                                                                      |
| RC-12 | `Domain.Subject.Aspect` as the *sole* canonical reference ID (Option B) | [K W 9.3 → P] | Only rejected as a *sole* reference ID — readable names are still wanted, as the `Name` field (AC-26). Speaking IDs break cross-references (`03`/`04`/`05`) when domain vocabulary changes; `BR-XX` stays stable. |
| RC-13 | Consequence-if-broken as a mandatory schema field                   | [K W 9.2/9.4 → P] | The third pillar of the BRG business perspective; not in Table 9-4. Too heavy for this documentation-oriented pipeline (belongs to enforcement/compliance processes). Available as `Notes`. |
| RC-14 | `Static/Dynamic` as an implementation/hard-coding directive         | [K W 9.4 → P]   | `05_system-design.md` — the *classification* is kept (AC-30); the *implementation decision* (how to make it configurable) is code/design.                                 |
| RC-15 | Four Boyer & Mili sources needing external artifacts                | [K W 9.5 → P]   | Common knowledge (no long-serving staff), legacy systems (no reverse-engineering corpus), existing documentation (no prior specs/contracts/business plans in input), compliance departments (no org unit) → out of scope. If such an artifact appears in a real project, it may be cited as a `Source` (candidate → confirmed, AC-36). |
| RC-16 | von Halle's full rule-discovery process                             | [K W 9.5]       | Like RC-07: heavyweight methodology only for strongly rules-driven systems. We use the light sweep (AC-35).                                                                |
| RC-17 | Parent/suffix functional-requirement tables in the catalog          | [K W 9.6 → P]   | `03_use-cases.md` — suffix tables (`Expired.Notify.*`) structure *functional requirements*. **Does not affect AC-19**: decision/rule tables and permission matrices stay in `02` when they document the rule layer itself. |
| RC-18 | Requirements-management tool dependency                             | [K W 9.7 → P]   | Not required. The `Origin` concept is adopted, implemented through simple `BR-XX` references in Markdown.                                                                  |
| RC-19 | Heavy traceability matrix as a mandatory artifact                   | [K W 9.7 → P]   | Not mandatory; a simple trace section / `BR-XX` field suffices. (Distinct from the rule-layer matrices/tables of AC-19.) A matrix may be added later if the project grows. |
| RC-20 | Hyperlinks as the primary trace mechanism                           | [K W 9.7 → P]   | Hyperlinks may help but are not primary; stable `BR-XX` IDs are, because paths/links break when documents move.                                                            |
| RC-21 | Forced "one rule per type" quota                                    | [K W 9.7 → P]   | The book's "find at least one of each type" is a discovery prompt, not a quota. The role must not invent rules to satisfy coverage (paired with AC-45).                    |

## 10. Application Rules (How to apply these concepts to any project)

- **State the rule, not the functionality.** `02` holds policy; *how* it is enforced
  is `03` (functionality) / `05` (mechanism). The same rule may yield different
  functionality, so never freeze the "how". [K W 9.6 → P] (AC-39, AC-40)
- **One Primary Type per rule; consistency over perfection.** Classify by the
  five types; on ambiguity pick one, note it, continue. [K W 9.2] (AC-10, AC-12)
- **Write atomic.** One statement per `BR-XX`. Avoid `or` on the left when it
  represents alternative business conditions, and `and` on the right when it
  represents multiple independent outcomes — a semantic split rule, not a grammar
  rule. Split composites and link via `Related rules`. [K W 9.3] (AC-22, AC-23)
- **Mint `BR-XX` here; stable ID + readable Name.** Downstream references by ID,
  never redefines. [K W 9.3/9.7 → P] (AC-26, AC-27, AC-41)
- **Every rule records `Source`, `Static/Dynamic`, `Influences`.** Source anchors
  it outside software and starts any review; Influences split into upstream
  (review) and downstream (impact). [K W 9.4 → P] (AC-29, AC-30, AC-04)
- **Origin before Ownership for constraints.** A business policy lives in `02`, not
  `00`; only a project/product boundary is `00`. [K W 9.2 → P] (AC-15, AC-15-R1)
- **Run the Figure-9-3 coverage sweep.** Check all eight perspectives; deepen the
  relevant, mark the rest `N/A` with a reason; never invent a rule to fill one.
  [K W 9.5 → P] (AC-35, AC-45)
- **Classify confidence.** Confirmed → `BR-XX` in `02`; Candidate / Open Question →
  `02a`. Personas never invent confirmed rules. [K W 9.5 → P] (AC-36, AC-34-R1)
- **Upstream hit → review, never silent rewrite.** A rule touching `00`/`01` raises
  an Upstream Impact Review. [P] (AC-09)
- **Downstream rationale that is really a rule → feedback loop.** A `03` requirement
  whose rationale is a rule writes a candidate back into `02a`. [K W 9.7 → P] (AC-44)
- **No overlapping value ranges.** Enforce gap-free, non-overlapping boundaries.
  [K W 9.2 → P] (AC-20)
- **Keep the catalog clean.** The discovery transcript lives in `02a`; `02` carries
  only confirmed `BR-XX` and minimal trace references. [P] (AC-38)

## 11. Binding Vocabulary

**Use these terms** — the controlled vocabulary of this anchor:

- Core: `business rule`, `business logic`, `enforces`, `master instance` [K W 9.1/9.4]
- Types: `fact`, `constraint`, `action enabler`, `inference`, `computation`, `Primary Type` [K W 9.2]
- Schema fields: `ID` (`BR-XX`), `Name`, `Statement`, `Source`, `Static/Dynamic`, `Influences`, `Downstream impact`, `Upstream Impact Review`, `Related rules`, `Representation`, `Notes/Rationale` [K W 9.4 → P]
- Representations: `roles & permissions matrix`, `decision table`, `rule table` [K W 9.2]
- Discovery: `Figure-9-3 coverage sweep`, `Confirmed` / `Candidate` / `Open Question`, `scope of applicability`, `BA persona`, `stakeholder persona` [K W 9.5 / A]
- Pipeline loops: `Upstream Impact Review` (`02 → 00/01`), `Rule-Discovery Feedback Loop` (`03 → 02a`) [P / K W 9.7 → P]
- Discipline: `atomic`, `no duplication` [K W 9.3/9.7]

**Forbidden** — in generated `02_business-rules.md` content, any occurrence means
the file has drifted into another phase (these terms may still appear in this
anchor's own boundary notes, skip-list explanations, and impact references):

- Functional-requirement language: `shall` statements, use-case steps, scenarios
- UI / implementation: screen, controller, endpoint, API behavior, notification
  channel / SMTP, framework name, database engine
- Data-model design: `foreign key`, `normal form`, `index`, entity structure
  _(belong to `04_data-model.md`)_
- Parent/suffix requirement tables (`Expired.Notify.*`) _(belong to `03`; see RC-17)_
- Heavy tracing: a full requirements traceability matrix presented as mandatory;
  hyperlinks used as the primary reference _(see RC-19, RC-20)_
- Inventing a rule to fill a type or a Figure-9-3 perspective _(see AC-35, AC-45)_

_(A rule may **name** an affected artifact and **reference** it for impact; it
never writes the downstream content. The boundary is: state the rule, its source,
and where it lands — never implement it, and never freeze the "how".)_

## 12. Role Usage Rule

The role file (`02-business-rules-architect.md`) consumes this anchor **and**
`prompt-engineering-anchor.md` as binding context. Wiegers & Beatty Chapter 9 is
background knowledge, distilled in `docs/anchor-sources/business-rules.md`; the
book itself is not the direct prompt source.

This anchor produces **two artifacts**: `02_business-rules.md` (the clean rule
catalog, primary) and `02a_business-rules-discovery.md` (the discovery/audit
artifact, subordinate but persistent).

Hard rules for every generated `02_business-rules.md`:

1. Every `BR-XX` is **atomic** and carries `Primary Type`, `Source`,
   `Static/Dynamic`, and `Influences`. [K W 9.2/9.3/9.4]
2. `BR-XX` IDs are **minted here**; `00`/`01`/`03`/`04`/`05` reference by ID and
   never redefine a rule. Stable `ID` + readable `Name` (Option C). [K W 9.4/9.7 → P]
3. The **Figure-9-3 coverage sweep** is run; all eight perspectives are checked,
   non-relevant ones marked `N/A` with a reason; no rule is invented to fill a
   perspective or a type. [K W 9.5 → P]
4. Discovery is **confidence-tiered**: only `Confirmed` rules enter `02`;
   `Candidate` and `Open Question` items live in `02a`. The full interview
   transcript stays in `02a`, never embedded in `02`. [K W 9.5 → P / P]
5. Constraints are classified **Origin before Ownership**: a business policy lives
   in `02`; only a project/product boundary is `00`; an upstream hit goes through
   an **Upstream Impact Review**, never a silent rewrite. [K W 9.2 → P / P]
6. The file **states the rule, not the functionality**: no `shall`-statements, UI,
   API, notification channels, mechanisms, or architecture; the trace names the
   affected downstream artifact (the *where*), never the enforcement solution
   (the *how*). [K W 9.6 → P]

---

> **Scope note.** This anchor rests on Wiegers & Beatty **Chapter 9 only**
> (§9.1–9.7), which carries the business-rules concepts: definition, the five-type
> taxonomy, atomic rules, the catalog schema, discovery, and the rule ↔ requirement
> relationship. Enterprise-wide business-rules governance, automated BRMS, the full
> rule-discovery methodologies (von Halle, Ross), and heavy requirements-management
> tooling / mandatory traceability matrices are **deliberately excluded** as
> premature for a lightweight, single-project documentation pipeline. The data
> model, the use cases, and the system design themselves belong to their own
> anchors and are referenced by `BR-XX`, never redefined here. If generated output
> later shows a concrete weakness, read the matching material and extend this
> anchor; the boundary stays until then.