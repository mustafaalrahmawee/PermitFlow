/**
 * UC-12 organization-settings types, mirroring the admin API seam's singleton
 * shape (`organization_name`, `settings_payload`). `settings_payload` is
 * whole-block JSON, read and written as one block. No fetch logic here — the
 * organization-settings Pinia store owns the seam calls.
 */

/** The whole-block settings payload. v1 constrains `locale` and `timezone`. */
export interface OrganizationSettingsPayload {
  locale: string;
  timezone: string;
}

export interface OrganizationSettings {
  organization_name: string;
  settings_payload: OrganizationSettingsPayload;
}

/** Fields the update seam accepts — the whole record, written as one block. */
export interface UpdateOrganizationSettingsPayload {
  organization_name: string;
  settings_payload: OrganizationSettingsPayload;
}

/** Supported UI locales — mirrors the backend's v1 hard-constraint set. */
export const localeOptions: Record<string, string> = {
  en: "English",
  es: "Español",
  fr: "Français",
  de: "Deutsch",
  ar: "العربية",
};
