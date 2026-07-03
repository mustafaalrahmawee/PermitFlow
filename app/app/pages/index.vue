<script setup lang="ts">
import type { AuthUser } from "~/stores/auth";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

// Post-sign-in landing. Confirms the session against the `GET /api/user` seam and
// shows the functions available for the account's single role (main flow step 5).
// Fails closed on the client: no token, or a token the API rejects, returns to sign-in.

const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);

onMounted(async () => {
  if (!auth.token) {
    await router.replace("/login");
    return;
  }

  try {
    if (!auth.user) {
      await auth.fetchUser();
    }
  } catch {
    auth.logout();
    await router.replace("/login");
    return;
  } finally {
    loading.value = false;
  }
});

const roleLabels: Record<AuthUser["role"], string> = {
  citizen: "Citizen",
  staff_member: "Staff member",
  administrator: "Administrator",
};

// Functions surfaced per role, mirroring the role gates/abilities in
// docs/conventions.md (Authorization). Presentational only — the API enforces access.
const functionsByRole: Record<AuthUser["role"], string[]> = {
  citizen: [
    "Submit and track your permit requests",
    "Provide requested information",
    "Message staff about your requests",
  ],
  staff_member: [
    "Review assigned requests",
    "Record decisions",
    "View reporting",
  ],
  administrator: [
    "Assign requests to staff",
    "Manage user accounts and roles",
    "Manage request categories",
    "Manage organization settings",
    "View reporting",
  ],
};

const availableFunctions = computed(() =>
  auth.user ? functionsByRole[auth.user.role] : [],
);

async function onSignOut(): Promise<void> {
  auth.logout();
  await router.replace("/login");
}
</script>

<template>
  <main class="mx-auto max-w-2xl px-6 py-12">
    <p v-if="loading" class="text-sm text-muted-foreground">Loading your account…</p>

    <Card v-else-if="auth.user">
      <CardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
        <div>
          <CardTitle class="text-2xl">Welcome, {{ auth.user.display_name }}</CardTitle>
          <p class="mt-1 text-sm text-muted-foreground">
            Signed in as {{ roleLabels[auth.user.role] }} · {{ auth.user.email }}
          </p>
        </div>
        <Button variant="outline" size="sm" @click="onSignOut">Sign out</Button>
      </CardHeader>

      <CardContent>
        <h2 class="text-sm font-medium uppercase tracking-wide text-muted-foreground">Available functions</h2>
        <ul class="mt-3 space-y-2">
          <li
            v-for="fn in availableFunctions"
            :key="fn"
            class="rounded-lg border px-4 py-3 text-sm"
          >
            {{ fn }}
          </li>
        </ul>
      </CardContent>
    </Card>
  </main>
</template>
