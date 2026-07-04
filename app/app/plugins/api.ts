import { ofetch } from "ofetch";

/**
 * Global `$fetch`: default the API base URL and attach the Sanctum bearer token
 * from the auth store, so callers hit the seam with just a path — e.g.
 * `$fetch("/admin/user-accounts")` — instead of repeating baseURL/headers.
 *
 * Nuxt's own asset requests (`_nuxt`) and any call that already set its own
 * `baseURL` are left untouched, so the UC-00 auth store keeps working unchanged.
 */
export default defineNuxtPlugin(() => {
  const runtimeConfig = useRuntimeConfig();

  // ofetch.create returns ofetch's $Fetch; cast to Nuxt's augmented global type.
  globalThis.$fetch = ofetch.create({
    onRequest({ request, options }) {
      const { token } = storeToRefs(useAuthStore());

      if (typeof request === "string" && !request.includes("_nuxt") && !options.baseURL) {
        options.baseURL = runtimeConfig.public.apiBase;
      }

      // Merge onto any per-call headers rather than replacing them.
      const headers = new Headers(options.headers as HeadersInit | undefined);
      if (!headers.has("Accept")) {
        headers.set("Accept", "application/json");
      }
      if (token.value) {
        headers.set("Authorization", `Bearer ${token.value}`);
      }
      options.headers = headers;
    },
  }) as typeof globalThis.$fetch;
});
