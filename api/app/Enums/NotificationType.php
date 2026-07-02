<?php

namespace App\Enums;

/**
 * Notification type value set on notifications.
 *
 * Source: [04_data-model.md §2.1 notifications].
 */
enum NotificationType: string
{
    case RequestSubmitted = 'request_submitted';
    case Assigned = 'assigned';
    case Reassigned = 'reassigned';
    case MissingInformationRequested = 'missing_information_requested';
    case InformationProvided = 'information_provided';
    case StatusChanged = 'status_changed';
    case DecisionRecorded = 'decision_recorded';
    case MessageReceived = 'message_received';

    public function label(): string
    {
        return match ($this) {
            self::RequestSubmitted => 'Request submitted',
            self::Assigned => 'Assigned',
            self::Reassigned => 'Reassigned',
            self::MissingInformationRequested => 'Missing information requested',
            self::InformationProvided => 'Information provided',
            self::StatusChanged => 'Status changed',
            self::DecisionRecorded => 'Decision recorded',
            self::MessageReceived => 'Message received',
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
