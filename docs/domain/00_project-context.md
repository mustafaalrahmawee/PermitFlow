# Project Context — PermitFlow

## 1. Identity

- **Name:** PermitFlow
- **Product category:** Gov-tech case-management and citizen-request portal

## 2. Description

PermitFlow is a central portal where citizens submit formal applications or permit requests and follow their progress transparently. Staff members review submitted requests, ask for missing information, record decisions, and keep each request's progress up to date. Administrators manage user accounts, request categories, and basic organization settings. The first version focuses on a stable core workflow that is useful on its own.

## 3. Users

### Active Users

- Citizen — submits requests, provides supporting information, replies to staff questions, reviews request details before submission, and tracks request progress.
- Staff member — reviews assigned requests, checks submitted information, communicates with citizens, records decisions, and updates request progress.
- Administrator — manages user accounts, request categories, organization settings, and overall system oversight.

### External Actors

- _None._

## 4. Business Goal

PermitFlow makes the handling of citizen requests more structured, transparent, and traceable. It replaces scattered communication, missing information, and unclear status states by giving every request a clear path from submission to decision.

## 5. Anticipated Domains

- `Identity and Access` — user roles, access boundaries, and account-level responsibilities.
- `Requests` — creation, submission, viewing, and lifecycle of citizen requests.
- `Documents` — supporting files and decision documents connected to a request.
- `Review Workflow` — staff review, status changes, missing-information handling, decision steps, and progress tracking.
- `Communication` — questions, replies, staff–citizen messages, and request-related notifications.
- `Administration` — management of users, request categories, and organization-level settings.
- `Reporting` — basic summary views of request volume, request status, and processing progress.

## 6. Hard Constraints

- Phase 1 must work without AI — the core request, review, communication, document, decision, and tracking workflow must be useful on its own; AI assistance is reserved for a later extension and is not modeled as active v1 functionality.
- Single organization in v1 — the project stays focused on one institution.
- Single interface language in v1 — translation and multilingual workflows are not part of the initial scope.
- No payment handling in v1 — fees and online payments are excluded.
- Privacy-conscious request handling — request information, documents, and messages are visible only to authorized users; the external privacy basis is EU GDPR / DSGVO, implemented as request-scoped access (formalized as BR-016).
- Traceable decisions and request progress — important status changes and decisions remain understandable after they happen (formalized as BR-017).
- Single-developer portfolio scope — the first version stays realistic for one developer to design, build, document, and present.

## 7. Out of Scope (v1)

- AI-assisted citizen intake, request summaries, and missing-information detection
- Mobile app
- Integration with external government systems
- Real identity verification
- Digital signature workflows
- Advanced reporting, analytics, and forecasting
- Appeal or objection workflows after a decision
- Appointment booking with staff members
- Representatives acting on behalf of citizens within the system
- Automated approval or rejection decisions without human review (the positive rule is BR-007: every decision is made by a staff member)
- Anonymous public request search
- Request withdrawal after a request has been submitted

## 8. Open Questions

- _None._