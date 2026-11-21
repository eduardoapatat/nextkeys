import { fetchProduct } from '~/api/catalog';
import type { ProductDetail } from '~/api/schemas';

/**
 * The cart lines with their current product from the API: `product` is null
 * when it no longer exists, and `available` is false without stock. Prices
 * here are informative; the API sets the order's total.
 */
export async function useCartProducts() {
    const cart = useCart();
    const slugs = computed(() => cart.lines.value.map((line) => line.slug));

    const { data: products, pending } = await useAsyncData(
        () => `cart:${slugs.value.join(',')}`,
        async () => {
            const entries = await Promise.all(
                slugs.value.map(async (slug) => {
                    try {
                        return [slug, await fetchProduct(slug)] as const;
                    } catch (error) {
                        if (toApiError(error).statusCode === 404) {
                            return [slug, null] as const;
                        }

                        throw error;
                    }
                }),
            );

            return Object.fromEntries(entries) as Record<
                string,
                ProductDetail | null
            >;
        },
    );

    const lines = computed(() =>
        cart.lines.value.map((line) => {
            const product = products.value?.[line.slug] ?? null;

            return { ...line, product, available: Boolean(product?.in_stock) };
        }),
    );

    const total = computed(() =>
        lines.value.reduce(
            (sum, line) =>
                sum + (line.product ? line.product.price * line.quantity : 0),
            0,
        ),
    );

    const ready = computed(
        () =>
            !pending.value &&
            lines.value.length > 0 &&
            lines.value.every((line) => line.available),
    );

    return { cart, lines, total, pending, ready };
}
