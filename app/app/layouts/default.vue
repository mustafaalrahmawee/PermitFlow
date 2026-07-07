<script setup lang="ts">
// Authenticated app shell: a sticky top nav over the page content. The account
// is loaded here (once, when a token exists but the user isn't cached yet) so
// the nav can show it on any entry point; page-level guards still handle a
// token the API rejects.
const auth = useAuthStore();

onMounted(async () => {
  if (auth.token && !auth.user) {
    try {
      await auth.fetchUser();
    } catch {
      // A rejected token is handled by the page/route guards, not the shell.
    }
  }
});
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <AppSidebar />
    <!-- Content sits to the right of the fixed sidebar on desktop; on mobile the
         sidebar is an overlay drawer, so no left offset is applied. -->
    <div class="lg:pl-64">
      <slot />
    </div>
  </div>
</template>
