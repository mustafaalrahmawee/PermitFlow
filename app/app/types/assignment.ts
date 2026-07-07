/**
 * UC-05 assignment types, mirroring the administrator assignment API seam. No
 * fetch logic here — the assignments Pinia store owns the seam calls. The seam is
 * exactly three endpoints [docs/by-use-case/uc05_assign-or-reassign-a-request-for-handling.md API seam]:
 *   - GET /admin/requests            → eligible requests, each with its current responsible staff
 *   - GET /admin/assignable-staff    → active Staff-member accounts for the picker
 *   - PUT /requests/{id}/assignment  → body { responsible_staff_user_account_id, reason? }
 */
import type { RequestListItem } from "~/types/request";
import type { UserAccount } from "~/types/user-account";

/** An active Staff-member account the request may be assigned to (assignee picker). */
export type AssignableStaff = Pick<UserAccount, "id" | "display_name">;

/**
 * A request in the assignment worklist: the request row plus the current
 * responsible staff member (null when unassigned — a first assignment) the GET
 * /admin/requests seam eager-loads under `responsible_staff`.
 */
export interface AssignmentWorklistItem extends RequestListItem {
  responsible_staff?: AssignableStaff | null;
}

/**
 * The PUT /requests/{id}/assignment body: the single responsible staff member,
 * and — on reassignment (the request is already assigned) — a short reason.
 */
export interface AssignmentPayload {
  responsible_staff_user_account_id: number;
  reason?: string;
}
