<script setup lang="ts">
import type { Component } from "vue";
import { storeToRefs } from "pinia";
import {
  LucideBuilding2,
  LucideClipboardList,
  LucideFilePlus,
  LucideFileText,
  LucideChartColumn,
  LucideHouse,
  LucideLogOut,
  LucideMenu,
  LucideTags,
  LucideUsers,
} from "#components";
import { Button } from "@/components/ui/button";
import type { AuthUser } from "~/stores/auth";

// Left sidebar for authenticated areas: brand, role-aware navigation, and the
// signed-in account with sign-out pinned to the bottom. Only links to pages that
// exist are shown, so there are no dead ends; the list grows as later use cases
// add pages. On small screens the sidebar collapses behind a top bar toggle.

const auth = useAuthStore();
const { user } = storeToRefs(auth);
const route = useRoute();
const router = useRouter();

interface NavLink {
  label: string;
  to: string;
  icon: Component;
  roles?: AuthUser["role"][];
}

const allLinks: NavLink[] = [
  { label: "Home", to: "/", icon: LucideHouse },
  { label: "My requests", to: "/requests", icon: LucideFileText, roles: ["citizen"] },
  { label: "New request", to: "/requests/new", icon: LucideFilePlus, roles: ["citizen"] },
  { label: "Assigned requests", to: "/requests", icon: LucideClipboardList, roles: ["staff_member"] },
  { label: "Reporting", to: "/reporting", icon: LucideChartColumn, roles: ["staff_member"] },
  { label: "Reporting", to: "/reporting/admin", icon: LucideChartColumn, roles: ["administrator"] },
  { label: "Assign requests", to: "/admin/requests", icon: LucideClipboardList, roles: ["administrator"] },
  { label: "User accounts", to: "/admin/user-accounts", icon: LucideUsers, roles: ["administrator"] },
  { label: "Request categories", to: "/admin/request-categories", icon: LucideTags, roles: ["administrator"] },
  { label: "Organization settings", to: "/admin/organization-settings", icon: LucideBuilding2, roles: ["administrator"] },
];

const links = computed(() =>
  allLinks.filter((link) => !link.roles || (user.value && link.roles.includes(user.value.role))),
);

// Highlight exactly one link: the most specific one whose path the current route
// falls under. Without the longest-match tie-break, `/requests/new` would light up
// both "New request" and "My requests" (`/requests`), since the latter is a prefix.
function matches(to: string): boolean {
  return to === "/"
    ? route.path === "/"
    : route.path === to || route.path.startsWith(`${to}/`);
}

const activeTo = computed(() =>
  links.value.reduce(
    (best, link) => (matches(link.to) && link.to.length > best.length ? link.to : best),
    "",
  ),
);

function isActive(to: string): boolean {
  return to === activeTo.value;
}

const roleLabels: Record<AuthUser["role"], string> = {
  citizen: "Citizen",
  staff_member: "Staff member",
  administrator: "Administrator",
};

// Mobile: the sidebar is an off-canvas drawer. It closes on navigation so a tap
// on a link doesn't leave the overlay covering the page it opened.
const open = ref(false);
watch(
  () => route.path,
  () => {
    open.value = false;
  },
);

async function onSignOut(): Promise<void> {
  auth.logout();
  await router.replace("/login");
}
</script>

<template>
  <!-- Mobile top bar: brand + toggle. Hidden once the sidebar is always visible. -->
  <div
    class="sticky top-0 z-40 flex h-14 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur lg:hidden"
  >
    <Button variant="outline" size="icon" aria-label="Toggle navigation" @click="open = !open">
      <LucideMenu class="size-5" />
    </Button>
    <NuxtLink to="/" class="flex items-center gap-2.5">
      <span
        class="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground"
      >PF</span>
      <span class="text-base font-semibold tracking-tight">PermitFlow</span>
    </NuxtLink>
  </div>

  <!-- Backdrop for the mobile drawer. -->
  <div
    v-if="open"
    class="fixed inset-0 z-40 bg-foreground/30 lg:hidden"
    @click="open = false"
  />

  <aside
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-background transition-transform duration-200 lg:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-16 items-center border-b px-6">
      <NuxtLink to="/" class="flex items-center gap-2.5">
        <span
          class="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground"
        >PF</span>
        <span class="text-base font-semibold tracking-tight">PermitFlow</span>
      </NuxtLink>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
      <NuxtLink
        v-for="link in links"
        :key="link.to"
        :to="link.to"
        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
        :class="
          isActive(link.to)
            ? 'bg-accent text-accent-foreground'
            : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground'
        "
      >
        <component :is="link.icon" class="size-4 shrink-0" />
        {{ link.label }}
      </NuxtLink>
    </nav>

    <div class="border-t p-3">
      <div v-if="user" class="px-2 pb-3">
        <p class="text-sm font-medium leading-tight">{{ user.display_name }}</p>
        <p class="text-xs leading-tight text-muted-foreground">{{ roleLabels[user.role] }}</p>
      </div>
      <Button variant="outline" size="sm" class="w-full" @click="onSignOut">
        <LucideLogOut class="size-4" />
        Sign out
      </Button>
    </div>
  </aside>
</template>
