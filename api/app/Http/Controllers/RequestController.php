<?php

namespace App\Http\Controllers;

use App\Enums\AccountState;
use App\Enums\HistoryEventType;
use App\Enums\NotificationType;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\Notification;
use App\Models\Request;
use App\Models\RequestHistoryEntry;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Citizen filing and submission of permit requests (UC-02).
 *
 * A request is created as a Draft owned by the caller; a Draft may be edited
 * before submission; submission moves it Draft → Submitted through the status
 * guard, writes the status-change history entry atomically, and — best-effort —
 * notifies administrators so the request gets assigned. Reach is request-scoped:
 * a record outside the actor's scope is reported as not found (404) rather than
 * forbidden, so existence is not revealed; an in-scope actor who lacks the
 * ability is denied (403) [03_use-cases.md UC-02; 02_business-rules.md BR-003,
 * BR-004, BR-016, BR-017; docs/conventions.md Authorization, Status transitions].
 */
class RequestController extends Controller
{
    /**
     * Fixed page size for the citizen's request list; secondary/performance
     * tuning is a v1 non-goal, so this is a constant, not a client parameter
     * [docs/conventions.md API success responses].
     */
    private const PER_PAGE = 15;

    /**
     * List the requests in the caller's scope, paginated. The scope follows the
     * caller's role so this one seam serves both worklists: a citizen sees the
     * requests they own (UC-03 steps 1–2), a staff member sees the requests they
     * are responsible for (UC-06 steps 1–2). Both are strictly scoped — a citizen
     * never sees another person's request, a staff member never sees a request
     * assigned to someone else — and a caller with nothing in scope gets an empty
     * `data` array with a `meta.total` of 0 (UC-03 ext 2a, UC-06 ext 2a). `data`
     * stays the flat array of requests and the page cursor rides alongside in
     * `meta`, never nested under `data` [03_use-cases.md UC-03 steps 1–2, UC-06
     * steps 1–2; BR-009; BR-016; docs/conventions.md API success responses].
     *
     * The scope column is chosen from the caller's role explicitly: a citizen is
     * owner-scoped, a staff member is responsibility-scoped. An administrator is
     * neither — request-scoped visibility grants administrators only oversight,
     * not a list allow-all (BR-016 note; InteractsWithRequestScope), so they track
     * through per-request oversight reads and the `/admin/requests` worklist
     * (UC-05), and this list is empty for them rather than owner-scoped.
     */
    public function index(HttpRequest $httpRequest): JsonResponse
    {
        $actor = $httpRequest->user();

        $scopeColumn = match (true) {
            $actor->isStaffMember() => 'responsible_staff_user_account_id',
            $actor->isCitizen() => 'owner_user_account_id',
            default => null,
        };

        if ($scopeColumn === null) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => self::PER_PAGE,
                    'total' => 0,
                ],
                'message' => 'Requests retrieved.',
            ]);
        }

        $requests = Request::query()
            ->where($scopeColumn, $actor->id)
            ->with('category')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'message' => 'Requests retrieved.',
        ]);
    }

    /**
     * Show one request's current status and understandable history (UC-03
     * steps 3–6). Reach is request-scoped: a record outside the caller's scope
     * reads as not found (404, ext 3a) rather than forbidden, so existence is
     * not revealed.
     *
     * The detail carries the current `status` (its slug, e.g.
     * `waiting_for_citizen` while a response is pending — ext 6a), `submitted_at`,
     * the `category`, `request_details`, and the connected collections the
     * citizen reviews in steps 5–6: history entries ordered by `sequence_number`
     * (each with its frozen `summary` — ext 5a), messages, documents, and the
     * decision where one exists [03_use-cases.md UC-03 steps 3–6;
     * 04_data-model.md §2.1; BR-016, BR-017].
     */
    public function show(HttpRequest $httpRequest, Request $request): JsonResponse
    {
        $this->ensureInScope($httpRequest->user(), $request);

        $request->load([
            'category',
            'historyEntries' => function (HasMany $query): void {
                $query->orderBy('sequence_number');
            },
            'messages',
            'documents',
            'decision',
        ]);

        return response()->json([
            'data' => $request,
            'message' => 'Request retrieved.',
        ]);
    }

    /**
     * Create a request as a Draft owned by the caller (steps 1–4). The active
     * category and shape are validated by the form request; success is 201 with
     * the created resource in the `data` envelope.
     */
    public function store(StoreRequestRequest $storeRequest): JsonResponse
    {
        $validated = $storeRequest->validated();

        $request = DB::transaction(fn (): Request => Request::create([
            'owner_user_account_id' => $storeRequest->user()->id,
            'request_category_id' => $validated['request_category_id'],
            'title' => $validated['title'],
            'request_details' => $validated['request_details'],
            'status' => RequestStatus::Draft,
        ]));

        return response()->json([
            'data' => $request,
            'message' => 'Request created as a draft.',
        ], 201);
    }

    /**
     * Edit a Draft before submission (steps 3–6; ext 6a). Out-of-scope records
     * read as not found (404); an in-scope non-owner, or an owner whose request
     * is no longer editable, is denied (403) through `provideInformation`. On
     * success the request stays Draft with the edited fields.
     */
    public function update(UpdateRequestRequest $updateRequest, Request $request): JsonResponse
    {
        $actor = $updateRequest->user();

        $this->ensureInScope($actor, $request);
        abort_if(
            Gate::forUser($actor)->denies('provideInformation', $request),
            403,
            'You are not allowed to edit this request.',
        );

        $validated = $updateRequest->validated();

        DB::transaction(function () use ($request, $validated): void {
            $request->update([
                'request_category_id' => $validated['request_category_id'],
                'title' => $validated['title'],
                'request_details' => $validated['request_details'],
            ]);
        });

        return response()->json([
            'data' => $request->fresh(),
            'message' => 'Request updated.',
        ]);
    }

    /**
     * Submit a Draft (steps 7–10). Out-of-scope records read as not found (404,
     * ext 7a); only the owning citizen may submit (`submit`, 403 otherwise).
     *
     * The durable-write path is atomic: the guarded Draft → Submitted transition,
     * `submitted_at`, and the `status_changed` history entry are saved in one
     * transaction, so an illegal transition is a 409 (ext 8a) and a persistence
     * fault rolls back to Draft (500, ext 8a) with no history row. Administrator
     * notifications are best-effort and run after the commit: a notification
     * fault leaves the request Submitted and discoverable in the submitted list
     * (ext 10a).
     */
    public function submit(HttpRequest $httpRequest, Request $request): JsonResponse
    {
        $actor = $httpRequest->user();

        $this->ensureInScope($actor, $request);
        abort_if(
            Gate::forUser($actor)->denies('submit', $request),
            403,
            'You are not allowed to submit this request.',
        );

        $fromStatus = $request->status;

        $historyEntry = DB::transaction(function () use ($request, $actor, $fromStatus): RequestHistoryEntry {
            // Guarded transition — sets the status in memory, raising
            // IllegalStatusTransitionException (rendered 409) on an illegal target.
            $request->transitionTo(RequestStatus::Submitted);
            $request->submitted_at = now();
            $request->save();

            $sequence = (int) $request->historyEntries()->max('sequence_number') + 1;

            return $request->historyEntries()->create([
                'sequence_number' => $sequence,
                'actor_user_account_id' => $actor->id,
                'event_type' => HistoryEventType::StatusChanged,
                'from_status' => $fromStatus,
                'to_status' => RequestStatus::Submitted,
                'summary' => 'Citizen submitted the request.',
                'event_occurred_at' => now(),
            ]);
        });

        $this->notifyAdministrators($request, $historyEntry);

        return response()->json([
            'data' => $request->fresh(),
            'message' => 'Request submitted.',
        ]);
    }

    /**
     * Start reviewing an assigned request (UC-06 step 5). Out-of-scope records
     * read as not found (404, ext 3a); only the responsible staff member may
     * start review (`review`, 403 otherwise) — an in-scope owner or administrator
     * lacks the ability.
     *
     * The durable-write path is atomic: the guarded Submitted → In Review
     * transition and its `status_changed` history entry are saved in one
     * transaction, so the review is never treated as started without a recorded
     * trace (ext 5a, ext 8a). Start review on a request that is not Submitted is a
     * blocked transition — a 409 (ext 5a, ext 7a) — and a persistence fault rolls
     * back to Submitted with no history row (500, ext 5a, ext 8a). UC-06 defines
     * no notification [03_use-cases.md UC-06 step 5; BR-004, BR-009, BR-017;
     * docs/conventions.md Status transitions].
     */
    public function startReview(HttpRequest $httpRequest, Request $request): JsonResponse
    {
        $actor = $httpRequest->user();

        $this->ensureInScope($actor, $request);
        abort_if(
            Gate::forUser($actor)->denies('review', $request),
            403,
            'You are not allowed to review this request.',
        );

        $fromStatus = $request->status;

        DB::transaction(function () use ($request, $actor, $fromStatus): void {
            // Guarded transition — sets the status in memory, raising
            // IllegalStatusTransitionException (rendered 409) on an illegal target.
            $request->transitionTo(RequestStatus::InReview);
            $request->save();

            $sequence = (int) $request->historyEntries()->max('sequence_number') + 1;

            $request->historyEntries()->create([
                'sequence_number' => $sequence,
                'actor_user_account_id' => $actor->id,
                'event_type' => HistoryEventType::StatusChanged,
                'from_status' => $fromStatus,
                'to_status' => RequestStatus::InReview,
                'summary' => 'Staff member started the review.',
                'event_occurred_at' => now(),
            ]);
        });

        return response()->json([
            'data' => $request->fresh(),
            'message' => 'Review started.',
        ]);
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
     * Best-effort: one `request_submitted` notification per active administrator
     * so a submitted request gets assigned. The submission is already durable, so
     * any fault here is swallowed and reported — it never rolls back the submit
     * (ext 10a) [03_use-cases.md UC-02 step 10; 05_system-design.md §4].
     */
    private function notifyAdministrators(Request $request, RequestHistoryEntry $historyEntry): void
    {
        try {
            $administrators = UserAccount::query()
                ->where('role', Role::Administrator)
                ->where('account_state', AccountState::Active)
                ->get();

            foreach ($administrators as $administrator) {
                Notification::create([
                    'recipient_user_account_id' => $administrator->id,
                    'request_id' => $request->id,
                    'request_history_entry_id' => $historyEntry->id,
                    'notification_type' => NotificationType::RequestSubmitted,
                    'body' => 'A new request was submitted and needs assignment.',
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
