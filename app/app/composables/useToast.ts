import { ref } from "vue";

// Transient snackbar feedback for actions whose outcome isn't otherwise visible
// on the page — e.g. a staff member starting a review (UC-06 step 5). Not part of
// a use-case contract; a presentation concern shared across pages and stores.

export type ToastVariant = "success" | "error";

export interface Toast {
  id: number;
  message: string;
  variant: ToastVariant;
}

// The app is SPA-only (nuxt.config `ssr: false`), so a module-level singleton is
// safe: there is one client instance and every caller shares the same stack. The
// <AppToaster> in the shell renders it; pages and stores push through toast().
const toasts = ref<Toast[]>([]);
let nextId = 0;

const DEFAULT_DURATION = 4000;

function dismiss(id: number): void {
  toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

function toast(
  message: string,
  variant: ToastVariant = "success",
  duration = DEFAULT_DURATION,
): number {
  const id = ++nextId;
  toasts.value.push({ id, message, variant });
  if (duration > 0) {
    setTimeout(() => dismiss(id), duration);
  }
  return id;
}

export function useToast() {
  return { toasts, toast, dismiss };
}
