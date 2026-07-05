import type { PageMeta } from "~/types/pagination";
import type {
  CreateRequestCategoryPayload,
  RequestCategory,
  UpdateRequestCategoryPayload,
} from "~/types/request-category";

/**
 * UC-11 — the single client of the administrator request-category API seam. The
 * store owns every `$fetch` against the seam and the resulting state; pages call
 * these actions and render the outcomes. The global `$fetch` plugin supplies the
 * base URL and the Sanctum bearer token, so only the path is passed here.
 * Every success uses the one envelope: `data` carries the payload, `message` a
 * short summary [docs/conventions.md API success responses].
 *   - GET    /admin/request-categories?page=N   → { data: RequestCategory[], meta, message }
 *   - GET    /admin/request-categories/{id}      → { data: RequestCategory, message }
 *   - POST   /admin/request-categories           → { data: RequestCategory, message }  (201)
 *   - PATCH  /admin/request-categories/{id}       → { data: RequestCategory, message }
 *   - DELETE /admin/request-categories/{id}       → { data: null, message }
 * Actions throw on 403/404/409/422 (error envelope `{ message, errors? }`) for
 * the calling page to render. Create/update/delete patch the local `categories`
 * list from the response — no full refetch.
 */
export const useRequestCategoriesStore = defineStore("requestCategories", () => {
  const categories = ref<RequestCategory[]>([]);
  const meta = ref<PageMeta | null>(null);
  const current = ref<RequestCategory | null>(null);

  /** Load one page of the category list (main flow steps 1–2); page is 1-based. */
  async function fetchAll(page = 1): Promise<void> {
    const res = await $fetch<{ data: RequestCategory[]; meta: PageMeta }>("/admin/request-categories", {
      query: { page },
    });
    categories.value = res.data;
    meta.value = res.meta;
  }

  /** Load one category for maintenance; a missing category throws (404, ext 2a). */
  async function fetchOne(id: number | string): Promise<RequestCategory> {
    const res = await $fetch<{ data: RequestCategory }>(`/admin/request-categories/${id}`);
    current.value = res.data;
    return res.data;
  }

  /** Create a category, then append it to the local list (no refetch). */
  async function create(payload: CreateRequestCategoryPayload): Promise<RequestCategory> {
    const res = await $fetch<{ data: RequestCategory }>("/admin/request-categories", {
      method: "POST",
      body: payload,
    });

    categories.value.push(res.data);

    return res.data;
  }

  /**
   * Maintain a category; a semantic rename of a used category surfaces as 409 for
   * the page to render (ext 6b). On success, replace the matching row in the
   * local list in place rather than refetching the whole list.
   */
  async function update(
    id: number | string,
    payload: UpdateRequestCategoryPayload,
  ): Promise<RequestCategory> {
    const res = await $fetch<{ data: RequestCategory }>(`/admin/request-categories/${id}`, {
      method: "PATCH",
      body: payload,
    });

    const updated = res.data;
    const index = categories.value.findIndex((category) => category.id === updated.id);
    if (index !== -1) {
      categories.value[index] = updated;
    }
    if (current.value?.id === updated.id) {
      current.value = updated;
    }

    return updated;
  }

  /**
   * Delete a category; deleting one used by existing requests surfaces as 409 for
   * the page to render (ext 6a). On success, drop the row from the local list.
   */
  async function remove(id: number | string): Promise<void> {
    await $fetch(`/admin/request-categories/${id}`, { method: "DELETE" });

    categories.value = categories.value.filter((category) => category.id !== Number(id));
    if (current.value?.id === Number(id)) {
      current.value = null;
    }
  }

  return { categories, meta, current, fetchAll, fetchOne, create, update, remove };
});
