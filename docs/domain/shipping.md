# Shipping domain model

Status: **decided** (2026-11-24), implementation in progress (phase 5): customer addresses implemented. Orders live in [`orders.md`](orders.md).

## Decisions

- **Home delivery only.** No in-store pickup: every order with physical lines carries a shipping address. One delivery method keeps the checkout, the panel and the emails to one branch.
- **Flat shipping fee**, `config('orders.shipping_cost')` (3 990 CLP), stored on the order as `orders.shipping_cost` (0 without physical lines) and added to `orders.total`. Webpay charges `total`, so the fee is fixed when the order is placed, before any payment attempt. Per-region rates were rejected for now: a table to maintain and a total that moves with the address.
- **`completed` on delivery.** An order with physical lines stays `paid` while it ships and becomes `completed` when the shipment is delivered, in the same transaction. `completed` keeps meaning "everything delivered" for every order, and the 10-day withdrawal period counts from `delivered_at`.
- **One shipment per order** (`shipments`, 1:1 with `orders`): an order is one parcel. The order keeps a single status (the rejection of separate `payment_status`/`fulfillment_status` in [`orders.md`](orders.md#rejected-alternatives) stands); the shipment has its own: `pending` (to ship) → `shipped` → `delivered`.
- **Address snapshot.** The shipment copies the address when the order is placed; editing or deleting the saved address never changes an order.

## Customer addresses

- `customer_addresses`: `user_id` (cascade), `public_id` (ULID, the only identifier the API exposes), `recipient_name`, `phone`, `region`, `commune`, `street`, `street_number`, `apartment` (optional).
- At most 5 per customer, no default address. Deleted for real: orders keep their own copy.
- **Regions:** `ChileRegion` enum with the 16 ISO 3166-2:CL codes (`AP`, `TA`, … `MA`). Not called `Region`: that name is the game key region of the catalog. **Communes:** the 346 communes listed per region in `ChileRegion::communes()`; the commune must belong to the region (checked by `AddressData::validated()`, `tests/Unit/ChileRegionTest.php` counts them).
- **Phone:** Chilean mobile, normalized to `+569XXXXXXXX` (accepts spaces, `+56`, `56` or a bare `9XXXXXXXX`).
- **API** (`auth:sanctum`, own addresses only, another customer's answers 404): `GET|POST /api/v1/account/addresses`, `PUT|DELETE /api/v1/account/addresses/{public_id}`, with `AddressResource` (`public_id`, recipient, phone, `region{code,name}`, commune, street, number, apartment). Public `GET /api/v1/shipping` serves `{cost, regions: [{code, name, communes}]}` for the address form and the checkout.
- **Implementation:** `CreateCustomerAddress` (locks the customer's row to count), `UpdateCustomerAddress`, `DeleteCustomerAddress` in `app/Actions/Customers`; CHECKs for region, phone format and blank text in the migration.

## Shipments

- `shipments`: `order_id` (unique, cascade), the address snapshot, `status`, `carrier`, `tracking_number`, `shipped_at`, `delivered_at`.
- **Carriers:** `Carrier` enum (Starken, Chilexpress, Blue Express, Correos de Chile), each with its tracking URL template.
- **Database rules:** CHECKs tie `carrier`, `tracking_number` and `shipped_at` to `shipped`/`delivered` and `delivered_at` to `delivered`; a trigger allows only `pending → shipped → delivered` and `shipped` only while the order is `paid`; the order transition `paid → completed` is rejected while a shipment is not delivered; a deferred constraint trigger requires a shipment for an order with physical lines and forbids one without them.
- **Admin panel:** mark a paid order as shipped (carrier and tracking number), then as delivered.
- **Shipping email:** queued after commit when the order ships, like the payment confirmation; the job carries only the order id and never logs the address.
