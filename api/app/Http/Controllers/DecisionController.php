<?php

namespace App\Http\Controllers;

use App\Enums\DecisionOutcome;
use App\Enums\DocumentKind;
use App\Enums\HistoryEventType;
use App\Enums\NotificationType;
use App\Enums\RequestStatus;
use App\Http\Requests\StoreDecisionRequest;
use App\Models\Decision;
use App\Models\Document;
use App\Models\Notification;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The responsible staff member records the decision that closes a request
 * (UC-09). Reach is request-scoped: an out-of-scope record reads as not found
 * (404, ext 1a); deciding is reserved for the responsible staff member through
 * `RequestPolicy@decide` (403 otherwise — an in-scope owner or administrator
 * lacks the ability, ext 5a), protecting human accountability (BR-007/008/009).
 *
 * The durable-write path is atomic and carries legal/audit state: the guarded
 * Ready for Decision → Decided transition, the decision row, an optional decision
 * document, and the linked `decision_recorded` history entry are saved in one
 * transaction, so no unapproved, untraceable, or unsupported-document decision is
 * ever treated as final. A request that is not Ready for Decision is a blocked
 * transition — a 409 (ext 2a, ext 8a) — and a persistence or history fault rolls
 * back with no decision recorded (500, ext 8a, ext 9a). The decision document
 * bytes go to the S3/MinIO disk before the transaction; a store fault is a 500
 * that records no decision (ext 4b). The citizen notification is best-effort and
 * runs after the commit: a notification fault leaves the decision recorded and
 * visible inside the request (ext 10a) [03_use-cases.md UC-09; BR-004, BR-006,
 * BR-007, BR-008, BR-009, BR-016, BR-017; docs/conventions.md Authorization,
 * Status transitions, Storage].
 */
class DecisionController extends Controller
{
    /**
     * Record the decision on the request (steps 5–10). Success is 201 with the
     * created decision in the `data` envelope.
     */
    public function store(StoreDecisionRequest $storeRequest, Request $request): JsonResponse
    {
        $actor = $storeRequest->user();

        $this->ensureInScope($actor, $request);
        abort_if(
            Gate::forUser($actor)->denies('decide', $request),
            403,
            'You are not allowed to decide this request.',
        );

        $validated = $storeRequest->validated();
        $outcome = DecisionOutcome::from($validated['outcome']);
        $file = $storeRequest->file('file');

        // Store the decision document first (if any); the object key is the
        // durable reference. A store fault is a 500 that records no decision and
        // leaves the request Ready for Decision (ext 4b).
        $objectKey = null;
        if ($file instanceof UploadedFile) {
            $objectKey = Storage::disk('s3')->putFile('documents', $file);
            abort_if($objectKey === false, 500, 'The decision document could not be stored.');
        }

        $fromStatus = $request->status;

        $decision = DB::transaction(function () use ($request, $actor, $fromStatus, $outcome, $validated, $file, $objectKey): Decision {
            // Guarded transition — sets the status in memory, raising
            // IllegalStatusTransitionException (rendered 409) on an illegal target
            // (ext 2a, ext 8a).
            $request->transitionTo(RequestStatus::Decided);
            $request->save();

            $decision = Decision::create([
                'request_id' => $request->id,
                'decided_by_user_account_id' => $actor->id,
                'outcome' => $outcome,
                'decision_text' => $validated['decision_text'] ?? null,
                'decided_at' => now(),
            ]);

            $document = null;
            if ($file instanceof UploadedFile) {
                $document = Document::create([
                    'request_id' => $request->id,
                    'uploaded_by_user_account_id' => $actor->id,
                    'decision_id' => $decision->id,
                    'kind' => DocumentKind::Decision,
                    'file_reference' => $objectKey,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'uploaded_at' => now(),
                    'description' => $validated['description'] ?? null,
                ]);
            }

            $sequence = (int) $request->historyEntries()->max('sequence_number') + 1;

            // Explicit, linked history entry carrying the status pair; the
            // decision does not complete without it (ext 9a).
            $request->historyEntries()->create([
                'sequence_number' => $sequence,
                'actor_user_account_id' => $actor->id,
                'decision_id' => $decision->id,
                'document_id' => $document?->id,
                'event_type' => HistoryEventType::DecisionRecorded,
                'from_status' => $fromStatus,
                'to_status' => RequestStatus::Decided,
                'summary' => sprintf('Staff member recorded the decision: %s.', $outcome->label()),
                'event_occurred_at' => now(),
            ]);

            return $decision;
        });

        $this->notifyCitizenOfDecision($request, $outcome);

        return response()->json([
            'data' => $decision->fresh(),
            'message' => 'Decision recorded.',
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

    /**
     * Best-effort: one `decision_recorded` notification for the owning citizen
     * (step 10). The decision, transition, and history entry are already durable,
     * so any fault here is swallowed and reported — it never rolls back the
     * decision, which stays recorded and visible inside the request (ext 10a)
     * [03_use-cases.md UC-09 step 10; 05_system-design.md §4].
     */
    private function notifyCitizenOfDecision(Request $request, DecisionOutcome $outcome): void
    {
        try {
            Notification::create([
                'recipient_user_account_id' => $request->owner_user_account_id,
                'request_id' => $request->id,
                'notification_type' => NotificationType::DecisionRecorded,
                'body' => sprintf('A decision was recorded on your request: %s.', $outcome->label()),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
