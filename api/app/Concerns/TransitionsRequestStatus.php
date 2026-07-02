<?php

namespace App\Concerns;

use App\Enums\RequestStatus;
use App\Exceptions\IllegalStatusTransitionException;

/**
 * Request status guard (§5.6, §4).
 *
 * Holds the explicit allowed-transition map confirmed against the v1 transition
 * graph [03_use-cases.md UC-08 step "Notes"], a predicate that tests whether a
 * target is legal, and a transition method that validates against the map and
 * sets the status IN MEMORY ONLY, raising IllegalStatusTransitionException on an
 * illegal target.
 *
 * Persistence is the caller's responsibility: a use case saves the status change
 * and its history entry together in one transaction so the durable-write path
 * stays atomic [05_system-design.md §4].
 *
 * Allowed transitions:
 *   draft                -> submitted                                  [UC-02 step 8]
 *   submitted            -> in_review                                  [UC-06 step 5]
 *   in_review            -> waiting_for_citizen, ready_for_decision     [UC-07 step 5; UC-08]
 *   waiting_for_citizen  -> in_review                                  [UC-04 step 7]
 *   ready_for_decision   -> decided                                    [UC-09 step 8]
 *   decided              -> (terminal)                                 [UC-08 notes]
 */
trait TransitionsRequestStatus
{
    /**
     * @return array<string, array<int, RequestStatus>>
     */
    public static function allowedStatusTransitions(): array
    {
        return [
            RequestStatus::Draft->value => [
                RequestStatus::Submitted,
            ],
            RequestStatus::Submitted->value => [
                RequestStatus::InReview,
            ],
            RequestStatus::InReview->value => [
                RequestStatus::WaitingForCitizen,
                RequestStatus::ReadyForDecision,
            ],
            RequestStatus::WaitingForCitizen->value => [
                RequestStatus::InReview,
            ],
            RequestStatus::ReadyForDecision->value => [
                RequestStatus::Decided,
            ],
            RequestStatus::Decided->value => [],
        ];
    }

    /**
     * Whether moving the current status to the given target is a legal v1 transition.
     */
    public function canTransitionTo(RequestStatus $target): bool
    {
        $current = $this->status;

        if (! $current instanceof RequestStatus) {
            // Fail closed: an unevaluatable current status permits nothing.
            return false;
        }

        foreach (self::allowedStatusTransitions()[$current->value] ?? [] as $legal) {
            if ($legal === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate against the allowed-transition map and set the status in memory.
     * Does NOT persist — the caller saves the change and its history entry in one
     * transaction.
     *
     * @throws IllegalStatusTransitionException on an illegal target.
     */
    public function transitionTo(RequestStatus $target): static
    {
        $current = $this->status;

        if (! $current instanceof RequestStatus || ! $this->canTransitionTo($target)) {
            throw new IllegalStatusTransitionException(
                $current instanceof RequestStatus ? $current : RequestStatus::from((string) $current),
                $target,
            );
        }

        $this->status = $target;

        return $this;
    }
}
