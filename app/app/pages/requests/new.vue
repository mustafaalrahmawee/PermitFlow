<script setup lang="ts">
import { storeToRefs } from "pinia";
import { z } from "zod";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

// UC-02 — a citizen files and submits a permit request. Drives the seam through
// the requests store: select an active category, enter the details, attach
// supporting documents, review, and submit. Renders the states the acceptance
// checklist implies — success (submitted), authorization denial (403), and
// validation error (422) — plus the two frontend-only states: no active category
// available so submission is blocked (ext 3c), and a document that could not be
// attached while the draft stays editable (ext 5b). Authenticated page: no
// layout/middleware declared — the defaults cover it (docs/conventions.md
// Frontend routing).

const store = useRequestsStore();
const { activeCategories, draft, documents } = storeToRefs(store);
const router = useRouter();

const loadingCategories = ref(true);
const categoriesError = ref<string | null>(null);

const form = reactive({
  request_category_id: "" as number | "",
  title: "",
  details: "",
});

const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const denied = ref(false);
const saving = ref(false);
const submitting = ref(false);
const submitted = ref(false);

// ext 5b — a document that could not be stored; the draft stays editable and we
// tell the citizen it was not attached.
const documentError = ref<string | null>(null);
const uploading = ref(false);
const description = ref("");
const fileInput = ref<HTMLInputElement | null>(null);

const schema = z.object({
  request_category_id: z.number({ message: "Select a category." }).int(),
  title: z.string().min(1, "Title is required."),
  details: z.string().min(1, "Request details are required."),
});

// ext 3c — with no active category available the citizen cannot select one and
// cannot submit until an administrator makes one available.
const noCategoriesAvailable = computed(
  () => !loadingCategories.value && activeCategories.value.length === 0,
);

onMounted(async () => {
  store.reset();
  try {
    await store.fetchActiveCategories();
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 403) {
      denied.value = true;
    } else {
      categoriesError.value = "Could not load categories. Please try again.";
    }
  } finally {
    loadingCategories.value = false;
  }
});

function clearErrors(): void {
  formError.value = null;
  denied.value = false;
  for (const key of Object.keys(fieldErrors)) {
    fieldErrors[key] = undefined;
  }
}

/** The seam's whole-block `request_details` payload from the details field. */
function detailsPayload(): Record<string, unknown> {
  return { description: form.details.trim() };
}

function validate(): { request_category_id: number; title: string } | null {
  const parsed = schema.safeParse({
    request_category_id:
      form.request_category_id === "" ? undefined : Number(form.request_category_id),
    title: form.title,
    details: form.details,
  });
  if (!parsed.success) {
    for (const issue of parsed.error.issues) {
      const key = issue.path[0] as string;
      fieldErrors[key] ??= issue.message;
    }
    return null;
  }
  return { request_category_id: parsed.data.request_category_id, title: parsed.data.title };
}

function applyError(error: unknown): void {
  const err = error as {
    statusCode?: number;
    data?: { message?: string; errors?: Record<string, string[]> };
  };
  if (err.statusCode === 422 && err.data?.errors) {
    for (const [key, messages] of Object.entries(err.data.errors)) {
      // Map the whole-block `request_details` error back onto the details field.
      const field = key.startsWith("request_details") ? "details" : key;
      fieldErrors[field] = messages[0];
    }
  } else if (err.statusCode === 403) {
    denied.value = true;
  } else if (err.statusCode === 404) {
    // The draft no longer exists (e.g. removed server-side). Drop the stale
    // reference so the still-filled form saves as a fresh draft rather than
    // wedging on a missing record.
    store.reset();
    formError.value = "This draft is no longer available. Save again to create a new request.";
  } else {
    formError.value = err.data?.message ?? "Something went wrong. Please try again.";
  }
}

/** Create the draft, or save edits to an existing one (ext 6a keeps it Draft). */
async function saveDraft(): Promise<void> {
  clearErrors();
  const valid = validate();
  if (!valid) {
    return;
  }

  saving.value = true;
  try {
    const payload = { ...valid, request_details: detailsPayload() };
    if (draft.value) {
      await store.updateDraft(draft.value.id, payload);
    } else {
      await store.createDraft(payload);
    }
  } catch (error: unknown) {
    applyError(error);
  } finally {
    saving.value = false;
  }
}

async function onSelectFile(event: Event): Promise<void> {
  documentError.value = null;
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file || !draft.value) {
    return;
  }

  uploading.value = true;
  try {
    await store.attachDocument(draft.value.id, file, description.value.trim() || undefined);
    description.value = "";
    if (fileInput.value) {
      fileInput.value.value = "";
    }
  } catch (error: unknown) {
    const err = error as { statusCode?: number; data?: { message?: string; errors?: Record<string, string[]> } };
    // ext 5b — surface that the document was not attached; the draft is untouched.
    documentError.value =
      err.data?.errors?.file?.[0] ??
      err.data?.message ??
      "The document could not be attached. Your draft is unchanged — you can try again.";
  } finally {
    uploading.value = false;
  }
}

async function onSubmit(): Promise<void> {
  clearErrors();
  if (!draft.value) {
    return;
  }

  submitting.value = true;
  try {
    await store.submit(draft.value.id);
    submitted.value = true;
  } catch (error: unknown) {
    applyError(error);
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <main class="mx-auto max-w-2xl px-6 py-10">
    <NuxtLink
      to="/"
      class="mb-4 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
    >&larr; Back to home</NuxtLink>

    <!-- Success state (main flow step 8): the request is submitted. -->
    <Card v-if="submitted">
      <CardHeader>
        <CardTitle class="text-2xl">Request submitted</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <Alert>
          <AlertTitle>Your request was submitted</AlertTitle>
          <AlertDescription>
            "{{ draft?.title }}" is now with the institution for handling. You will be notified as it progresses.
          </AlertDescription>
        </Alert>
        <Button as-child variant="outline">
          <NuxtLink to="/">Back to home</NuxtLink>
        </Button>
      </CardContent>
    </Card>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-2xl">New request</CardTitle>
      </CardHeader>

      <CardContent class="space-y-6">
        <Alert v-if="denied" variant="destructive">
          <AlertTitle>Not authorized</AlertTitle>
          <AlertDescription>You do not have permission to file this request.</AlertDescription>
        </Alert>

        <Alert v-if="formError" variant="destructive">
          <AlertDescription>{{ formError }}</AlertDescription>
        </Alert>

        <Alert v-if="categoriesError" variant="destructive">
          <AlertDescription>{{ categoriesError }}</AlertDescription>
        </Alert>

        <!-- ext 3c — no active category available: selection and submission are blocked. -->
        <Alert v-if="noCategoriesAvailable">
          <AlertTitle>No categories available</AlertTitle>
          <AlertDescription>
            There are no request categories to file under yet. An administrator must make one available before you can submit a request.
          </AlertDescription>
        </Alert>

        <p v-if="loadingCategories" class="text-sm text-muted-foreground">Loading categories…</p>

        <form v-else class="space-y-5" novalidate @submit.prevent="onSubmit">
          <div class="space-y-2">
            <Label for="category">Category</Label>
            <select
              id="category"
              v-model="form.request_category_id"
              :disabled="noCategoriesAvailable"
              class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
              :aria-invalid="Boolean(fieldErrors.request_category_id)"
            >
              <option value="" disabled>Select a category</option>
              <option v-for="category in activeCategories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
            <p v-if="fieldErrors.request_category_id" class="text-xs text-destructive">
              {{ fieldErrors.request_category_id }}
            </p>
          </div>

          <div class="space-y-2">
            <Label for="title">Title</Label>
            <Input id="title" v-model="form.title" :aria-invalid="Boolean(fieldErrors.title)" />
            <p v-if="fieldErrors.title" class="text-xs text-destructive">{{ fieldErrors.title }}</p>
          </div>

          <div class="space-y-2">
            <Label for="details">Request details</Label>
            <textarea
              id="details"
              v-model="form.details"
              rows="5"
              class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
              :aria-invalid="Boolean(fieldErrors.details)"
            />
            <p v-if="fieldErrors.details" class="text-xs text-destructive">{{ fieldErrors.details }}</p>
          </div>

          <div class="flex gap-3">
            <Button type="button" variant="outline" :disabled="saving || noCategoriesAvailable" @click="saveDraft">
              {{ saving ? "Saving…" : draft ? "Save changes" : "Save draft" }}
            </Button>
          </div>

          <!-- Supporting documents (step 5) — available once the draft exists. -->
          <section v-if="draft" class="space-y-3 border-t pt-5">
            <div>
              <h2 class="text-sm font-medium">Supporting documents <span class="text-muted-foreground">(optional)</span></h2>
              <p class="text-xs text-muted-foreground">Attach files that support this request.</p>
            </div>

            <Alert v-if="documentError" variant="destructive">
              <AlertTitle>Document not attached</AlertTitle>
              <AlertDescription>{{ documentError }}</AlertDescription>
            </Alert>

            <ul v-if="documents.length" class="space-y-1 text-sm">
              <li v-for="document in documents" :key="document.id" class="flex items-center gap-2">
                <span class="text-muted-foreground">📎</span>{{ document.original_filename }}
              </li>
            </ul>

            <div class="space-y-2">
              <Input
                v-model="description"
                placeholder="Description (optional)"
                :disabled="uploading"
              />
              <input
                ref="fileInput"
                type="file"
                class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border file:border-input file:bg-transparent file:px-3 file:py-1.5 file:text-sm"
                :disabled="uploading"
                @change="onSelectFile"
              />
              <p v-if="uploading" class="text-xs text-muted-foreground">Uploading…</p>
            </div>
          </section>

          <!-- Review & submit (steps 6–7). Submission needs a saved draft. -->
          <div class="flex items-center gap-3 border-t pt-5">
            <Button type="submit" :disabled="submitting || !draft || noCategoriesAvailable">
              {{ submitting ? "Submitting…" : "Submit request" }}
            </Button>
            <p v-if="!draft" class="text-xs text-muted-foreground">Save the draft before submitting.</p>
          </div>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
