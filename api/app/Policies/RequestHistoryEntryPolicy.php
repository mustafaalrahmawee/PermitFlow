<?php

namespace App\Policies;

use App\Models\RequestHistoryEntry;
use App\Models\UserAccount;
use App\Policies\Concerns\InteractsWithRequestScope;

/**
 * Authorization for request history entries (§5.5).
 *
 * A history entry is viewable only through its parent request's scope: the
 * owning citizen, the responsible staff member, or administrator oversight
 * [02_business-rules.md BR-016].
 */
class RequestHistoryEntryPolicy
{
    use InteractsWithRequestScope;

    /** Scoped through the history entry's request [BR-016]. */
    public function view(UserAccount $user, RequestHistoryEntry $entry): bool
    {
        return $this->canViewRequest($user, $entry->request);
    }
}
