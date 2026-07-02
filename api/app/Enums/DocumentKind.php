<?php

namespace App\Enums;

/**
 * Document kind value set on documents.
 *
 * Source: [02_business-rules.md BR-006].
 */
enum DocumentKind: string
{
    case Supporting = 'supporting';
    case Decision = 'decision';

    public function label(): string
    {
        return match ($this) {
            self::Supporting => 'Supporting',
            self::Decision => 'Decision',
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
