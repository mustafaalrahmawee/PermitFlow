<?php

namespace App\Enums;

/**
 * Message kind value set on messages.
 *
 * Source: [04_data-model.md §1.2] / [03_use-cases.md UC-07, UC-10].
 */
enum MessageKind: string
{
    case General = 'general';
    case MissingInformationRequest = 'missing_information_request';
    case CitizenReply = 'citizen_reply';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::MissingInformationRequest => 'Missing information request',
            self::CitizenReply => 'Citizen reply',
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
