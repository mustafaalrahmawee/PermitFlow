<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-portal alert. Source: [04_data-model.md §2.1 notifications].
 */
class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'recipient_user_account_id',
        'request_id',
        'request_history_entry_id',
        'notification_type',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'notification_type' => NotificationType::class,
            'read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'recipient_user_account_id');
    }

    /** @return BelongsTo<Request, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /** @return BelongsTo<RequestHistoryEntry, $this> */
    public function historyEntry(): BelongsTo
    {
        return $this->belongsTo(RequestHistoryEntry::class, 'request_history_entry_id');
    }
}
