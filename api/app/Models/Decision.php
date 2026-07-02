<?php

namespace App\Models;

use App\Enums\DecisionOutcome;
use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Source: [04_data-model.md §2.1 decisions]. One decision per request.
 */
class Decision extends Model
{
    /** @use HasFactory<\Database\Factories\DecisionFactory> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'decided_by_user_account_id',
        'outcome',
        'decision_text',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => DecisionOutcome::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Request, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'decided_by_user_account_id');
    }

    /** @return HasOne<Document, $this> */
    public function decisionDocument(): HasOne
    {
        return $this->hasOne(Document::class, 'decision_id')
            ->where('kind', DocumentKind::Decision->value);
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'decision_id');
    }
}
