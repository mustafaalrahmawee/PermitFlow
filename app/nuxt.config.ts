// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";
export default defineNuxtConfig({
  ssr: false,
  
  compatibilityDate: "2025-07-15",
  devtools: { enabled: false},
  modules: ["@pinia/nuxt", "@vueuse/nuxt", "nuxt-lucide-icons", "shadcn-nuxt"],
  runtimeConfig: {
    public: {
      // Base URL of the PermitFlow API (Sanctum bearer-token auth). The browser
      // talks to the API directly; override with NUXT_PUBLIC_API_BASE elsewhere.
      apiBase: "http://localhost:8000/api",
    },
  },
  css: ["~/assets/css/tailwind.css"],
  vite: {
    plugins: [tailwindcss()],
  },
  shadcn: {
    /**
     * Prefix for all the imported component.
     * @default "Ui"
     */
    prefix: "",
    /**
     * Directory that the component lives in.
     * Will respect the Nuxt aliases.
     * @link https://nuxt.com/docs/api/nuxt-config#alias
     * @default "@/components/ui"
     */
    componentDir: "@/components/ui",
  },
});
