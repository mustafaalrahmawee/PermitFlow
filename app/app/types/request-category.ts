/**
 * UC-11 request-category types, mirroring the admin API seam's category shape
 * (`name`, `description`, `is_active`). No fetch logic here — the
 * request-categories Pinia store owns the seam calls.
 */
export interface RequestCategory {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
}

/** Fields the create seam accepts. */
export interface CreateRequestCategoryPayload {
  name: string;
  description: string | null;
  is_active: boolean;
}

/** PATCH is partial: any subset of the editable fields. */
export type UpdateRequestCategoryPayload = Partial<CreateRequestCategoryPayload>;

export const availabilityOptions: Record<"active" | "inactive", string> = {
  active: "Active — available for new requests",
  inactive: "Inactive — hidden from new requests",
};
