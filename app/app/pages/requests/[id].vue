<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { decisionOutcomeLabels, requestStatusLabels } from "~/types/request";

// UC-03 / UC-06 — one request's detail, shared by the owning citizen tracking
// progress and the responsible staff member reviewing an assigned request (main
// flow steps 3–6 / UC-06 step 4). Reads the request detail from the store and
// renders the current status, the understandable history (each entry's frozen
// summary — ext 5a), and the connected messages, documents, and decision — the
// submitted information the staff member verifies before judging next steps
// (UC-06 ext 6a, ext 6b). A request outside the caller's scope reads as not
// found (404 → the not-found state, ext 3a), so existence is never revealed. For
// the responsible staff member of a Submitted request, the review can be started
// here (UC-06 step 5). Authenticated page: the routing defaults cover it.

const route = useRoute();
const store = useRequestsStore();
const auth = useAuthStore();
const { current } = storeToRefs(store);
const { user } = storeToRefs(auth);

const loading = ref(true);
const notFound = ref(false);
const loadError = ref<string | null>(null);

// UC-06 step 5 — the responsible staff member may start review only while the
// request is still Submitted; the action moves it to In Review.
const canStartReview = computed(
  () =>
    Boolean(current.value) &&
    user.value?.role === "staff_member" &&
    current.value?.responsible_staff_user_account_id === user.value?.id &&
    current.value?.status === "submitted",
);

const startingReview = ref(false);
const reviewError = ref<string | null>(null);
const { toast } = useToast();

async function onStartReview(): Promise<void> {
  if (!current.value) {
    return;
  }
  startingReview.value = true;
  reviewError.value = null;
  try {
    await store.startReview(current.value.id);
    toast("Review started.");
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    reviewError.value =
      status === 409
        ? "This request can no longer be moved into review."
        : status === 403
          ? "You are not allowed to review this request."
          : "Could not start the review. Please try again.";
  } finally {
    startingReview.value = false;
  }
}

// UC-07 — the responsible staff member may request missing information only
// while the request is In Review; the action records the message and moves the
// request to Waiting for Citizen.
const canRequestInformation = computed(
  () =>
    Boolean(current.value) &&
    user.value?.role === "staff_member" &&
    current.value?.responsible_staff_user_account_id === user.value?.id &&
    current.value?.status === "in_review",
);

const informationBody = ref("");
const requestingInformation = ref(false);
const informationError = ref<string | null>(null);
const informationFieldError = ref<string | null>(null);

async function onRequestInformation(): Promise<void> {
  if (!current.value) {
    return;
  }
  requestingInformation.value = true;
  informationError.value = null;
  informationFieldError.value = null;
  try {
    await store.requestInformation(current.value.id, informationBody.value);
    informationBody.value = "";
    toast("Missing information requested from the citizen.");
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    const data = (error as { data?: { errors?: { body?: string[] } } }).data;
    if (status === 422) {
      // ext 3a — an empty message is not sent; the system asks for a clear message.
      informationFieldError.value =
        data?.errors?.body?.[0] ?? "Please write a clear message for the citizen.";
    } else {
      informationError.value =
        status === 409
          ? "This request can no longer accept a missing-information request."
          : status === 403
            ? "You are not allowed to request information on this request."
            : "Could not send the request. Please try again.";
    }
  } finally {
    requestingInformation.value = false;
  }
}

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
      &larr; {{ user?.role === "staff_member" ? "Back to assigned requests" : "Back to my requests" }}
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

      <!-- UC-06 step 5 — the responsible staff member starts the review of a
           Submitted request; the status then moves to In Review and a history
           entry records the change. A blocked transition (409) or denial (403)
           surfaces here without leaving the review treated as started. -->
      <Card v-if="canStartReview || reviewError" class="mb-6">
        <CardContent class="flex flex-col gap-3 py-5 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-medium">Start reviewing this request</p>
            <p class="mt-1 text-sm text-muted-foreground">
              Verify the submitted information and documents, then move the request into review.
            </p>
            <p v-if="reviewError" class="mt-2 text-sm text-destructive">{{ reviewError }}</p>
          </div>
          <Button
            v-if="canStartReview"
            :disabled="startingReview"
            class="shrink-0"
            @click="onStartReview"
          >
            {{ startingReview ? "Starting…" : "Start review" }}
          </Button>
        </CardContent>
      </Card>

      <!-- UC-07 — the responsible staff member requests missing information from
           the citizen while the request is In Review. On success the message is
           recorded and the request moves to Waiting for Citizen; an empty message
           is rejected inline (422, ext 3a) and a blocked transition (409) or
           denial (403) surfaces without recording anything. -->
      <Card v-if="canRequestInformation" class="mb-6">
        <CardHeader>
          <CardTitle class="text-base">Request missing information</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="mb-3 text-sm text-muted-foreground">
            Explain what the citizen must provide. Sending this moves the request to
            Waiting for Citizen.
          </p>
          <form class="space-y-3" @submit.prevent="onRequestInformation">
            <div class="space-y-1.5">
              <Label for="information-body">Message to the citizen</Label>
              <textarea
                id="information-body"
                v-model="informationBody"
                rows="4"
                :aria-invalid="Boolean(informationFieldError)"
                class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-3 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="Describe the information or documents still needed…"
              />
              <p v-if="informationFieldError" class="text-sm text-destructive">
                {{ informationFieldError }}
              </p>
            </div>
            <p v-if="informationError" class="text-sm text-destructive">
              {{ informationError }}
            </p>
            <Button type="submit" :disabled="requestingInformation">
              {{ requestingInformation ? "Sending…" : "Request information" }}
            </Button>
          </form>
        </CardContent>
      </Card>

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
