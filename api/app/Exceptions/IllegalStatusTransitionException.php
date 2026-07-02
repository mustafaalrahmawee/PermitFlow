<?php

namespace App\Exceptions;

use App\Enums\RequestStatus;
use RuntimeException;

/**
 * Raised when a request status transition outside the allowed-transition map is
 * attempted. Carries the from-status and to-status (§5.6).
 *
 * Source: [06-foundation-architect.md §5; 05_system-design.md §4] — the
 * durable-write path carrying legal/audit state must not fail silently.
 */
class IllegalStatusTransitionException extends RuntimeException
{
    public function __construct(
        public readonly RequestStatus $from,
        public readonly RequestStatus $to,
    ) {
        parent::__construct(sprintf(
            'Illegal request status transition from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
