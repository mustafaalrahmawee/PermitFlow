/**
 * UC-02 request types, mirroring the citizen filing/submission API seam. No
 * fetch logic here — the requests Pinia store owns the seam calls. The seam
 * fields are exactly: `request_category_id`, `title`, `request_details`
 * (whole-block JSON) on create/edit; submit takes no body
 * [docs/by-use-case/uc02_submit-a-request.md API seam].
 */
import type { RequestCategory } from "~/types/request-category";

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

/**
 * UC-03 detail-read types, mirroring the `GET /api/requests/{id}` seam's
 * connected collections. The frozen `summary` on a history entry carries the
 * understandable wording the citizen reads (ext 5a); no fetch logic lives here —
 * the requests store owns the seam calls
 * [docs/by-use-case/uc03_track-request-progress.md API seam].
 */
export interface RequestHistoryEntry {
  id: number;
  request_id: number;
  sequence_number: number;
  event_type: string;
  from_status: RequestStatusSlug | null;
  to_status: RequestStatusSlug | null;
  summary: string;
  reason: string | null;
  event_occurred_at: string | null;
}

export interface RequestMessage {
  id: number;
  request_id: number;
  message_kind: string;
  body: string;
  sent_at: string | null;
}

export interface RequestDecision {
  id: number;
  request_id: number;
  outcome: "approved" | "rejected";
  decision_text: string | null;
  decided_at: string | null;
}

/**
 * The full request the detail read returns: the request row plus its `category`
 * and the connected history entries (ordered by `sequence_number`), messages,
 * documents, and decision where one exists.
 */
export interface RequestDetail extends RequestRecord {
  category?: RequestCategory | null;
  history_entries: RequestHistoryEntry[];
  messages: RequestMessage[];
  documents: RequestDocument[];
  decision: RequestDecision | null;
}

/** A row in the citizen's own request list; carries its current status slug. */
export interface RequestListItem extends RequestRecord {
  category?: RequestCategory | null;
}

export const decisionOutcomeLabels: Record<RequestDecision["outcome"], string> = {
  approved: "Approved",
  rejected: "Rejected",
};

export const requestStatusLabels: Record<RequestStatusSlug, string> = {
  draft: "Draft",
  submitted: "Submitted",
  in_review: "In Review",
  waiting_for_citizen: "Waiting for Citizen",
  ready_for_decision: "Ready for Decision",
  decided: "Decided",
};
