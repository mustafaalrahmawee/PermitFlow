<script setup lang="ts">
import { storeToRefs } from "pinia";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

// UC-11 — administrator list of request categories (main flow steps 1–2). Reads
// the list from the request-categories store and renders the category list, the
// authorization-denial state (403), and a load error. Authenticated page: no
// layout/middleware declared — the defaults cover it (docs/conventions.md
// Frontend routing).

const store = useRequestCategoriesStore();
const { categories, meta } = storeToRefs(store);

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
      loadError.value = "Could not load request categories. Please try again.";
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
        <h1 class="text-2xl font-semibold tracking-tight">Request categories</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Maintain the categories citizens file requests under; activate or deactivate them for future filing.
        </p>
      </div>
      <Button as-child>
        <NuxtLink to="/admin/request-categories/new">New category</NuxtLink>
      </Button>
    </div>

    <p v-if="loading" class="text-sm text-muted-foreground">Loading categories…</p>

    <Alert v-else-if="denied" variant="destructive">
      <AlertTitle>Not authorized</AlertTitle>
      <AlertDescription>You do not have permission to manage request categories.</AlertDescription>
    </Alert>

    <Alert v-else-if="loadError" variant="destructive">
      <AlertDescription>{{ loadError }}</AlertDescription>
    </Alert>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-base">{{ meta?.total ?? categories.length }} categor(y/ies)</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-[15px]">
            <thead>
              <tr class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                <th class="py-3 pr-4 font-medium">Name</th>
                <th class="py-3 pr-4 font-medium">Description</th>
                <th class="py-3 pr-4 font-medium">Availability</th>
                <th class="py-3 font-medium"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="category in categories"
                :key="category.id"
                class="border-b transition-colors last:border-0 hover:bg-muted/50"
              >
                <td class="py-3 pr-4 font-medium">{{ category.name }}</td>
                <td class="py-3 pr-4 text-muted-foreground">{{ category.description ?? "—" }}</td>
                <td class="py-3 pr-4">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="
                      category.is_active
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'bg-muted text-muted-foreground'
                    "
                  >
                    <span
                      class="size-1.5 rounded-full"
                      :class="category.is_active ? 'bg-emerald-500' : 'bg-muted-foreground/50'"
                    />
                    {{ category.is_active ? "Active" : "Inactive" }}
                  </span>
                </td>
                <td class="py-3 text-right">
                  <NuxtLink
                    :to="`/admin/request-categories/${category.id}`"
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
