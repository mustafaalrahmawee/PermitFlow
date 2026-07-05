import type {
  OrganizationSettings,
  UpdateOrganizationSettingsPayload,
} from "~/types/organization-settings";

/**
 * UC-12 — the single client of the administrator organization-settings API
 * seam. The record is a singleton, so the seam is a GET/PUT pair with no id,
 * create, or delete. The store owns both `$fetch` calls and the resulting
 * state; the page calls these actions and renders the outcomes. The global
 * `$fetch` plugin supplies the base URL and the Sanctum bearer token, so only
 * the path is passed here. Every success uses the one envelope: `data` carries
 * the payload, `message` a short summary [docs/conventions.md API success
 * responses].
 *   - GET /admin/organization-settings → { data: OrganizationSettings, message }
 *   - PUT /admin/organization-settings → { data: OrganizationSettings, message }
 * Actions throw on 403/422 (error envelope `{ message, errors? }`) for the page
 * to render. A successful update replaces the local `settings` in place — no
 * refetch.
 */
export const useOrganizationSettingsStore = defineStore("organizationSettings", () => {
  const settings = ref<OrganizationSettings | null>(null);

  /** Load the current organization settings (main flow steps 1–2). */
  async function fetchSettings(): Promise<OrganizationSettings> {
    const res = await $fetch<{ data: OrganizationSettings }>("/admin/organization-settings");
    settings.value = res.data;
    return res.data;
  }

  /**
   * Save the changed settings as one whole block (steps 3–6). A block that
   * conflicts with v1 hard constraints surfaces as 422 for the page to render
   * (ext 5a); denial surfaces as 403 (ext 4a). On success, replace the local
   * settings in place rather than refetching.
   */
  async function update(
    payload: UpdateOrganizationSettingsPayload,
  ): Promise<OrganizationSettings> {
    const res = await $fetch<{ data: OrganizationSettings }>("/admin/organization-settings", {
      method: "PUT",
      body: payload,
    });

    settings.value = res.data;

    return res.data;
  }

  return { settings, fetchSettings, update };
});
