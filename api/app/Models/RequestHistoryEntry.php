<?php

namespace App\Models;

use App\Enums\HistoryEventType;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Weak entity owned by Request; partial key sequence_number.
 * Source: [04_data-model.md §2.1 request_history_entries, §1.2].
 * summary is a frozen audit snapshot — written explicitly, never regenerated (§4).
 */
class RequestHistoryEntry extends Model
{
    /** @use HasFactory<\Database\Factories\RequestHistoryEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'sequence_number',
        'actor_user_account_id',
        'decision_id',
        'message_id',
        'document_id',
        'previous_staff_user_account_id',
        'new_staff_user_account_id',
        'event_type',
        'from_status',
        'to_status',
        'summary',
        'reason',
        'event_occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'event_type' => HistoryEventType::class,
            'from_status' => RequestStatus::class,
            'to_status' => RequestStatus::class,
            'event_occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Request, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'actor_user_account_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function previousStaff(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'previous_staff_user_account_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function newStaff(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'new_staff_user_account_id');
    }

    /** @return BelongsTo<Decision, $this> */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'decision_id');
    }

    /** @return BelongsTo<Message, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /** @return HasMany<Notification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'request_history_entry_id');
    }
}
