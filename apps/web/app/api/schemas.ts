/**
 * Shapes of the /api/v1 responses (contracts/api/v1/*.json).
 *
 * Only the tests parse with these schemas: the app imports the derived types
 * with `import type`, so valibot never reaches the bundle (ESLint enforces it).
 * After changing an API Resource, regenerate the fixtures in apps/api and
 * update these schemas until test/unit/contract.test.ts passes.
 */
import * as v from 'valibot';

const Reference = v.strictObject({
    slug: v.string(),
    name: v.string(),
});

export const productTypes = ['digital_key', 'license', 'physical'] as const;

const ProductTypeReference = v.strictObject({
    slug: v.picklist(productTypes),
    name: v.string(),
});

const productFields = {
    slug: v.string(),
    type: ProductTypeReference,
    price: v.pipe(v.number(), v.integer()),
    in_stock: v.boolean(),
    cover_url: v.nullable(v.string()),
    platforms: v.array(Reference),
    region: v.nullable(Reference),
    activation_service: v.nullable(Reference),
};

export const ProductSummarySchema = v.strictObject({
    ...productFields,
    title: v.strictObject({ name: v.string() }),
});

export const ProductDetailSchema = v.strictObject({
    data: v.strictObject({
        ...productFields,
        title: v.strictObject({ name: v.string(), description: v.string() }),
        variants: v.array(ProductSummarySchema),
    }),
});

const PaginationLink = v.strictObject({
    url: v.nullable(v.string()),
    label: v.string(),
    page: v.nullable(v.number()),
    active: v.boolean(),
});

export const ProductPageSchema = v.strictObject({
    data: v.array(ProductSummarySchema),
    links: v.strictObject({
        first: v.string(),
        last: v.string(),
        prev: v.nullable(v.string()),
        next: v.nullable(v.string()),
    }),
    meta: v.strictObject({
        current_page: v.number(),
        from: v.nullable(v.number()),
        last_page: v.number(),
        links: v.array(PaginationLink),
        path: v.string(),
        per_page: v.number(),
        to: v.nullable(v.number()),
        total: v.number(),
    }),
});

export const CatalogFiltersSchema = v.strictObject({
    data: v.strictObject({
        platforms: v.array(Reference),
        types: v.array(ProductTypeReference),
        activation_services: v.array(Reference),
        regions: v.array(Reference),
    }),
});

export const ValidationErrorSchema = v.strictObject({
    message: v.string(),
    errors: v.record(v.string(), v.array(v.string())),
});

/** An error body with only a message: a 404, or a 401 without a session. */
export const MessageSchema = v.strictObject({
    message: v.string(),
});

export const NotFoundSchema = MessageSchema;

/** The signed in storefront customer (`/auth/me`, login and registration). */
export const CustomerSchema = v.strictObject({
    data: v.strictObject({
        name: v.string(),
        email: v.string(),
        email_verified: v.boolean(),
    }),
});

export type Reference = v.InferOutput<typeof Reference>;
export type ProductType = (typeof productTypes)[number];
export type ProductSummary = v.InferOutput<typeof ProductSummarySchema>;
export type ProductDetail = v.InferOutput<typeof ProductDetailSchema>['data'];
export type ProductPage = v.InferOutput<typeof ProductPageSchema>;
export type CatalogFilters = v.InferOutput<typeof CatalogFiltersSchema>['data'];
export type Customer = v.InferOutput<typeof CustomerSchema>['data'];
export type ValidationErrorBody = v.InferOutput<typeof ValidationErrorSchema>;
