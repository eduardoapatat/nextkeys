# Orders domain model

Status: **model implemented** (2026-11-20): tables, rules, reservation, expiry and `MarkOrderPaid` as a domain Action, called by the Webpay return and reconciliation. No storefront endpoints yet (checkout comes with Sanctum in phase 4). Phase 3. Payments live in [`payments.md`](payments.md); key delivery by email in *Confirmation email and key delivery* (implemented 2026-11-20).

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
- Transitions are enforced in the database (a `BEFORE UPDATE OF status` trigger on `orders`), like the key lifecycle. There is no `refunded` order status: a refund belongs to the payment attempt, and the order keeps its own status (see [`payments.md`](payments.md)).

## Reservation

- **At checkout** (creating the `pending` order), in one transaction:
  - Keys: `SELECT … WHERE product_id = ? AND status = 'available' … LIMIT n FOR UPDATE SKIP LOCKED`, then set `reserved` and `order_item_id`. Fewer than `n` rows ⇒ the whole checkout fails with a validation error (no partial orders).
  - Physical: `UPDATE products SET stock = stock - n WHERE id = ? AND stock >= n`; 0 rows ⇒ the same error. A box is held during checkout, symmetric with keys.
- **Expiry: 15 minutes.** A scheduled job runs every minute and moves `pending` orders past `expires_at` to `expired`, releasing their stock: keys back to `available` with `order_item_id = NULL`, physical stock added back. Cancelling a `pending` order releases stock the same way.
- **Late payment** (the customer comes back from the gateway after the order `expired`): before the payment is committed, `ReopenExpiredOrder` reserves the stock again and moves the order `expired` → `pending` with a fresh hold (decided 2026-11-20, replaces `expired` → `paid`). Without stock the order stays `expired` and the payment is not committed. Starting a payment attempt also extends the hold (see [`payments.md`](payments.md)).
- **Key lifecycle trigger** on `product_keys` (`BEFORE UPDATE OF status`): allowed `available` → `reserved`, `reserved` → `available`, `reserved` → `sold`; leaving `sold` is rejected. Tested by updating the row directly, bypassing the Action.

## Mixed orders

- One order can mix key-delivered and physical products, with one payment.
- Keys are delivered on payment; physical lines wait for shipping (phase 5), and the order stays `paid` until then. Order status is not per line: shipping status per line, if needed, comes with phase 5.
- Splitting a mixed cart into two orders was rejected: one payment spanning two orders complicates webhooks and refunds.

## Implementation

- **Actions** in `app/Actions/Orders`: `PlaceOrder` (validates role, empty cart, published products, quantity, duplicates; then one transaction), `CancelOrder`, `ExpireOrders`, `MarkOrderPaid`, `LockPayableOrder` / `ReopenExpiredOrder` (see [`payments.md`](payments.md)), and the shared `ReserveOrderStock` / `ReleaseOrderStock`. Errors are Spanish `ValidationException`s keyed `items.{i}.product_id` / `items.{i}.quantity`; a payment that cannot be taken throws `OrderCannotBePaid`.
- **Locking.** `ReserveOrderStock` walks lines in `product_id` order (no deadlocks between checkouts): keys with one `UPDATE … WHERE id IN (SELECT … FOR UPDATE SKIP LOCKED)`, boxes with `UPDATE … SET stock = stock - n WHERE stock >= n`. `CancelOrder` and `MarkOrderPaid` lock the order row first (a payment through `LockPayableOrder`, with a 3-second lock timeout); `ExpireOrders` takes pending orders `FOR UPDATE SKIP LOCKED`, so it skips an order whose payment is being recorded.
- **Reference.** `Order::generateReference()`; `PlaceOrder` inserts in a savepoint and retries on a unique violation (an error would otherwise abort the Postgres transaction).
- **Expiry.** `orders:expire` runs `ExpireOrders` every minute (`routes/console.php`). `composer dev` starts `schedule:work` (`AppServiceProvider::configureDevCommands`).
- **Settings.** `config/orders.php`: `reservation_minutes`, `max_quantity_per_line` (the CHECK repeats 5).
- **Factories.** `ProductKeyFactory::reserved()` / `sold()` create a one-unit line of a pending / completed order, as the CHECK requires.
- **Not covered by tests:** concurrency across two connections (`SKIP LOCKED`, the stock `UPDATE`), like the catalog's advisory lock.

## Admin panel (orders)

Decided and implemented on 2026-11-20.

- **List:** a data table with reference, customer (name and email), status badge, total (CLP), creation date and, for pending orders, `expires_at`. Filter by status, search by reference or customer email, sort by date and total. Same pattern as the product list.
- **Detail:** header (reference, customer, status, `paid_at` / `completed_at` / `cancelled_at`, `expires_at`) and lines from the snapshot (title name, type, quantity, unit price, line total). Digital lines list their keys by `code_hint` and status; the code is never shown, as everywhere else in the panel.
- **Actions:** cancel a pending order (`CancelOrder`, with confirmation). Mark as paid (`MarkOrderPaid`) by hand to test without a payment gateway, **only in the local environment**: in production it would deliver keys without charging. Gated by `orders.manual_payments` (`ORDERS_MANUAL_PAYMENTS`, default `false`): when off, the route returns 404 and the button is hidden.
- **Implementation.** `OrderController` (`routes/orders.php`, bound by `{order:reference}`: URLs never carry the id), `OrdersTable`, pages `orders/Index` and `orders/Show`. `OrderCannotBePaid` is turned into a Spanish validation error only in the panel; the Action keeps the domain exception for the webhook. Confirmations use the generic `ConfirmActionButton` (POST or DELETE); `ConfirmDeleteButton` wraps it.

## Confirmation email and key delivery

Decided and implemented on 2026-11-20.

- **One email per paid order**, the payment confirmation: key-delivered lines carry their codes; physical lines say they will ship (the shipping notice comes with phase 5). Physical-only orders get it too, without keys. Spanish, Markdown (`App\Mail\OrderPaidMail`, `resources/views/mail/orders/paid.blade.php`).
- **Content:** reference, payment date (America/Santiago), lines from the snapshot, each code with its activation service (or platforms) and region, the total in CLP, the receipt of the `authorized` payment attempt (authorization code, payment type and installments, last 4 digits, date; omitted for a manual local payment) and the withdrawal conditions (below).
- **Dispatch, from one place.** `MarkOrderPaid` dispatches `App\Jobs\SendOrderPaidEmail` inside its locked callback; the job is `ShouldQueueAfterCommit`, so it is queued only when the **outermost** transaction commits (the `LockPayableOrder` transaction of `ConfirmPaymentAttempt` and the reconciliation) and dropped on rollback. The Webpay return, the reconciliation and the panel's manual payment all go through `MarkOrderPaid`; a repeated confirmation (`AlreadyPaid`) never enters the callback, so it sends nothing. An arch test keeps the job dispatched only from `MarkOrderPaid` and `ResendOrderPaidEmail`.
- **Record.** `orders.paid_email_sent_at` (last successful send) and `orders.paid_email_resends` (0 to 3, CHECK). Delivery is at least once: if the send succeeds but recording it fails, the retry sends again; harmless, the same keys to the same owner.
- **Keys never leak.** The job serializes only the order id; `OrderPaidMail` is never queued (arch test) and decrypts the codes while rendering. A send failure is rethrown as `OrderPaidEmailFailed`, without the original exception (a rendering error or a stack trace with arguments could carry the codes into the logs and `failed_jobs`); only SMTP transport messages are kept. The job refuses the `log` transport (it would write the codes to `laravel.log`).
- **Retries:** `tries` 5, backoff 1, 5, 15 and 60 minutes, `timeout` 30 s; `failed()` logs the reference and the exception class only.
- **Resend from the panel** (order detail): paid or completed orders, always to the customer's current email (never another address), at most `orders.max_paid_email_resends` (3) times and once a minute per order (`throttle:paid-email-resend`). `ResendOrderPaidEmail` locks the order and counts the resend.
- **Withdrawal (derecho a retracto).** Delivered digital keys are excluded: "Las claves digitales entregadas no admiten derecho a retracto (exclusión informada antes del pago, art. 3 bis letra b) de la Ley 19.496 y Decreto 52 de 2024)", keeping the legal warranty if a key does not work. Physical products: 10 days from receipt (shown only when the order has them). Same practice as the Chilean key stores reviewed (ReveChl, Soft Store, CJM Digitales, ChileCodes); the storefront must show the exclusion before payment (phase 4, see [`payments.md`](payments.md#chile)).
- **Local check:** `MAIL_MAILER=smtp` to Mailpit (`127.0.0.1:1025`, UI on http://localhost:8025) and `QUEUE_CONNECTION=database`; `composer dev` runs `queue:listen`.
- **Phase 4:** "My library" (Sanctum endpoint listing the customer's paid lines, codes revealed on request and never in the listing, with its API contract), a link to it from the email, and the withdrawal notice in the Nuxt checkout.

## Rejected alternatives

- **One `order_items` row per unit**: simpler 1:1 link to a key, but every list and total has to group rows.
- **Pivot `order_item_keys`**: leaves `product_keys` untouched at the cost of one more table and join; the nullable FK plus a CHECK states the same rule.
- **Separate `payment_status` and `fulfillment_status`**: more expressive for mixed orders, but many more combinations to validate; one status is enough until shipping exists.
- **No hold, assign keys on payment**: two customers could pay for the last key and one would need a refund.
- **30-minute hold**: blocks stock twice as long for little gain with card payments.

## Resolved questions

- **Late payment without stock** (resolved 2026-11-20). With Transbank Webpay Plus the store does not commit an order that cannot be paid, so the customer is never charged. The one payment that can still need a refund (committed but not recorded) is refunded automatically by the reconciliation job; the refund is recorded on the payment attempt, not as an order status (see [`payments.md`](payments.md)).
