<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Source: [04_data-model.md §2.1 organization_settings].
 * settings_payload is whole-block JSON (§3.1).
 */
class OrganizationSettings extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationSettingsFactory> */
    use HasFactory;

    protected $fillable = [
        'singleton_key',
        'organization_name',
        'settings_payload',
        'updated_by_user_account_id',
    ];

    protected function casts(): array
    {
        return [
            'settings_payload' => 'array',
        ];
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'updated_by_user_account_id');
    }
}
