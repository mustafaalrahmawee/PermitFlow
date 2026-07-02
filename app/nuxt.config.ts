// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  modules: [
    "@pinia/nuxt",
    "@vueuse/nuxt",
    "@vee-validate/nuxt",
    "nuxt-lucide-icons",
    "@nuxtjs/tailwindcss",
  ],
  runtimeConfig: {
    public: {
      // Base URL of the PermitFlow API (Sanctum bearer-token auth). The browser
      // talks to the API directly; override with NUXT_PUBLIC_API_BASE elsewhere.
      apiBase: "http://localhost:8000/api",
    },
  },
});
