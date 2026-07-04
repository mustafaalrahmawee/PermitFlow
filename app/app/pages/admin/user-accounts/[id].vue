<script setup lang="ts">
import { z } from "zod";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  accountStateOptions,
  roleOptions,
  type UpdateUserAccountPayload,
} from "~/types/user-account";

// UC-01 — maintain an existing user account. Loads via the store (404 → not
// found, ext 2a), then renders the acceptance states on save: success (back to
// the list), validation error (422, incl. ext 4a), lifecycle conflict
// (409, ext 5a/5b/5c: deactivation or role change blocked), and denial (403).

const store = useUserAccountsStore();
const route = useRoute();
const router = useRouter();
const id = route.params.id as string;

const form = reactive({
  display_name: "",
  email: "",
  role: "citizen" as keyof typeof roleOptions,
  account_state: "active" as keyof typeof accountStateOptions,
  password: "",
});

const loading = ref(true);
const notFound = ref(false);
const loadDenied = ref(false);
const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const conflict = ref<string | null>(null);
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
  // Optional on edit: only sent when the admin sets a new one.
  password: z.string().min(8, "Password must be at least 8 characters.").optional().or(z.literal("")),
});

const selectClass =
  "border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3";

async function load(): Promise<void> {
  try {
    const account = await store.fetchOne(id);
    form.display_name = account.display_name;
    form.email = account.email;
    form.role = account.role;
    form.account_state = account.account_state;
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 404) {
      notFound.value = true;
    } else if (status === 403) {
      loadDenied.value = true;
    } else {
      formError.value = "Could not load the account. Please try again.";
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

  const payload: UpdateUserAccountPayload = {
    display_name: parsed.data.display_name,
    email: parsed.data.email,
    role: parsed.data.role,
    account_state: parsed.data.account_state,
  };
  if (parsed.data.password) {
    payload.password = parsed.data.password;
  }

  submitting.value = true;
  try {
    await store.update(id, payload);
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
    } else if (err.statusCode === 409) {
      conflict.value = err.data?.message ?? "This change is blocked by the account's request history.";
    } else if (err.statusCode === 403) {
      denied.value = true;
    } else {
      formError.value = err.data?.message ?? "Could not save the account. Please try again.";
    }
  } finally {
    submitting.value = false;
  }
}

onMounted(load);
</script>

<template>
  <main class="mx-auto max-w-lg px-6 py-10">
    <NuxtLink
      to="/admin/user-accounts"
      class="mb-4 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
    >&larr; Back to accounts</NuxtLink>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading account…</p>

    <Alert v-else-if="notFound" variant="destructive">
      <AlertTitle>Account not found</AlertTitle>
      <AlertDescription>
        This account no longer exists.
        <NuxtLink to="/admin/user-accounts" class="underline underline-offset-4">Back to accounts</NuxtLink>.
      </AlertDescription>
    </Alert>

    <Alert v-else-if="loadDenied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to manage user accounts.</AlertDescription>
    </Alert>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-2xl">Manage account</CardTitle>
      </CardHeader>

      <CardContent>
        <Alert v-if="denied" variant="destructive" class="mb-6">
          <AlertTitle>Not authorized</AlertTitle>
          <AlertDescription>You do not have permission to manage user accounts.</AlertDescription>
        </Alert>

        <Alert v-if="conflict" variant="destructive" class="mb-6">
          <AlertTitle>Change blocked</AlertTitle>
          <AlertDescription>{{ conflict }}</AlertDescription>
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
            <Label for="password">New password <span class="text-muted-foreground">(optional)</span></Label>
            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" :aria-invalid="Boolean(fieldErrors.password)" />
            <p v-if="fieldErrors.password" class="text-xs text-destructive">{{ fieldErrors.password }}</p>
          </div>

          <div class="flex gap-3 pt-2">
            <Button type="submit" :disabled="submitting">
              {{ submitting ? "Saving…" : "Save changes" }}
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
