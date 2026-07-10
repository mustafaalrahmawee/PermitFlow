import type { StaffReportingSummary } from "~/types/reporting";

/**
 * UC-13 — the single client of the staff reporting API seam. The store owns the
 * `$fetch` call and the resulting summary state; the page calls this action and
 * renders the view the staff member selects. The global `$fetch` plugin supplies
 * the base URL and the Sanctum bearer token, so only the path is passed here.
 * Success uses the one envelope: `data` carries the summary, `message` a short
 * summary [docs/conventions.md API success responses].
 *   - GET /reporting/staff-summary → { data: StaffReportingSummary, message }
 * The action throws on 403 (view-reporting gate denial — the error envelope
 * `{ message }`) for the page to render.
 */
export const useReportingStore = defineStore("reporting", () => {
  const summary = ref<StaffReportingSummary | null>(null);

  /** Load the staff member's reporting summary (main flow steps 1–4). */
  async function fetchStaffSummary(): Promise<StaffReportingSummary> {
    const res = await $fetch<{ data: StaffReportingSummary }>("/reporting/staff-summary");
    summary.value = res.data;
    return res.data;
  }

  return { summary, fetchStaffSummary };
});
