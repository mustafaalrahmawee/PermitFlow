import type { PageMeta } from "~/types/pagination";
import type {
  CreateUserAccountPayload,
  UpdateUserAccountPayload,
  UserAccount,
} from "~/types/user-account";

/**
 * UC-01 — the single client of the administrator user-account API seam. The
 * store owns every `$fetch` against the seam and the resulting state; pages call
 * these actions and render the outcomes. The global `$fetch` plugin supplies the
 * base URL and the Sanctum bearer token, so only the path is passed here.
 * Every success uses the one envelope: `data` carries the payload, `message` a
 * short summary [docs/conventions.md API success responses].
 *   - GET   /admin/user-accounts?page=N   → { data: UserAccount[], meta, message }
 *   - GET   /admin/user-accounts/{id}      → { data: UserAccount, message }
 *   - POST  /admin/user-accounts           → { data: UserAccount, message }  (201)
 *   - PATCH /admin/user-accounts/{id}       → { data: UserAccount, message }
 * Actions throw on 403/404/409/422 (error envelope `{ message, errors? }`) for
 * the calling page to render. Create and update patch the local `accounts` list
 * from the response — no full refetch.
 */
export const useUserAccountsStore = defineStore("userAccounts", () => {
  const accounts = ref<UserAccount[]>([]);
  const meta = ref<PageMeta | null>(null);
  const current = ref<UserAccount | null>(null);

  /** Load one page of the account list (main flow steps 1–3); page is 1-based. */
  async function fetchAll(page = 1): Promise<void> {
    const res = await $fetch<{ data: UserAccount[]; meta: PageMeta }>("/admin/user-accounts", {
      query: { page },
    });
    accounts.value = res.data;
    meta.value = res.meta;
  }

  /** Load one account for maintenance; a missing account throws (404). */
  async function fetchOne(id: number | string): Promise<UserAccount> {
    const res = await $fetch<{ data: UserAccount }>(`/admin/user-accounts/${id}`);
    current.value = res.data;
    return res.data;
  }

  /** Create an account, then append it to the local list (no refetch). */
  async function create(payload: CreateUserAccountPayload): Promise<UserAccount> {
    const res = await $fetch<{ data: UserAccount }>("/admin/user-accounts", {
      method: "POST",
      body: payload,
    });

    accounts.value.push(res.data);

    return res.data;
  }

  /**
   * Maintain an account; lifecycle blockers surface as 409 for the page to
   * render. On success, replace the matching row in the local list in place
   * rather than refetching the whole list.
   */
  async function update(
    id: number | string,
    payload: UpdateUserAccountPayload,
  ): Promise<UserAccount> {
    const res = await $fetch<{ data: UserAccount }>(`/admin/user-accounts/${id}`, {
      method: "PATCH",
      body: payload,
    });

    const updated = res.data;
    const index = accounts.value.findIndex((account) => account.id === updated.id);
    if (index !== -1) {
      accounts.value[index] = updated;
    }
    if (current.value?.id === updated.id) {
      current.value = updated;
    }

    return updated;
  }

  return { accounts, meta, current, fetchAll, fetchOne, create, update };
});
