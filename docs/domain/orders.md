# Orders domain model

Status: **model implemented** (2026-11-20): tables, rules, reservation, expiry and `MarkOrderPaid` as a domain Action. No HTTP endpoints yet (checkout comes with Sanctum in phase 4, the webhook with `PaymentGateway`). Phase 3. Payments (`PaymentGateway`, Stripe, webhooks) and key delivery by email get their own sections when designed.

## Overview

```
User (customer)
 └─ Orders                       ← one checkout, one payment
     └─ OrderItems               ← one line per product, with quantity and a snapshot
         └─ ProductKeys          ← keys reserved or sold for that line (key-delivered types only)
```

- An order belongs to a `customer` user (`user_id`, required). No guest checkout: "My library" (phase 4) needs an account, and admins cannot buy (see [`users.md`](users.md)).
- Money is integer CLP, like `products.price`. One currency, so no currency column.

## Tables

### `orders`

| column | notes |
|---|---|
| `user_id` | FK to `users`, required |
| `reference` | Public identifier for the API, URLs and emails (never the `id`, the public API exposes no ids). `NK-` + 6 random Crockford base32 characters (e.g. `NK-7QF2K9`), unique, regenerated on collision. A ULID was rejected: unreadable for customers and support |
| `status` | `OrderStatus` (see *Statuses*), CHECK |
| `total` | Integer CLP, sum of the lines' `line_total`. `CHECK (total > 0)` |
| `expires_at` | End of the reservation: `created_at + 15 min`. The only expiry of the order's stock (keys have no expiry of their own) |
| `paid_at`, `completed_at`, `cancelled_at` | Nullable timestamps of each transition |

### `order_items`

One row per product in the order.

| column | notes |
|---|---|
| `order_id` | FK to `orders`, cascade on delete |
| `product_id` | FK to `products`, **restrict**: a product with orders cannot be deleted; it is archived instead (`DeleteProduct` already returns that error) |
| `quantity` | 1 to 5 (`CHECK`): one checkout cannot hold a product's whole stock for 15 minutes. Applies to every type |
| `unit_price` | Product price when the order was created. `CHECK (unit_price > 0)` |
| `line_total` | `unit_price * quantity`, CHECK |
| `title_name`, `product_type` | Snapshot for receipts and "My library": the line keeps saying what was bought if the product is later renamed or archived |

`(order_id, product_id)` is unique: the same product twice is one line with a larger quantity.

### `product_keys` (changes)

- New `order_item_id`, nullable FK to `order_items`.
- CHECK `product_keys_order_item_matches_status`: `available` ⇔ `order_item_id IS NULL`. A reserved or sold key always knows its order; an available key never does.
- Composite FK `(order_item_id, product_id)` → `order_items(id, product_id)`: a key can only belong to a line of its own product.
- Trigger `product_keys_order_item_quantity` rejects assigning more keys to a line than its `quantity`.
- `reserved_until` on the key (the catalog reminder) was replaced by `orders.expires_at`: one expiry per order, not one per key that could drift apart.

## Statuses

`OrderStatus`: `pending`, `paid`, `completed`, `cancelled`, `expired`.

```
pending ──payment──▶ paid ──delivery──▶ completed
   │
   ├──customer cancels / payment fails──▶ cancelled
   └──reservation expires──────────────▶ expired
```

- `pending`: created at checkout; its stock is held until `expires_at`.
- `paid`: payment confirmed (webhook). In the same transaction the reserved keys become `sold`.
- `completed`: everything delivered. An order with only key-delivered lines goes `paid` → `completed` in the same transaction (the keys are assigned; the email is a notification, its failure does not undo the delivery). An order with physical lines stays `paid` until it ships (phase 5).
- `cancelled` / `expired`: stock released (see *Reservation*).
- Transitions are enforced in the database (a `BEFORE UPDATE OF status` trigger on `orders`), like the key lifecycle. `refunded` is not part of phase 3; it is added with refunds.

## Reservation

- **At checkout** (creating the `pending` order), in one transaction:
  - Keys: `SELECT … WHERE product_id = ? AND status = 'available' … LIMIT n FOR UPDATE SKIP LOCKED`, then set `reserved` and `order_item_id`. Fewer than `n` rows ⇒ the whole checkout fails with a validation error (no partial orders).
  - Physical: `UPDATE products SET stock = stock - n WHERE id = ? AND stock >= n`; 0 rows ⇒ the same error. A box is held during checkout, symmetric with keys.
- **Expiry: 15 minutes.** A scheduled job runs every minute and moves `pending` orders past `expires_at` to `expired`, releasing their stock: keys back to `available` with `order_item_id = NULL`, physical stock added back. Cancelling a `pending` order releases stock the same way.
- **Late payment** (the webhook confirms an order already `expired`): the order tries to reserve its stock again; if it succeeds it goes `expired` → `paid`. Otherwise it stays `expired` and needs a refund (see *Open questions*).
- **Key lifecycle trigger** on `product_keys` (`BEFORE UPDATE OF status`): allowed `available` → `reserved`, `reserved` → `available`, `reserved` → `sold`; leaving `sold` is rejected. Tested by updating the row directly, bypassing the Action.

## Mixed orders

- One order can mix key-delivered and physical products, with one payment.
- Keys are delivered on payment; physical lines wait for shipping (phase 5), and the order stays `paid` until then. Order status is not per line: shipping status per line, if needed, comes with phase 5.
- Splitting a mixed cart into two orders was rejected: one payment spanning two orders complicates webhooks and refunds.

## Implementation

- **Actions** in `app/Actions/Orders`: `PlaceOrder` (validates role, empty cart, published products, quantity, duplicates; then one transaction), `CancelOrder`, `ExpireOrders`, `MarkOrderPaid`, and the shared `ReserveOrderStock` / `ReleaseOrderStock`. Errors are Spanish `ValidationException`s keyed `items.{i}.product_id` / `items.{i}.quantity`; a payment that cannot be taken throws `OrderCannotBePaid`.
- **Locking.** `ReserveOrderStock` walks lines in `product_id` order (no deadlocks between checkouts): keys with one `UPDATE … WHERE id IN (SELECT … FOR UPDATE SKIP LOCKED)`, boxes with `UPDATE … SET stock = stock - n WHERE stock >= n`. `CancelOrder` and `MarkOrderPaid` lock the order row first; `ExpireOrders` takes pending orders `FOR UPDATE SKIP LOCKED`, so it skips an order whose payment is being recorded.
- **Reference.** `Order::generateReference()`; `PlaceOrder` inserts in a savepoint and retries on a unique violation (an error would otherwise abort the Postgres transaction).
- **Expiry.** `orders:expire` runs `ExpireOrders` every minute (`routes/console.php`). `composer dev` starts `schedule:work` (`AppServiceProvider::configureDevCommands`).
- **Settings.** `config/orders.php`: `reservation_minutes`, `max_quantity_per_line` (the CHECK repeats 5).
- **Factories.** `ProductKeyFactory::reserved()` / `sold()` create a one-unit line of a pending / completed order, as the CHECK requires.
- **Not covered by tests:** concurrency across two connections (`SKIP LOCKED`, the stock `UPDATE`), like the catalog's advisory lock.

## Rejected alternatives

- **One `order_items` row per unit**: simpler 1:1 link to a key, but every list and total has to group rows.
- **Pivot `order_item_keys`**: leaves `product_keys` untouched at the cost of one more table and join; the nullable FK plus a CHECK states the same rule.
- **Separate `payment_status` and `fulfillment_status`**: more expressive for mixed orders, but many more combinations to validate; one status is enough until shipping exists.
- **No hold, assign keys on payment**: two customers could pay for the last key and one would need a refund.
- **30-minute hold**: blocks stock twice as long for little gain with card payments.

## Open questions

- **Stripe session lifetime.** Stripe Checkout sessions last at least 30 minutes, longer than the 15-minute hold. Expiring the order should also expire its Stripe session (`checkout.sessions.expire`) so late payments are rare; decide with the `PaymentGateway` design.
- **Late payment without stock**: automatic refund through the gateway or manual from the panel (with `refunded`)?
