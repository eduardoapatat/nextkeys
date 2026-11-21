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

/** The raw body of a request to a mocked endpoint (an h3 v1 event). */
async function requestText(event: object): Promise<string> {
    const body = '_requestBody' in event ? event._requestBody : undefined;

    return body instanceof ReadableStream ? new Response(body).text() : '';
}

/**
 * Serves `body` with `status` to POST requests at `/api/v1${path}` and
 * records the JSON body of every request.
 */
export function servePost(path: string, body: unknown, status = 200) {
    const bodies: unknown[] = [];

    cleanups.push(
        registerEndpoint(`/api/v1${path}`, {
            method: 'POST',
            handler: async (event) => {
                const text = await requestText(event);
                bodies.push(text === '' ? undefined : JSON.parse(text));

                return new Response(
                    body === undefined ? null : JSON.stringify(body),
                    {
                        status,
                        headers: { 'content-type': 'application/json' },
                    },
                );
            },
        }),
    );

    return bodies;
}

/** The CSRF cookie route the auth calls hit before their first POST. */
export function serveCsrfCookie() {
    cleanups.push(
        registerEndpoint(
            '/api/v1/auth/csrf-cookie',
            () => new Response(null, { status: 204 }),
        ),
    );
}

export function resetApi() {
    cleanups.splice(0).forEach((cleanup) => cleanup());
    clearNuxtData();
    useCustomer().value = null;
}
