import type { CatalogFilters, ProductDetail, ProductPage } from './schemas';

/** `query` is the canonical listing query (serializeListingQuery). */
export function fetchProducts(
    query: Record<string, string>,
): Promise<ProductPage> {
    return useNuxtApp().$api<ProductPage>('/products', { query });
}

export async function fetchProduct(slug: string): Promise<ProductDetail> {
    const response = await useNuxtApp().$api<{ data: ProductDetail }>(
        `/products/${encodeURIComponent(slug)}`,
    );

    return response.data;
}

/**
 * The filter options, each with how many products it would list given the
 * other facets in `query` (the listing's facets, without sort or page).
 */
export async function fetchCatalogFilters(
    query: Record<string, string> = {},
): Promise<CatalogFilters> {
    const response = await useNuxtApp().$api<{ data: CatalogFilters }>(
        '/catalog/filters',
        { query },
    );

    return response.data;
}
