import type {
  AssignableStaff,
  AssignmentPayload,
  AssignmentWorklistItem,
} from "~/types/assignment";
import type { PageMeta } from "~/types/pagination";
import type { UserAccount } from "~/types/user-account";

/**
 * UC-05 — the single client of the administrator assignment API seam. The store
 * owns every `$fetch` against the seam and the resulting state; the worklist page
 * calls these actions and renders the outcomes. The global `$fetch` plugin
 * supplies the base URL and the Sanctum bearer token, so only the path is passed.
 * Every success uses the one envelope: `data` carries the payload, `message` a
 * short summary [docs/conventions.md API success responses].
 *   - GET /admin/requests?page=N     → { data: AssignmentWorklistItem[], meta, message }  (eligible only)
 *   - GET /admin/assignable-staff    → { data: UserAccount[], message }                    (active staff)
 *   - PUT /requests/{id}/assignment  → { data: AssignmentWorklistItem, message }           (assign/reassign)
 * Actions throw on 403/404/409/422 (error envelope `{ message, errors? }`) for
 * the calling page to render. `assign` patches the matching worklist row in place
 * from the response — no full refetch.
 */
export const useAssignmentsStore = defineStore("assignments", () => {
  const worklist = ref<AssignmentWorklistItem[]>([]);
  const meta = ref<PageMeta | null>(null);
  const assignableStaff = ref<AssignableStaff[]>([]);

  /** Load one page of the eligible-request worklist (main flow steps 1–2); page is 1-based. */
  async function fetchWorklist(page = 1): Promise<void> {
    const res = await $fetch<{ data: AssignmentWorklistItem[]; meta: PageMeta }>("/admin/requests", {
      query: { page },
    });
    worklist.value = res.data;
    meta.value = res.meta;
  }

  /** Load the active Staff-member accounts a request may be assigned to (step 3). */
  async function fetchAssignableStaff(): Promise<void> {
    const res = await $fetch<{ data: UserAccount[] }>("/admin/assignable-staff");
    assignableStaff.value = res.data.map((account) => ({
      id: account.id,
      display_name: account.display_name,
    }));
  }

  /**
   * Assign or reassign a request (steps 5–11). On success, replace the matching
   * worklist row in place from the response rather than refetching the whole list;
   * the status is unchanged, so the request stays in the worklist with its new
   * responsible staff member.
   */
  async function assign(
    id: number,
    payload: AssignmentPayload,
  ): Promise<AssignmentWorklistItem> {
    const res = await $fetch<{ data: AssignmentWorklistItem }>(`/requests/${id}/assignment`, {
      method: "PUT",
      body: payload,
    });

    const updated = res.data;
    const index = worklist.value.findIndex((item) => item.id === updated.id);
    if (index !== -1) {
      worklist.value[index] = updated;
    }

    return updated;
  }

  return { worklist, meta, assignableStaff, fetchWorklist, fetchAssignableStaff, assign };
});
