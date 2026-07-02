<?php

namespace App\Enums;

/**
 * Role value set on user_accounts.
 *
 * Source: [02_business-rules.md BR-001] / [04_data-model.md §2.1 user_accounts].
 * Stored value is the snake_case slug; label() returns the spec label verbatim.
 */
enum Role: string
{
    case Citizen = 'citizen';
    case StaffMember = 'staff_member';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Citizen',
            self::StaffMember => 'Staff member',
            self::Administrator => 'Administrator',
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
