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
    cover_band: v.boolean(),
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

const paginationFields = {
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
};

export const ProductPageSchema = v.strictObject({
    data: v.array(ProductSummarySchema),
    ...paginationFields,
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
export const orderStatuses = [
    'pending',
    'paid',
    'completed',
    'cancelled',
    'expired',
] as const;

export const paymentStatuses = [
    'initialized',
    'authorized',
    'rejected',
    'cancelled',
    'aborted',
    'refunded',
] as const;

const OrderSchemaData = v.strictObject({
    reference: v.string(),
    status: v.strictObject({
        slug: v.picklist(orderStatuses),
        name: v.string(),
    }),
    total: v.pipe(v.number(), v.integer()),
    created_at: v.string(),
    expires_at: v.string(),
    paid_at: v.nullable(v.string()),
    can_pay: v.boolean(),
    has_physical_items: v.boolean(),
    items: v.array(
        v.strictObject({
            product_slug: v.string(),
            title_name: v.string(),
            type: ProductTypeReference,
            quantity: v.number(),
            unit_price: v.number(),
            line_total: v.number(),
        }),
    ),
    last_payment: v.nullable(
        v.strictObject({
            status: v.strictObject({
                slug: v.picklist(paymentStatuses),
                name: v.string(),
            }),
            amount: v.number(),
            authorization_code: v.nullable(v.string()),
            payment_type: v.nullable(v.string()),
            installments_number: v.nullable(v.number()),
            installments_amount: v.nullable(v.number()),
            card_last_digits: v.nullable(v.string()),
            transaction_date: v.nullable(v.string()),
        }),
    ),
});

/** A customer's order (`/orders/{reference}`, and the result of placing one). */
export const OrderSchema = v.strictObject({ data: OrderSchemaData });

export const OrderPageSchema = v.strictObject({
    data: v.array(OrderSchemaData),
    ...paginationFields,
});

/** Where the storefront posts `token_ws` to start the Webpay payment. */
export const PaymentRedirectSchema = v.strictObject({
    data: v.strictObject({ url: v.string(), token: v.string() }),
});

const LibraryItemData = v.strictObject({
    order_reference: v.string(),
    paid_at: v.nullable(v.string()),
    title_name: v.string(),
    type: ProductTypeReference,
    quantity: v.number(),
    product: v.strictObject({
        slug: v.string(),
        platforms: v.array(Reference),
        region: v.nullable(Reference),
        activation_service: v.nullable(Reference),
    }),
    keys: v.array(v.strictObject({ hint: v.string(), revealed: v.boolean() })),
});

/** "My library": key lines of paid orders, with hints only. */
export const LibraryPageSchema = v.strictObject({
    data: v.array(LibraryItemData),
    ...paginationFields,
});

/** The codes of one library line, revealed on request. */
export const LibraryKeysSchema = v.strictObject({
    data: v.strictObject({
        keys: v.array(v.strictObject({ hint: v.string(), code: v.string() })),
    }),
});

export type LibraryItem = v.InferOutput<typeof LibraryItemData>;
export type LibraryPage = v.InferOutput<typeof LibraryPageSchema>;
export type RevealedKey = v.InferOutput<
    typeof LibraryKeysSchema
>['data']['keys'][number];
export type Order = v.InferOutput<typeof OrderSchemaData>;
export type OrderPage = v.InferOutput<typeof OrderPageSchema>;
export type PaymentRedirect = v.InferOutput<
    typeof PaymentRedirectSchema
>['data'];
export type Customer = v.InferOutput<typeof CustomerSchema>['data'];
export type ValidationErrorBody = v.InferOutput<typeof ValidationErrorSchema>;
