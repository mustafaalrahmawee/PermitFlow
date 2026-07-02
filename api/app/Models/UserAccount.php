<?php

namespace App\Models;

use App\Enums\AccountState;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * The authenticatable user account (Sanctum SPA, §5.5).
 *
 * Source: [04_data-model.md §2.1 user_accounts]. The framework-default `users`
 * table is left in place but unused for auth; AUTH_MODEL points here.
 */
class UserAccount extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserAccountFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_accounts';

    protected $fillable = [
        'display_name',
        'email',
        'role',
        'account_state',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'account_state' => AccountState::class,
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->account_state === AccountState::Active;
    }

    public function isAdministrator(): bool
    {
        return $this->role === Role::Administrator;
    }

    public function isStaffMember(): bool
    {
        return $this->role === Role::StaffMember;
    }

    public function isCitizen(): bool
    {
        return $this->role === Role::Citizen;
    }

    /** @return HasMany<Request, $this> */
    public function ownedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'owner_user_account_id');
    }

    /** @return HasMany<Request, $this> */
    public function assignedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'responsible_staff_user_account_id');
    }

    /** @return HasMany<Document, $this> */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_account_id');
    }

    /** @return HasMany<Decision, $this> */
    public function decisionsMade(): HasMany
    {
        return $this->hasMany(Decision::class, 'decided_by_user_account_id');
    }

    /** @return HasMany<Message, $this> */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_user_account_id');
    }

    /** @return HasMany<Message, $this> */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_user_account_id');
    }

    /** @return HasMany<Notification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'recipient_user_account_id');
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function actedHistoryEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'actor_user_account_id');
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function previousStaffHistoryEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'previous_staff_user_account_id');
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function newStaffHistoryEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'new_staff_user_account_id');
    }

    /** @return HasMany<OrganizationSettings, $this> */
    public function updatedOrganizationSettings(): HasMany
    {
        return $this->hasMany(OrganizationSettings::class, 'updated_by_user_account_id');
    }
}
