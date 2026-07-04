<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { accountStateOptions, roleOptions } from "~/types/user-account";

// UC-01 — administrator list of user accounts (main flow steps 1–3). Reads the
// list from the user-accounts store and renders the account list, the
// authorization-denial state (403), and a load error. Authenticated page: no
// layout/middleware declared — the defaults cover it (docs/conventions.md
// Frontend routing).

const store = useUserAccountsStore();
const { accounts, meta } = storeToRefs(store);

const loading = ref(true);
const denied = ref(false);
const loadError = ref<string | null>(null);

// Load one page; clamped to the last page the server reports so the controls
// never request out of range.
async function load(page = 1): Promise<void> {
  loading.value = true;
  denied.value = false;
  loadError.value = null;
  try {
    await store.fetchAll(page);
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 403) {
      denied.value = true;
    } else {
      loadError.value = "Could not load user accounts. Please try again.";
    }
  } finally {
    loading.value = false;
  }
}

function goTo(page: number): void {
  if (!meta.value || page < 1 || page > meta.value.last_page || page === meta.value.current_page) {
    return;
  }
  load(page);
}

onMounted(() => load());
</script>

<template>
  <main class="mx-auto max-w-5xl px-6 py-10">
    <div class="mb-6 flex items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">User accounts</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Create accounts, set roles, and activate or deactivate access.
        </p>
      </div>
      <Button as-child>
        <NuxtLink to="/admin/user-accounts/new">New account</NuxtLink>
      </Button>
    </div>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading accounts…</p>

    <Alert v-else-if="denied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to manage user accounts.</AlertDescription>
    </Alert>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-base">{{ meta?.total ?? accounts.length }} account(s)</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-[15px]">
            <thead>
              <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                <th class="py-3 pr-4 font-medium">Name</th>
                <th class="py-3 pr-4 font-medium">Email</th>
                <th class="py-3 pr-4 font-medium">Role</th>
                <th class="py-3 pr-4 font-medium">State</th>
                <th class="py-3 font-medium"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="account in accounts"
                :key="account.id"
                class="border-b transition-colors last:border-0 hover:bg-muted/50"
              >
                <td class="py-3 pr-4 font-medium">{{ account.display_name }}</td>
                <td class="py-3 pr-4 text-muted-foreground">{{ account.email }}</td>
                <td class="py-3 pr-4">{{ roleOptions[account.role] }}</td>
                <td class="py-3 pr-4">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="
                      account.account_state === 'active'
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'bg-muted text-muted-foreground'
                    "
                  >
                    <span
                      class="size-1.5 rounded-full"
                      :class="account.account_state === 'active' ? 'bg-emerald-500' : 'bg-muted-foreground/50'"
                    />
                    {{ accountStateOptions[account.account_state] }}
                  </span>
                </td>
                <td class="py-3 text-right">
                  <NuxtLink
                    :to="`/admin/user-accounts/${account.id}`"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                  >
                    Manage
                  </NuxtLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="meta && meta.last_page > 1"
          class="mt-4 flex items-center justify-between gap-4 border-t pt-4"
        >
          <p class="text-sm text-muted-foreground">
            Page {{ meta.current_page }} of {{ meta.last_page }}
          </p>
          <div class="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="loading || meta.current_page <= 1"
              @click="goTo(meta.current_page - 1)"
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              :disabled="loading || meta.current_page >= meta.last_page"
              @click="goTo(meta.current_page + 1)"
            >
              Next
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </main>
</template>
