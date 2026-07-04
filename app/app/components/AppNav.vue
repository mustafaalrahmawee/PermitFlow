<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Button } from "@/components/ui/button";
import type { AuthUser } from "~/stores/auth";

// Top app bar for authenticated areas: brand, role-aware navigation, and the
// signed-in account with sign-out. Only links to pages that exist are shown, so
// there are no dead ends; the list grows as later use cases add pages.

const auth = useAuthStore();
const { user } = storeToRefs(auth);
const route = useRoute();
const router = useRouter();

interface NavLink {
  label: string;
  to: string;
  roles?: AuthUser["role"][];
}

const allLinks: NavLink[] = [
  { label: "Home", to: "/" },
  { label: "User accounts", to: "/admin/user-accounts", roles: ["administrator"] },
];

const links = computed(() =>
  allLinks.filter((link) => !link.roles || (user.value && link.roles.includes(user.value.role))),
);

function isActive(to: string): boolean {
  return to === "/" ? route.path === "/" : route.path.startsWith(to);
}

const roleLabels: Record<AuthUser["role"], string> = {
  citizen: "Citizen",
  staff_member: "Staff member",
  administrator: "Administrator",
};

async function onSignOut(): Promise<void> {
  auth.logout();
  await router.replace("/login");
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b bg-background/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-6">
      <NuxtLink to="/" class="flex items-center gap-2.5">
        <span
          class="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground"
        >PF</span>
        <span class="text-base font-semibold tracking-tight">PermitFlow</span>
      </NuxtLink>

      <nav class="hidden items-center gap-1 sm:flex">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
          :class="
            isActive(link.to)
              ? 'bg-accent text-accent-foreground'
              : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground'
          "
        >
          {{ link.label }}
        </NuxtLink>
      </nav>

      <div class="ml-auto flex items-center gap-4">
        <div v-if="user" class="hidden text-right sm:block">
          <p class="text-sm font-medium leading-tight">{{ user.display_name }}</p>
          <p class="text-xs leading-tight text-muted-foreground">{{ roleLabels[user.role] }}</p>
        </div>
        <Button variant="outline" size="sm" @click="onSignOut">Sign out</Button>
      </div>
    </div>
  </header>
</template>
