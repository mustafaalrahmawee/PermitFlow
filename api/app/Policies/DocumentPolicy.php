<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\Document;
use App\Models\Request;
use App\Models\UserAccount;
use App\Policies\Concerns\InteractsWithRequestScope;

/**
 * Authorization for request documents (§5.5).
 *
 * Viewing is scoped through the document's parent request. Creation abilities
 * take the parent Request (the document does not exist yet) and are invoked as
 * `Gate::forUser($user)->check('createSupporting', [Document::class, $request])`
 * [02_business-rules.md BR-005, BR-006, BR-007, BR-016;
 * 03_use-cases.md UC-02, UC-04, UC-09].
 */
class DocumentPolicy
{
    use InteractsWithRequestScope;

    /** Scoped through the document's request [BR-016]. */
    public function view(UserAccount $user, Document $document): bool
    {
        return $this->canViewRequest($user, $document->request);
    }

    /** Owning citizen, request Draft (pre-submission) or Waiting for Citizen [BR-005, BR-006; UC-02, UC-04]. */
    public function createSupporting(UserAccount $user, Request $request): bool
    {
        return $this->isOwner($user, $request)
            && in_array($request->status, [
                RequestStatus::Draft,
                RequestStatus::WaitingForCitizen,
            ], true);
    }

    /** Responsible staff member only, as part of recording a decision [BR-006, BR-007; UC-09]. */
    public function createDecisionDocument(UserAccount $user, Request $request): bool
    {
        return $this->isResponsibleStaff($user, $request)
            && $request->status === RequestStatus::ReadyForDecision;
    }
}
