import type { LocationQuery, LocationQueryValue } from 'vue-router';

export const listingFacets = [
    'platform',
    'type',
    'activation_service',
    'region',
] as const;

export type ListingFacet = (typeof listingFacets)[number];

/** The listing state that lives in the URL. Values are not validated here: the API answers 422. */
export type ListingQuery = Record<ListingFacet, string[]> & {
    sort?: string;
    page?: string;
};

type QueryValue = LocationQueryValue | LocationQueryValue[] | undefined;

function strings(value: QueryValue): string[] {
    const values = Array.isArray(value) ? value : [value];

    return values.filter((item): item is string => typeof item === 'string');
}

/** Accepts comma-separated slugs (`?platform=ps4,ps5`) and repeated keys (a GET form without JS). */
function slugs(value: QueryValue): string[] {
    const all = strings(value)
        .flatMap((item) => item.split(','))
        .map((slug) => slug.trim())
        .filter((slug) => slug !== '');

    return [...new Set(all)];
}

function first(value: QueryValue): string | undefined {
    return strings(value).find((item) => item !== '');
}

export function parseListingQuery(query: LocationQuery): ListingQuery {
    return {
        platform: slugs(query.platform),
        type: slugs(query.type),
        activation_service: slugs(query.activation_service),
        region: slugs(query.region),
        sort: first(query.sort),
        page: first(query.page),
    };
}

/** Builds the listing state from the filter form; a new filter or sort starts again at page 1. */
export function listingQueryFromForm(form: FormData): ListingQuery {
    const values = (name: string) =>
        form.getAll(name).filter((item) => typeof item === 'string');

    return {
        platform: slugs(values('platform')),
        type: slugs(values('type')),
        activation_service: slugs(values('activation_service')),
        region: slugs(values('region')),
        sort: first(values('sort')),
    };
}

/**
 * Canonical query string: comma-separated slugs, no empty values and no
 * `page=1`. Used both for the storefront URL and for the API request.
 */
export function serializeListingQuery(
    query: ListingQuery,
): Record<string, string> {
    const serialized: Record<string, string> = {};

    for (const facet of listingFacets) {
        if (query[facet].length > 0) {
            serialized[facet] = query[facet].join(',');
        }
    }

    if (query.sort !== undefined) {
        serialized.sort = query.sort;
    }

    if (query.page !== undefined && query.page !== '1') {
        serialized.page = query.page;
    }

    return serialized;
}

export function withPage(query: ListingQuery, page: number): ListingQuery {
    return { ...query, page: String(page) };
}

/** Where the product listing lives. Every link to the catalog goes through `catalogLink`. */
export const catalogPath = '/catalogo';

/** A link to the catalog with some facets (and optionally a sort) already applied. */
export function catalogLink(
    filters: Partial<Record<ListingFacet, readonly string[]>> & {
        sort?: string;
    } = {},
): { path: string; query: Record<string, string> } {
    return {
        path: catalogPath,
        query: serializeListingQuery({
            platform: [...(filters.platform ?? [])],
            type: [...(filters.type ?? [])],
            activation_service: [...(filters.activation_service ?? [])],
            region: [...(filters.region ?? [])],
            sort: filters.sort,
        }),
    };
}
