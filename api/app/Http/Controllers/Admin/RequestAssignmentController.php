<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountState;
use App\Enums\HistoryEventType;
use App\Enums\NotificationType;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRequestAssignmentRequest;
use App\Models\Notification;
use App\Models\Request;
use App\Models\RequestHistoryEntry;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Administrator assignment and reassignment of requests (UC-05).
 *
 * Every action is administrator-only: the route group applies `auth:sanctum` and
 * the `assign-requests` gate, which fails closed for inactive or non-admin actors
 * (403, ext 5a) [BR-010; docs/conventions.md Authorization]. A request may be
 * assigned or reassigned only while its status is one of the eligible set
 * (Submitted, In Review, Waiting for Citizen, Ready for Decision); a Draft (ext
 * 2a), a Decided request (ext 2b), or any other status (ext 6a) is a 409
 * lifecycle conflict — the status is validated, never changed here. The
 * assignment write and its `assignment_changed` history entry are saved together
 * in one transaction, so a history-write failure rolls back the assignment (500,
 * ext 8a). The `assigned` / `reassigned` notifications are best-effort and run
 * after the commit [03_use-cases.md UC-05; BR-004, BR-017; docs/conventions.md
 * Status transitions, API error responses].
 */
class RequestAssignmentController extends Controller
{
    /**
     * Fixed page size for the assignment worklist; secondary/performance tuning
     * is a v1 non-goal, so this is a constant rather than a client parameter.
     */
    private const PER_PAGE = 15;

    /**
     * Statuses a request may be assigned or reassigned in (BR-004). A request
     * outside this set is a 409 lifecycle conflict (ext 2a/2b/6a).
     *
     * @var array<int, RequestStatus>
     */
    private const ELIGIBLE_STATUSES = [
        RequestStatus::Submitted,
        RequestStatus::InReview,
        RequestStatus::WaitingForCitizen,
        RequestStatus::ReadyForDecision,
    ];

    /**
    ^
     */
    public function index(): JsonResponse
    {
        $requests = Request::query()
            ->whereIn('status', array_map(fn (RequestStatus $s): string => $s->value, self::ELIGIBLE_STATUSES))
            ->with(['category', 'responsibleStaff'])
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
            'message' => 'Requests needing assignment retrieved.',
        ]);
    }

    /**
     * List the active Staff-member accounts a request may be assigned to (step 3).
     *
     * Only active accounts with the Staff member role are assignable (BR-001,
     * BR-009); the list feeds the assignee picker, so it is returned flat (no
     * pagination) [03_use-cases.md UC-05 step 3; docs/conventions.md API success
     * responses].
     */
    public function assignableStaff(): JsonResponse
    {
        $staff = UserAccount::query()
            ->where('role', Role::StaffMember)
            ->where('account_state', AccountState::Active)
            ->orderBy('display_name')
            ->get();

        return response()->json([
            'data' => $staff,
            'message' => 'Assignable staff retrieved.',
        ]);
    }

    /**
     * Assign or reassign a request to a staff member (steps 5–11).
     *
     * The `assign-requests` gate (403, ext 5a) and route-model binding (404 for a
     * missing request) run first; the form request validates the assignee and, on
     * reassignment, the reason (422, ext 3a/3b/4a). The eligibility guard rejects
     * a Draft, Decided, or otherwise out-of-status request (409, ext 2a/2b/6a)
     * before any write. The assignment and its `assignment_changed` history entry
     * are then saved in one transaction — a history-write fault rolls the
     * assignment back (500, ext 8a). The `assigned` (and, on reassignment,
     * `reassigned`) notifications are best-effort and run after the commit.
     */
    public function update(UpdateRequestAssignmentRequest $assignmentRequest, Request $request): JsonResponse
    {
        abort_unless(
            in_array($request->status, self::ELIGIBLE_STATUSES, true),
            409,
            'This request cannot be assigned or reassigned in its current status.',
        );

        $administrator = $assignmentRequest->user();
        $validated = $assignmentRequest->validated();
        $newStaffId = (int) $validated['responsible_staff_user_account_id'];
        $previousStaffId = $request->responsible_staff_user_account_id;
        $isReassignment = $previousStaffId !== null;

        $historyEntry = DB::transaction(function () use (
            $request,
            $administrator,
            $newStaffId,
            $previousStaffId,
            $isReassignment,
            $validated,
        ): RequestHistoryEntry {
            $request->responsible_staff_user_account_id = $newStaffId;
            $request->save();

            $sequence = (int) $request->historyEntries()->max('sequence_number') + 1;

            return $request->historyEntries()->create([
                'sequence_number' => $sequence,
                'actor_user_account_id' => $administrator->id,
                'event_type' => HistoryEventType::AssignmentChanged,
                'previous_staff_user_account_id' => $previousStaffId,
                'new_staff_user_account_id' => $newStaffId,
                'summary' => $isReassignment
                    ? 'Administrator reassigned the request to another staff member.'
                    : 'Administrator assigned the request to a staff member.',
                'reason' => $isReassignment ? $validated['reason'] : null,
                'event_occurred_at' => now(),
            ]);
        });

        $this->notifyStaff($request, $historyEntry, $newStaffId, $previousStaffId, $isReassignment);

        return response()->json([
            'data' => $request->fresh(['category', 'responsibleStaff']),
            'message' => $isReassignment ? 'Request reassigned.' : 'Request assigned.',
        ]);
    }

    /**
     * Best-effort in-portal notifications: an `assigned` alert for the newly
     * responsible staff member, and — on reassignment — a `reassigned` alert for
     * the previous responsible staff member. The assignment is already durable, so
     * any fault here is swallowed and reported; it never rolls back the assignment
     * [03_use-cases.md UC-05 steps 10–11; 05_system-design.md §4].
     */
    private function notifyStaff(
        Request $request,
        RequestHistoryEntry $historyEntry,
        int $newStaffId,
        ?int $previousStaffId,
        bool $isReassignment,
    ): void {
        try {
            Notification::create([
                'recipient_user_account_id' => $newStaffId,
                'request_id' => $request->id,
                'request_history_entry_id' => $historyEntry->id,
                'notification_type' => NotificationType::Assigned,
                'body' => 'You were assigned a request.',
            ]);

            if ($isReassignment && $previousStaffId !== null) {
                Notification::create([
                    'recipient_user_account_id' => $previousStaffId,
                    'request_id' => $request->id,
                    'request_history_entry_id' => $historyEntry->id,
                    'notification_type' => NotificationType::Reassigned,
                    'body' => 'A request was reassigned to another staff member.',
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
