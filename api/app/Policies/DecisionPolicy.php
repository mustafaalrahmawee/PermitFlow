<?php

namespace App\Policies;

use App\Models\Decision;
use App\Models\UserAccount;
use App\Policies\Concerns\InteractsWithRequestScope;

/**
 * Authorization for request decisions (§5.5).
 *
 * A decision is viewable only through its parent request's scope: the owning
 * citizen, the responsible staff member, or administrator oversight
 * [02_business-rules.md BR-016].
 */
class DecisionPolicy
{
    use InteractsWithRequestScope;

    /** Scoped through the decision's request [BR-016]. */
    public function view(UserAccount $user, Decision $decision): bool
    {
        return $this->canViewRequest($user, $decision->request);
    }
}
