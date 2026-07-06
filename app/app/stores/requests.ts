import type { PageMeta } from "~/types/pagination";
import type { RequestCategory } from "~/types/request-category";
import type {
  RequestDetail,
  RequestDocument,
  RequestListItem,
  RequestPayload,
  RequestRecord,
} from "~/types/request";

/**
 * UC-02 — the single client of the citizen filing/submission API seam. The store
 * owns every `$fetch` against the seam and the resulting state; the filing page
 * calls these actions and renders the outcomes. The global `$fetch` plugin
 * supplies the base URL and the Sanctum bearer token, so only the path is passed.
 * Every success uses the one envelope: `data` carries the payload, `message` a
 * short summary [docs/conventions.md API success responses].
 *   - GET  /request-categories            → { data: RequestCategory[], message }  (active only)
 *   - POST /requests                       → { data: RequestRecord, message }       (201, Draft)
 *   - PATCH /requests/{id}                  → { data: RequestRecord, message }
 *   - POST /requests/{id}/documents         → { data: RequestDocument, message }    (201, multipart)
 *   - POST /requests/{id}/submit            → { data: RequestRecord, message }
 * UC-03 adds the two read seams the citizen tracks progress through:
 *   - GET  /requests?page=N                 → { data: RequestListItem[], meta, message }  (owner-scoped)
 *   - GET  /requests/{id}                    → { data: RequestDetail, message }            (owner; 404 out-of-scope)
 * Actions throw on 403/404/409/422 (error envelope `{ message, errors? }`) for
 * the calling page to render. Mutations patch local state in place — no refetch.
 */
export const useRequestsStore = defineStore("requests", () => {
  const activeCategories = ref<RequestCategory[]>([]);
  const draft = ref<RequestRecord | null>(null);
  const documents = ref<RequestDocument[]>([]);
  const list = ref<RequestListItem[]>([]);
  const listMeta = ref<PageMeta | null>(null);
  const current = ref<RequestDetail | null>(null);

  /** Load the active categories a citizen may file under (step 3; ext 3c). */
  async function fetchActiveCategories(): Promise<void> {
    const res = await $fetch<{ data: RequestCategory[] }>("/request-categories");
    activeCategories.value = res.data;
  }

  /**
   * UC-03 steps 1–2 — load one page of the requests the caller owns; page is
   * 1-based. A citizen owning nothing gets an empty list with `meta.total` 0
   * (ext 2a). The list is owner-scoped server-side, so it never carries another
   * person's request.
   */
  async function fetchList(page = 1): Promise<void> {
    const res = await $fetch<{ data: RequestListItem[]; meta: PageMeta }>("/requests", {
      query: { page },
    });
    list.value = res.data;
    listMeta.value = res.meta;
  }

  /**
   * UC-03 steps 3–6 — load one request's current status and understandable
   * history plus its messages, documents, and decision. A request the caller
   * does not own reads as not found (404), which this throws for the page to
   * render as "not found" rather than revealing existence (ext 3a).
   */
  async function fetchOne(id: number | string): Promise<RequestDetail> {
    const res = await $fetch<{ data: RequestDetail }>(`/requests/${id}`);
    current.value = res.data;
    return res.data;
  }

  /** Create the request as a Draft owned by the caller (steps 1–4). */
  async function createDraft(payload: RequestPayload): Promise<RequestRecord> {
    const res = await $fetch<{ data: RequestRecord }>("/requests", {
      method: "POST",
      body: payload,
    });

    draft.value = res.data;
    documents.value = [];

    return res.data;
  }

  /** Edit the Draft before submission; the request stays Draft (steps 3–6, ext 6a). */
  async function updateDraft(
    id: number,
    payload: RequestPayload,
  ): Promise<RequestRecord> {
    const res = await $fetch<{ data: RequestRecord }>(`/requests/${id}`, {
      method: "PATCH",
      body: payload,
    });

    draft.value = res.data;

    return res.data;
  }

  /**
   * Attach a supporting document to the Draft (step 5). On success the document
   * is appended to the local list; a rejected upload (422) or a store fault (500)
   * throws for the page to surface without losing the editable draft (ext 5b).
   */
  async function attachDocument(
    id: number,
    file: File,
    description?: string,
  ): Promise<RequestDocument> {
    const form = new FormData();
    form.append("file", file);
    if (description) {
      form.append("description", description);
    }

    const res = await $fetch<{ data: RequestDocument }>(`/requests/${id}/documents`, {
      method: "POST",
      body: form,
    });

    documents.value.push(res.data);

    return res.data;
  }

  /** Submit the Draft; on success it becomes Submitted (steps 7–8). */
  async function submit(id: number): Promise<RequestRecord> {
    const res = await $fetch<{ data: RequestRecord }>(`/requests/${id}/submit`, {
      method: "POST",
    });

    draft.value = res.data;

    return res.data;
  }

  /** Reset local state for a new filing session. */
  function reset(): void {
    draft.value = null;
    documents.value = [];
  }

  return {
    activeCategories,
    draft,
    documents,
    list,
    listMeta,
    current,
    fetchActiveCategories,
    fetchList,
    fetchOne,
    createDraft,
    updateDraft,
    attachDocument,
    submit,
    reset,
  };
});
