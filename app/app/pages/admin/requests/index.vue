<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { AssignmentPayload, AssignmentWorklistItem } from "~/types/assignment";
import { requestStatusLabels } from "~/types/request";

// UC-05 — administrator assigns or reassigns eligible requests. Loads the
// eligible-request worklist and the assignable staff from the store, then renders
// the acceptance states: nothing to assign (ext 1a), authorization denial (403,
// ext 5a), and — per selected request — the inline assignment form whose save
// surfaces validation errors (422: missing/invalid staff ext 3a/3b, missing
// reason ext 4a), a lifecycle conflict (409: draft/decided/other status
// ext 2a/2b/6a), and denial (403). Authenticated page: no layout/middleware
// declared — the defaults cover it (docs/conventions.md Frontend routing).

const store = useAssignmentsStore();
const { worklist, meta, assignableStaff } = storeToRefs(store);

const loading = ref(true);
const denied = ref(false);
const loadError = ref<string | null>(null);

const selected = ref<AssignmentWorklistItem | null>(null);
const form = reactive({ responsible_staff_user_account_id: "" as number | "", reason: "" });
const fieldErrors = reactive<Record<string, string | undefined>>({});
const formError = ref<string | null>(null);
const conflict = ref<string | null>(null);
const saveDenied = ref(false);
const submitting = ref(false);
const saved = ref<string | null>(null);

const isReassignment = computed(
  () => selected.value != null && selected.value.responsible_staff_user_account_id != null,
);

// The assignee options for the selected request: on a reassignment the current
// responsible staff member is excluded, so the admin cannot reassign to the same
// person (the seam rejects it as 422 too).
const staffOptions = computed(() => {
  const currentId = selected.value?.responsible_staff_user_account_id ?? null;
  return currentId == null
    ? assignableStaff.value
    : assignableStaff.value.filter((staff) => staff.id !== currentId);
});

const selectClass =
  "border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3";

async function load(page = 1): Promise<void> {
  loading.value = true;
  denied.value = false;
  loadError.value = null;
  try {
    await store.fetchWorklist(page);
    await store.fetchAssignableStaff();
  } catch (error: unknown) {
    const status = (error as { statusCode?: number }).statusCode;
    if (status === 403) {
      denied.value = true;
    } else {
      loadError.value = "Could not load requests. Please try again.";
    }
  } finally {
    loading.value = false;
  }
}

function goTo(page: number): void {
  if (!meta.value || page < 1 || page > meta.value.last_page || page === meta.value.current_page) {
    return;
  }
  closeForm();
  load(page);
}

function clearErrors(): void {
  formError.value = null;
  conflict.value = null;
  saveDenied.value = false;
  for (const key of Object.keys(fieldErrors)) {
    fieldErrors[key] = undefined;
  }
}

function openForm(item: AssignmentWorklistItem): void {
  selected.value = item;
  form.responsible_staff_user_account_id = "";
  form.reason = "";
  saved.value = null;
  clearErrors();
}

function closeForm(): void {
  selected.value = null;
  clearErrors();
}

async function onSubmit(): Promise<void> {
  if (!selected.value) {
    return;
  }
  clearErrors();

  // Client mirror of the seam's rules: exactly one staff member (ext 3a) and,
  // on reassignment, a reason (ext 4a). The server re-validates and is the
  // source of truth (422).
  if (form.responsible_staff_user_account_id === "") {
    fieldErrors.responsible_staff_user_account_id = "Select a staff member.";
    return;
  }
  if (isReassignment.value && form.reason.trim() === "") {
    fieldErrors.reason = "Enter a short reassignment reason.";
    return;
  }

  const payload: AssignmentPayload = {
    responsible_staff_user_account_id: Number(form.responsible_staff_user_account_id),
  };
  if (isReassignment.value) {
    payload.reason = form.reason.trim();
  }

  submitting.value = true;
  try {
    const updated = await store.assign(selected.value.id, payload);
    saved.value = `${updated.title} is now assigned to ${
      updated.responsible_staff?.display_name ?? "the selected staff member"
    }.`;
    selected.value = null;
  } catch (error: unknown) {
    const err = error as {
      statusCode?: number;
      data?: { message?: string; errors?: Record<string, string[]> };
    };
    if (err.statusCode === 422 && err.data?.errors) {
      for (const [key, messages] of Object.entries(err.data.errors)) {
        fieldErrors[key] = messages[0];
      }
    } else if (err.statusCode === 409) {
      conflict.value =
        err.data?.message ?? "This request can no longer be assigned in its current status.";
    } else if (err.statusCode === 403) {
      saveDenied.value = true;
    } else {
      formError.value = err.data?.message ?? "Could not save the assignment. Please try again.";
    }
  } finally {
    submitting.value = false;
  }
}

onMounted(() => load());
</script>

<template>
  <main class="mx-auto max-w-5xl px-6 py-10">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold tracking-tight">Assign requests</h1>
      <p class="mt-1 text-sm text-muted-foreground">
        Assign submitted and active requests to a responsible staff member, or reassign them.
      </p>
    </div>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading requests…</p>

    <Alert v-else-if="denied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to assign requests.</AlertDescription>
    </Alert>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <template v-else>
      <Alert v-if="saved" class="mb-6">
        <AlertTitle>Assignment saved</AlertTitle>
        <AlertDescription>{{ saved }}</AlertDescription>
      </Alert>

      <!-- ext 1a — nothing needs assignment or reassignment. -->
      <Card v-if="worklist.length === 0">
        <CardContent class="py-10 text-center text-sm text-muted-foreground">
          There is nothing to assign right now.
        </CardContent>
      </Card>

      <Card v-else>
        <CardHeader>
          <CardTitle class="text-base">{{ meta?.total ?? worklist.length }} request(s) to handle</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-[15px]">
              <thead>
                <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                  <th class="py-3 pr-4 font-medium">Request</th>
                  <th class="py-3 pr-4 font-medium">Status</th>
                  <th class="py-3 pr-4 font-medium">Responsible staff</th>
                  <th class="py-3 font-medium"></th>
                </tr>
              </thead>
              <tbody>
                <template v-for="item in worklist" :key="item.id">
                  <tr class="border-b transition-colors hover:bg-muted/50">
                    <td class="py-3 pr-4 font-medium">{{ item.title }}</td>
                    <td class="py-3 pr-4 text-muted-foreground">{{ requestStatusLabels[item.status] }}</td>
                    <td class="py-3 pr-4">
                      <span v-if="item.responsible_staff">{{ item.responsible_staff.display_name }}</span>
                      <span v-else class="text-muted-foreground">Unassigned</span>
                    </td>
                    <td class="py-3 text-right">
                      <Button variant="outline" size="sm" @click="openForm(item)">
                        {{ item.responsible_staff_user_account_id ? "Reassign" : "Assign" }}
                      </Button>
                    </td>
                  </tr>

                  <!-- Inline assignment form for the selected request. -->
                  <tr v-if="selected && selected.id === item.id" :key="`form-${item.id}`" class="border-b bg-muted/30">
                    <td colspan="4" class="px-1 py-5">
                      <form class="space-y-4" novalidate @submit.prevent="onSubmit">
                        <Alert v-if="conflict" variant="destructive">
                          <AlertTitle>Cannot assign</AlertTitle>
                          <AlertDescription>{{ conflict }}</AlertDescription>
                        </Alert>
                        <Alert v-if="saveDenied" variant="destructive">
                          <AlertTitle>Not authorized</AlertTitle>
                          <AlertDescription>You do not have permission to assign requests.</AlertDescription>
                        </Alert>
                        <Alert v-if="formError" variant="destructive">
                          <AlertDescription>{{ formError }}</AlertDescription>
                        </Alert>

                        <div class="space-y-2">
                          <Label for="responsible_staff_user_account_id">Responsible staff member</Label>
                          <select
                            id="responsible_staff_user_account_id"
                            v-model="form.responsible_staff_user_account_id"
                            :class="selectClass"
                          >
                            <option value="" disabled>Select a staff member…</option>
                            <option v-for="staff in staffOptions" :key="staff.id" :value="staff.id">
                              {{ staff.display_name }}
                            </option>
                          </select>
                          <p v-if="fieldErrors.responsible_staff_user_account_id" class="text-xs text-destructive">
                            {{ fieldErrors.responsible_staff_user_account_id }}
                          </p>
                        </div>

                        <div v-if="isReassignment" class="space-y-2">
                          <Label for="reason">Reassignment reason</Label>
                          <Input
                            id="reason"
                            v-model="form.reason"
                            :aria-invalid="Boolean(fieldErrors.reason)"
                            placeholder="Why is this request being reassigned?"
                          />
                          <p v-if="fieldErrors.reason" class="text-xs text-destructive">{{ fieldErrors.reason }}</p>
                        </div>

                        <div class="flex gap-3">
                          <Button type="submit" size="sm" :disabled="submitting">
                            {{ submitting ? "Saving…" : isReassignment ? "Reassign" : "Assign" }}
                          </Button>
                          <Button type="button" size="sm" variant="outline" @click="closeForm">Cancel</Button>
                        </div>
                      </form>
                    </td>
                  </tr>
                </template>
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
    </template>
  </main>
</template>
