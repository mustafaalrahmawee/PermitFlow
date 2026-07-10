/**
 * UC-13 reporting-summary types, mirroring the staff reporting API seam
 * (`GET /api/reporting/staff-summary`). The summary is derived, recomputed on
 * every read, and never persisted; its content is scoped to the requests the
 * staff member is responsible for, so it carries counts only — no per-request
 * information. No fetch logic here — the reporting Pinia store owns the seam call
 * [docs/by-use-case/uc13_view-staff-reporting-summaries.md API seam].
 */
import type { RequestStatusSlug } from "~/types/request";

/** Per-status counts: every `RequestStatus` slug present, zero-filled. */
export type StatusCounts = Record<RequestStatusSlug, number>;

export interface StaffReportingSummary {
  scope: string;
  volume: {
    total: number;
  };
  by_status: StatusCounts;
  processing_progress: {
    /** Requests still being processed (everything not yet Decided). */
    open: number;
    /** Completed requests. */
    decided: number;
    /** Requests blocked on the citizen — a work-planning highlight. */
    awaiting_citizen: number;
  };
}
