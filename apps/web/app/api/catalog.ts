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

export async function fetchCatalogFilters(): Promise<CatalogFilters> {
    const response = await useNuxtApp().$api<{ data: CatalogFilters }>(
        '/catalog/filters',
    );

    return response.data;
}
