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
 * UC-03 adds the two read seams the citizen tracks progress through — the list
 * is role-scoped server-side, so a staff caller gets the requests they are
 * responsible for (UC-06 steps 1–2) and the detail read reaches an assigned
 * request the same way:
 *   - GET  /requests?page=N                 → { data: RequestListItem[], meta, message }  (role-scoped)
 *   - GET  /requests/{id}                    → { data: RequestDetail, message }            (in-scope; 404 otherwise)
 * UC-06 adds the responsible staff member's start-review action:
 *   - POST /requests/{id}/start-review       → { data: RequestRecord, message }            (submitted → in_review)
 * UC-07 adds the responsible staff member's request-missing-information action:
 *   - POST /requests/{id}/request-information → { data: RequestRecord, message }            (in_review → waiting_for_citizen)
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

  /**
   * UC-06 step 5 — the responsible staff member starts reviewing an assigned
   * request. On success the request moves Submitted → In Review. The transition
   * response carries only the bare `RequestRecord` (no relations), so patching
   * it into the detail would show the new status but miss the `status_changed`
   * history entry the transaction just wrote; the loaded detail is therefore
   * reloaded so status *and* history reflect the change without the user
   * refreshing (ext 5a). The matching worklist row is patched in place so the
   * list still reflects the transition without a refetch. A request not in the
   * staff member's scope throws 404 (ext 3a), a caller who is not the
   * responsible staff throws 403, and a request no longer Submitted throws 409
   * (ext 5a) for the page to render.
   */
  async function startReview(id: number | string): Promise<RequestRecord> {
    const res = await $fetch<{ data: RequestRecord }>(`/requests/${id}/start-review`, {
      method: "POST",
    });

    const row = list.value.find((item) => item.id === res.data.id);
    if (row) {
      row.status = res.data.status;
    }

    if (current.value && current.value.id === res.data.id) {
      await fetchOne(res.data.id);
    }

    return res.data;
  }

  /**
   * UC-07 — the responsible staff member requests missing information from the
   * citizen with a message explaining what to provide. On success the request
   * moves In Review → Waiting for Citizen and the missing-information message is
   * recorded on the request. The transition response carries only the bare
   * `RequestRecord` (no relations), so — as with start-review — the loaded detail
   * is reloaded so status, history, and the new message all reflect the change
   * without the user refreshing; the matching worklist row is patched in place.
   * A request not in the staff member's scope throws 404, a caller who is not the
   * responsible staff throws 403, a request no longer In Review throws 409
   * (ext 2a), and an empty message throws 422 (ext 3a) for the page to render.
   */
  async function requestInformation(
    id: number | string,
    body: string,
  ): Promise<RequestRecord> {
    const res = await $fetch<{ data: RequestRecord }>(
      `/requests/${id}/request-information`,
      {
        method: "POST",
        body: { body },
      },
    );

    const row = list.value.find((item) => item.id === res.data.id);
    if (row) {
      row.status = res.data.status;
    }

    if (current.value && current.value.id === res.data.id) {
      await fetchOne(res.data.id);
    }

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
    startReview,
    requestInformation,
    reset,
  };
});
