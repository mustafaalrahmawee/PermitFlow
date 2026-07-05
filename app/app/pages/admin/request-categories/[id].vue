<script setup lang="ts">
import { z } from "zod";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { UpdateRequestCategoryPayload } from "~/types/request-category";

// UC-11 — maintain an existing request category. Loads via the store (404 → not
// found, ext 2a), then renders the acceptance states on save/delete: success
// (back to the list), validation error (422), used-category conflict (409 —
// ext 6b rename blocked, ext 6a delete blocked), and denial (403). Deactivating
// a used category (ext 6c) is a normal successful save.

const store = useRequestCategoriesStore();
const route = useRoute();
const router = useRouter();
const id = route.params.id as string;

const form = reactive({
  name: "",
  description: "",
  is_active: true,
});

const loading = ref(true);
const notFound = ref(false);
const loadDenied = ref(false);
const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const conflict = ref<string | null>(null);
const denied = ref(false);
const submitting = ref(false);
const deleting = ref(false);

const schema = z.object({
  name: z.string().min(1, "Name is required."),
  description: z.string(),
  is_active: z.boolean(),
});

async function load(): Promise<void> {
  try {
    const category = await store.fetchOne(id);
    form.name = category.name;
    form.description = category.description ?? "";
    form.is_active = category.is_active;
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 404) {
      notFound.value = true;
    } else if (status === 403) {
      loadDenied.value = true;
    } else {
      formError.value = "Could not load the category. Please try again.";
    }
  } finally {
    loading.value = false;
  }
}

function clearErrors(): void {
  formError.value = null;
  conflict.value = null;
  denied.value = false;
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
      fieldErrors[key] = messages[0];
    }
  } else if (err.statusCode === 409) {
    conflict.value = err.data?.message ?? "This change is blocked because the category is used by existing requests.";
  } else if (err.statusCode === 403) {
    denied.value = true;
  } else if (err.statusCode === 404) {
    notFound.value = true;
  } else {
    formError.value = err.data?.message ?? fallback;
  }
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

  const payload: UpdateRequestCategoryPayload = {
    name: parsed.data.name,
    description: parsed.data.description.trim() === "" ? null : parsed.data.description,
    is_active: parsed.data.is_active,
  };

  submitting.value = true;
  try {
    await store.update(id, payload);
    await router.push("/admin/request-categories");
  } catch (error: unknown) {
    handleError(error, "Could not save the category. Please try again.");
  } finally {
    submitting.value = false;
  }
}

async function onDelete(): Promise<void> {
  clearErrors();
  deleting.value = true;
  try {
    await store.remove(id);
    await router.push("/admin/request-categories");
  } catch (error: unknown) {
    handleError(error, "Could not delete the category. Please try again.");
  } finally {
    deleting.value = false;
  }
}

onMounted(load);
</script>

<template>
  <main class="mx-auto max-w-lg px-6 py-10">
    <NuxtLink
      to="/admin/request-categories"
      class="mb-4 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
    >&larr; Back to categories</NuxtLink>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading category…</p>

    <Alert v-else-if="notFound" variant="destructive">
      <AlertTitle>Category not found</AlertTitle>
      <AlertDescription>
        This category no longer exists.
        <NuxtLink to="/admin/request-categories" class="underline underline-offset-4">Back to categories</NuxtLink>.
      </AlertDescription>
    </Alert>

    <Alert v-else-if="loadDenied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to manage request categories.</AlertDescription>
    </Alert>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-2xl">Manage category</CardTitle>
      </CardHeader>

      <CardContent>
        <Alert v-if="denied" variant="destructive" class="mb-6">
          <AlertTitle>Not authorized</AlertTitle>
          <AlertDescription>You do not have permission to manage request categories.</AlertDescription>
        </Alert>

        <Alert v-if="conflict" variant="destructive" class="mb-6">
          <AlertTitle>Change blocked</AlertTitle>
          <AlertDescription>
            {{ conflict }}
            You can still deactivate it to hide it from new request filing.
          </AlertDescription>
        </Alert>

        <Alert v-if="formError" variant="destructive" class="mb-6">
          <AlertDescription>{{ formError }}</AlertDescription>
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="onSubmit">
          <div class="space-y-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" :aria-invalid="Boolean(fieldErrors.name)" />
            <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
          </div>

          <div class="space-y-2">
            <Label for="description">Description <span class="text-muted-foreground">(optional)</span></Label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
            />
            <p v-if="fieldErrors.description" class="text-xs text-destructive">{{ fieldErrors.description }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input id="is_active" v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" />
            <Label for="is_active" class="font-normal">Active — available for new request filing</Label>
          </div>

          <div class="flex items-center justify-between gap-3 pt-2">
            <div class="flex gap-3">
              <Button type="submit" :disabled="submitting || deleting">
                {{ submitting ? "Saving…" : "Save changes" }}
              </Button>
              <Button as-child type="button" variant="outline">
                <NuxtLink to="/admin/request-categories">Cancel</NuxtLink>
              </Button>
            </div>
            <Button type="button" variant="destructive" :disabled="submitting || deleting" @click="onDelete">
              {{ deleting ? "Deleting…" : "Delete" }}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
