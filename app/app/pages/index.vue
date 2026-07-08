<script setup lang="ts">
import type { AuthUser } from "~/stores/auth";

// Post-sign-in dashboard. Confirms the session against the `GET /api/user` seam
// and surfaces the functions available for the account's single role (main flow
// step 5). Sign-out now lives in the app nav. Fails closed: no token, or a token
// the API rejects, returns to sign-in.

const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);

onMounted(async () => {
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

interface AppFunction {
  label: string;
  description: string;
  to?: string;
}

// Functions per role, mirroring the role gates/abilities in docs/conventions.md
// (Authorization). A `to` marks a function whose page exists today; the rest are
// listed but not yet linked. Presentational only — the API enforces access.
const functionsByRole: Record<AuthUser["role"], AppFunction[]> = {
  citizen: [
    {
      label: "Submit and track requests",
      description: "Start a new permit request and follow its status.",
      to: "/requests/new",
    },
    {
      label: "Provide information",
      description: "Respond when staff need more detail.",
    },
    {
      label: "Message staff",
      description: "Ask questions on an open request.",
    },
  ],
  staff_member: [
    {
      label: "Review assigned requests",
      description: "Work through the requests assigned to you.",
      to: "/requests",
    },
    {
      label: "Record decisions",
      description: "Approve or reject with a rationale.",
    },
    {
      label: "View reporting",
      description: "Track volumes, decisions, and turnaround.",
    },
  ],
  administrator: [
    {
      label: "Assign requests to staff",
      description: "Route incoming requests to reviewers.",
      to: "/admin/requests",
    },
    {
      label: "Manage user accounts and roles",
      description: "Create accounts and set roles and state.",
      to: "/admin/user-accounts",
    },
    {
      label: "Manage request categories",
      description: "Define the permit types citizens can request.",
      to: "/admin/request-categories",
    },
    {
      label: "Manage organization settings",
      description: "Update the organization name and preferences.",
      to: "/admin/organization-settings",
    },
    {
      label: "View reporting",
      description: "Track volumes, decisions, and turnaround.",
    },
  ],
};

const functions = computed(() =>
  auth.user ? functionsByRole[auth.user.role] : [],
);
</script>

<template>
  <main class="mx-auto max-w-5xl px-6 py-10">
    <p v-if="loading" class="text-sm text-muted-foreground">
      Loading your account…
    </p>

    <template v-else-if="auth.user">
      <header class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">
          Welcome, {{ auth.user.display_name }}
        </h1>
        <p class="mt-1 text-muted-foreground">
          Signed in as {{ roleLabels[auth.user.role] }} · {{ auth.user.email }}
        </p>
      </header>

      <h2
        class="mb-3 text-xs font-medium uppercase tracking-wide text-muted-foreground"
      >
        Available functions
      </h2>

      <div class="grid gap-4 sm:grid-cols-2">
        <component
          :is="fn.to ? 'NuxtLink' : 'div'"
          v-for="fn in functions"
          :key="fn.label"
          :to="fn.to"
          class="group flex flex-col rounded-xl border bg-card p-5 transition-colors"
          :class="
            fn.to
              ? 'hover:border-foreground/20 hover:bg-accent/50'
              : 'opacity-70'
          "
        >
          <NuxtLink v-if="fn.to" :to="fn.to">
            <div class="flex items-center justify-between gap-3">
              <h3 class="font-medium">{{ fn.label }}</h3>
              <span
                v-if="fn.to"
                class="text-muted-foreground transition-transform group-hover:translate-x-0.5"
                >&rarr;</span
              >
            </div>
            <p class="mt-1.5 text-sm text-muted-foreground">
              {{ fn.description }}
            </p>
          </NuxtLink>
          <div v-else>
            <div class="flex items-center justify-between gap-3">
              <h3 class="font-medium">{{ fn.label }}</h3>
              <span
                class="rounded-full border px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                >Soon</span
              >
            </div>
            <p class="mt-1.5 text-sm text-muted-foreground">
              {{ fn.description }}
            </p>
          </div>
        </component>
      </div>
    </template>
  </main>
</template>
