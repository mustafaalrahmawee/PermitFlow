<script setup lang="ts">
import { z } from "zod";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

// UC-00 sign-in. Posts to the `POST /api/login` seam via the auth store and
// renders the three states the acceptance checklist implies: success (redirect
// to the role-aware landing), authentication denial (ext 2a/3a → the API's
// generic 401 message, never revealing which part failed), and validation error.

definePageMeta({ layout: "guest", middleware: "guest" });

const auth = useAuthStore();
const router = useRouter();

const email = ref("");
const password = ref("");
const fieldErrors = reactive<{ email?: string; password?: string }>({});
const formError = ref<string | null>(null);
const submitting = ref(false);

// Local shape check before hitting the API (email present + well-formed, password present).
const schema = z.object({
  email: z
    .string()
    .min(1, "Email is required.")
    .refine(
      (value) => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(value),
      "Enter a valid email address.",
    ),
  password: z.string().min(1, "Password is required."),
});

function clearErrors(): void {
  formError.value = null;
  fieldErrors.email = undefined;
  fieldErrors.password = undefined;
}

async function onSubmit(): Promise<void> {
  clearErrors();

  const parsed = schema.safeParse({ email: email.value, password: password.value });
  if (!parsed.success) {
    for (const issue of parsed.error.issues) {
      const key = issue.path[0] as "email" | "password";
      if (!fieldErrors[key]) {
        fieldErrors[key] = issue.message;
      }
    }
    return;
  }

  submitting.value = true;
  try {
    await auth.login(parsed.data.email, parsed.data.password);
    await router.push("/");
  } catch (error: unknown) {
    const err = error as { statusCode?: number; data?: { message?: string; errors?: Record<string, string[]> } };
    if (err.statusCode === 422 && err.data?.errors) {
      // Server-side validation (e.g. malformed payload): surface field messages.
      fieldErrors.email = err.data.errors.email?.[0] ?? fieldErrors.email;
      fieldErrors.password = err.data.errors.password?.[0] ?? fieldErrors.password;
    } else {
      // 401 authentication denial — show the uniform message; do not disclose
      // whether the email, the password, or the account state was the problem.
      formError.value = err.data?.message ?? "The provided credentials are incorrect.";
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6">
    <Card>
      <CardHeader>
        <CardTitle class="text-2xl">Sign in to PermitFlow</CardTitle>
        <CardDescription>Access your account to manage permit requests.</CardDescription>
      </CardHeader>

      <CardContent>
        <Alert v-if="formError" variant="destructive" class="mb-6">
          <AlertDescription>{{ formError }}</AlertDescription>
        </Alert>

        <form class="space-y-4" novalidate @submit.prevent="onSubmit">
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="email"
              type="email"
              autocomplete="username"
              :aria-invalid="Boolean(fieldErrors.email)"
            />
            <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email }}</p>
          </div>

          <div class="space-y-2">
            <Label for="password">Password</Label>
            <Input
              id="password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              :aria-invalid="Boolean(fieldErrors.password)"
            />
            <p v-if="fieldErrors.password" class="text-xs text-destructive">{{ fieldErrors.password }}</p>
          </div>

          <Button type="submit" class="w-full" :disabled="submitting">
            {{ submitting ? "Signing in…" : "Sign in" }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </main>
</template>
