# Miniworld — PermitFlow

## 1. Domain Overview

PermitFlow is the shared place where members of the public bring formal
applications and permit requests to a public institution, and where that
institution handles them. The domain is the everyday business
of receiving a citizen's request, gathering whatever is needed to decide it,
reaching a decision, and keeping the citizen informed at every step along the
way.

Today this kind of work tends to be scattered: communication happens in many
places, information arrives incomplete, and the citizen is often left unsure of
where their request stands. PermitFlow replaces that with one transparent
process: every request follows a clear path from submission to decision, and
both sides can see where it is and what happens next.

Three groups of people work inside the system. **Citizens** raise requests and
follow their progress. **Staff members** of the institution review requests, ask
for anything missing, talk with the citizen, and record decisions. **Administrators**
keep user accounts, request categories, and organization settings in order.

The first version is deliberately narrow so that this core is useful on its own.
It covers a single organization, a single interface language, no fees or
payments, and no automated assistance — the manual workflow of submit, review,
communicate, decide, and track must stand on its own merits before anything is
added on top of it.

## 2. Stakeholders and Actors

### Active Users

- **Citizen** — submits requests, supplies supporting information, reviews a
  request before sending it, replies to staff questions, and follows the
  progress of their own requests.
- **Staff member** — reviews the requests they are responsible for, checks the
  information submitted, asks the citizen for anything missing, communicates with
  the citizen, records the decision, and keeps each request's progress up to
  date.
- **Administrator** — manages user accounts, maintains the request categories,
  configures organization settings, and oversees the system as a whole.

### External Actors

- _None._

## 3. Business Objects (the "nouns")

- **Request** — a formal application or permit request raised by a citizen and
  handled by the institution.
- **Request category** — a classification that says what kind of request this is
  and is maintained by administrators.
- **Request status** — the current stage of a request along its path from
  submission to decision.
- **Document** — a supporting file provided with a request, or a decision
  document produced for it.
- **Decision** — the recorded outcome a staff member reaches on a request.
- **Request history** — the understandable record of the important status changes
  and decisions made while a request is handled.
- **Message** — a piece of communication between a citizen and a staff member,
  including a staff member's request for missing information and the citizen's
  reply.
- **Notification** — an alert that informs a user that something has happened on
  a request they are involved with.
- **User account** — the record by which a person is known to the system and
  through which they sign in and act.
- **Role** — the kind of user a person is (citizen, staff member, or
  administrator), which sets what they are allowed to see and do.
- **Organization settings** — the institution-level configuration administrators
  maintain for the single organization the system serves.

## 4. Business Processes (the "verbs")

- **Submit a request** — a citizen creates and sends a request into the
  institution (acts on Request).
- **Review a request before submission** — a citizen checks the details of a
  request they are about to send (acts on Request).
- **Provide supporting information** — a citizen attaches files or supplies
  details for a request (acts on Document, Request).
- **Track request progress** — a citizen follows where their request stands
  (acts on Request, Request status).
- **Assign a request for handling** — a submitted request becomes the
  responsibility of a staff member (acts on Request).
- **Review an assigned request** — a staff member examines a request they are
  responsible for (acts on Request).
- **Verify submitted information** — a staff member checks the information and
  files provided (acts on Request, Document).
- **Request missing information** — a staff member asks the citizen to supply
  something that is missing (acts on Message, Request).
- **Exchange messages** — a citizen and a staff member ask and answer questions
  about a request (acts on Message).
- **Record a decision** — a staff member sets and records the outcome of a
  request, kept in the request history (acts on Decision, Request, Request
  history).
- **Update request progress** — a staff member advances a request's stage and
  notes the change in the request history (acts on Request status, Request
  history).
- **Send notifications** — the system informs the relevant users when something
  changes on a request (acts on Notification).
- **Manage user accounts** — an administrator creates and maintains accounts and
  the role each person holds (acts on User account, Role).
- **Manage request categories** — an administrator maintains the set of request
  categories (acts on Request category).
- **Manage organization settings** — an administrator configures the
  institution-level settings (acts on Organization settings).
- **View reporting summaries** — staff and administrators look at basic summaries
  of request volume, status, and processing progress (acts on Request, Request
  status).

## 5. Business Rules and Scope Signals

- **Single clear path** — every request follows one understandable path from
  submission to decision; scattered communication and unclear status are what
  the system exists to remove.
- **Requests are categorized** — each request is filed under a request category
  that administrators maintain.
- **Human review** — every decision is reached and recorded by a staff member,
  not produced automatically.
- **Authorized visibility** — a request's information, documents, and messages
  are visible only to the people authorized to see them, governed by each user's
  role.
- **Traceability** — important status changes and decisions stay understandable
  after the fact, so the history of a request can be followed.
- **Manual assignment in v1** — assignment is done by hand rather than decided
  automatically; a submitted request stays unassigned until an administrator
  assigns it to one staff member.
- **Controlled post-submission input** — after submission, a citizen may add
  information or documents only when a staff member requests missing information;
  free editing after submission is not part of v1.
- **Defined request path in v1** — a request moves through Draft, Submitted, In
  Review, Waiting for Citizen, Ready for Decision, and Decided.
- **Simple decision outcomes in v1** — a recorded decision is either Approved or
  Rejected; missing information is handled by the Waiting for Citizen status, not
  as a decision outcome.
- **In-portal notifications only in v1** — users see request-related
  notifications inside the portal; delivery outside the portal belongs to a later
  phase.
- **Single organization (v1)** — the first version serves exactly one
  institution.
- **Single interface language (v1)** — the first version is presented in one
  language.
- **No fees or payments (v1)** — the first version handles no money.
- **Works without automated assistance (v1)** — the core workflow is complete
  and useful with no AI involvement; any such help belongs to a later phase.
- **Realistic single-developer scope (v1)** — the first version stays within what
  one person can design, build, document, and present.

## 6. Assumptions

- **One category per request** — a request is filed under a single category;
  multi-category filing is not indicated by the context.
  *(formalized as BR-002; remains an assumption pending validation)*
- **One submitting citizen** — a request is submitted by the citizen who owns it;
  acting on behalf of someone else is not part of v1.
  *(formalized as BR-003; remains an assumption pending validation)*

## 7. Resolved Tensions

- **Traceability vs. single-developer scope** — v1 keeps a lightweight,
  human-readable history of status changes and decisions rather than a
  comprehensive audit apparatus.
- **Authorized visibility vs. transparent progress** — transparency is scoped to
  each citizen's own requests and the staff and administrators authorized to
  handle them; it is never public, so privacy and transparency coexist.

## 8. Out of Scope (v1)

- AI-assisted intake, request summaries, and missing-information detection
- Mobile application
- Integration with external government systems
- Real identity verification of citizens
- Digital signature workflows
- Advanced reporting, analytics, and forecasting
- Appeal or objection workflows after a decision
- Appointment booking with staff members
- Representatives acting on behalf of citizens within the system
- Automated approval or rejection without human review
- Anonymous public request search
- Request withdrawal after a request has been submitted

## 9. Open Questions

- _None._

## 10. Traceability Notes

- **Primary source:** `00_project-context.md`
- **Directly derived:** the three active users and the empty external-actor list;
  the objects Request, Request category, Request status, Document, Decision,
  Message, Notification, User account, Role, Organization settings; the processes
  for submit, review-before-submission, provide information, track, review,
  verify, request missing information, exchange messages, record decision, update
  progress, notify, manage accounts/categories/settings, and reporting; the v1
  scope signals (single organization, single language, no payments, no automated
  assistance, authorized visibility, traceability, single-developer scope).
- **Inferred:** assignment of a request for handling; the request history implied
  by the traceability constraint; sign-in as the act behind an active user.
- **Assumptions needing validation:** one category per request; one submitting
  citizen.
- **Resolved for v1 (product-owner decisions):** manual assignment by an
  administrator to one staff member; citizen additions after submission only when
  staff request missing information; the Draft → Submitted → In Review → Waiting
  for Citizen → Ready for Decision → Decided path; Approved and Rejected as the
  decision outcomes; in-portal notifications only.