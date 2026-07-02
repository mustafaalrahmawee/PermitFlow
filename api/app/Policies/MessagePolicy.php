<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\Request;
use App\Models\UserAccount;
use App\Policies\Concerns\InteractsWithRequestScope;

/**
 * Authorization for request messages (§5.5).
 *
 * Viewing is scoped through the message's parent request. Creation is limited to
 * the request's owning citizen or responsible staff member; it takes the parent
 * Request and is invoked as
 * `Gate::forUser($user)->check('create', [Message::class, $request])`. The
 * matching sender/recipient pairing is enforced in the UC-10 transaction, not
 * here [02_business-rules.md BR-011, BR-016; 03_use-cases.md UC-10].
 */
class MessagePolicy
{
    use InteractsWithRequestScope;

    /** Scoped through the message's request [BR-016]. */
    public function view(UserAccount $user, Message $message): bool
    {
        return $this->canViewRequest($user, $message->request);
    }

    /** Only the request's owning citizen or responsible staff member [BR-011; UC-10]. */
    public function create(UserAccount $user, Request $request): bool
    {
        return $this->participatesInRequest($user, $request);
    }
}
