<script setup lang="ts">
import { LucideCircleAlert, LucideCircleCheck, LucideX } from "#components";

// Snackbar stack for the app shell: renders the toasts pushed through useToast()
// as transient, auto-dismissing notices (success in green, error in red). Mounted
// once in the authenticated layout so any page or store can surface feedback
// without owning a mount point of its own.
const { toasts, dismiss } = useToast();
</script>

<template>
  <div
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        role="status"
        aria-live="polite"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border px-4 py-3 shadow-lg"
        :class="
          toast.variant === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200'
        "
      >
        <component
          :is="toast.variant === 'success' ? LucideCircleCheck : LucideCircleAlert"
          class="mt-0.5 size-4 shrink-0"
        />
        <p class="flex-1 text-sm">{{ toast.message }}</p>
        <button
          type="button"
          aria-label="Dismiss"
          class="shrink-0 opacity-70 transition-opacity hover:opacity-100"
          @click="dismiss(toast.id)"
        >
          <LucideX class="size-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(0.5rem);
}
</style>
