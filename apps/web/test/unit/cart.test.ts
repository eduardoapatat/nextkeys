import { describe, expect, it } from 'vitest';
import {
    addToCart,
    cartCount,
    parseCart,
    removeFromCart,
    updateCartQuantity,
} from '../../app/utils/cart';

describe('cart', () => {
    it('adds a new line and sums an existing one, up to 5 units', () => {
        const cart = addToCart([], 'starfall', 2);

        expect(cart).toEqual([{ slug: 'starfall', quantity: 2 }]);
        expect(addToCart(cart, 'starfall', 2)).toEqual([
            { slug: 'starfall', quantity: 4 },
        ]);
        expect(addToCart(cart, 'starfall', 9)).toEqual([
            { slug: 'starfall', quantity: 5 },
        ]);
    });

    it('holds at most 10 lines', () => {
        const full = Array.from({ length: 10 }, (_, i) => ({
            slug: `p${i}`,
            quantity: 1,
        }));

        expect(addToCart(full, 'p11', 1)).toBe(full);
        expect(addToCart(full, 'p0', 1)[0]).toEqual({
            slug: 'p0',
            quantity: 2,
        });
    });

    it('updates a quantity between 1 and 5 and removes a line', () => {
        const cart = [
            { slug: 'a', quantity: 1 },
            { slug: 'b', quantity: 3 },
        ];

        expect(updateCartQuantity(cart, 'b', 0)).toEqual([
            { slug: 'a', quantity: 1 },
            { slug: 'b', quantity: 1 },
        ]);
        expect(updateCartQuantity(cart, 'b', 8)[1]).toEqual({
            slug: 'b',
            quantity: 5,
        });
        expect(removeFromCart(cart, 'a')).toEqual([{ slug: 'b', quantity: 3 }]);
        expect(cartCount(cart)).toBe(4);
    });

    it('reads an invalid cookie as an empty cart and drops invalid lines', () => {
        expect(parseCart('not json')).toEqual([]);
        expect(parseCart({ slug: 'a' })).toEqual([]);
        expect(
            parseCart([
                { slug: 'a', quantity: 2 },
                { slug: '', quantity: 1 },
                { slug: 'b', quantity: 'x' },
                { slug: 'c', quantity: 9 },
                { slug: 'a', quantity: 1 },
            ]),
        ).toEqual([
            { slug: 'a', quantity: 2 },
            { slug: 'c', quantity: 5 },
        ]);
    });
});
