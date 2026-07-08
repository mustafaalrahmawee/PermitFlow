<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\UserAccount;
use App\Policies\Concerns\InteractsWithRequestScope;

/**
 * Authorization for permit requests (§5.5).
 *
 * view is request-scoped (owner, responsible staff, or administrator oversight).
 * Every operation ability is reserved for a single role acting on a request in a
 * specific status and fails closed. There is no administrator bypass: oversight
 * never authorizes citizen or staff operations
 * [02_business-rules.md BR-003, BR-005, BR-007, BR-008, BR-009, BR-016;
 * 03_use-cases.md UC-02, UC-04, UC-06, UC-07, UC-08, UC-09].
 */
class RequestPolicy
{
    use InteractsWithRequestScope;

    /** Owner, responsible staff, or administrator oversight [BR-016]. */
    public function view(UserAccount $user, Request $request): bool
    {
        return $this->canViewRequest($user, $request);
    }

    /** Owning citizen only [BR-003; UC-02]. */
    public function submit(UserAccount $user, Request $request): bool
    {
        return $this->isOwner($user, $request);
    }

    /** Owning citizen only, request Draft or Waiting for Citizen [BR-005; UC-04]. */
    public function provideInformation(UserAccount $user, Request $request): bool
    {
        return $this->isOwner($user, $request)
            && in_array($request->status, [
                RequestStatus::Draft,
                RequestStatus::WaitingForCitizen,
            ], true);
    }

    /** Responsible staff member only [BR-009; UC-06]. */
    public function review(UserAccount $user, Request $request): bool
    {
        return $this->isResponsibleStaff($user, $request);
    }

    /** Responsible staff member only, request In Review [BR-009; UC-07]. */
    public function requestMissingInformation(UserAccount $user, Request $request): bool
    {
        return $this->isResponsibleStaff($user, $request)
            && $request->status === RequestStatus::InReview;
    }

    /** Responsible staff member only, request In Review [BR-009; UC-06/UC-08]. */
    public function markReadyForDecision(UserAccount $user, Request $request): bool
    {
        return $this->isResponsibleStaff($user, $request)
            && $request->status === RequestStatus::InReview;
    }

    /**
     * Responsible staff member only [BR-007, BR-008, BR-009; UC-09]. Authorization
     * is the role/ownership check; the Ready for Decision → Decided status legality
     * is owned by the `TransitionsRequestStatus` guard, so a decision attempted on a
     * request that is not Ready for Decision is a lifecycle conflict (409, ext 2a),
     * not an authorization denial (403).
     */
    public function decide(UserAccount $user, Request $request): bool
    {
        return $this->isResponsibleStaff($user, $request);
    }
}
