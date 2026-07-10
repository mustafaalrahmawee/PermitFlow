<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

/**
 * Staff reporting summaries (UC-13).
 *
 * A pure read: the responsible staff member gets a basic, recomputed summary of
 * request volume, per-status counts, and processing progress for work planning.
 * The summary is never persisted — it is aggregated on every read from `requests`
 * (UC-13 Data touched; 05_system-design.md §2). Access is behind the
 * `view-reporting` role gate (staff member or administrator; a citizen is denied
 * 403), applied on the route. Within the gate the summary content is bounded by
 * request-scoped access: it aggregates only over requests where the actor is the
 * responsible staff member, so no request information outside the actor's scope is
 * revealed (ext 2a, ext 4a) [03_use-cases.md UC-13; 02_business-rules.md BR-015,
 * BR-016; docs/conventions.md Authorization]. There is no status change, history
 * write, or notification.
 */
class ReportingController extends Controller
{
    /**
     * Return the staff member's reporting summary (main flow steps 1–4). The
     * `view-reporting` gate (route middleware) has already admitted only an active
     * staff member or administrator; the aggregates below are scoped to the
     * requests the actor is responsible for, so the payload carries no per-request
     * information from outside that scope.
     *
     * A staff member with no matching requests gets a zero-filled summary in the
     * `data` envelope rather than an error (ext 3a). The shape is stable across
     * callers: `volume.total`, a `by_status` map with every `RequestStatus` slug
     * present (zero-filled), and a `processing_progress` breakdown derived from the
     * same counts — from which the client selects the view it needs (step 3).
     */
    public function staffSummary(HttpRequest $httpRequest): JsonResponse
    {
        $actor = $httpRequest->user();

        // One grouped read over the actor's responsible requests — the scope
        // filter that bounds the summary to BR-016 (ext 2a, ext 4a). No column is
        // written; committed state only.
        $countsByStatus = Request::query()
            ->where('responsible_staff_user_account_id', $actor->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Zero-fill every status slug so the shape is stable and an empty summary
        // is a full object of zeros, not a sparse map (ext 3a).
        $byStatus = [];
        foreach (RequestStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($countsByStatus[$status->value] ?? 0);
        }

        $total = array_sum($byStatus);
        $decided = $byStatus[RequestStatus::Decided->value];

        $summary = [
            'scope' => 'responsible_staff',
            'volume' => [
                'total' => $total,
            ],
            'by_status' => $byStatus,
            'processing_progress' => [
                // Still being processed (everything not yet Decided).
                'open' => $total - $decided,
                // Completed.
                'decided' => $decided,
                // Blocked on the citizen — a work-planning highlight.
                'awaiting_citizen' => $byStatus[RequestStatus::WaitingForCitizen->value],
            ],
        ];

        return response()->json([
            'data' => $summary,
            'message' => 'Reporting summary retrieved.',
        ]);
    }
}
