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
});
