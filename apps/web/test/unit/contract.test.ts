import * as v from 'valibot';
import { describe, expect, it } from 'vitest';
import {
    CatalogFiltersSchema,
    NotFoundSchema,
    ProductDetailSchema,
    ProductPageSchema,
    ValidationErrorSchema,
} from '../../app/api/schemas';
import { fixture } from '../fixtures';

describe('API contract fixtures', () => {
    it.each([
        ['products.index', ProductPageSchema],
        ['products.index.422', ValidationErrorSchema],
        ['products.show', ProductDetailSchema],
        ['products.show.404', NotFoundSchema],
        ['catalog.filters', CatalogFiltersSchema],
    ] as const)('%s matches its schema', (name, schema) => {
        expect(() => fixture(name, schema)).not.toThrow();
    });

    it('rejects an extra key, a missing key and an unknown product type', () => {
        const product = fixture('products.show', ProductDetailSchema).data;

        const extra = { data: { ...product, stock: 3 } };
        const { slug: _slug, ...missing } = product;
        const unknownType = {
            data: { ...product, type: { slug: 'bundle', name: 'Pack' } },
        };

        expect(v.safeParse(ProductDetailSchema, extra).success).toBe(false);
        expect(
            v.safeParse(ProductDetailSchema, { data: missing }).success,
        ).toBe(false);
        expect(v.safeParse(ProductDetailSchema, unknownType).success).toBe(
            false,
        );
    });
});
