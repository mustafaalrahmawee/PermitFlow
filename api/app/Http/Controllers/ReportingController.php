<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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

    /**
     * Return the administrator's organization-level reporting summary (UC-14 main
     * flow steps 1–4). The `view-reporting` gate (route middleware) has already
     * admitted only an active staff member or administrator; this seam is the
     * *administrative* variant, which is narrower than the shared gate — ext 2a
     * denies a non-administrator (e.g. a staff member) — so it additionally
     * requires the Administrator role. The conventions define no dedicated
     * admin-reporting gate, so the narrowing is realized here as an in-controller
     * role check that fails closed (403, ext 1a/2a) [uc14 Authorization; BR-015,
     * BR-016].
     *
     * The summary aggregates organization-wide over `requests` (single
     * institution) — request volume, per-status counts, and processing progress
     * including an assignment breakdown from `responsible_staff_user_account_id`.
     * It is derived, recomputed on every read, and never persisted; the payload
     * carries organization-level aggregates only, "what oversight requires" — no
     * per-request bodies or message content (ext 4a). A selected view with no
     * matching requests is a zero-filled bucket, not an error (ext 3a). No column
     * is written; committed state only.
     */
    public function adminSummary(HttpRequest $httpRequest): JsonResponse
    {
        $actor = $httpRequest->user();

        // Derived narrowing (uc14 Authorization): the administrative summary is
        // administrator-only. Fail closed — a staff member is inside the
        // `view-reporting` gate but denied here (ext 2a).
        abort_unless(
            $actor->isActive() && $actor->isAdministrator(),
            HttpResponse::HTTP_FORBIDDEN,
        );

        // One grouped read over all requests of the single organization — no
        // scope filter, since administrator oversight is organization-wide (BR-016
        // notes). Committed state only; no column written.
        $countsByStatus = Request::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Zero-fill every status slug so the shape is stable and a status with no
        // matching requests is a zero bucket, not a missing key (ext 3a).
        $byStatus = [];
        foreach (RequestStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($countsByStatus[$status->value] ?? 0);
        }

        $total = array_sum($byStatus);
        $decided = $byStatus[RequestStatus::Decided->value];

        // Processing progress incl. an assignment aggregate — an oversight
        // dimension the administrative summary adds over the staff view. Aggregate
        // counts only; no staff identity is revealed (ext 4a).
        $assigned = (int) Request::query()
            ->whereNotNull('responsible_staff_user_account_id')
            ->count();

        $summary = [
            'scope' => 'organization',
            'volume' => [
                'total' => $total,
            ],
            'by_status' => $byStatus,
            'processing_progress' => [
                // Still being processed (everything not yet Decided).
                'open' => $total - $decided,
                // Completed.
                'decided' => $decided,
                // Blocked on the citizen.
                'awaiting_citizen' => $byStatus[RequestStatus::WaitingForCitizen->value],
                // Assignment oversight: how much of the pipeline has a responsible
                // staff member vs. still awaiting assignment.
                'assigned' => $assigned,
                'unassigned' => $total - $assigned,
            ],
        ];

        return response()->json([
            'data' => $summary,
            'message' => 'Reporting summary retrieved.',
        ]);
    }
}
