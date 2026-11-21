const readOnlyMethods = new Set(['GET', 'HEAD', 'OPTIONS']);

/** The decoded value of the cookie `name` in a `document.cookie` string. */
export function readCookie(cookies: string, name: string): string | undefined {
    for (const pair of cookies.split(';')) {
        const [key, ...value] = pair.trim().split('=');

        if (key === name) {
            return decodeURIComponent(value.join('='));
        }
    }

    return undefined;
}

/**
 * Sanctum's CSRF protection: requests that change state send the
 * `XSRF-TOKEN` cookie back in the `X-XSRF-TOKEN` header.
 */
export function xsrfHeaders(
    method: string | undefined,
    cookies: string,
): Record<string, string> {
    if (readOnlyMethods.has((method ?? 'GET').toUpperCase())) {
        return {};
    }

    const token = readCookie(cookies, 'XSRF-TOKEN');

    return token === undefined ? {} : { 'X-XSRF-TOKEN': token };
}
