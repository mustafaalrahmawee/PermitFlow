# Use Cases — PermitFlow

## Identity and Access Domain

### UC-01 — Manage User Accounts and Roles
- **Scope:** PermitFlow
- **Primary Actor:** Administrator
- **Supporting Actors:** `_None_`
- **Level:** User-Goal 🌊
- **Trigger:** The administrator needs to create or maintain a user account, assign its role, or change its account state.
- **Preconditions:** The administrator has access to administration functions; the person to be represented by the account is known to the institution.
- **Main Success Scenario:**
  1. The administrator opens user account management.
  2. The administrator chooses whether to create a new account or maintain an existing account.
  3. The administrator enters or reviews the account details.
  4. The administrator selects exactly one role for the account.
  5. The administrator chooses the account state where account activation or deactivation is relevant.
  6. The system validates the account details, selected role, account state, and lifecycle impact.
  7. The system saves the user account, role, and account state.
  8. The system makes the account available according to the assigned role and account state.
- **Extensions:**
  - **2a.** The selected existing account cannot be found → the system leaves existing accounts unchanged and asks the administrator to choose another account.
  - **4a.** No role or more than one role is selected → the system rejects the change and asks for exactly one role; reference `BR-001`.
  - **5a.** The administrator tries to deactivate a citizen account that owns an undecided request → the system blocks deactivation until the request reaches a decided state.
  - **5b.** The administrator tries to deactivate a staff account that is responsible for an undecided request → the system blocks deactivation until the request is reassigned or decided.
  - **5c.** The administrator tries to change the role of a user account that is connected to request ownership, responsibility, messages, or decisions → the system blocks the role change in v1 to protect request history and responsibility.
  - **6a.** A non-authorized actor attempts account maintenance → the system denies the action; reference `BR-013`, `BR-016`.
  - **7a.** The account change cannot be saved → existing user accounts, roles, and account states remain unchanged.
- **Guarantees:**
  - Minimal (on failure): Existing user accounts, roles, account states, request ownership, responsibility, and historical links remain unchanged.
  - Success (on success): The user account exists with exactly one role and an allowed account state, and can be used according to that role and state.
- **Business objects touched:** `User account`, `Role`, `Request`, `Message`, `Decision`, `Request history`
- **Business Rules:** `BR-001`, `BR-013`, `BR-016`, `BR-017`
- **Notes:** v1 uses deactivation rather than physical deletion for accounts that have request history. Role changes are deliberately conservative because changing historical ownership or responsibility would weaken traceability.

## Requests Domain

### UC-02 — Submit a Request
- **Scope:** PermitFlow
- **Primary Actor:** Citizen
- **Supporting Actors:** Administrator
- **Level:** User-Goal 🌊
- **Trigger:** The citizen wants to send a formal application or permit request to the institution.
- **Preconditions:** The citizen has an active user account with the Citizen role; at least one active request category is available.
- **Main Success Scenario:**
  1. The citizen starts a new request.
  2. The system creates the request as a draft owned by the citizen.
  3. The citizen selects one active request category.
  4. The citizen enters the required request information.
  5. The citizen attaches supporting documents when needed.
  6. The citizen reviews the request before submission.
  7. The citizen submits the request.
  8. The system changes the request status from Draft to Submitted.
  9. The system makes the submitted request visible to authorized institution users for handling.
  10. The system creates an in-portal notification for administrators that a submitted request needs assignment.
- **Extensions:**
  - **3a.** The citizen selects no category or attempts multiple categories → the system blocks submission until exactly one active category is selected; reference `BR-002`.
  - **3b.** The selected category is inactive → the system prevents the category from being used for a new request.
  - **5a.** The citizen attaches a document that is not a supporting file for the request → the system rejects the attachment for this use case; reference `BR-006`.
  - **5b.** The system cannot accept or store a supporting document → the system rejects that document, keeps the request editable, and shows that the document was not attached.
  - **6a.** The citizen finds wrong or missing information during review → the request remains Draft and the citizen may correct it before submission.
  - **7a.** The citizen attempts to submit a request that belongs to someone else → the system denies the action; reference `BR-003`, `BR-016`.
  - **8a.** The request cannot move to Submitted → the request remains Draft and the citizen sees that submission did not complete; reference `BR-004`.
  - **10a.** The administrator notification cannot be created → the request remains Submitted, and authorized administrators can still find it in the submitted-request list.
- **Guarantees:**
  - Minimal (on failure): No incomplete submission is treated as submitted; the citizen's draft remains available where possible.
  - Success (on success): The request is submitted, owned by the citizen, categorized, ready for institutional handling, and visible to administrators for assignment.
- **Business objects touched:** `Request`, `Request category`, `Request status`, `Document`, `Notification`, `User account`, `Role`
- **Business Rules:** `BR-001`, `BR-002`, `BR-003`, `BR-004`, `BR-006`, `BR-016`
- **Notes:** The process “Review a request before submission” is subsumed into steps 6–7 because it serves the submit goal. Exact upload limits are not defined in the current business rules and are therefore not specified here.

### UC-03 — Track Request Progress
- **Scope:** PermitFlow
- **Primary Actor:** Citizen
- **Supporting Actors:** `_None_`
- **Level:** User-Goal 🌊
- **Trigger:** The citizen wants to know where their submitted request stands.
- **Preconditions:** The citizen has at least one request in the system.
- **Main Success Scenario:**
  1. The citizen opens their request list.
  2. The system shows only requests the citizen owns.
  3. The citizen selects a request.
  4. The system shows the current request status.
  5. The system shows the understandable request history for important status changes, assignments, reassignments, and decisions.
  6. The citizen reviews any visible messages, requested information, documents, or decision information connected to the request.
- **Extensions:**
  - **2a.** The citizen has no requests → the system shows an empty request list and no request detail is opened.
  - **3a.** The citizen attempts to open a request they do not own → the system denies access; reference `BR-003`, `BR-016`.
  - **5a.** A status change or decision exists but is not understandable to the citizen → the system must not present unclear progress as complete; reference `BR-017`.
  - **6a.** A requested response is pending → the system shows the request as Waiting for Citizen; reference `BR-004`, `BR-005`.
- **Guarantees:**
  - Minimal (on failure): The citizen does not gain access to another person's request information.
  - Success (on success): The citizen can see the current status and understandable progress of their own request.
- **Business objects touched:** `Request`, `Request status`, `Request history`, `Message`, `Document`, `Decision`, `Notification`
- **Business Rules:** `BR-003`, `BR-004`, `BR-005`, `BR-016`, `BR-017`
- **Notes:** This use case protects transparency without making request information public.

## Documents Domain

### UC-04 — Provide Supporting Information
- **Scope:** PermitFlow
- **Primary Actor:** Citizen
- **Supporting Actors:** Staff member
- **Level:** User-Goal 🌊
- **Trigger:** The citizen needs to provide documents or details for a request, either before submission or after staff requested missing information.
- **Preconditions:** The citizen owns the request; the request is either still Draft or is Waiting for Citizen because staff requested missing information.
- **Main Success Scenario:**
  1. The citizen opens the relevant request.
  2. The system confirms that the citizen may provide information at the request's current status.
  3. The citizen enters the requested details or attaches supporting documents.
  4. The citizen reviews the provided information.
  5. The citizen sends the information to the system.
  6. The system stores the information with the request.
  7. If the request was Waiting for Citizen, the system changes the request status back to In Review.
  8. If the status changed, the system records the status change in the request history.
  9. The system makes the information visible to the responsible staff member.
  10. The system creates an in-portal notification for the responsible staff member.
- **Extensions:**
  - **1a.** The citizen attempts to open a request they do not own → the system denies access; reference `BR-003`, `BR-016`.
  - **2a.** The request is already submitted and no staff request for missing information is open → the system blocks free post-submission input; reference `BR-005`.
  - **3a.** The attached document is not a supporting file for this request → the system rejects that document; reference `BR-006`.
  - **3b.** The system cannot accept or store a supporting document → the system rejects that document, keeps the existing request content unchanged, and shows that the document was not attached.
  - **5a.** The citizen cancels before sending → the request and existing documents remain unchanged.
  - **7a.** The request cannot move from Waiting for Citizen back to In Review → the system does not complete the response as a returned review item; reference `BR-004`, `BR-017`.
  - **9a.** No responsible staff member is assigned yet → the information remains connected to the request, and visibility waits for assignment; reference `BR-009`, `BR-016`.
  - **10a.** The notification cannot be created → the information remains stored and visible to the responsible staff member.
- **Guarantees:**
  - Minimal (on failure): The citizen does not add uncontrolled post-submission information; existing request content remains unchanged.
  - Success (on success): The supporting information is connected to the request, the request returns to review where applicable, and the authorized staff member can see the new information.
- **Business objects touched:** `Request`, `Request status`, `Document`, `Message`, `Request history`, `Notification`
- **Business Rules:** `BR-003`, `BR-004`, `BR-005`, `BR-006`, `BR-009`, `BR-016`, `BR-017`
- **Notes:** Exact supporting-document file limits are not defined in the current business rules and are therefore not specified here. Submitted documents remain connected to the request history and cannot be silently removed by the citizen after submission.

## Review Workflow Domain

### UC-05 — Assign or Reassign a Request for Handling
- **Scope:** PermitFlow
- **Primary Actor:** Administrator
- **Supporting Actors:** Staff member
- **Level:** User-Goal 🌊
- **Trigger:** A submitted or active request needs a responsible staff member, or an already assigned request needs a different responsible staff member.
- **Preconditions:** The request has been submitted; at least one active staff member account exists.
- **Main Success Scenario:**
  1. The administrator opens the list of requests that need assignment or reassignment.
  2. The administrator selects one eligible request.
  3. The administrator selects one active staff member to become responsible for the request.
  4. If the request is already assigned, the administrator enters a short reassignment reason.
  5. The system validates that the assignment or reassignment is made by an administrator.
  6. The system validates that the request status allows assignment or reassignment.
  7. The system assigns the request to the selected staff member.
  8. If this is a reassignment, the system records the previous responsible staff member, new responsible staff member, administrator, timestamp, and reason in the request history.
  9. The system makes the request visible to the selected responsible staff member.
  10. The system creates an in-portal notification for the selected responsible staff member.
  11. If this is a reassignment, the system also creates an in-portal notification for the previous responsible staff member.
- **Extensions:**
  - **1a.** No requests need assignment or reassignment → the system shows that there is nothing to assign.
  - **2a.** The selected request is Draft → the system blocks assignment because a draft has not been submitted; reference `BR-004`.
  - **2b.** The selected request is Decided → the system blocks assignment or reassignment because the request is closed in v1; reference `BR-004`.
  - **3a.** No staff member or more than one responsible staff member is selected → the system blocks the assignment until exactly one staff member is selected; reference `BR-009`.
  - **3b.** The selected staff account is inactive or does not have the Staff member role → the system blocks the assignment; reference `BR-001`, `BR-009`.
  - **4a.** A reassignment reason is missing → the system blocks reassignment until the administrator enters a short reason.
  - **5a.** A non-administrator attempts assignment or reassignment → the system denies the action; reference `BR-010`, `BR-016`.
  - **6a.** The request status is not Submitted, In Review, Waiting for Citizen, or Ready for Decision → the system blocks the assignment or reassignment; reference `BR-004`.
  - **8a.** The reassignment trace cannot be recorded → the system does not complete the reassignment.
- **Guarantees:**
  - Minimal (on failure): The request is not assigned to an unauthorized, inactive, ambiguous, or untraceable responsible staff member.
  - Success (on success): The request has exactly one responsible staff member, the request is visible for handling, and reassignment is traceable where it happened.
- **Business objects touched:** `Request`, `Request status`, `Request history`, `User account`, `Role`, `Notification`
- **Business Rules:** `BR-001`, `BR-004`, `BR-009`, `BR-010`, `BR-016`, `BR-017`
- **Notes:** Reassignment is allowed in v1 before a request is Decided. Draft requests cannot be assigned, and Decided requests are terminal for v1.

### UC-06 — Review an Assigned Request
- **Scope:** PermitFlow
- **Primary Actor:** Staff member
- **Supporting Actors:** Citizen
- **Level:** User-Goal 🌊
- **Trigger:** The responsible staff member starts reviewing a request assigned to them.
- **Preconditions:** The request is assigned to the staff member; the request is visible to them under request-scoped access.
- **Main Success Scenario:**
  1. The staff member opens their assigned requests.
  2. The system shows requests for which the staff member is responsible.
  3. The staff member selects a request.
  4. The system shows the submitted request information, supporting documents, current status, messages, and request history.
  5. If the request is Submitted, the system allows the staff member to start review and changes the request status to In Review.
  6. The staff member verifies the submitted information and supporting documents.
  7. The staff member decides whether the request needs missing information, can move forward, or is ready for decision.
  8. The system keeps the review state understandable through the request's status and history.
- **Extensions:**
  - **2a.** The staff member has no assigned requests → the system shows an empty assigned-request list.
  - **3a.** The staff member attempts to open a request assigned to someone else → the system denies access; reference `BR-009`, `BR-016`.
  - **5a.** The request cannot move from Submitted to In Review → the system does not treat the review as started; reference `BR-004`, `BR-017`.
  - **6a.** Submitted information is incomplete → the staff member continues with UC-07 to request missing information.
  - **6b.** A supporting document cannot be treated as a supporting or decision document → the staff member cannot rely on it for review; reference `BR-006`.
  - **7a.** The request is not at a status that supports the intended next step → the system blocks that step; reference `BR-004`.
  - **8a.** The review action would leave no understandable trace for an important status change → the system blocks completion until the change is recorded; reference `BR-017`.
- **Guarantees:**
  - Minimal (on failure): The staff member does not gain access to requests outside their responsibility.
  - Success (on success): The staff member understands the request state and can choose the correct next review action.
- **Business objects touched:** `Request`, `Request status`, `Document`, `Message`, `Request history`, `User account`, `Role`
- **Business Rules:** `BR-001`, `BR-004`, `BR-006`, `BR-009`, `BR-016`, `BR-017`
- **Notes:** The process “Verify submitted information” is subsumed here because verification is part of reviewing an assigned request.

### UC-07 — Request Missing Information
- **Scope:** PermitFlow
- **Primary Actor:** Staff member
- **Supporting Actors:** Citizen
- **Level:** User-Goal 🌊
- **Trigger:** The responsible staff member finds that a request lacks information needed for review.
- **Preconditions:** The request is assigned to the staff member; the request is In Review and missing information may be requested.
- **Main Success Scenario:**
  1. The staff member opens the assigned request.
  2. The staff member chooses to request missing information.
  3. The staff member writes a message that explains what the citizen must provide.
  4. The system records the message on the request.
  5. The system changes the request status to Waiting for Citizen.
  6. The system records the status change in the request history.
  7. The system creates an in-portal notification for the citizen.
- **Extensions:**
  - **1a.** The request is not assigned to this staff member → the system denies the action; reference `BR-009`, `BR-016`.
  - **2a.** The request status does not allow a missing-information request → the system blocks the action; reference `BR-004`.
  - **3a.** The staff member leaves the message empty → the system does not send the request for information and asks for a clear message.
  - **4a.** The message participant relationship is invalid → the system blocks the message; reference `BR-011`.
  - **5a.** The status cannot be changed to Waiting for Citizen → the system leaves the request in its prior status and does not notify the citizen; reference `BR-004`, `BR-017`.
  - **7a.** The notification cannot be created → the missing-information request remains recorded and visible to the citizen inside the request.
- **Guarantees:**
  - Minimal (on failure): The citizen is not notified of a missing-information request that was not recorded.
  - Success (on success): The citizen receives a clear in-portal request for missing information, and the request waits for citizen input.
- **Business objects touched:** `Request`, `Request status`, `Message`, `Request history`, `Notification`
- **Business Rules:** `BR-004`, `BR-009`, `BR-011`, `BR-016`, `BR-017`
- **Notes:** This use case is high-risk because it opens controlled post-submission input; the extension set keeps that boundary explicit.

### UC-08 — Update Request Progress
- **Scope:** PermitFlow
- **Primary Actor:** Staff member
- **Supporting Actors:** Citizen
- **Level:** User-Goal 🌊
- **Trigger:** The responsible staff member needs to move a request to the next understandable status.
- **Preconditions:** The request is assigned to the staff member; the intended status belongs to the defined status set.
- **Main Success Scenario:**
  1. The staff member opens the assigned request.
  2. The staff member chooses the next appropriate request status.
  3. The system validates the status against the defined status set.
  4. The system validates the status change against the allowed v1 transition graph.
  5. The system changes the request status.
  6. The system records the important status change in the request history.
  7. The system creates an in-portal notification for the citizen when the change is relevant to the citizen.
- **Extensions:**
  - **1a.** The request is not assigned to this staff member → the system denies the action; reference `BR-009`, `BR-016`.
  - **2a.** The chosen status is outside the defined set → the system rejects the change; reference `BR-004`.
  - **4a.** The requested transition is not allowed in the v1 transition graph → the system blocks the change and leaves the request unchanged; reference `BR-004`.
  - **5a.** The status change cannot be applied → the request status remains unchanged.
  - **6a.** The status change cannot be recorded understandably → the system does not complete the progress change; reference `BR-017`.
  - **7a.** The notification cannot be created → the status change remains recorded and visible in the request.
- **Guarantees:**
  - Minimal (on failure): The request does not move to an undefined, disallowed, or untraceable status.
  - Success (on success): The request has the new status and the progress change is understandable afterward.
- **Business objects touched:** `Request`, `Request status`, `Request history`, `Notification`
- **Business Rules:** `BR-004`, `BR-009`, `BR-016`, `BR-017`
- **Notes:** The allowed v1 transition graph is: Draft → Submitted; Submitted → In Review; In Review → Waiting for Citizen; Waiting for Citizen → In Review; In Review → Ready for Decision; Ready for Decision → Decided. Decided is terminal in v1.

### UC-09 — Record a Decision
- **Scope:** PermitFlow
- **Primary Actor:** Staff member
- **Supporting Actors:** Citizen
- **Level:** User-Goal 🌊
- **Trigger:** The responsible staff member has enough information to decide the request.
- **Preconditions:** The request is assigned to the staff member; the request is Ready for Decision; the staff member has reviewed the submitted information.
- **Main Success Scenario:**
  1. The staff member opens the assigned request.
  2. The staff member chooses to record a decision.
  3. The staff member selects the decision outcome.
  4. The staff member adds decision information or one decision document where needed.
  5. The system validates that the decision is made by the responsible staff member.
  6. The system validates the decision outcome and decision document.
  7. The system records the decision on the request.
  8. The system changes the request status to Decided.
  9. The system records the decision and status change in the request history.
  10. The system creates an in-portal notification for the citizen.
- **Extensions:**
  - **1a.** The request is not assigned to this staff member → the system denies the action; reference `BR-009`, `BR-016`.
  - **2a.** The request is not Ready for Decision → the system blocks decision recording until the request reaches the correct status; reference `BR-004`.
  - **3a.** The selected outcome is neither Approved nor Rejected → the system rejects the decision outcome; reference `BR-008`.
  - **4a.** The attached decision document is not a decision document for this request → the system rejects the document; reference `BR-006`.
  - **4b.** The system cannot accept or store the decision document → the system rejects the decision document and does not record the decision as complete.
  - **5a.** An actor other than a staff member tries to make the decision → the system denies the action; reference `BR-007`, `BR-016`.
  - **8a.** The request cannot move from Ready for Decision to Decided → the system does not record a completed decision; reference `BR-004`.
  - **9a.** The decision cannot be recorded understandably in the request history → the system does not complete the decision; reference `BR-017`.
  - **10a.** The notification cannot be created → the decision remains recorded and visible to the citizen inside the request.
- **Guarantees:**
  - Minimal (on failure): No unapproved, untraceable, unsupported-document, or non-human decision is treated as final.
  - Success (on success): The request is decided with an Approved or Rejected outcome, and the citizen can see the decision state.
- **Business objects touched:** `Request`, `Request status`, `Decision`, `Document`, `Request history`, `Notification`
- **Business Rules:** `BR-004`, `BR-006`, `BR-007`, `BR-008`, `BR-009`, `BR-016`, `BR-017`
- **Notes:** This is one of the deepest use cases because it closes the request path and must protect human accountability. Exact decision-document file limits are not defined in the current business rules and are therefore not specified here.

## Communication Domain

### UC-10 — Exchange Request Messages
- **Scope:** PermitFlow
- **Primary Actor:** Citizen or Staff member
- **Supporting Actors:** Citizen or Staff member
- **Level:** User-Goal 🌊
- **Trigger:** An authorized request participant wants to ask or answer a request-related question inside the portal.
- **Preconditions:** The request exists; the citizen owns the request; the request has exactly one responsible staff member for direct staff-citizen communication.
- **Main Success Scenario:**
  1. The primary actor opens an authorized request.
  2. The primary actor opens the request message thread.
  3. The primary actor writes a request-related message.
  4. The system validates that the message is between the request's citizen and the responsible staff member.
  5. The system records the message on the request.
  6. The system makes the message visible to the other authorized participant.
  7. The system creates an in-portal notification for the other authorized participant.
- **Extensions:**
  - **1a.** A citizen attempts to open another citizen's request → the system denies access; reference `BR-003`, `BR-016`.
  - **1b.** A staff member attempts to open a request for which they are not responsible → the system denies access; reference `BR-009`, `BR-016`.
  - **3a.** The message is empty → the system does not record it and asks the primary actor to enter message content.
  - **4a.** The request has no responsible staff member yet → the system cannot complete direct staff-citizen exchange and asks the actor to wait until assignment; reference `BR-009`, `BR-011`.
  - **4b.** A participant outside the request's citizen/responsible-staff relationship attempts to join the exchange → the system denies the message; reference `BR-011`, `BR-016`.
  - **5a.** The message cannot be recorded → no notification is created, and the primary actor sees that the message was not sent.
  - **7a.** The notification cannot be created → the message remains recorded and visible in the request thread.
- **Guarantees:**
  - Minimal (on failure): No message is silently delivered to the wrong participant.
  - Success (on success): The message is recorded on the request and visible to the other authorized participant.
- **Business objects touched:** `Request`, `Message`, `Notification`, `User account`, `Role`
- **Business Rules:** `BR-001`, `BR-003`, `BR-009`, `BR-011`, `BR-016`
- **Notes:** Staff-initiated missing-information communication is handled by UC-07. This use case covers general request-related exchange in both directions.

## Administration Domain

### UC-11 — Manage Request Categories
- **Scope:** PermitFlow
- **Primary Actor:** Administrator
- **Supporting Actors:** `_None_`
- **Level:** User-Goal 🌊
- **Trigger:** The administrator needs to maintain the categories under which citizens file requests.
- **Preconditions:** The administrator has access to category management.
- **Main Success Scenario:**
  1. The administrator opens request category management.
  2. The administrator chooses to create or maintain a request category.
  3. The administrator enters or reviews the category information.
  4. The administrator chooses whether the category is active or inactive for future request filing.
  5. The system validates that the actor is allowed to maintain categories.
  6. The system validates that the category change keeps existing requests understandable.
  7. The system saves the request category.
  8. The system makes active categories available for new request filing and hides inactive categories from new request filing.
- **Extensions:**
  - **2a.** The selected category cannot be found → the system leaves categories unchanged and asks the administrator to choose another category.
  - **5a.** A non-administrator attempts category maintenance → the system denies the action; reference `BR-012`, `BR-016`.
  - **6a.** The administrator tries to delete a category that is already used by existing requests → the system blocks deletion.
  - **6b.** The administrator tries to semantically rename a category that is already used by existing requests → the system blocks the change because existing request history would become unclear.
  - **6c.** The administrator marks a used category inactive → the system allows the change for future filing while existing requests keep their original category.
  - **7a.** The category change cannot be saved → existing request categories remain unchanged.
- **Guarantees:**
  - Minimal (on failure): Existing request categories and request classifications remain unchanged.
  - Success (on success): The maintained request category is available or inactive according to its state, and existing requests remain understandable.
- **Business objects touched:** `Request category`, `Request`
- **Business Rules:** `BR-002`, `BR-012`, `BR-016`, `BR-017`
- **Notes:** Used categories are historically protected in v1. Deactivation is the safe alternative to deletion when a category has already been used.

### UC-12 — Manage Organization Settings
- **Scope:** PermitFlow
- **Primary Actor:** Administrator
- **Supporting Actors:** `_None_`
- **Level:** User-Goal 🌊
- **Trigger:** The administrator needs to configure institution-level settings for the single organization.
- **Preconditions:** The administrator has access to organization settings.
- **Main Success Scenario:**
  1. The administrator opens organization settings.
  2. The system shows the current organization settings.
  3. The administrator changes the needed settings.
  4. The system validates that the actor is allowed to maintain organization settings.
  5. The system saves the changed settings.
  6. The system makes the settings effective for the organization.
- **Extensions:**
  - **3a.** The administrator cancels before saving → the settings remain unchanged.
  - **4a.** A non-administrator attempts settings maintenance → the system denies the action; reference `BR-014`, `BR-016`.
  - **5a.** A changed setting would conflict with v1 hard constraints → the system rejects the change and leaves the previous setting active.
- **Guarantees:**
  - Minimal (on failure): Existing organization settings remain active.
  - Success (on success): The organization settings are configured by an administrator and apply to the single organization.
- **Business objects touched:** `Organization settings`, `User account`, `Role`
- **Business Rules:** `BR-001`, `BR-014`, `BR-016`
- **Notes:** The concrete settings are intentionally not expanded beyond the miniworld object.

## Reporting Domain

### UC-13 — View Staff Reporting Summaries
- **Scope:** PermitFlow
- **Primary Actor:** Staff member
- **Supporting Actors:** `_None_`
- **Level:** User-Goal 🌊
- **Trigger:** The staff member wants a basic summary of request volume, status, or processing progress for work planning.
- **Preconditions:** The actor has the Staff member role; requests exist or the actor accepts an empty summary.
- **Main Success Scenario:**
  1. The staff member opens reporting summaries.
  2. The system validates that the actor may access reporting summaries.
  3. The staff member selects the summary view they need.
  4. The system shows authorized request volume, request status, or processing-progress information.
  5. The staff member reviews the summary for work planning.
- **Extensions:**
  - **1a.** A citizen attempts to open reporting summaries → the system denies access; reference `BR-015`, `BR-016`.
  - **2a.** The staff member attempts to view request information beyond their authorized scope → the system limits or denies the view; reference `BR-015`, `BR-016`.
  - **3a.** The selected summary has no matching requests → the system shows an empty summary rather than an error.
  - **4a.** The summary would reveal request information outside the staff member's permitted scope → the system does not show that information; reference `BR-016`.
- **Guarantees:**
  - Minimal (on failure): Reporting does not expose request information to unauthorized actors.
  - Success (on success): The staff member sees an authorized basic reporting summary.
- **Business objects touched:** `Request`, `Request status`, `User account`, `Role`
- **Business Rules:** `BR-001`, `BR-015`, `BR-016`
- **Notes:** This use case is one half of the miniworld process “View reporting summaries”; the administrator variant is UC-14.

### UC-14 — View Administrative Reporting Summaries
- **Scope:** PermitFlow
- **Primary Actor:** Administrator
- **Supporting Actors:** `_None_`
- **Level:** User-Goal 🌊
- **Trigger:** The administrator wants a basic organization-level summary of request volume, status, or processing progress.
- **Preconditions:** The actor has the Administrator role; requests exist or the actor accepts an empty summary.
- **Main Success Scenario:**
  1. The administrator opens reporting summaries.
  2. The system validates that the actor may access reporting summaries.
  3. The administrator selects the summary view they need.
  4. The system shows authorized request volume, request status, or processing-progress information.
  5. The administrator reviews the summary for oversight.
- **Extensions:**
  - **1a.** A citizen attempts to open reporting summaries → the system denies access; reference `BR-015`, `BR-016`.
  - **2a.** A non-authorized actor attempts to view administrative reporting summaries → the system denies access; reference `BR-015`, `BR-016`.
  - **3a.** The selected summary has no matching requests → the system shows an empty summary rather than an error.
  - **4a.** The summary would reveal request information outside authorized oversight → the system does not show that information; reference `BR-016`.
- **Guarantees:**
  - Minimal (on failure): Reporting does not expose request information outside authorized oversight.
  - Success (on success): The administrator sees an authorized basic reporting summary for oversight.
- **Business objects touched:** `Request`, `Request status`, `User account`, `Role`
- **Business Rules:** `BR-001`, `BR-015`, `BR-016`
- **Notes:** This use case keeps the same reporting process but separates the administrator as a distinct primary actor.

## Open Questions
- _None._
