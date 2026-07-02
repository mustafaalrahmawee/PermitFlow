<?php

namespace App\Models;

use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Source: [04_data-model.md §2.1 documents].
 * file_reference is the S3/MinIO object key, not the bytes (§5.8).
 */
class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'uploaded_by_user_account_id',
        'decision_id',
        'kind',
        'file_reference',
        'original_filename',
        'mime_type',
        'size_bytes',
        'uploaded_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'kind' => DocumentKind::class,
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Request, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /** @return BelongsTo<UserAccount, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'uploaded_by_user_account_id');
    }

    /** @return BelongsTo<Decision, $this> */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'decision_id');
    }

    /** @return HasMany<RequestHistoryEntry, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(RequestHistoryEntry::class, 'document_id');
    }
}
