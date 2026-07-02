<?php

namespace App\Policies\Concerns;

use App\Models\Request;
use App\Models\UserAccount;

/**
 * Request-scoped reach shared by every request-scoped policy (§5.5).
 *
 * A request and its child records are reachable only by the owning citizen, the
 * responsible staff member, or authorized administrator oversight. Every check
 * fails closed: an inactive account is denied, and role / ownership are read
 * live from the passed models, never from a cached copy
 * [02_business-rules.md BR-016; 05_system-design.md §4].
 *
 * There is deliberately no global administrator allow-all. Oversight is granted
 * only where a policy explicitly calls hasOversight(); it never authorizes the
 * operations reserved for the owning citizen or the responsible staff member
 * [02_business-rules.md BR-007, BR-011].
 */
trait InteractsWithRequestScope
{
    /**
     * The account is the owning citizen of this request.
     */
    protected function isOwner(UserAccount $user, Request $request): bool
    {
        return $user->isActive()
            && (int) $request->owner_user_account_id === (int) $user->id;
    }

    /**
     * The account is the responsible staff member assigned to this request.
     */
    protected function isResponsibleStaff(UserAccount $user, Request $request): bool
    {
        return $user->isActive()
            && $request->responsible_staff_user_account_id !== null
            && (int) $request->responsible_staff_user_account_id === (int) $user->id;
    }

    /**
     * The account is a direct participant (owner or responsible staff).
     */
    protected function participatesInRequest(UserAccount $user, Request $request): bool
    {
        return $this->isOwner($user, $request)
            || $this->isResponsibleStaff($user, $request);
    }

    /**
     * The account has administrator oversight. Oversight permits scoped viewing
     * only; it is never a blanket allow-all for operations.
     */
    protected function hasOversight(UserAccount $user): bool
    {
        return $user->isActive() && $user->isAdministrator();
    }

    /**
     * Whether the account may view records scoped to this request.
     */
    protected function canViewRequest(UserAccount $user, Request $request): bool
    {
        return $this->participatesInRequest($user, $request)
            || $this->hasOversight($user);
    }
}
