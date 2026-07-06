import type { RequestCategory } from "~/types/request-category";
import type {
  RequestDocument,
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
 * Actions throw on 403/404/409/422 (error envelope `{ message, errors? }`) for
 * the calling page to render. Mutations patch local state in place — no refetch.
 */
export const useRequestsStore = defineStore("requests", () => {
  const activeCategories = ref<RequestCategory[]>([]);
  const draft = ref<RequestRecord | null>(null);
  const documents = ref<RequestDocument[]>([]);

  /** Load the active categories a citizen may file under (step 3; ext 3c). */
  async function fetchActiveCategories(): Promise<void> {
    const res = await $fetch<{ data: RequestCategory[] }>("/request-categories");
    activeCategories.value = res.data;
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
    fetchActiveCategories,
    createDraft,
    updateDraft,
    attachDocument,
    submit,
    reset,
  };
});
