import { xsrfHeaders } from '~/utils/xsrf';

/**
 * The /api/v1 client: every call goes through it, with the configured base
 * URL and the customer's Sanctum session cookie.
 *
 * - In the browser it sends the cookies (`credentials: 'include'`) and, on
 *   requests that change state, the `X-XSRF-TOKEN` header.
 * - During SSR it forwards the browser's cookies and names the storefront in
 *   `Referer`, so Sanctum treats the call as a storefront session request.
 */
export default defineNuxtPlugin(() => {
    const headers: Record<string, string> = { Accept: 'application/json' };

    if (import.meta.server) {
        const { cookie } = useRequestHeaders(['cookie']);

        if (cookie) {
            headers.cookie = cookie;
        }

        headers.Referer = `${useRequestURL().origin}/`;
    }

    const api = $fetch.create({
        baseURL: useRuntimeConfig().public.apiBase,
        headers,
        credentials: 'include',
        onRequest({ options }) {
            if (import.meta.client) {
                const xsrf = xsrfHeaders(options.method, document.cookie);

                for (const [name, value] of Object.entries(xsrf)) {
                    options.headers.set(name, value);
                }
            }
        },
    });

    return { provide: { api } };
});
