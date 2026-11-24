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

// A filter option with how many products it would list given the other
// filters (contracts/api/v1/catalog.filters.json).
const FacetOption = v.strictObject({
    ...Reference.entries,
    count: v.pipe(v.number(), v.integer()),
});

const ProductTypeFacetOption = v.strictObject({
    ...ProductTypeReference.entries,
    count: v.pipe(v.number(), v.integer()),
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
        platforms: v.array(FacetOption),
        types: v.array(ProductTypeFacetOption),
        activation_services: v.array(FacetOption),
        regions: v.array(FacetOption),
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
export type FacetOption = v.InferOutput<typeof FacetOption>;
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

export const shipmentStatuses = ['pending', 'shipped', 'delivered'] as const;

/** A Chilean region by its ISO 3166-2:CL code. */
const ChileRegionReference = v.strictObject({
    code: v.string(),
    name: v.string(),
});

const addressFields = {
    recipient_name: v.string(),
    phone: v.string(),
    region: ChileRegionReference,
    commune: v.string(),
    street: v.string(),
    street_number: v.string(),
    apartment: v.nullable(v.string()),
};

const AddressData = v.strictObject({
    public_id: v.string(),
    ...addressFields,
});

/** A shipping address saved in the customer's account. */
export const AddressSchema = v.strictObject({ data: AddressData });

export const AddressListSchema = v.strictObject({ data: v.array(AddressData) });

/** The flat shipping fee and the regions with their communes. */
export const ShippingSchema = v.strictObject({
    data: v.strictObject({
        cost: v.pipe(v.number(), v.integer()),
        regions: v.array(
            v.strictObject({
                code: v.string(),
                name: v.string(),
                communes: v.array(v.string()),
            }),
        ),
    }),
});

/** An order's parcel: the address copied at checkout and where it is. */
const ShipmentData = v.strictObject({
    status: v.strictObject({
        slug: v.picklist(shipmentStatuses),
        name: v.string(),
    }),
    address: v.strictObject(addressFields),
    carrier: v.nullable(Reference),
    tracking_number: v.nullable(v.string()),
    tracking_url: v.nullable(v.string()),
    shipped_at: v.nullable(v.string()),
    delivered_at: v.nullable(v.string()),
    returnable_until: v.nullable(v.string()),
});

const OrderSchemaData = v.strictObject({
    reference: v.string(),
    status: v.strictObject({
        slug: v.picklist(orderStatuses),
        name: v.string(),
    }),
    total: v.pipe(v.number(), v.integer()),
    shipping_cost: v.pipe(v.number(), v.integer()),
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
    shipment: v.nullable(ShipmentData),
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
export type Shipment = v.InferOutput<typeof ShipmentData>;
export type Address = v.InferOutput<typeof AddressData>;
export type Shipping = v.InferOutput<typeof ShippingSchema>['data'];
export type OrderPage = v.InferOutput<typeof OrderPageSchema>;
export type PaymentRedirect = v.InferOutput<
    typeof PaymentRedirectSchema
>['data'];
export type Customer = v.InferOutput<typeof CustomerSchema>['data'];
export type ValidationErrorBody = v.InferOutput<typeof ValidationErrorSchema>;
