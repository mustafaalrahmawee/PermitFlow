<script setup lang="ts">
import { z } from "zod";

// UC-00 sign-in. Posts to the `POST /api/login` seam via the auth store and
// renders the three states the acceptance checklist implies: success (redirect
// to the role-aware landing), authentication denial (ext 2a/3a → the API's
// generic 401 message, never revealing which part failed), and validation error.

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
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 class="text-2xl font-semibold tracking-tight">Sign in to PermitFlow</h1>
      <p class="mt-1 text-sm text-slate-500">Access your account to manage permit requests.</p>

      <p
        v-if="formError"
        role="alert"
        class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >
        {{ formError }}
      </p>

      <form class="mt-6 space-y-4" novalidate @submit.prevent="onSubmit">
        <div>
          <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="username"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            :aria-invalid="Boolean(fieldErrors.email)"
          />
          <p v-if="fieldErrors.email" class="mt-1 text-xs text-red-600">{{ fieldErrors.email }}</p>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            autocomplete="current-password"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            :aria-invalid="Boolean(fieldErrors.password)"
          />
          <p v-if="fieldErrors.password" class="mt-1 text-xs text-red-600">{{ fieldErrors.password }}</p>
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ submitting ? "Signing in…" : "Sign in" }}
        </button>
      </form>
    </div>
  </main>
</template>
