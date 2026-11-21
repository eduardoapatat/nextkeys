/** One cart line: a product by its slug and how many units. */
export interface CartLine {
    slug: string;
    quantity: number;
}

/** The API's `orders.max_quantity_per_line`. */
export const maxQuantityPerLine = 5;

/** Keeps the cookie small; far more than a real checkout needs. */
export const maxCartLines = 10;

function clampQuantity(quantity: number): number {
    return Math.min(maxQuantityPerLine, Math.max(1, Math.trunc(quantity)));
}

/** Adds units of a product: an existing line sums, capped at 5 units. */
export function addToCart(
    cart: CartLine[],
    slug: string,
    quantity: number,
): CartLine[] {
    if (cart.some((line) => line.slug === slug)) {
        return cart.map((line) =>
            line.slug === slug
                ? { slug, quantity: clampQuantity(line.quantity + quantity) }
                : line,
        );
    }

    if (cart.length >= maxCartLines) {
        return cart;
    }

    return [...cart, { slug, quantity: clampQuantity(quantity) }];
}

export function updateCartQuantity(
    cart: CartLine[],
    slug: string,
    quantity: number,
): CartLine[] {
    return cart.map((line) =>
        line.slug === slug ? { slug, quantity: clampQuantity(quantity) } : line,
    );
}

export function removeFromCart(cart: CartLine[], slug: string): CartLine[] {
    return cart.filter((line) => line.slug !== slug);
}

export function cartCount(cart: CartLine[]): number {
    return cart.reduce((total, line) => total + line.quantity, 0);
}

/**
 * The cart stored in the cookie. The cookie comes from the browser, so
 * anything that is not a list of valid lines is dropped.
 */
export function parseCart(value: unknown): CartLine[] {
    if (!Array.isArray(value)) {
        return [];
    }

    let cart: CartLine[] = [];

    for (const line of value) {
        if (
            typeof line === 'object' &&
            line !== null &&
            typeof line.slug === 'string' &&
            line.slug !== '' &&
            typeof line.quantity === 'number' &&
            Number.isFinite(line.quantity) &&
            !cart.some((existing) => existing.slug === line.slug)
        ) {
            cart = addToCart(cart, line.slug, line.quantity);
        }
    }

    return cart;
}
