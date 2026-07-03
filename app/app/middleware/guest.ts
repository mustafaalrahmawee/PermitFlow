// Named middleware for sign-in/sign-up style pages: bounce an already
// authenticated session back to the landing page instead of showing the form.
export default defineNuxtRouteMiddleware(() => {
  const authStore = useAuthStore();
  const { token } = storeToRefs(authStore);

  if (token.value) {
    return navigateTo("/");
  }
});
