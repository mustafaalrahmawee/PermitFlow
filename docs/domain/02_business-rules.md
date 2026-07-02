# Business Rules — PermitFlow

## Identity and Access Domain

### BR-001 — IdentityAccess.User.SingleRole
- **Statement:** A user account holds exactly one role — Citizen, Staff member, or Administrator.
- **Type:** Fact
- **Source:** Organizational policy (role definition, PermitFlow institution)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`, `05_system-design.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`, `05_system-design.md`
- **Related rules:** BR-016
- **Representation:** see Table BR-016
- **Notes/Rationale:** A person's role sets what they may see and do; `dynamic` because multi-role accounts may be wanted later (see `02a` §7).

### BR-018 — IdentityAccess.Account.InactiveHasNoAccess
- **Statement:** An inactive user account cannot authenticate and cannot perform or pass any protected action, regardless of role.
- **Type:** Constraint
- **Source:** Organizational policy (account lifecycle and access control); concretizes the deactivation intent of UC-01
- **Static/Dynamic:** Dynamic
- **Influences:** `03_use-cases.md`, `04_data-model.md`, `05_system-design.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`, `05_system-design.md`
- **Related rules:** BR-013, BR-016
- **Notes/Rationale:** States the behavioral effect of the `inactive` account state that the use cases' `active account` preconditions rely on: an inactive account is denied access rather than removed, so request history stays intact (`03_use-cases.md` UC-01 notes; BR-017). Deactivation itself is performed by an administrator under BR-013; this rule governs what the resulting state permits. `dynamic` because the lifecycle states may grow.

## Requests Domain

### BR-002 — Requests.Category.SinglePerRequest
- **Statement:** A request is filed under exactly one request category.
- **Type:** Fact
- **Source:** Organizational policy (request classification); rests on a `01_miniworld.md` §6 tagged assumption
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Upstream Impact Review:** `01_miniworld.md` §6 — see `02a` §9
- **Notes/Rationale:** Multi-category filing is not indicated for v1; revisit if added.

### BR-003 — Requests.Ownership.SingleCitizen
- **Statement:** A request is submitted by, and belongs to, exactly one citizen — its owner.
- **Type:** Fact
- **Source:** Organizational policy (request ownership); rests on a `01_miniworld.md` §6 tagged assumption
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Upstream Impact Review:** `01_miniworld.md` §6 — see `02a` §9
- **Related rules:** BR-016
- **Notes/Rationale:** Acting on behalf of another is out of v1 scope; `dynamic` because representation may be added later.

### BR-004 — Requests.Status.DefinedSet
- **Statement:** A request's status is one of Draft, Submitted, In Review, Waiting for Citizen, Ready for Decision, or Decided.
- **Type:** Fact
- **Source:** Product-owner v1 workflow policy (defined request path)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Related rules:** BR-005, BR-008
- **Notes/Rationale:** Enumerates the recognized stages; the ordered transition flow between them is workflow and belongs to `03`. `dynamic` because the stage set may grow. Source is a v1 workflow decision, not a timeless institutional policy.

### BR-005 — Requests.PostSubmission.ControlledInput
- **Statement:** After submission, a citizen may add information or documents to a request only in response to a staff member's request for missing information.
- **Type:** Constraint
- **Source:** Organizational policy (controlled post-submission input)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`
- **Downstream impact:** `03_use-cases.md`
- **Related rules:** BR-004, BR-011
- **Notes/Rationale:** Classified Constraint over Action Enabler: its business force restricts *when* input is allowed (see `02a` §3). Free editing after submission is out of v1.

## Documents Domain

### BR-006 — Documents.Kind.SupportingOrDecision
- **Statement:** A document is either a supporting file provided with a request or a decision document produced for it.
- **Type:** Fact
- **Source:** Organizational policy (document handling)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Related rules:** BR-016
- **Notes/Rationale:** Distinguishes the two document kinds the institution recognizes for a request.

## Review Workflow Domain

### BR-007 — ReviewWorkflow.Decision.HumanReview
- **Statement:** Every decision on a request is made by a staff member.
- **Type:** Constraint
- **Source:** Organizational policy (human accountability for institutional decisions)
- **Static/Dynamic:** Static
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `05_system-design.md`
- **Downstream impact:** `03_use-cases.md`, `05_system-design.md`
- **Upstream Impact Review:** `00_project-context.md` §7 — see `02a` §9
- **Related rules:** BR-008, BR-017
- **Notes/Rationale:** Marked `static` as a foundational governance principle. Automated approval or rejection without human review is the excluded opposite (`00` §7); the recording and traceability of the decision are governed by BR-017.

### BR-008 — ReviewWorkflow.Decision.Outcome
- **Statement:** A recorded decision's outcome is either Approved or Rejected.
- **Type:** Fact
- **Source:** Product-owner v1 workflow policy (simple decision outcomes)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Related rules:** BR-004, BR-007
- **Notes/Rationale:** Missing information is carried by the Waiting for Citizen status (BR-004), not as a decision outcome.

### BR-009 — ReviewWorkflow.Assignment.SingleResponsibleStaff
- **Statement:** An assigned request has exactly one responsible staff member.
- **Type:** Fact
- **Source:** Organizational policy (single accountable handler)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Related rules:** BR-010
- **Representation:** see Table BR-016
- **Notes/Rationale:** States the cardinality of an assignment, not that every submitted request is already assigned — a submitted request may be temporarily unassigned until an administrator assigns it (`01` §5; see `02a` §9). `dynamic` because shared handling may be wanted later.

### BR-010 — ReviewWorkflow.Assignment.ByAdministrator
- **Statement:** A request assignment is performed by an administrator.
- **Type:** Constraint
- **Source:** Organizational policy (assignment authority)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`
- **Downstream impact:** `03_use-cases.md`
- **Related rules:** BR-009
- **Representation:** see Table BR-016
- **Notes/Rationale:** Assignment is performed by hand in v1; automated assignment is a later-phase scope item, not part of this rule.

## Communication Domain

### BR-011 — Communication.Message.Participants
- **Statement:** A request message is exchanged between the request's citizen and the staff member responsible for handling that request.
- **Type:** Fact
- **Source:** Organizational policy (request communication)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `04_data-model.md`
- **Downstream impact:** `03_use-cases.md`, `04_data-model.md`
- **Related rules:** BR-005, BR-009, BR-016
- **Notes/Rationale:** Defines the message relationship and ties it to the responsible staff member; who may *read* a message is governed by BR-016, not restated here.

## Administration Domain

### BR-012 — Administration.Categories.MaintainedByAdmin
- **Statement:** Request categories are created and maintained by administrators.
- **Type:** Constraint
- **Source:** Organizational policy (administrative authority)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`
- **Downstream impact:** `03_use-cases.md`
- **Representation:** see Table BR-016
- **Notes/Rationale:** Restricts maintenance of the category set to the administrator role.

### BR-013 — Administration.Accounts.MaintainedByAdmin
- **Statement:** User accounts and their roles are created and maintained by administrators.
- **Type:** Constraint
- **Source:** Organizational policy (administrative authority)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`
- **Downstream impact:** `03_use-cases.md`
- **Related rules:** BR-001, BR-018
- **Representation:** see Table BR-016
- **Notes/Rationale:** Restricts account and role management to the administrator role.

### BR-014 — Administration.Settings.MaintainedByAdmin
- **Statement:** Organization settings are configured by administrators.
- **Type:** Constraint
- **Source:** Organizational policy (administrative authority)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`
- **Downstream impact:** `03_use-cases.md`
- **Representation:** see Table BR-016
- **Notes/Rationale:** Restricts organization-level configuration to the administrator role.

## Reporting Domain

### BR-015 — Reporting.Summaries.StaffAndAdminOnly
- **Statement:** Reporting summaries are accessible to staff members and administrators.
- **Type:** Constraint
- **Source:** Organizational policy (reporting access)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `05_system-design.md`
- **Downstream impact:** `03_use-cases.md`, `05_system-design.md`
- **Related rules:** BR-016
- **Representation:** see Table BR-016
- **Notes/Rationale:** Reporting summaries are institution-side; citizens are not among the authorized viewers.

## Cross-Cutting Rules

<!-- NON-DOMAIN section: do not treat this heading as a domain in 03/04 -->

### BR-016 — CrossCutting.Visibility.RequestScopedAccess
- **Statement:** Request information, documents, and messages are visible only to the owning citizen, the responsible staff member, and authorized administrators.
- **Type:** Constraint
- **Source:** EU GDPR / DSGVO privacy principles, concretized by organizational policy (privacy-conscious request handling by the PermitFlow institution)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `05_system-design.md`
- **Downstream impact:** `03_use-cases.md`, `05_system-design.md`
- **Upstream Impact Review:** `00_project-context.md` §6 — see `02a` §9
- **Related rules:** BR-001, BR-003, BR-006, BR-009, BR-011, BR-015
- **Representation:** see Table BR-016
- **Notes/Rationale:** Access is scoped per request, not by role alone (`01` §7): a citizen reaches only their own requests, a staff member only the requests they are responsible for, and administrators only what oversight requires. The named external privacy basis is EU GDPR / DSGVO; the concrete request-scoped access model is the institution's policy implementation of that obligation. Visibility is never public.

### BR-017 — CrossCutting.Traceability.StatusAndDecisions
- **Statement:** A request's important status changes and decisions are recorded so they remain understandable afterward.
- **Type:** Constraint
- **Source:** Organizational policy (traceable decisions and request progress)
- **Static/Dynamic:** Dynamic
- **Influences:** `01_miniworld.md`, `03_use-cases.md`, `05_system-design.md`
- **Downstream impact:** `03_use-cases.md`, `05_system-design.md`
- **Upstream Impact Review:** `00_project-context.md` §6 — see `02a` §9
- **Related rules:** BR-007
- **Notes/Rationale:** A lightweight, human-readable history rather than a comprehensive audit apparatus (`01_miniworld.md` §7).

## Representations (rule-layer only)

<!-- NON-DOMAIN section: do not treat this heading as a domain in 03/04 -->

### Table BR-016 — Roles & permissions matrix

Documents the role-restricted constraints at the business-rule layer only (no
interface, mechanism, or data structure). `✓` = the role is permitted the business
operation; scope notes (own / assigned) reflect BR-016.

| Business operation (`01_miniworld.md` §4) | Citizen | Staff member | Administrator | Rules |
| --- | :---: | :---: | :---: | --- |
| Submit a request | ✓ (own) | | | BR-003 |
| Provide information when staff requests it | ✓ (own) | | | BR-005 |
| Track request progress | ✓ (own) | ✓ (responsible) | ✓ | BR-016 |
| Review / verify an assigned request | | ✓ (responsible) | | BR-009, BR-016 |
| Record a decision | | ✓ (responsible) | | BR-007, BR-008 |
| Assign a request to a staff member | | | ✓ | BR-010 |
| Manage request categories | | | ✓ | BR-012 |
| Manage user accounts and roles | | | ✓ | BR-013 |
| Manage organization settings | | | ✓ | BR-014 |
| View reporting summaries | | ✓ | ✓ | BR-015 |