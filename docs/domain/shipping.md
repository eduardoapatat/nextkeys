# Shipping domain model

Status: **decided** (2026-11-24), implementation in progress (phase 5): customer addresses, checkout, and shipping from the panel implemented. Orders live in [`orders.md`](orders.md).

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
- **Checkout:** `POST /api/v1/orders` takes `shipping_address` (an address `public_id`): required with physical lines, refused without them, and only the customer's own (422 `shipping_address`). `PlaceOrder` creates the shipment snapshot and sets `shipping_cost` in the same transaction. `OrderResource` adds `shipping_cost` and `shipment` (status, address, carrier, tracking number and URL, `shipped_at`, `delivered_at`, `returnable_until`). The payment email adds the fee and the address.
- **Admin panel:** the order detail shows the shipment (recipient, address, courier, tracking link, dates). "Marcar como despachado" (a dialog with courier and tracking number, letters, digits and dashes) runs `ShipOrder`; "Marcar como entregado" runs `MarkOrderDelivered`, which delivers the shipment and completes the order in one transaction. Both lock the order row. The orders table has a shipping status column and filter ("Por despachar").
- **Shipping email:** `ShipOrder` dispatches `SendOrderShippedEmail` (`ShouldQueueAfterCommit`, only the order id, same retries as the payment email) with `OrderShippedMail` (never queued): courier, tracking number and link, shipped lines, address, the 10-day withdrawal and a link to the order in the storefront. A failure is rethrown as `OrderShippedEmailFailed` without the original exception, and the job refuses the `log` mailer (`MailerGuard`, shared with the payment email): the address never reaches the logs or `failed_jobs`.
- **Tracking URLs** are each courier's public tracking page; the store is fictitious, so they are not checked against real parcels.
