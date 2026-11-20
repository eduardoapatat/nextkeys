/** The /api/v1 client: every call goes through it, with the configured base URL. */
export default defineNuxtPlugin(() => {
    const api = $fetch.create({
        baseURL: useRuntimeConfig().public.apiBase,
        headers: { Accept: 'application/json' },
    });

    return { provide: { api } };
});
