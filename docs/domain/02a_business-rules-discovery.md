# Business-Rules Discovery — PermitFlow

## 1. Discovery Setup

- **Primary sources:** `00_project-context.md`, `01_miniworld.md`
- **Personas:** BA persona (probes the rationale behind each process, constraint,
  decision, and exception) ↔ Stakeholder persona (answers only from `00`/`01`;
  surfaces candidates, assumptions, and open questions; never invents confirmed
  rules)
- **Method:** discovery, not direct asking — rules are swept out of the processes,
  objects, and scope signals rather than requested as a list
  (`business-rules-anchor.md` AC-32).

## 2. Figure-9-3 Coverage Sweep

- **Policies** — *Which institutional policies govern how requests are handled?* →
  **Relevant.** Human review of decisions, role-authorized visibility, controlled
  post-submission input, assignment authority, administrator maintenance
  authority (`01_miniworld.md` §5, §4) → candidates BR-005, BR-007, BR-010,
  BR-012, BR-013, BR-014, BR-016.
- **Regulations** — *Which external laws or standards bind the work?* →
  **Relevant.** The authorized-visibility obligation behind BR-016 is grounded in
  EU GDPR / DSGVO privacy principles, especially data minimisation,
  integrity/confidentiality, and security of processing. The concrete PermitFlow
  rule — request-scoped visibility for the owning citizen, responsible staff
  member, and authorized administrators — is the institution's organizational
  policy implementation of that legal obligation. No additional national or
  state-specific privacy statute is named because `00`/`01` do not define a concrete
  jurisdiction or public authority (`business-rules-anchor.md` AC-36).
- **Computations** — *Are there mandated formulas or calculations?* → **N/A.** v1
  handles no fees or money (`00_project-context.md` §6; `01_miniworld.md` §5), and no
  other calculation appears. No computation rule is invented to fill the perspective.
- **Data Models** — *Which facts about objects and relationships must hold?* →
  **Relevant.** Category cardinality, request ownership, the request-status set,
  decision outcomes, the role set, document kinds, message participants, the
  cardinality of an assignment (`01_miniworld.md` §3, §5, §6) → candidates BR-001,
  BR-002, BR-003, BR-004, BR-006, BR-008, BR-009, BR-011.
- **User Decisions** — *Which decisions do people make, and under what rule?* →
  **Relevant.** A staff member records Approved/Rejected under human review (BR-007,
  BR-008); an administrator assigns a request (BR-010) (`01_miniworld.md` §4, §5).
- **Events** — *Which events trigger constrained handling?* → **Relevant at
  rule-level.** A staff member's request for missing information is the one event
  that re-opens citizen input after submission (BR-005). Other event handling
  (submission, status change, notification) is process flow and lands in `03`.
- **System Decisions** — *What does the system decide automatically?* → **N/A by
  design.** v1 forbids automated approval/rejection and automated assignment
  (`00_project-context.md` §7; `01_miniworld.md` §5). The absence is captured
  positively as human review (BR-007); no automated-decision rule exists to mint.
- **Object Life Cycles** — *Which states does a request move through?* →
  **Relevant.** Draft → Submitted → In Review → Waiting for Citizen → Ready for
  Decision → Decided (`01_miniworld.md` §5). The **state set** is a Fact (BR-004);
  the ordered transition flow is workflow and belongs to `03`.

## 3. Taxonomy Coverage Check (five types — checklist, not quota)

- **Fact** — **Relevant.** BR-001, BR-002, BR-003, BR-004, BR-006, BR-008, BR-009,
  BR-011.
- **Constraint** — **Relevant.** BR-005, BR-007, BR-010, BR-012, BR-013,
  BR-014, BR-015, BR-016, BR-017.
- **Action Enabler** — **N/A as primary type.** BR-005 carries an enabler shape
  ("if a staff member requests missing information, the citizen may provide it"),
  but its business force is restrictive (it *limits* when post-submission input is
  allowed), so it classifies as a Constraint (`business-rules-anchor.md` AC-12). No
  rule is invented to fill this type.
- **Inference** — **N/A.** No rule derives a new *fact* from other facts; the
  request's status progression is a workflow transition (→ `03`), not a
  knowledge-derivation rule.
- **Computation** — **N/A.** No formulas or algorithmic transforms in v1 (no fees,
  no calculated values).

## 4. Self-Elicitation Notes (BA ↔ Stakeholder)

- **Record a decision** — *Q (rationale):* why must a decision be set by a staff
  member rather than produced by the system? — *A (from `01` §5; `00` §7):* a public
  institution's decisions require human accountability; automated approval/rejection
  is explicitly excluded. → Confirmed (BR-007).
- **Record a decision (outcome set)** — *Q:* why are the outcomes limited to two? —
  *A (from `01` §5):* v1 records Approved or Rejected only; missing information is a
  status, not an outcome. → Confirmed Fact (BR-008); the "missing-info is a status"
  clarification is captured by BR-004 + BR-005, not as an outcome.
- **Assign a request for handling** — *Q:* how many staff members are responsible
  for a request, and who assigns them? — *A (from `01` §5):* an assigned request has
  exactly one responsible staff member; assignment is performed by an administrator,
  and a submitted request stays unassigned until then. → Confirmed Fact (BR-009,
  assignment cardinality) + Confirmed Constraint (BR-010, by-administrator). The
  "manual, not automated" framing is a v1 scope choice, recorded as rationale, not
  as a separate rule.
- **Provide supporting information (after submission)** — *Q:* why can a citizen not
  freely edit after submitting? — *A (from `01` §5):* post-submission input is
  allowed only in response to a staff request for missing information; free editing
  is out of v1. → Confirmed Constraint (BR-005).
- **Track request progress / authorized visibility** — *Q:* why is progress visible
  to some and not others? — *A (from `01` §5; `00` §6):* request information,
  documents, and messages are visible only to those authorized by role and request relationship; transparency
  is scoped to the owning citizen and the handling staff/administrators, never
  public (`01` §7). → Confirmed Constraint (BR-016).
- **Record a decision / update progress (history)** — *Q:* why keep a history? —
  *A (from `01` §5; `00` §6):* important status changes and decisions must stay
  understandable after the fact. → Confirmed Constraint (BR-017).
- **Manage categories / accounts / settings** — *Q:* who is entitled to maintain
  these, and why restrict it? — *A (from `01` §4):* administrators maintain request
  categories, user accounts and roles, and organization settings. → Confirmed
  Constraints (BR-012, BR-013, BR-014).
- **Submit a request (category)** — *Q:* how many categories does a request carry? —
  *A (from `01` §6):* one category per request (tagged assumption). → Confirmed Fact
  resting on a tagged assumption (BR-002; see §7).
- **Submit a request (ownership)** — *Q:* who owns a submitted request? — *A (from
  `01` §6; `00` §7):* the submitting citizen; acting on behalf of another is out of
  v1. → Confirmed Fact (BR-003; see §7).
- **View reporting summaries** — *Q:* who may see reporting summaries? — *A (from
  `01` §4):* staff members and administrators. → Confirmed Constraint (BR-015).
- **Exchange messages** — *Q:* between whom does request communication flow? —
  *A (from `01` §3):* between the request's citizen and a staff member. → Confirmed
  Fact (BR-011); *who may read* a message is governed by BR-016, not restated here.

## 5. Candidate Rules (not yet `BR-XX`)

- **Requests.SingleClearPath** — Every request follows one path from submission to
  decision — Type: (rationale) — Source: `01` §5 / business goal `00` §4 — Confidence:
  Candidate — Scope: domain-wide — *This restates the business goal and the rationale
  behind BR-004; a goal is owned by `00` (`business-rules-anchor.md` RC-03), so it is
  not minted as a separate rule.*

**Resolved as product boundary, not a business rule:** request withdrawal after
submission. `00_project-context.md` §7 excludes withdrawal after submission from
v1. This means the feature is not implemented in v1; it does not establish a
permanent institutional policy that submitted requests may never be withdrawn.
Therefore no `BR-XX` is minted.

**Considered and classified as product/project boundaries (not business rules,
`business-rules-anchor.md` AC-15 / RC-09):** single organization in v1, single
interface language in v1, no fees or payments in v1, works without automated
assistance in v1, single-developer scope, in-portal notifications only in v1. Each
either bounds the product (→ `00`) or bounds the project (out of scope), and none is
a policy the institution operates under independently of the software. In-portal
notification delivery additionally touches the enforcement channel (→ `05`), which
`02` does not freeze.

- _None further._

## 6. Confirmed Rules (promoted to `02_business-rules.md`)

- `BR-001` IdentityAccess.User.SingleRole — a user account holds exactly one role —
  backed by `01` §3.
- `BR-002` Requests.Category.SinglePerRequest — one category per request — backed by
  `01` §5/§6.
- `BR-003` Requests.Ownership.SingleCitizen — one owning citizen per request — backed
  by `01` §6.
- `BR-004` Requests.Status.DefinedSet — the request-status set — backed by `01` §5.
- `BR-005` Requests.PostSubmission.ControlledInput — citizen input only on a staff
  request for missing information — backed by `01` §5.
- `BR-006` Documents.Kind.SupportingOrDecision — the two kinds of document — backed
  by `01` §3.
- `BR-007` ReviewWorkflow.Decision.HumanReview — decisions are made by a staff member
  — backed by `01` §5 / `00` §7.
- `BR-008` ReviewWorkflow.Decision.Outcome — outcome is Approved or Rejected — backed
  by `01` §5.
- `BR-009` ReviewWorkflow.Assignment.SingleResponsibleStaff — an assigned request
  has exactly one responsible staff member — backed by `01` §5.
- `BR-010` ReviewWorkflow.Assignment.ByAdministrator — assignment performed by an
  administrator — backed by `01` §5.
- `BR-011` Communication.Message.Participants — a message links the request's citizen
  and a staff member — backed by `01` §3.
- `BR-012` Administration.Categories.MaintainedByAdmin — categories maintained by
  administrators — backed by `01` §4/§5.
- `BR-013` Administration.Accounts.MaintainedByAdmin — accounts and roles maintained
  by administrators — backed by `01` §4.
- `BR-014` Administration.Settings.MaintainedByAdmin — settings configured by
  administrators — backed by `01` §4.
- `BR-015` Reporting.Summaries.StaffAndAdminOnly — reporting summaries for staff and
  administrators — backed by `01` §4.
- `BR-016` CrossCutting.Visibility.RequestScopedAccess — visible only to the owning
  citizen, the responsible staff member, and authorized administrators — backed by
  `01` §5, §7 / `00` §6.
- `BR-017` CrossCutting.Traceability.StatusAndDecisions — recorded, traceable status
  changes and decisions — backed by `01` §5 / `00` §6.

## 7. Assumptions

- **One category per request** — `01` §6 tags this as an assumption; treated as
  ground truth for v1 (`business-rules-anchor.md` AC-36) and minted as BR-002. Needs
  validation if multi-category filing is later wanted.
- **One submitting citizen per request** — `01` §6 tagged assumption, reinforced by
  the out-of-scope item "representatives acting on behalf" (`00` §7); minted as
  BR-003. Needs validation if representation is later added.
- **One role per user account** — derived from `01` §3 ("the kind of user a person
  is"), read as singular; minted as BR-001. Fragile — validate if a person may hold
  more than one role.
- **Status set is v1-defined and may grow** — BR-004 enumerates the v1 stages;
  marked `dynamic`. Adding a stage later changes only BR-004.

## 8. Open Questions

- _None._

## 9. Upstream Impact Reviews (`02 → 00/01`)

- `BR-016` → `00` §6: resolved. `00_project-context.md` now names EU GDPR / DSGVO
  as the external privacy basis and references BR-016 for request-scoped access.

- `BR-017` → `00` §6: resolved. `00_project-context.md` now references BR-017 for
  traceable decisions and request progress.

- `BR-007` → `00` §7: resolved. `00_project-context.md` now references BR-007 as
  the positive human-review rule behind the exclusion of automated approval or
  rejection.

- `BR-002`, `BR-003` → `01` §6: resolved. `01_miniworld.md` now states both as
  assumptions and notes that they are formalized as BR-002 and BR-003.

## 10. Source Notes

- **Organizational policy (PermitFlow institution)** — origin of the human-review,
  visibility, traceability, assignment, administrative-maintenance, and
  reporting-access rules. Contact: product owner for the single v1 institution.
- **Product-owner v1 decisions** — origin of the status set, the two decision
  outcomes, single-staff assignment, and controlled post-submission input
  (`01` §10, "Resolved for v1"). Contact: product owner on any change to v1 scope.
- **`00_project-context.md` / `01_miniworld.md`** — the upstream specs that back
  every confirmed rule; the entry point for any Upstream Impact Review.
- **EU GDPR / DSGVO** — named external privacy basis for BR-016. Used only as the
  high-level legal source for privacy-conscious, request-scoped access. The exact
  role/request visibility model remains organizational policy.