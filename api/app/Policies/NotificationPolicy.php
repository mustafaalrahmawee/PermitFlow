<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\UserAccount;

/**
 * Authorization for notifications (§5.5).
 *
 * Notifications are user-targeted alerts, not request-scoped records. The
 * default view is limited to the notification's recipient so one participant's
 * notification never leaks to another participant on the same request.
 * Administrator oversight exists only as the explicit viewForOversight ability
 * [04_data-model.md §2.1 notifications.recipient_user_account_id;
 * bounded by 02_business-rules.md BR-016].
 */
class NotificationPolicy
{
    /** Recipient only [derived; bounded by BR-016]. */
    public function view(UserAccount $user, Notification $notification): bool
    {
        return $user->isActive()
            && (int) $notification->recipient_user_account_id === (int) $user->id;
    }

    /** Explicit administrator oversight, never enabled by default [BR-016]. */
    public function viewForOversight(UserAccount $user, Notification $notification): bool
    {
        return $user->isActive() && $user->isAdministrator();
    }
}
