// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    modules: ['@nuxt/eslint', '@nuxt/image', '@nuxt/test-utils/module'],
    compatibilityDate: '2026-11-20',
    devtools: { enabled: import.meta.dev },
    runtimeConfig: {
        public: {
            // Overridden with NUXT_PUBLIC_API_BASE.
            apiBase: 'http://localhost:8000/api/v1',
        },
    },
    typescript: {
        strict: true,
    },
});
