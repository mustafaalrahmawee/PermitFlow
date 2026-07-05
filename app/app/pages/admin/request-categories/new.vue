<script setup lang="ts">
import { z } from "zod";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

// UC-11 — create a request category (main flow steps 2–8). Posts through the
// request-categories store and renders the states the acceptance checklist
// implies: success (back to the list), validation error (422), and authorization
// denial (403).

const store = useRequestCategoriesStore();
const router = useRouter();

const form = reactive({
  name: "",
  description: "",
  is_active: true,
});

const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const denied = ref(false);
const submitting = ref(false);

const schema = z.object({
  name: z.string().min(1, "Name is required."),
  description: z.string(),
  is_active: z.boolean(),
});

function clearErrors(): void {
  formError.value = null;
  denied.value = false;
  for (const key of Object.keys(fieldErrors)) {
    fieldErrors[key] = undefined;
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

  submitting.value = true;
  try {
    await store.create({
      name: parsed.data.name,
      description: parsed.data.description.trim() === "" ? null : parsed.data.description,
      is_active: parsed.data.is_active,
    });
    await router.push("/admin/request-categories");
  } catch (error: unknown) {
    const err = error as {
      statusCode?: number;
      data?: { message?: string; errors?: Record<string, string[]> };
    };
    if (err.statusCode === 422 && err.data?.errors) {
      for (const [key, messages] of Object.entries(err.data.errors)) {
        fieldErrors[key] = messages[0];
      }
    } else if (err.statusCode === 403) {
      denied.value = true;
    } else {
      formError.value = err.data?.message ?? "Could not create the category. Please try again.";
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <main class="mx-auto max-w-lg px-6 py-10">
    <NuxtLink
      to="/admin/request-categories"
      class="mb-4 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
    >&larr; Back to categories</NuxtLink>
    <Card>
      <CardHeader>
        <CardTitle class="text-2xl">New request category</CardTitle>
      </CardHeader>

      <CardContent>
        <Alert v-if="denied" variant="destructive" class="mb-6">
          <AlertTitle>Not authorized</AlertTitle>
          <AlertDescription>You do not have permission to manage request categories.</AlertDescription>
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

          <div class="flex gap-3 pt-2">
            <Button type="submit" :disabled="submitting">
              {{ submitting ? "Creating…" : "Create category" }}
            </Button>
            <Button as-child type="button" variant="outline">
              <NuxtLink to="/admin/request-categories">Cancel</NuxtLink>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
