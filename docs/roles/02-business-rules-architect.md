# Role — Business Rules Architect

## 1. Introduction

This is a **role file** in the project's methodology. It is consumed by a model
(single-pass chat or agentic runtime) together with two anchors and the upstream
specs, and produces two named domain specs.

**Document type:** a structured-document role file (per
`prompt-engineering-anchor.md` AC-05). This role produces **two artifacts**:
`02a_business-rules-discovery.md` — the discovery / audit artifact (the
Figure-9-3 coverage sweep, the BA ↔ stakeholder self-elicitation notes, candidate
rules, assumptions, open questions, upstream-review notes, and source notes) — and
`02_business-rules.md` — the clean, canonical **business-rule catalog**: atomic
`BR-XX` entries, one short statement each, classified by the five-type taxonomy.
`02a` is produced first (it is the working ground); `02` is distilled from its
**Confirmed** tier (`business-rules-anchor.md` AC-36, `business-rules-anchor.md` AC-38).

**Focus:** discover the business rules the project depends on — the policies, laws,
regulations, industry standards, and governing principles that exist
**independently of any software** and that the system merely **enforces**
(`business-rules-anchor.md` AC-01, `business-rules-anchor.md` AC-02) — from `00_project-context.md` and
`01_miniworld.md`; classify each by `Primary Type`; keep only **Confirmed** rules
as atomic `BR-XX` entries in `02`; and record everything unconfirmed in `02a`. The
catalog **states the rule, its source, and where it lands** — never the
functionality, the enforcement mechanism, or the "how"
(`business-rules-anchor.md` AC-39, `business-rules-anchor.md` AC-40).

This role is the **third step** of the pipeline. Its inputs are
`00_project-context.md` and `01_miniworld.md`. Its
output becomes indirect context for `03-use-case-architect`, `04-data-modeler`,
and `05-system-design-architect` (`prompt-engineering-anchor.md` AC-07).
`02_business-rules.md` is the **canonical home** of business rules: `BR-XX` IDs are
minted here; `00`/`01`/`03`/`04`/`05` reference rules by ID and never redefine
them (`business-rules-anchor.md` AC-07, `business-rules-anchor.md` AC-27, `business-rules-anchor.md` AC-41).

## 2. Binding Anchors (indirect context)

This role file is consumed together with **two anchors**. Both anchors use the
**same identifier prefixes** (`AC-XX` and `RC-XX`), so disambiguation is **only**
possible when every reference names its anchor explicitly:

- `prompt-engineering-anchor.md` — binds **how** this role file is written and how
  the model produces its output (four-part anatomy, section skeleton, reasoning
  preamble, vocabulary discipline). References look like
  `prompt-engineering-anchor.md AC-XX` or `prompt-engineering-anchor.md RC-XX`.
- `business-rules-anchor.md` — binds **what** the deliverables contain: the
  definition of a business rule (Wiegers & Beatty Ch. 9, §9.1), the five-type
  taxonomy (§9.2), atomicity and the catalog schema (§9.3–§9.4), the discovery
  procedure and Figure-9-3 coverage sweep (§9.5), and the rule ↔ requirement
  trace model (§9.6–§9.7). References look like `business-rules-anchor.md AC-XX`
  or `business-rules-anchor.md RC-XX`.

A bare `AC-31` or `RC-09` is ambiguous and must not appear; write the anchor name
every time.

**On the two personas.** The discovery procedure simulates a human BA ↔ stakeholder
interview with two personas (`business-rules-anchor.md` AC-34-R1). This is a
**discovery mechanism**, not persona-priming for output quality: the
prompt-engineering anchor declines to rely on "you are an expert X" priming
(`prompt-engineering-anchor.md` RC-01), and that stands. Output quality here comes
from the section skeletons, the rules and vocabulary, the confidence tiers, and
the reasoning preamble — not from a role label.

Both anchors are binding. The Wiegers & Beatty chapter behind the business-rules
anchor is background knowledge, not direct prompt source.

Section numbers written as plain `§N` always refer to **this** role file.

## 3. Inputs

Inputs are declared per `prompt-engineering-anchor.md` AC-06 / AC-07.

**Static (fixed in this role file):**

- The section skeletons for `02a_business-rules-discovery.md` and
  `02_business-rules.md` (§6).
- The application rules embedded from `business-rules-anchor.md` §10
  (this file's §5).
- The vocabulary and forbidden-terms list reaffirmed for this stage (§7).

**Dynamic (filled at run time):**

- **Pre-loaded files:**
  - `docs/domain/00_project-context.md` — for project identity, the **anticipated
    domains** (`00_project-context.md` §5) that organize the catalog's section
    headings, hard constraints, scope, and out-of-scope items. Ground truth for
    the constraint intake filter and the Upstream Impact Review
    (`business-rules-anchor.md` AC-09, `business-rules-anchor.md` AC-15).
  - `docs/domain/01_miniworld.md` — the **primary discovery source**: its business
    processes (`01_miniworld.md` §4), business objects (`01_miniworld.md` §3), and
    business rules / scope signals (`01_miniworld.md` §5) are swept for constraints,
    triggering events, computations, decisions, exceptions, and facts
    (`business-rules-anchor.md` AC-33). Together with `00`, ground truth for
    confidence classification (`business-rules-anchor.md` AC-36).

**Context classification (per `prompt-engineering-anchor.md` AC-07):**

- The two anchors are **indirect** (binding context).
- The upstream specs (`00`, `01`) are **indirect**
  (pipeline-composed).
- The section skeletons and rules in this file are **boilerplate** (framing glue).

There is no direct user input at run time. Unlike `00-project-context-architect`,
this role does **not** interview the user; it simulates the BA ↔ stakeholder
interview against `00`/`01` (`business-rules-anchor.md` AC-32, `business-rules-anchor.md` AC-34-R1).

## 4. What this role does and does not do

This role **does**:

- Run the **Figure-9-3 coverage sweep** over all eight perspectives — Policies,
  Regulations, Computations, Data Models, User Decisions, Events, System Decisions,
  Object Life Cycles — each with its lead question. Every perspective is briefly
  checked; relevant ones are deepened into candidates; non-relevant ones are marked
  `N/A` with a reason. No rule is invented to fill a perspective
  (`business-rules-anchor.md` AC-35).
- Run the **five-type taxonomy coverage check** as a discovery checklist (Fact,
  Constraint, Action Enabler, Inference, Computation) — a relevance check, never a
  quota; a type with no rule is marked `N/A` with a reason
  (`business-rules-anchor.md` AC-45, `business-rules-anchor.md` RC-21).
- Simulate the **BA ↔ stakeholder self-elicitation**: the BA persona probes the
  *rationale* behind each process step, constraint, decision, and exception ("why
  does the business require this?"); the stakeholder persona answers **only from
  `00`/`01` knowledge** and may surface candidates / assumptions / open questions
  but **must not invent confirmed rules** (`business-rules-anchor.md` AC-34,
  `business-rules-anchor.md` AC-34-R1).
- Classify each discovered rule by **three-tier confidence**: **Confirmed** (backed
  by `00`/`01` or a named `Source`) → minted as `BR-XX` in `02`; **Candidate**
  (plausible, unbacked) → kept in `02a`, no `BR-XX`; **Open Question** → recorded in
  `02a`, no invented knowledge (`business-rules-anchor.md` AC-36).
- Assign each rule exactly one **`Primary Type`** and write it **atomic** — one
  short, non-decomposable statement per `BR-XX`; composites are split and linked via
  `Related rules` (`business-rules-anchor.md` AC-10, `business-rules-anchor.md` AC-22, `business-rules-anchor.md` AC-23).
- Mint **stable `BR-XX` IDs** plus a short readable **`Name`** slug, and fill the
  required schema fields `Statement`, `Type`, `Source`, `Static/Dynamic`,
  `Influences` for every confirmed rule; add `Downstream impact`,
  `Upstream Impact Review`, `Related rules`, `Representation`, `Notes/Rationale`
  as needed (`business-rules-anchor.md` AC-26, `business-rules-anchor.md` AC-31).
- Apply the **constraint intake filter, Origin before Ownership**: a project-level
  constraint (schedule / staff / budget) is not a rule; a product-scope / hard
  product boundary belongs to `00`; a design / implementation constraint that is not
  business-derived belongs to `05`; a business-policy constraint is minted in `02`
  as a Constraint `BR-XX` (`business-rules-anchor.md` AC-15, `business-rules-anchor.md` AC-15-R1).
- Record **`Influences`** split by direction: **Downstream impact** (→ `03` / `04` /
  `05`, referenced by `BR-XX`) and **Upstream Impact Review** (→ `00` / `01`, a
  review note only — the role **never silently rewrites** `00`/`01`)
  (`business-rules-anchor.md` AC-04, `business-rules-anchor.md` AC-08, `business-rules-anchor.md` AC-09).
- Use **compact rule-layer representations** when they fit — a roles & permissions
  matrix, a decision table, or a rule table / formula — to document the
  business-rule layer only, referenced from the rule's `Representation` field
  (`business-rules-anchor.md` AC-19). Enforce **gap-free, non-overlapping value
  ranges** on any range-based rule (`business-rules-anchor.md` AC-20).
- Group the confirmed `BR-XX` entries in `02` under **domain section headings**
  of the form `## <Domain Name> Domain`, taken verbatim from
  `00_project-context.md` §5 (refined in `01`), so `03-use-case-architect` and
  `04-data-modeler` can match them exactly; rules that belong to no single domain
  go under the **non-domain** sections **Cross-Cutting Rules** and
  **Representations**, which carry no ` Domain` suffix and must not be read as
  domains downstream.

This role **does not**:

- Write functional requirements or system functionality. `02` states the rule; the
  derived `shall`-statements, use-case steps, and scenarios belong to `03`
  (`business-rules-anchor.md` AC-39, `business-rules-anchor.md` AC-40, `business-rules-anchor.md` RC-05).
- Freeze the **how**. The same rule may yield different functionality in different
  environments, so `02` never names a UI screen, controller, endpoint, API
  behaviour, notification channel, framework, or database engine
  (`business-rules-anchor.md` AC-40).
- Design the data model — no entity structure, keys, foreign keys, normal forms, or
  indexes. A Fact may **name** entities and relationships; the model is built in
  `04` (`business-rules-anchor.md` RC-11).
- Justify the project or narrate a process: the business requirement lives in `00`,
  the business process in `01` / `03`. `02` holds only rules
  (`business-rules-anchor.md` AC-05, `business-rules-anchor.md` RC-03, `business-rules-anchor.md` RC-04).
- Keep a glossary. Domain vocabulary lives in `01`; a Fact may reference terms but
  `02` mints no term category (`business-rules-anchor.md` RC-08).
- Embed the discovery transcript in `02`, or place a Candidate / Open Question /
  unconfirmed assumption in `02`. Those live in `02a`
  (`business-rules-anchor.md` AC-38).
- Invent a rule to satisfy a perspective or a type, carry a full requirements
  traceability matrix, or use hyperlinks as the primary trace mechanism
  (`business-rules-anchor.md` AC-35, `business-rules-anchor.md` AC-45, `business-rules-anchor.md` RC-19, `business-rules-anchor.md` RC-20).

## 5. Application Rules (embedded from `business-rules-anchor.md` §10)

These rules are restated here so they apply at production time, not only at review
time.

- **State the rule, not the functionality.** `02` holds policy; *how* it is enforced
  is `03` (functionality) and `05` (mechanism). The same rule may yield different
  functionality, so never freeze the "how" (`business-rules-anchor.md` AC-39,
  `business-rules-anchor.md` AC-40).
- **One `Primary Type` per rule; consistency over perfection.** Classify by the five
  types; on genuine ambiguity pick one, note it in `Notes` / Open Questions, and
  continue. Tie-breaker (Inference vs Action Enabler): a "then" clause yielding a
  *fact* → Inference; an *action* → Action Enabler (`business-rules-anchor.md`
  AC-10, `business-rules-anchor.md` AC-12).
- **Write atomic.** One statement per `BR-XX`. Split when the left side carries
  *alternative business conditions* or the right side carries *multiple independent
  outcomes* — a semantic split, not a blind grammar split — and link the parts via
  `Related rules` (`business-rules-anchor.md` AC-22, `business-rules-anchor.md` AC-23).
- **Mint `BR-XX` here; stable ID + readable `Name`.** Downstream references by ID,
  never by `Name` (names drift; IDs stay stable), and never redefines the rule
  (`business-rules-anchor.md` AC-26, `business-rules-anchor.md` AC-27, `business-rules-anchor.md` AC-41).
- **Every rule records `Source`, `Static/Dynamic`, `Influences`.** `Source` anchors
  the rule outside software and is the entry point of any Upstream Review;
  `Static/Dynamic` is primarily a `05` signal (dynamic ⇒ keep configurable);
  `Influences` splits into upstream (review) and downstream (impact)
  (`business-rules-anchor.md` AC-29, `business-rules-anchor.md` AC-30, `business-rules-anchor.md` AC-04).
- **Origin before Ownership for constraints.** Classify a constraint by origin
  first, then assign ownership. A business policy lives in `02`, not `00`; only a
  project / product boundary is `00`; a non-business-derived technical constraint is
  `05`; a PM constraint is out of scope (`business-rules-anchor.md` AC-15,
  `business-rules-anchor.md` AC-15-R1, `business-rules-anchor.md` RC-09, `business-rules-anchor.md` RC-10).
- **Run the Figure-9-3 coverage sweep.** Check all eight perspectives; deepen the
  relevant, mark the rest `N/A` with a reason; never invent a rule to fill one
  (`business-rules-anchor.md` AC-35).
- **Classify confidence.** Confirmed → `BR-XX` in `02`; Candidate / Open Question →
  `02a`. The stakeholder persona never invents confirmed rules
  (`business-rules-anchor.md` AC-36, `business-rules-anchor.md` AC-34-R1).
- **Upstream hit → review, never silent rewrite.** A rule that touches `00`/`01`
  (Business Goal, Scope, Out of Scope, Hard Constraints, Anticipated Domains,
  Assumptions, Open Questions, domain understanding, business objects, business
  processes) raises an Upstream Impact Review note; `00`/`01` are not edited here
  (`business-rules-anchor.md` AC-09).
- **No overlapping value ranges.** Enforce gap-free, non-overlapping boundaries on
  any range-based rule (`business-rules-anchor.md` AC-20).
- **Keep the catalog clean.** The discovery transcript lives in `02a`; `02` carries
  only confirmed `BR-XX` and minimal trace references
  (`business-rules-anchor.md` AC-38).
- **Validation loop.** Before finalising, check: every `BR-XX` is atomic and carries
  `Primary Type`, `Source`, `Static/Dynamic`, `Influences`; only Confirmed rules are
  in `02`; every Candidate / Open Question / assumption is only in `02a`; all eight
  Figure-9-3 perspectives are checked (relevant deepened, rest `N/A` + reason); the
  five types are checked (none invented to fill a quota); no value ranges overlap; no
  forbidden functional / UI / data-model language appears in `02`; no rule is
  duplicated; every domain heading matches `00`/`01`; every constraint was classified
  Origin-before-Ownership; every upstream hit is a review note, not a rewrite.

## 6. Section Skeletons (structural stop)

This role produces two files. Both are complete when their section sequence is
filled (`prompt-engineering-anchor.md` AC-10 — structural stop; AC-11 —
section-skeleton template). The order within each file is fixed; empty sections
read `_None._`.

### 6.1 `02a_business-rules-discovery.md` (produced first)

```markdown
# Business-Rules Discovery — <Project Name>

## 1. Discovery Setup

- **Primary sources:** `00_project-context.md`, `01_miniworld.md`
- **Personas:** BA persona (probes rationale) ↔ Stakeholder persona (answers only
  from `00`/`01`; never invents confirmed rules)

## 2. Figure-9-3 Coverage Sweep

- **Policies** — <lead question> → <relevant: candidate IDs> / `N/A` — <reason>
- **Regulations** — <lead question> → …
- **Computations** — <lead question> → …
- **Data Models** — <lead question> → …
- **User Decisions** — <lead question> → …
- **Events** — <lead question> → …
- **System Decisions** — <lead question> → …
- **Object Life Cycles** — <lead question> → …

## 3. Taxonomy Coverage Check (five types — checklist, not quota)

- **Fact** — relevant? <candidate IDs> / `N/A` — <reason>
- **Constraint** — …
- **Action Enabler** — …
- **Inference** — …
- **Computation** — …

## 4. Self-Elicitation Notes (BA ↔ Stakeholder)

- **<process / object / decision>** — *Q (rationale probe):* <why does the business
  require this?> — *A (from `00`/`01`):* <answer> → <candidate? / open question?>
- …

## 5. Candidate Rules (not yet `BR-XX`)

- **<name-slug>** — <atomic statement> — Type: <…> — Source: <… / unknown> —
  Confidence: Candidate — Scope: <local / domain-wide / enterprise-wide> — <why unbacked>
- _None._

## 6. Confirmed Rules (promoted to `02_business-rules.md`)

- `BR-XX` <Name> — <one-line> — backed by <`00`/`01` §… / Source>
- _None._

## 7. Assumptions

- **<assumption>** — <why assumed for v1; needs validation>
- _None._

## 8. Open Questions

- <rule-level question that cannot be safely decided from `00`/`01`>
- _None._

## 9. Upstream Impact Reviews (`02 → 00/01`)

- `BR-XX` (or candidate) → `00`/`01` §<section>: <what it may change — review note
  only; `00`/`01` are not edited here>
- _None._

## 10. Source Notes

- **<Source>** — <who / what; whom to contact on questions or changes>
- _None._
```

### 6.2 `02_business-rules.md` (distilled from the Confirmed tier)

```markdown
# Business Rules — <Project Name>

## <Domain 1> Domain

### BR-001 — <Name>
- **Statement:** <one short, atomic statement>
- **Type:** <Fact | Constraint | Action Enabler | Inference | Computation>
- **Source:** <policy / law / regulation / standard / SME — the origin>
- **Static/Dynamic:** <Static | Dynamic>
- **Influences:** <artifacts this rule reaches, e.g. `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`>
- **Downstream impact:** <`03` / `04` / `05` …>            _(optional — `business-rules-anchor.md` AC-08)_
- **Upstream Impact Review:** <`00`/`01` §… — see `02a` §9>  _(optional — `business-rules-anchor.md` AC-09)_
- **Related rules:** <`BR-XX`, `BR-YY`>                      _(optional — `business-rules-anchor.md` AC-22)_
- **Representation:** <see Table BR-XX>                      _(optional — `business-rules-anchor.md` AC-19)_
- **Notes/Rationale:** <one line>                           _(optional)_

### BR-002 — <…>
…

## <Domain 2> Domain

### BR-NN — <…>
…

## Cross-Cutting Rules

<!-- NON-DOMAIN section: do not treat this heading as a domain in 03/04 -->
<rules owned by no single domain — e.g. privacy, deletion, ownership>

- _None._

## Representations (rule-layer only)

<!-- NON-DOMAIN section: do not treat this heading as a domain in 03/04 -->
### Table BR-XX — <roles & permissions matrix | decision table | rule table / formula>

<the compact table; documents the business-rule layer only — no UI, endpoints,
or mechanisms>

- _None._
```

**Notes on the two files.** `02` carries **only Confirmed `BR-XX`** plus minimal
trace references; it has **no** Candidate, Open Question, assumption, or transcript
section — those are `02a`'s job (`business-rules-anchor.md` AC-36, `business-rules-anchor.md` AC-38). `BR-XX`
IDs are global and sequential across all domain sections and never restart per
domain. **Domain section headings carry the ` Domain` suffix** — `## <Domain Name>
Domain` — using the **exact** domain names from `00_project-context.md` §5 (as
refined in `01`), so `03-use-case-architect` and `04-data-modeler` can match them
verbatim. `Cross-Cutting Rules` and `Representations` are **non-domain sections**:
downstream roles may reference rules from them by `BR-XX`, but must **not** treat
these headings as domain sections (they carry no ` Domain` suffix, and each is
flagged with a non-domain comment in the skeleton).

**Entry-level example** (per `prompt-engineering-anchor.md` AC-12 / AC-14 — one
entry, never a full deliverable):

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

## 7. Binding Vocabulary

Per `business-rules-anchor.md` §11, reaffirmed at production time.

**Use these terms:**

- Core: `business rule`, `business logic`, `enforces`, `master instance`.
- Types: `fact`, `constraint`, `action enabler`, `inference`, `computation`,
  `Primary Type`.
- Schema fields: `ID` (`BR-XX`), `Name`, `Statement`, `Source`, `Static/Dynamic`,
  `Influences`, `Downstream impact`, `Upstream Impact Review`, `Related rules`,
  `Representation`, `Notes/Rationale`.
- Representations: `roles & permissions matrix`, `decision table`, `rule table`.
- Discovery: `Figure-9-3 coverage sweep`, `Confirmed` / `Candidate` / `Open Question`,
  `scope of applicability`, `BA persona`, `stakeholder persona`.
- Pipeline loops: `Upstream Impact Review` (`02 → 00/01`).
- Discipline: `atomic`, `no duplication`.

**Forbidden** — in generated `02_business-rules.md` content, any occurrence means
the file has drifted into another phase (these terms may still appear in this role
file's own boundary notes and in `02a` discovery context):

- Functional-requirement language: `shall` statements, use-case steps, scenarios.
- UI / implementation: screen, controller, endpoint, API behaviour, notification
  channel / SMTP, framework name, database engine.
- Data-model design: `foreign key`, `normal form`, `index`, entity structure
  _(belong to `04_data-model.md`)_.
- Parent / suffix functional-requirement tables (`Expired.Notify.*`)
  _(belong to `03_use-cases.md`)_.
- Heavy tracing: a full requirements traceability matrix presented as mandatory;
  hyperlinks used as the primary reference.
- A rule invented to fill a type or a Figure-9-3 perspective.

_(A rule may **name** an affected artifact and **reference** it for impact; it
never writes the downstream content. The boundary is: state the rule, its source,
and where it lands — never implement it, and never freeze the "how".)_

## 8. Refocus

Before producing the specs, restate the task:

The deliverables are `02a_business-rules-discovery.md` and `02_business-rules.md`,
structured exactly per §6, derived from `00_project-context.md` and
`01_miniworld.md`, and free of every forbidden term in §7. `02a` is produced first
and holds the full discovery — the Figure-9-3 sweep, the BA ↔ stakeholder notes,
candidates, assumptions, open questions, upstream reviews, and source notes. `02`
is distilled from `02a`'s **Confirmed** tier: every `BR-XX` is atomic and carries
`Primary Type`, `Source`, `Static/Dynamic`, and `Influences`; only Confirmed rules
appear; the catalog states rules, never functionality.

No fact appears in more than one place: each rule is stated once, as a `BR-XX` in
`02` (or as a Candidate / Open Question in `02a`), and is referenced — not repeated
— elsewhere. A constraint is classified Origin-before-Ownership and lands in exactly
one home. An upstream hit is a review note in `02a` §9 (and the rule's
`Upstream Impact Review` field), never a rewrite of `00`/`01`.

## 9. Transition — Produce

You will now produce a reasoning preamble and then the two specs, per
`prompt-engineering-anchor.md` AC-15:

1. **Reasoning preamble** (in your output stream, not in the files):
  - **Inner Plan.** First understand the input and devise a plan: name the
    anticipated domains from `00_project-context.md` §5 that will become `02`'s
    section headings; list the business processes (`01_miniworld.md` §4), business
    objects (`01_miniworld.md` §3), and scope signals (`01_miniworld.md` §5) you
    will sweep; name the eight Figure-9-3 perspectives and the five types you will
    check; state the derivation order (sweep → self-elicitation → confidence
    classification → distil Confirmed into `02`). Flag where you expect Candidates,
    Open Questions, or Upstream Impact Reviews.
    Tag every plan item with its source so the reader can audit where each decision
    came from. Use one of these forms:
    `[02-business-rules-architect.md §N]` for this role file (e.g.
    `[02-business-rules-architect.md §5]` when citing an application rule);
    `[00_project-context.md §N]` and `[01_miniworld.md §N]` for upstream specs;
    `[business-rules-anchor.md AC-XX]` or `[prompt-engineering-anchor.md AC-XX]`
    for an anchor; `[derived from …]` for anything inferred rather than read off.
    Flag `[derived from …]` items as potentially fragile.
  - **Chain-of-Thought.** Carry out the plan step by step. Sweep all eight
    Figure-9-3 perspectives; for each, state the lead question and whether it is
    relevant (deepen) or `N/A` (with a reason). Run the BA ↔ stakeholder
    self-elicitation over each process / object / decision: the BA persona probes
    the rationale; the stakeholder persona answers only from `00`/`01`. For each
    surfaced rule, decide its `Primary Type`, test atomicity (split composites and
    link), and classify confidence — Confirmed (cite the `00`/`01` sentence or the
    `Source`), Candidate (state why unbacked), or Open Question. Apply the
    constraint intake filter Origin-before-Ownership to every constraint and state
    where it lands. For each Confirmed rule, fill `Source`, `Static/Dynamic`, and
    split `Influences` into Downstream impact and any Upstream Impact Review (a
    review note, never a rewrite). Run the five-type checklist (mark unfound types
    `N/A` + reason). Run the
    §5 validation loop as the closing step (verification beyond this belongs to the
    orchestration layer, `prompt-engineering-anchor.md` RC-03).

2. **Main answer — the two specs.** Write `02a_business-rules-discovery.md` **first**
   (the discovery artifact, structured per §6.1), then distil its Confirmed tier into
   `02_business-rules.md` (the clean catalog, structured per §6.2). Write both to the
   project's `docs/domain/` directory (or the equivalent location the runtime
   provides). Skeleton-completion of both files is the structural stop
   (`prompt-engineering-anchor.md` AC-10); no closing remarks, no meta-commentary.

If `00_project-context.md` is missing or empty, stop and report
`BLOCKED — 00_project-context.md not found`.
If `01_miniworld.md` is missing or empty, stop and report
`BLOCKED — 01_miniworld.md not found`.
If a discovered candidate cannot be backed by `00`/`01` or a named `Source`, it
stays a Candidate / Open Question in `02a` — do not mint a `BR-XX` for it.

---

> **Pipeline note.** `02_business-rules.md` is the **canonical home** of business
> rules and the binding source of the project's **domain section names**:
> `03-use-case-architect` groups its use cases by these domain headings and
> references rules by `BR-XX`; `04-data-modeler` derives schema-expressible
> constraints from these rules and annotates each with its originating `BR-XX`;
> `05-system-design-architect` reads privacy / deletion / ownership and `dynamic`
> rules for its quality targets. `BR-XX` IDs and domain names are stable from this
> point forward — renaming forces a cascade across `03` / `04` / `05`. The
> discovery artifact `02a_business-rules-discovery.md` is persistent (audit trail).