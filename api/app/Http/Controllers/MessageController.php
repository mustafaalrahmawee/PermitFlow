<?php

namespace App\Http\Controllers;

use App\Enums\MessageKind;
use App\Enums\NotificationType;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * General request-related messaging between a request's citizen and its
 * responsible staff member (UC-10).
 *
 * The thread hangs off a request and is reachable under request-scoped reach:
 * the owning citizen, the responsible staff member, or an administrator may read
 * it (BR-016), and a record outside the actor's scope reads as not found (404)
 * rather than forbidden. Sending is narrower: only the two BR-011 participants
 * (citizen or responsible staff) may write, so an in-scope administrator is
 * denied the send (403). A message is recorded with sender = the actor and
 * recipient = the other participant, then the recipient is notified best-effort;
 * UC-10 changes no `requests.status` and writes no history entry
 * [03_use-cases.md UC-10; 02_business-rules.md BR-009, BR-011, BR-016;
 * docs/conventions.md Authorization]. Staff-initiated missing-information
 * exchange is UC-07; this seam is general two-way exchange.
 */
class MessageController extends Controller
{
    /**
     * Fixed page size for a request's message thread; secondary/performance
     * tuning is a v1 non-goal, so this is a constant, not a client parameter
     * [docs/conventions.md API success responses].
     */
    private const PER_PAGE = 15;

    /**
     * List a request's message thread, paginated (UC-10 step 2). Reach is
     * request-scoped: a record outside the caller's scope reads as not found
     * (404, ext 1a/1b) rather than forbidden, so existence is not revealed. The
     * thread is ordered chronologically for reading; `data` stays the flat array
     * of messages and the page cursor rides alongside in `meta`
     * [03_use-cases.md UC-10 step 2; BR-016; docs/conventions.md API success
     * responses].
     */
    public function index(HttpRequest $httpRequest, Request $request): JsonResponse
    {
        $this->ensureInScope($httpRequest->user(), $request);

        $messages = $request->messages()
            ->orderBy('sent_at')
            ->orderBy('id')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
            'message' => 'Messages retrieved.',
        ]);
    }

    /**
     * Send a general message on a request (UC-10 steps 3–7). Out-of-scope records
     * read as not found (404, ext 1a/1b); the message participant relationship is
     * guarded by `MessagePolicy@create` (owning citizen or responsible staff), so
     * an in-scope actor outside the BR-011 pair — e.g. an administrator — is
     * denied (403, ext 4b). With no responsible staff member assigned yet, direct
     * staff-citizen exchange cannot complete: the send is blocked by current state
     * (409, ext 4a) — a lifecycle conflict, not an identity denial — so the actor
     * is asked to wait until assignment. An empty `body` is rejected by the form
     * request (422, ext 3a) before any write.
     *
     * The durable write is atomic: the `general` message is recorded with sender
     * = the actor and recipient = the other authorized participant (BR-011). If
     * the message cannot be recorded the transaction rolls back and no
     * notification is created (500, ext 5a). The recipient notification is
     * best-effort and runs after the commit: a notification fault leaves the
     * message recorded and visible in the thread (ext 7a) [03_use-cases.md UC-10
     * steps 3–7; BR-009, BR-011, BR-016; docs/conventions.md Authorization].
     */
    public function store(StoreMessageRequest $formRequest, Request $request): JsonResponse
    {
        $actor = $formRequest->user();

        $this->ensureInScope($actor, $request);
        abort_if(
            Gate::forUser($actor)->denies('create', [Message::class, $request]),
            403,
            'You are not allowed to message on this request.',
        );
        abort_if(
            $request->responsible_staff_user_account_id === null,
            409,
            'This request has no responsible staff member yet; please wait until it is assigned.',
        );

        // Sender is the actor; recipient is the other authorized participant — the
        // responsible staff member when the citizen sends, the owning citizen when
        // the staff member sends (BR-011). The participant guard above guarantees
        // the actor is one of the pair, and the 409 guard guarantees the staff side
        // is set, so this resolves the counterpart unambiguously.
        $recipientId = (int) $request->owner_user_account_id === (int) $actor->id
            ? $request->responsible_staff_user_account_id
            : $request->owner_user_account_id;

        $body = $formRequest->validated()['body'];

        $message = DB::transaction(fn (): Message => Message::create([
            'request_id' => $request->id,
            'sender_user_account_id' => $actor->id,
            'recipient_user_account_id' => $recipientId,
            'message_kind' => MessageKind::General,
            'body' => $body,
            'sent_at' => now(),
        ]));

        $this->notifyRecipient($request, $message);

        return response()->json([
            'data' => $message,
            'message' => 'Message sent.',
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
     * Best-effort: one `message_received` notification for the other authorized
     * participant (step 7). The message is already durable, so any fault here is
     * swallowed and reported — it never rolls back the send, which stays recorded
     * and visible in the thread (ext 7a) [03_use-cases.md UC-10 step 7;
     * 05_system-design.md §4].
     */
    private function notifyRecipient(Request $request, Message $message): void
    {
        try {
            Notification::create([
                'recipient_user_account_id' => $message->recipient_user_account_id,
                'request_id' => $request->id,
                'notification_type' => NotificationType::MessageReceived,
                'body' => 'You received a message on a request.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
