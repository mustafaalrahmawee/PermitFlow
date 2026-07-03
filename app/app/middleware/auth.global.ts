// Runs on every route change. Pages that don't require a session must be
// listed here; everything else redirects to sign-in without a token.
const publicPages = ["login"];

export default defineNuxtRouteMiddleware((to) => {
  if (publicPages.includes(to.name as string)) {
    return;
  }

  const authStore = useAuthStore();
  const { token } = storeToRefs(authStore);

  if (!token.value) {
    return navigateTo("/login");
  }
});
