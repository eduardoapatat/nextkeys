import type { CartLine } from '~/utils/cart';

/**
 * The cart, kept in the `nk_cart` cookie so SSR renders it too. Only slugs
 * and quantities: prices, stock and publication are checked by the API when
 * the order is placed. See docs/domain/storefront.md, "Cart".
 */
export function useCart() {
    const cookie = useCookie<unknown>('nk_cart', {
        default: () => [],
        sameSite: 'lax',
        maxAge: 60 * 60 * 24 * 30,
    });
    const lines = useState<CartLine[]>('cart', () => parseCart(cookie.value));

    function save(cart: CartLine[]) {
        lines.value = cart;
        cookie.value = cart;
    }

    return {
        lines: readonly(lines),
        count: computed(() => cartCount(lines.value)),
        add: (slug: string, quantity: number) =>
            save(addToCart(lines.value, slug, quantity)),
        update: (slug: string, quantity: number) =>
            save(updateCartQuantity(lines.value, slug, quantity)),
        remove: (slug: string) => save(removeFromCart(lines.value, slug)),
        clear: () => save([]),
    };
}
