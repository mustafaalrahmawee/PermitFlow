<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { requestStatusLabels } from "~/types/request";

// UC-03 — the citizen's list of their own requests (main flow steps 1–2). Reads
// the owner-scoped list from the requests store and renders the list, the empty
// state (ext 2a — no requests yet), and a load error. Authenticated page: no
// layout/middleware declared — the defaults cover it (docs/conventions.md
// Frontend routing).

const store = useRequestsStore();
const { list, listMeta } = storeToRefs(store);

const loading = ref(true);
const loadError = ref<string | null>(null);

async function load(page = 1): Promise<void> {
  loading.value = true;
  loadError.value = null;
  try {
    await store.fetchList(page);
  } catch {
    loadError.value = "Could not load your requests. Please try again.";
  } finally {
    loading.value = false;
  }
}

function goTo(page: number): void {
  if (
    !listMeta.value ||
    page < 1 ||
    page > listMeta.value.last_page ||
    page === listMeta.value.current_page
  ) {
    return;
  }
  load(page);
}

function statusClass(status: string): string {
  return status === "decided"
    ? "bg-muted text-muted-foreground"
    : "bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300";
}

onMounted(() => load());
</script>

<template>
  <main class="mx-auto max-w-4xl px-6 py-10">
    <div class="mb-6 flex items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">My requests</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Track the status and history of the requests you have submitted.
        </p>
      </div>
      <Button as-child>
        <NuxtLink to="/requests/new">New request</NuxtLink>
      </Button>
    </div>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading your requests…</p>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <!-- ext 2a — a citizen with no requests sees an empty list, no detail opened. -->
    <Card v-else-if="list.length === 0">
      <CardContent class="py-12 text-center">
        <p class="text-sm font-medium">You have no requests yet</p>
        <p class="mt-1 text-sm text-muted-foreground">
          When you submit a permit request it will appear here.
        </p>
        <Button as-child class="mt-4">
          <NuxtLink to="/requests/new">Start a request</NuxtLink>
        </Button>
      </CardContent>
    </Card>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-base">
          {{ listMeta?.total ?? list.length }} request(s)
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-[15px]">
            <thead>
              <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                <th class="py-3 pr-4 font-medium">Title</th>
                <th class="py-3 pr-4 font-medium">Category</th>
                <th class="py-3 pr-4 font-medium">Status</th>
                <th class="py-3 font-medium"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="request in list"
                :key="request.id"
                class="border-b transition-colors last:border-0 hover:bg-muted/50"
              >
                <td class="py-3 pr-4 font-medium">{{ request.title }}</td>
                <td class="py-3 pr-4 text-muted-foreground">
                  {{ request.category?.name ?? "—" }}
                </td>
                <td class="py-3 pr-4">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="statusClass(request.status)"
                  >
                    {{ requestStatusLabels[request.status] }}
                  </span>
                </td>
                <td class="py-3 text-right">
                  <NuxtLink
                    :to="`/requests/${request.id}`"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                  >
                    View
                  </NuxtLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="listMeta && listMeta.last_page > 1"
          class="mt-4 flex items-center justify-between gap-4 border-t pt-4"
        >
          <p class="text-sm text-muted-foreground">
            Page {{ listMeta.current_page }} of {{ listMeta.last_page }}
          </p>
          <div class="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="loading || listMeta.current_page <= 1"
              @click="goTo(listMeta.current_page - 1)"
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              :disabled="loading || listMeta.current_page >= listMeta.last_page"
              @click="goTo(listMeta.current_page + 1)"
            >
              Next
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </main>
</template>
