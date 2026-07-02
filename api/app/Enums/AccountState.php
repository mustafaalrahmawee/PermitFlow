<?php

namespace App\Enums;

/**
 * Account state value set on user_accounts.
 *
 * Source: [03_use-cases.md UC-01] / [04_data-model.md §2.1 user_accounts].
 * Inactive retires accounts tied to request history instead of deleting them.
 */
enum AccountState: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
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
