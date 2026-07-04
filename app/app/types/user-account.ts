/**
 * UC-01 user-account types and select option maps, mirroring the backend enums
 * (role slugs and account-state slugs) and the admin API seam's `user_account`
 * shape. No fetch logic here — the user-accounts Pinia store owns the seam calls.
 */
export interface UserAccount {
  id: number;
  display_name: string;
  email: string;
  role: "citizen" | "staff_member" | "administrator";
  account_state: "active" | "inactive";
}

/** Fields the create seam accepts (password is the implementation-only login column). */
export interface CreateUserAccountPayload {
  display_name: string;
  email: string;
  role: UserAccount["role"];
  account_state: UserAccount["account_state"];
  password: string;
}

/** PATCH is partial: any subset of the editable fields plus an optional new password. */
export type UpdateUserAccountPayload = Partial<CreateUserAccountPayload>;

export const roleOptions: Record<UserAccount["role"], string> = {
  citizen: "Citizen",
  staff_member: "Staff member",
  administrator: "Administrator",
};

export const accountStateOptions: Record<UserAccount["account_state"], string> = {
  active: "Active",
  inactive: "Inactive",
};
