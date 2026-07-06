/**
 * UC-02 request types, mirroring the citizen filing/submission API seam. No
 * fetch logic here — the requests Pinia store owns the seam calls. The seam
 * fields are exactly: `request_category_id`, `title`, `request_details`
 * (whole-block JSON) on create/edit; submit takes no body
 * [docs/by-use-case/uc02_submit-a-request.md API seam].
 */
export type RequestStatusSlug =
  | "draft"
  | "submitted"
  | "in_review"
  | "waiting_for_citizen"
  | "ready_for_decision"
  | "decided";

export interface RequestRecord {
  id: number;
  owner_user_account_id: number;
  request_category_id: number;
  responsible_staff_user_account_id: number | null;
  title: string;
  request_details: Record<string, unknown>;
  status: RequestStatusSlug;
  submitted_at: string | null;
  created_at?: string;
  updated_at?: string;
}

/** Fields the create/edit seam accepts (whole-block `request_details`). */
export interface RequestPayload {
  request_category_id: number;
  title: string;
  request_details: Record<string, unknown>;
}

/** A supporting document returned by the attach seam. */
export interface RequestDocument {
  id: number;
  request_id: number;
  uploaded_by_user_account_id: number;
  kind: string;
  file_reference: string;
  original_filename: string;
  mime_type: string;
  size_bytes: number;
  description: string | null;
}

export const requestStatusLabels: Record<RequestStatusSlug, string> = {
  draft: "Draft",
  submitted: "Submitted",
  in_review: "In Review",
  waiting_for_citizen: "Waiting for Citizen",
  ready_for_decision: "Ready for Decision",
  decided: "Decided",
};
