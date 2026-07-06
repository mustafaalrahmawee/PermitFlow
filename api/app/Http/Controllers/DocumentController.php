<?php

namespace App\Http\Controllers;

use App\Enums\DocumentKind;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Attaching supporting documents to a request (UC-02 step 5; shared seam with
 * UC-04). Reach is request-scoped: an out-of-scope record reads as not found
 * (404); attaching is reserved for the owning citizen while the request is
 * editable (`createSupporting`, 403 otherwise).
 *
 * The bytes go to the S3/MinIO disk and `documents.file_reference` holds the
 * returned object key, not the bytes. A rejected upload is a 422 (ext 5a) and a
 * store fault is a 500 that creates no `documents` row and leaves the draft
 * intact (ext 5b) [03_use-cases.md UC-02 ext 5a/5b; 02_business-rules.md BR-006;
 * docs/conventions.md Storage, Authorization, API error responses].
 */
class DocumentController extends Controller
{
    /**
     * Store a supporting document against the request.
     */
    public function store(StoreDocumentRequest $storeRequest, Request $request): JsonResponse
    {
        $actor = $storeRequest->user();

        $this->ensureInScope($actor, $request);
        abort_if(
            Gate::forUser($actor)->denies('createSupporting', [Document::class, $request]),
            403,
            'You are not allowed to attach documents to this request.',
        );

        $file = $storeRequest->file('file');

        // Store the bytes first; the object key is the durable reference. A store
        // fault leaves no `documents` row and the draft unchanged (ext 5b).
        $objectKey = Storage::disk('s3')->putFile('documents', $file);
        abort_if($objectKey === false, 500, 'The document could not be stored.');

        $document = Document::create([
            'request_id' => $request->id,
            'uploaded_by_user_account_id' => $actor->id,
            'kind' => DocumentKind::Supporting,
            'file_reference' => $objectKey,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_at' => now(),
            'description' => $storeRequest->validated()['description'] ?? null,
        ]);

        return response()->json([
            'data' => $document,
            'message' => 'Supporting document attached.',
        ], 201);
    }

    /**
     * Report an out-of-scope record as not found (404) rather than forbidden, so
     * existence is not revealed [02_business-rules.md BR-016; docs/conventions.md
     * API error responses — 404].
     */
    private function ensureInScope(UserAccount $user, Request $request): void
    {
        abort_if($user->cannot('view', $request), 404, 'Request not found.');
    }
}
