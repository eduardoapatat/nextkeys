import { registerEndpoint } from '@nuxt/test-utils/runtime';

const cleanups: Array<() => void> = [];

/**
 * Serves `body` with `status` at `/api/v1${path}` (apiBase is `/api/v1` in
 * the nuxt test project) and records the query of every request.
 */
export function serveApi(path: string, body: unknown, status = 200) {
    const queries: Array<Record<string, string>> = [];

    cleanups.push(
        registerEndpoint(`/api/v1${path}`, (event) => {
            const url = new URL(
                'url' in event && event.url instanceof URL
                    ? event.url.href
                    : String(event.path),
                'http://localhost',
            );
            queries.push(Object.fromEntries(url.searchParams));

            return new Response(JSON.stringify(body), {
                status,
                headers: { 'content-type': 'application/json' },
            });
        }),
    );

    return queries;
}

export function resetApi() {
    cleanups.splice(0).forEach((cleanup) => cleanup());
    clearNuxtData();
}
