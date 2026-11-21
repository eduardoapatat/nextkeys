// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from '@tailwindcss/vite';

export default defineNuxtConfig({
    modules: ['@nuxt/eslint', '@nuxt/image', '@nuxt/test-utils/module'],
    compatibilityDate: '2026-11-20',
    devtools: { enabled: false },
    css: ['~/assets/css/main.css'],
    vite: {
        plugins: [tailwindcss()],
    },
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
