<?php

namespace App\Enums;

/**
 * History event type value set on request_history_entries.
 *
 * Source: [02_business-rules.md BR-017] / [04_data-model.md §1.2].
 */
enum HistoryEventType: string
{
    case StatusChanged = 'status_changed';
    case AssignmentChanged = 'assignment_changed';
    case DecisionRecorded = 'decision_recorded';
    case InformationRequested = 'information_requested';
    case InformationProvided = 'information_provided';
    case MessageRecorded = 'message_recorded';

    public function label(): string
    {
        return match ($this) {
            self::StatusChanged => 'Status changed',
            self::AssignmentChanged => 'Assignment changed',
            self::DecisionRecorded => 'Decision recorded',
            self::InformationRequested => 'Information requested',
            self::InformationProvided => 'Information provided',
            self::MessageRecorded => 'Message recorded',
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
