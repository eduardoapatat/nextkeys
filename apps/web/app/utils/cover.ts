/**
 * Generated covers for products without `cover_url`: the tint is stable per
 * slug, so a product keeps its color across pages and renders.
 */
const coverTints = [
    'bg-cover-1',
    'bg-cover-2',
    'bg-cover-3',
    'bg-cover-4',
    'bg-cover-5',
    'bg-cover-6',
] as const;

export type CoverTint = (typeof coverTints)[number];

export function coverTint(slug: string): CoverTint {
    let hash = 0;
    for (const char of slug) {
        hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
    }
    return coverTints[hash % coverTints.length]!;
}
