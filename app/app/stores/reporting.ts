import type { AdminReportingSummary, StaffReportingSummary } from "~/types/reporting";

/**
 * UC-13 / UC-14 — the single client of the reporting API seams. The store owns
 * the `$fetch` calls and the resulting summary state; each page calls its action
 * and renders the view the actor selects. The global `$fetch` plugin supplies the
 * base URL and the Sanctum bearer token, so only the path is passed here. Success
 * uses the one envelope: `data` carries the summary, `message` a short summary
 * [docs/conventions.md API success responses].
 *   - GET /reporting/staff-summary → { data: StaffReportingSummary, message }
 *   - GET /reporting/admin-summary → { data: AdminReportingSummary, message }
 * Each action throws on 403 (reporting-gate / admin-narrowing denial — the error
 * envelope `{ message }`) for the page to render.
 */
export const useReportingStore = defineStore("reporting", () => {
  const summary = ref<StaffReportingSummary | null>(null);
  const adminSummary = ref<AdminReportingSummary | null>(null);

  /** Load the staff member's reporting summary (UC-13 main flow steps 1–4). */
  async function fetchStaffSummary(): Promise<StaffReportingSummary> {
    const res = await $fetch<{ data: StaffReportingSummary }>("/reporting/staff-summary");
    summary.value = res.data;
    return res.data;
  }

  /** Load the administrator's organization-level summary (UC-14 main flow steps 1–4). */
  async function fetchAdminSummary(): Promise<AdminReportingSummary> {
    const res = await $fetch<{ data: AdminReportingSummary }>("/reporting/admin-summary");
    adminSummary.value = res.data;
    return res.data;
  }

  return { summary, adminSummary, fetchStaffSummary, fetchAdminSummary };
});
