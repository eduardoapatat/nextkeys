/**
 * The `redirect` query parameter of the sign in page, only when it is a path
 * of this site: anything else (another origin, `//host`, `/\host`) would make
 * the page an open redirect.
 */
export function safeRedirect(value: unknown, fallback = '/cuenta'): string {
    if (
        typeof value !== 'string' ||
        !value.startsWith('/') ||
        value.startsWith('//') ||
        value.startsWith('/\\')
    ) {
        return fallback;
    }

    return value;
}
