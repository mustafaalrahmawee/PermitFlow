<script setup lang="ts">
import { z } from "zod";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { accountStateOptions, roleOptions } from "~/types/user-account";

// UC-01 — create a user account (main flow steps 2–8). Posts through the
// user-accounts store and renders the states the acceptance checklist implies:
// success (back to the list), validation error (422, incl. ext 4a role required),
// and authorization denial (403).

const store = useUserAccountsStore();
const router = useRouter();

const form = reactive({
  display_name: "",
  email: "",
  role: "citizen" as keyof typeof roleOptions,
  account_state: "active" as keyof typeof accountStateOptions,
  password: "",
});

const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const denied = ref(false);
const submitting = ref(false);

const schema = z.object({
  display_name: z.string().min(1, "Name is required."),
  email: z
    .string()
    .min(1, "Email is required.")
    .refine((v) => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v), "Enter a valid email address."),
  role: z.enum(["citizen", "staff_member", "administrator"]),
  account_state: z.enum(["active", "inactive"]),
  password: z.string().min(8, "Password must be at least 8 characters."),
});

const selectClass =
  "border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3";

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
    await store.create(parsed.data);
    await router.push("/admin/user-accounts");
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
      formError.value = err.data?.message ?? "Could not create the account. Please try again.";
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <main class="mx-auto max-w-lg px-6 py-10">
    <NuxtLink
      to="/admin/user-accounts"
      class="mb-4 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
    >&larr; Back to accounts</NuxtLink>
    <Card>
      <CardHeader>
        <CardTitle class="text-2xl">New user account</CardTitle>
      </CardHeader>

      <CardContent>
        <Alert v-if="denied" variant="destructive" class="mb-6">
          <AlertTitle>Not authorized</AlertTitle>
          <AlertDescription>You do not have permission to manage user accounts.</AlertDescription>
        </Alert>

        <Alert v-if="formError" variant="destructive" class="mb-6">
          <AlertDescription>{{ formError }}</AlertDescription>
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="onSubmit">
          <div class="space-y-2">
            <Label for="display_name">Name</Label>
            <Input id="display_name" v-model="form.display_name" :aria-invalid="Boolean(fieldErrors.display_name)" />
            <p v-if="fieldErrors.display_name" class="text-xs text-destructive">{{ fieldErrors.display_name }}</p>
          </div>

          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input id="email" v-model="form.email" type="email" :aria-invalid="Boolean(fieldErrors.email)" />
            <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email }}</p>
          </div>

          <div class="space-y-2">
            <Label for="role">Role</Label>
            <select id="role" v-model="form.role" :class="selectClass">
              <option v-for="(label, value) in roleOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="fieldErrors.role" class="text-xs text-destructive">{{ fieldErrors.role }}</p>
          </div>

          <div class="space-y-2">
            <Label for="account_state">Account state</Label>
            <select id="account_state" v-model="form.account_state" :class="selectClass">
              <option v-for="(label, value) in accountStateOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="fieldErrors.account_state" class="text-xs text-destructive">{{ fieldErrors.account_state }}</p>
          </div>

          <div class="space-y-2">
            <Label for="password">Password</Label>
            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" :aria-invalid="Boolean(fieldErrors.password)" />
            <p v-if="fieldErrors.password" class="text-xs text-destructive">{{ fieldErrors.password }}</p>
          </div>

          <div class="flex gap-3 pt-2">
            <Button type="submit" :disabled="submitting">
              {{ submitting ? "Creating…" : "Create account" }}
            </Button>
            <Button as-child type="button" variant="outline">
              <NuxtLink to="/admin/user-accounts">Cancel</NuxtLink>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
