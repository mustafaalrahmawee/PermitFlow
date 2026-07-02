<?php

namespace App\Models;

use App\Enums\MessageKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Source: [04_data-model.md §2.1 messages].
 */
class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'sender_user_account_id',
        'recipient_user_account_id',
        'message_kind',
        'body',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'message_kind' => MessageKind::class,
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Request, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'sender_user_account_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'recipient_user_account_id');
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'message_id');
    }
}
