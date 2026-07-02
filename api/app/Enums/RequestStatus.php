<?php

namespace App\Enums;

/**
 * Request status value set on requests (and from/to status on history entries).
 *
 * Source: [02_business-rules.md BR-004]. The ordered transition flow between
 * these stages lives in the status guard trait, not here.
 */
enum RequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case WaitingForCitizen = 'waiting_for_citizen';
    case ReadyForDecision = 'ready_for_decision';
    case Decided = 'decided';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::InReview => 'In Review',
            self::WaitingForCitizen => 'Waiting for Citizen',
            self::ReadyForDecision => 'Ready for Decision',
            self::Decided => 'Decided',
        };
    }

    /** @return array<int, string> Backing slugs, for validation rules. */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /** @return array<string, string> Slug => label map, for select controls. */
    public static function options(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        return $map;
    }
}
