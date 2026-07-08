import type { PageMeta } from "~/types/pagination";
import type { ThreadMessage } from "~/types/message";

/**
 * UC-10 — the single client of the exchange-request-messages API seam. The store
 * owns every `$fetch` against the seam and the resulting thread state; the
 * request detail page calls these actions and renders the outcomes. The global
 * `$fetch` plugin supplies the base URL and the Sanctum bearer token, so only the
 * path is passed. Every success uses the one envelope: `data` carries the
 * payload, `message` a short summary [docs/conventions.md API success responses].
 *   - GET  /requests/{id}/messages?page=N → { data: ThreadMessage[], meta, message }  (request-scoped read)
 *   - POST /requests/{id}/messages         → { data: ThreadMessage, message }          (201; records a general message)
 * The thread is ordered chronologically server-side, so a sent message appends to
 * the end. Sending patches local state in place — no refetch. `sendMessage`
 * throws on 403 (not a participant), 404 (out of scope), 409 (no responsible
 * staff assigned yet, ext 4a), and 422 (empty body, ext 3a) for the page to
 * render.
 */
export const useMessagesStore = defineStore("messages", () => {
  const thread = ref<ThreadMessage[]>([]);
  const threadMeta = ref<PageMeta | null>(null);

  /** Load one page of a request's message thread (UC-10 step 2); page is 1-based. */
  async function fetchThread(
    requestId: number | string,
    page = 1,
  ): Promise<void> {
    const res = await $fetch<{ data: ThreadMessage[]; meta: PageMeta }>(
      `/requests/${requestId}/messages`,
      { query: { page } },
    );
    thread.value = res.data;
    threadMeta.value = res.meta;
  }

  /**
   * Send a general message on the request (UC-10 steps 3–7). On success the
   * created message is appended to the loaded thread so it is immediately visible
   * without a refetch.
   */
  async function sendMessage(
    requestId: number | string,
    body: string,
  ): Promise<ThreadMessage> {
    const res = await $fetch<{ data: ThreadMessage }>(
      `/requests/${requestId}/messages`,
      { method: "POST", body: { body } },
    );

    thread.value.push(res.data);

    return res.data;
  }

  /** Clear the thread when moving to a different request's detail. */
  function reset(): void {
    thread.value = [];
    threadMeta.value = null;
  }

  return { thread, threadMeta, fetchThread, sendMessage, reset };
});
