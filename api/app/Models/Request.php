<?php

namespace App\Models;

use App\Concerns\TransitionsRequestStatus;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Source: [04_data-model.md §2.1 requests].
 * request_details is whole-block JSON (§3.1). Status changes go through the
 * status guard trait (§5.6); persistence stays the caller's responsibility.
 */
class Request extends Model
{
    /** @use HasFactory<\Database\Factories\RequestFactory> */
    use HasFactory, TransitionsRequestStatus;

    protected $fillable = [
        'owner_user_account_id',
        'request_category_id',
        'responsible_staff_user_account_id',
        'title',
        'request_details',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'request_details' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'owner_user_account_id');
    }

    /** @return BelongsTo<RequestCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RequestCategory::class, 'request_category_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function responsibleStaff(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'responsible_staff_user_account_id');
    }

    /** @return HasOne<Decision, $this> */
    public function decision(): HasOne
    {
        return $this->hasOne(Decision::class, 'request_id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'request_id');
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'request_id');
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'request_id');
    }

    /** @return HasMany<Notification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'request_id');
    }
}
