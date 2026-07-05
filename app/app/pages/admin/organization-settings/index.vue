<script setup lang="ts">
import { z } from "zod";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { localeOptions } from "~/types/organization-settings";
import type { UpdateOrganizationSettingsPayload } from "~/types/organization-settings";

// UC-12 — maintain the single organization-settings record. Loads the current
// settings via the store, then renders the acceptance states on save: success
// (a confirmation), validation error (422 — ext 5a, a block that conflicts with
// v1 hard constraints), and denial (403 — ext 4a, a non-administrator). Cancel
// (ext 3a) navigates back to Home without saving and sends no request, so the
// saved settings stay unchanged.

const store = useOrganizationSettingsStore();

const form = reactive({
  organization_name: "",
  locale: "",
  timezone: "",
});

const loading = ref(true);
const loadDenied = ref(false);
const loadError = ref<string | null>(null);
const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const denied = ref(false);
const saved = ref(false);
const submitting = ref(false);

const schema = z.object({
  organization_name: z.string().min(1, "Organization name is required."),
  locale: z.string().min(1, "Locale is required."),
  timezone: z.string().min(1, "Timezone is required."),
});

/** Populate the form from the loaded settings (also used by Cancel, ext 3a). */
function applyLoaded(): void {
  const settings = store.settings;
  if (!settings) {
    return;
  }
  form.organization_name = settings.organization_name;
  form.locale = settings.settings_payload.locale;
  form.timezone = settings.settings_payload.timezone;
}

async function load(): Promise<void> {
  try {
    await store.fetchSettings();
    applyLoaded();
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 403) {
      loadDenied.value = true;
    } else {
      loadError.value = "Could not load organization settings. Please try again.";
    }
  } finally {
    loading.value = false;
  }
}

function clearErrors(): void {
  formError.value = null;
  denied.value = false;
  saved.value = false;
  for (const key of Object.keys(fieldErrors)) {
    fieldErrors[key] = undefined;
  }
}

/** Map an error envelope onto the page's alert/field state. */
function handleError(error: unknown, fallback: string): void {
  const err = error as {
    statusCode?: number;
    data?: { message?: string; errors?: Record<string, string[]> };
  };
  if (err.statusCode === 422 && err.data?.errors) {
    for (const [key, messages] of Object.entries(err.data.errors)) {
      fieldErrors[normaliseKey(key)] = messages[0];
    }
  } else if (err.statusCode === 403) {
    denied.value = true;
  } else {
    formError.value = err.data?.message ?? fallback;
  }
}

/** Map a dotted server key (`settings_payload.locale`) onto a form field. */
function normaliseKey(key: string): string {
  if (key === "settings_payload.locale") return "locale";
  if (key === "settings_payload.timezone") return "timezone";
  return key;
}

async function onSubmit(): Promise<void> {
  clearErrors();

  const parsed = schema.safeParse({ ...form });
  if (!parsed.success) {
    for (const issue of parsed.error.issues) {
      const key = issue.path[0] as string;
      fieldErrors[key] ??= issue.message;
    }
    return;
  }

  const payload: UpdateOrganizationSettingsPayload = {
    organization_name: parsed.data.organization_name,
    settings_payload: {
      locale: parsed.data.locale,
      timezone: parsed.data.timezone.trim(),
    },
  };

  submitting.value = true;
  try {
    await store.update(payload);
    applyLoaded();
    saved.value = true;
  } catch (error: unknown) {
    handleError(error, "Could not save organization settings. Please try again.");
  } finally {
    submitting.value = false;
  }
}

onMounted(load);
</script>

<template>
  <main class="mx-auto max-w-lg px-6 py-10">
    <p v-if="loading" class="text-sm text-muted-foreground">Loading organization settings…</p>

    <Alert v-else-if="loadDenied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to manage organization settings.</AlertDescription>
    </Alert>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-2xl">Organization settings</CardTitle>
      </CardHeader>

      <CardContent>
        <Alert v-if="denied" variant="destructive" class="mb-6">
          <AlertTitle>Not authorized</AlertTitle>
          <AlertDescription>You do not have permission to manage organization settings.</AlertDescription>
        </Alert>

        <Alert v-if="saved" class="mb-6">
          <AlertTitle>Settings saved</AlertTitle>
          <AlertDescription>The organization settings are now in effect.</AlertDescription>
        </Alert>

        <Alert v-if="formError" variant="destructive" class="mb-6">
          <AlertDescription>{{ formError }}</AlertDescription>
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="onSubmit">
          <div class="space-y-2">
            <Label for="organization_name">Organization name</Label>
            <Input
              id="organization_name"
              v-model="form.organization_name"
              :aria-invalid="Boolean(fieldErrors.organization_name)"
            />
            <p v-if="fieldErrors.organization_name" class="text-xs text-destructive">
              {{ fieldErrors.organization_name }}
            </p>
          </div>

          <div class="space-y-2">
            <Label for="locale">Locale</Label>
            <select
              id="locale"
              v-model="form.locale"
              :aria-invalid="Boolean(fieldErrors.locale)"
              class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
            >
              <option v-for="(label, value) in localeOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="fieldErrors.locale" class="text-xs text-destructive">{{ fieldErrors.locale }}</p>
          </div>

          <div class="space-y-2">
            <Label for="timezone">Timezone</Label>
            <Input id="timezone" v-model="form.timezone" :aria-invalid="Boolean(fieldErrors.timezone)" />
            <p v-if="fieldErrors.timezone" class="text-xs text-destructive">{{ fieldErrors.timezone }}</p>
            <p class="text-xs text-muted-foreground">An IANA identifier, e.g. <code>UTC</code> or <code>Europe/Berlin</code>.</p>
          </div>

          <div class="flex gap-3 pt-2">
            <Button type="submit" :disabled="submitting">
              {{ submitting ? "Saving…" : "Save changes" }}
            </Button>
            <Button as-child type="button" variant="outline">
              <NuxtLink to="/">Cancel</NuxtLink>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
