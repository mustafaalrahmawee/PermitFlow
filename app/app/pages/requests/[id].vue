<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { decisionOutcomeLabels, requestStatusLabels } from "~/types/request";

// UC-03 — a citizen tracks one request's progress (main flow steps 3–6). Reads
// the request detail from the requests store and renders the current status,
// the understandable history (each entry's frozen summary — ext 5a), and the
// connected messages, documents, and decision. A request the caller does not
// own reads as not found (404 → the not-found state, ext 3a), so existence is
// never revealed. Authenticated page: the routing defaults cover it.

const route = useRoute();
const store = useRequestsStore();
const { current } = storeToRefs(store);

const loading = ref(true);
const notFound = ref(false);
const loadError = ref<string | null>(null);

function formatDate(value: string | null): string {
  if (!value) {
    return "—";
  }
  return new Date(value).toLocaleString();
}

// The status carries the current progress, e.g. "Waiting for Citizen" while a
// requested response is pending (ext 6a).
function statusClass(status: string): string {
  return status === "decided"
    ? "bg-muted text-muted-foreground"
    : "bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300";
}

onMounted(async () => {
  try {
    await store.fetchOne(route.params.id as string);
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 404) {
      notFound.value = true;
    } else {
      loadError.value = "Could not load this request. Please try again.";
    }
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <main class="mx-auto max-w-3xl px-6 py-10">
    <NuxtLink
      to="/requests"
      class="mb-6 inline-flex text-sm text-muted-foreground underline-offset-4 hover:underline"
    >
      &larr; Back to my requests
    </NuxtLink>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading request…</p>

    <!-- ext 3a — a request the citizen does not own is reported as not found. -->
    <Alert v-else-if="notFound" variant="destructive">
      <AlertTitle>Request not found</AlertTitle>
      <AlertDescription>
        This request does not exist or is not available to you.
      </AlertDescription>
    </Alert>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <template v-else-if="current">
      <header class="mb-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ current.title }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
              {{ current.category?.name ?? "Uncategorized" }}
              · Submitted {{ formatDate(current.submitted_at) }}
            </p>
          </div>
          <span
            class="mt-1 inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
            :class="statusClass(current.status)"
          >
            {{ requestStatusLabels[current.status] }}
          </span>
        </div>
      </header>

      <!-- Decision, when one has been recorded. -->
      <Card v-if="current.decision" class="mb-6">
        <CardHeader>
          <CardTitle class="text-base">Decision</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <span
            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
            :class="
              current.decision.outcome === 'approved'
                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
            "
          >
            {{ decisionOutcomeLabels[current.decision.outcome] }}
          </span>
          <p v-if="current.decision.decision_text" class="text-sm">
            {{ current.decision.decision_text }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ formatDate(current.decision.decided_at) }}
          </p>
        </CardContent>
      </Card>

      <!-- History: the understandable progress, each entry's frozen summary (ext 5a). -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle class="text-base">History</CardTitle>
        </CardHeader>
        <CardContent>
          <p
            v-if="current.history_entries.length === 0"
            class="text-sm text-muted-foreground"
          >
            No progress has been recorded yet.
          </p>
          <ol v-else class="space-y-4">
            <li
              v-for="entry in current.history_entries"
              :key="entry.id"
              class="flex gap-3"
            >
              <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary/60" />
              <div>
                <p class="text-sm">{{ entry.summary }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ formatDate(entry.event_occurred_at) }}
                </p>
              </div>
            </li>
          </ol>
        </CardContent>
      </Card>

      <!-- Messages connected to the request (step 6). -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle class="text-base">Messages</CardTitle>
        </CardHeader>
        <CardContent>
          <p
            v-if="current.messages.length === 0"
            class="text-sm text-muted-foreground"
          >
            No messages yet.
          </p>
          <ul v-else class="space-y-3">
            <li
              v-for="message in current.messages"
              :key="message.id"
              class="rounded-lg border p-3"
            >
              <p class="text-sm">{{ message.body }}</p>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ formatDate(message.sent_at) }}
              </p>
            </li>
          </ul>
        </CardContent>
      </Card>

      <!-- Documents connected to the request (step 6). -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Documents</CardTitle>
        </CardHeader>
        <CardContent>
          <p
            v-if="current.documents.length === 0"
            class="text-sm text-muted-foreground"
          >
            No documents attached.
          </p>
          <ul v-else class="space-y-2">
            <li
              v-for="document in current.documents"
              :key="document.id"
              class="flex items-center justify-between gap-4 border-b py-2 text-sm last:border-0"
            >
              <span class="font-medium">{{ document.original_filename }}</span>
              <span class="text-muted-foreground">
                {{ document.description ?? document.kind }}
              </span>
            </li>
          </ul>
        </CardContent>
      </Card>
    </template>
  </main>
</template>
