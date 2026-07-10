<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { requestStatusLabels } from "~/types/request";
import type { RequestStatusSlug } from "~/types/request";

// UC-14 — an administrator views organization-level reporting summaries for
// oversight. Loads the summary via the store, then renders the acceptance
// states: the selected view (step 3 — volume, request status, or processing
// progress incl. assignment), a denial (403 — a citizen via ext 1a or a
// non-administrator staff member via ext 2a reaching the page), and an empty
// summary rather than an error when the organization has no requests (ext 3a).
// The summary is organization-wide and carries aggregates only (ext 4a).
// Authenticated page: no layout/middleware declared — the defaults cover it
// (docs/conventions.md Frontend routing).

const store = useReportingStore();
const { adminSummary } = storeToRefs(store);

const loading = ref(true);
const denied = ref(false);
const loadError = ref<string | null>(null);

type View = "volume" | "status" | "progress";
const view = ref<View>("volume");

const views: { key: View; label: string }[] = [
  { key: "volume", label: "Volume" },
  { key: "status", label: "By status" },
  { key: "progress", label: "Processing progress" },
];

// Status rows in workflow order, so the by-status view reads as a pipeline.
const statusOrder: RequestStatusSlug[] = [
  "draft",
  "submitted",
  "in_review",
  "waiting_for_citizen",
  "ready_for_decision",
  "decided",
];

// ext 3a — an organization with no requests gets an all-zero summary, shown as
// an empty state rather than an error.
const isEmpty = computed(() => (adminSummary.value?.volume.total ?? 0) === 0);

async function load(): Promise<void> {
  loading.value = true;
  denied.value = false;
  loadError.value = null;
  try {
    await store.fetchAdminSummary();
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 403) {
      denied.value = true;
    } else {
      loadError.value = "Could not load the reporting summary. Please try again.";
    }
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>

<template>
  <main class="mx-auto max-w-4xl px-6 py-10">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold tracking-tight">Administrative reporting</h1>
      <p class="mt-1 text-sm text-muted-foreground">
        Request volume, status, and processing progress across the whole organization.
      </p>
    </div>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading summary…</p>

    <!-- ext 1a / ext 2a — a citizen or non-administrator staff member reaching the
         page is denied by the view-reporting gate / administrator narrowing. -->
    <Alert v-else-if="denied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to view administrative reporting.</AlertDescription>
    </Alert>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <template v-else-if="adminSummary">
      <!-- Step 3 — select the summary view. -->
      <div class="mb-6 inline-flex rounded-md border p-1">
        <Button
          v-for="v in views"
          :key="v.key"
          size="sm"
          :variant="view === v.key ? 'default' : 'ghost'"
          @click="view = v.key"
        >
          {{ v.label }}
        </Button>
      </div>

      <!-- ext 3a — nothing in the organization: an empty summary, not an error. -->
      <Card v-if="isEmpty">
        <CardContent class="py-10 text-center text-sm text-muted-foreground">
          There are no requests in the organization yet, so there is nothing to summarize.
        </CardContent>
      </Card>

      <template v-else>
        <!-- Volume view. -->
        <Card v-if="view === 'volume'">
          <CardHeader>
            <CardTitle class="text-base">Request volume</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-4xl font-semibold tabular-nums">{{ adminSummary.volume.total }}</p>
            <p class="mt-1 text-sm text-muted-foreground">
              Total requests across the organization.
            </p>
          </CardContent>
        </Card>

        <!-- By-status view. -->
        <Card v-else-if="view === 'status'">
          <CardHeader>
            <CardTitle class="text-base">Requests by status</CardTitle>
          </CardHeader>
          <CardContent>
            <table class="w-full text-[15px]">
              <thead>
                <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                  <th class="py-3 pr-4 font-medium">Status</th>
                  <th class="py-3 text-right font-medium">Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="status in statusOrder" :key="status" class="border-b">
                  <td class="py-3 pr-4">{{ requestStatusLabels[status] }}</td>
                  <td class="py-3 text-right tabular-nums">{{ adminSummary.by_status[status] }}</td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>

        <!-- Processing-progress view, incl. the assignment breakdown. -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle class="text-sm text-muted-foreground">Open</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-semibold tabular-nums">{{ adminSummary.processing_progress.open }}</p>
              <p class="mt-1 text-xs text-muted-foreground">Still being processed.</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle class="text-sm text-muted-foreground">Awaiting citizen</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-semibold tabular-nums">
                {{ adminSummary.processing_progress.awaiting_citizen }}
              </p>
              <p class="mt-1 text-xs text-muted-foreground">Blocked on a citizen response.</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle class="text-sm text-muted-foreground">Decided</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-semibold tabular-nums">{{ adminSummary.processing_progress.decided }}</p>
              <p class="mt-1 text-xs text-muted-foreground">Completed.</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle class="text-sm text-muted-foreground">Assigned</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-semibold tabular-nums">{{ adminSummary.processing_progress.assigned }}</p>
              <p class="mt-1 text-xs text-muted-foreground">Have a responsible staff member.</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle class="text-sm text-muted-foreground">Unassigned</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-3xl font-semibold tabular-nums">{{ adminSummary.processing_progress.unassigned }}</p>
              <p class="mt-1 text-xs text-muted-foreground">Awaiting assignment.</p>
            </CardContent>
          </Card>
        </div>
      </template>
    </template>
  </main>
</template>
