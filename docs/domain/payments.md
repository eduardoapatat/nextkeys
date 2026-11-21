# Payments domain model

Status: **implemented** (2026-11-20) in the integration environment; checkout and receipt page in phase 4. Phase 3.

## Decisions

- **First gateway: Transbank Webpay Plus** (REST API, integration environment with Transbank's public test credentials), behind the `PaymentGateway` interface. Replaces Stripe, planned until 2026-11-20. Reasons:
  - It is the expected gateway for a Chilean store; Stripe is, per most sources, not available to businesses registered in Chile.
  - Its **commit** model fits the stock reservation: the charge is only confirmed when the store commits it, so the store decides with the order locked.
  - The payment form expires after **4 minutes** in production (10 in integration), well inside the 15-minute hold.
- **PHP client: Laravel's HTTP client**, not the official `transbank/transbank-sdk` (decided 2026-11-20, confirmed after reading the SDK source). Webpay Plus needs four calls (create, commit, status, refund); they live in the Transbank implementation of `PaymentGateway` and are tested with `Http::fake`, without network. Why not the SDK:
  - The released 5.1.0 (2025-06-17) requires Guzzle 7 while this app runs Guzzle 8 (`composer require --dry-run` only resolves by downgrading `guzzlehttp/guzzle`, `psr7` and `promises`), and its CI only tests PHP 8.2. The SDK *is* maintained: version 6.0.0 (PHP >= 8.4, Guzzle ^8, CI on 8.4 and 8.5, no behavior changes) sits in a draft PR since 2026-09-10, unreleased. Revisit if it ships and the client grows.
  - Traps in its code that our client must not copy: `isApproved()` also returns true for `REVERSED`, `NULLIFIED` and `PARTIALLY_NULLIFIED`; the default timeout is 600 s (the commit window is under a minute); every exception carries the failed request **with the `Tbk-Api-Key-Secret` header**, so logging it leaks the key; network errors escape unwrapped; a 2xx with invalid JSON yields objects full of nulls without an error; the token goes into the URL unvalidated.
- **Committed but unrecorded payments are refunded automatically** (decided 2026-11-20). It is the only case that needs a refund: a commit that succeeded (`AUTHORIZED`) while the order was not recorded as paid, e.g. the server failed between the commit and `MarkOrderPaid`. The reconciliation job finds it through `status` (within the 7 days): if the order can still take the payment it records it as paid; otherwise it refunds through `PaymentGateway` at once (a full reversal while within the 3 hours), without waiting for an admin, and the panel shows the refunded payment on the order so there is a record. The refund state lives on the **payment attempt** (`refunded`), not on the order: the order keeps its own status (`expired` or `cancelled`), so `OrderStatus` gains no new transition. Rejected alternative: only flagging it in the panel for an admin to decide, which leaves the customer charged for nothing until someone acts.
- **A second gateway may come later** (Mercado Pago Checkout Pro, or Stripe) as another `PaymentGateway` implementation, to prove the interface. Not planned yet.
- **General rule for any gateway:** its payment window must be no longer than the order's reservation (`orders.expires_at`), so a late payment only happens when the gateway misbehaves.

## Checkout flow (target)

1. `PlaceOrder` creates the `pending` order and reserves its stock (done, see [`orders.md`](orders.md)).
2. The gateway creates a transaction for a new **payment attempt** of the order (its own `buy_order`, see the spec below) with the **server-side `total`**; the customer is redirected to Webpay with an HTML form that POSTs `token_ws` to the returned `url` (never in an iframe). The token lives 5 minutes.
3. Webpay sends the customer back to `return_url`, by GET or POST (an abort comes by POST in integration and GET in production), so the route accepts both and needs no CSRF token. The cases:
   - only `token_ws`: the customer paid or the bank rejected it; go to step 4 (the commit tells which).
   - `TBK_TOKEN` + `TBK_ORDEN_COMPRA` + `TBK_ID_SESION`: the customer cancelled in Webpay. **Never commit**; the attempt is cancelled and the order stays `pending`, so the customer can try again.
   - only `TBK_ORDEN_COMPRA` + `TBK_ID_SESION`: the form timed out. Never commit; the attempt is abandoned.
   - all four (`token_ws` and `TBK_TOKEN`): the customer came back from a Webpay error page (only reproducible in production). Never commit: commit only when `token_ws` arrives **without** `TBK_TOKEN`.
   - The attempt is found by `buy_order` (`TBK_ORDEN_COMPRA`) or by token, never trusted from the request alone: the token must match the stored one and have the documented format (64 hex characters).
4. On `token_ws`: lock the order. If it can still be paid (`pending`, or `expired` with stock that can be reserved again), **commit**; approved means exactly `response_code` 0 **and** `status` `AUTHORIZED`, and the returned `amount` and `buy_order` must match the attempt; then `MarkOrderPaid`. A rejection (`response_code` -1 to -5, status `FAILED`) leaves the order `pending` for another attempt. Otherwise **do not commit**: the customer is not charged and sees that the order expired. The commit happens **in the same request, within seconds** of the return (the window is under a minute, see *Integration environment experiments*); it is never queued. The order lock waits with a short timeout so a busy lock cannot push the commit past the window.
5. The return page only shows the result; nothing is delivered from the browser's word alone.
6. Delivery: keys by email through the queue, see [`orders.md` → Confirmation email and key delivery](orders.md#confirmation-email-and-key-delivery).

## Design (decided 2026-11-20)

### `payment_attempts`

One row per attempt to pay an order; an order has at most **5** (`payments.max_attempts`, `CHECK (number BETWEEN 1 AND 5)`), well under the card brands' retry blocks.

| column | notes |
|---|---|
| `order_id` | FK to `orders`, restrict |
| `number` | 1 to 5, unique per order |
| `gateway` | `webpay_plus` (CHECK) |
| `buy_order` | `{reference}-{number}` (`NK-7QF2K9-2`), unique. An attempt whose create call fails still counts: its `buy_order` may have reached Transbank, so it is never reused |
| `token` | 64 lowercase hex characters (CHECK), unique, null until create answers |
| `amount` | integer CLP, the order's `total` when the attempt starts |
| `status` | `PaymentAttemptStatus` (below) |
| receipt | `response_code`, `authorization_code`, `payment_type_code`, `installments_number`, `installments_amount`, `card_last_digits`, `transaction_date`, `accounting_date`, from the commit (or `status`) response. `vci` is neither stored nor validated |
| refund | `refund_type` (`reversed` / `nullified`), `refunded_amount`, `refunded_at` |
| `finished_at` | when it left `initialized` |

Statuses: `initialized` (sent to Webpay), `authorized` (committed and recorded on the order), `rejected` (the bank said no), `cancelled` (the customer cancelled in Webpay), `aborted` (never committed: form timeout, order no longer payable, or the commit window passed; nothing charged), `refunded` (charged and refunded automatically). A trigger allows only `initialized` → any other; CHECKs tie the receipt to `authorized` / `refunded` and `refunded_at` to `refunded`.

### Order changes

- **Reopening replaces late payment.** The `orders` trigger trades `expired` → `paid` for `expired` → `pending`. `ReopenExpiredOrder` reserves the stock again and gives the order a fresh hold; `MarkOrderPaid` only takes a `pending` order. So "the stock is held" means exactly "`pending` (or paid)". If the bank then rejects the payment, the reopened order stays `pending` and the customer can try again.
- **Starting an attempt extends the hold** to `max(expires_at, now + 11 min)` (`payments.payment_window_minutes`: the 10-minute integration form plus the commit), so an order rarely expires while the customer is in Webpay.
- `LockPayableOrder` is the single "can it still be charged?" rule: it runs a callback in a transaction holding the order's row lock, taken with `SET LOCAL lock_timeout = '3s'` (`orders.payment_lock_timeout_seconds`, `OrderBusy` when it runs out); the order goes to the callback when `pending`, is reopened first when `expired`, and `OrderCannotBePaid` (with a reason: already paid, cancelled, out of stock) is thrown otherwise. `MarkOrderPaid`, `StartPaymentAttempt`, `ConfirmPaymentAttempt` and the reconciliation all go through it.

### Gateway

- `App\Payments\PaymentGateway`: `start`, `confirm` (commit), `status`, `refund`, with gateway-neutral typed results (`PaymentRedirect`, `GatewayPayment`, `GatewayRefund`). Approved means exactly `Authorized` with response code 0. Every failure is one `PaymentGatewayException` that carries no request, no URL and no previous exception (the HTTP client's exceptions hold the `Tbk-Api-Key-Secret` header).
- `App\Payments\Transbank\WebpayPlusGateway` (Laravel's HTTP client): connect timeout 3 s, total 10 s; the commit retries once on a connection error only (it is idempotent), so the worst case (3 s lock + 2 × 13 s) stays under the ~42 s window. Tokens are checked against `^[0-9a-f]{64}$` before they go into a URL. Credentials and hosts live in `config/payments.php` (integration defaults).

### Return route

`GET|POST /payments/webpay/return`, outside CSRF, throttled, no auth (the token identifies the attempt).

- only `token_ws` → `ConfirmPaymentAttempt`: lock the order (`LockPayableOrder`); not payable → `aborted`, no commit; lock timeout → no commit, the attempt stays `initialized`; otherwise commit. Approved with matching `amount` and `buy_order` → `MarkOrderPaid` and `authorized`; approved but mismatched → refunded at once; rejected → `rejected`. A gateway error leaves the attempt `initialized` for reconciliation.
- `TBK_TOKEN` (with or without `token_ws`) → `cancelled` when it matches the stored token and `buy_order`; never committed.
- only `TBK_ORDEN_COMPRA` (form timeout) → nothing changes; reconciliation closes it, so a forged request cannot touch someone else's attempt. `TBK_ID_SESION` (either spelling) is not used.
- Then a redirect to the storefront result page (`payments.result_url`/`{reference}`, phase 4).

### Reconciliation

`payments:reconcile` every 5 minutes takes `initialized` attempts older than 15 minutes (`payments.reconcile_after_minutes`); a busy order (`OrderBusy`) is retried on the next run: no token or older than 7 days → `aborted` without a call; `status` `INITIALIZED` → `aborted`; `FAILED` → `rejected`; `AUTHORIZED` → recorded as paid if the order can take it, otherwise refunded in full and `refunded`; `REVERSED` / `NULLIFIED` → `refunded`. A gateway error is logged and retried on the next run.

### Implementation

- `app/Payments`: `PaymentGateway`, its results and `PaymentGatewayException`; `app/Payments/Transbank`: `WebpayPlusGateway`, `WebpayResponse` (field-by-field parsing), `WebpayReturn` (the four return cases). Bound in `AppServiceProvider` from `config/payments.php`.
- `app/Actions/Payments`: `StartPaymentAttempt`, `ConfirmPaymentAttempt`, `CancelPaymentAttempt`, `ReconcilePaymentAttempts` (`payments:reconcile`, `routes/console.php`). Return route in `routes/payments.php` (`WebpayReturnController`).
- Verified end to end against `webpay3gint` on 2026-11-20 (test VISA, RUT 11.111.111-1): commit, receipt and order completed. Posting the same token to Webpay twice shows Webpay's error page; the attempt stays `initialized` until reconciliation aborts it.

### Attempt origin

Decided on 2026-11-20. `payment_attempts.origin` (`storefront` or `panel`, CHECK) records who started the attempt; `StartPaymentAttempt` requires it. The Webpay return sends a `panel` attempt back to the order in the panel and every other attempt to the storefront result page, so turning on `ORDERS_MANUAL_PAYMENTS` locally no longer hijacks storefront payments. Rows from before the column are `panel` (the panel was the only way to start one).

### Panel and phase 4

- The order detail lists its payment attempts. With `ORDERS_MANUAL_PAYMENTS=true` (local only) a "Pagar con Webpay (prueba)" button starts an attempt and auto-posts the form to Webpay, to try the integration end to end before the storefront exists.
- Phase 4: the Sanctum endpoint that starts an attempt, the Nuxt checkout with the auto-POST form and the withdrawal notice, the result/receipt page and its API contract, HTTPS for the deployed `return_url`.

## Webpay Plus API (spec for the HTTP client)

From the official docs source (`transbank-developers-docs`) and the SDK code; everything else is undocumented.

- **Hosts:** `https://webpay3gint.transbank.cl` (integration), `https://webpay3g.transbank.cl` (production). Base path `/rswebpaytransaction/api/webpay/v1.2/transactions`. Headers on every call: `Tbk-Api-Key-Id` (commerce code), `Tbk-Api-Key-Secret`, `Content-Type: application/json`. Integration credentials are public (`597055555532` and the key in the SDK's `Options::INTEGRATION_API_KEY`) and live in config, never in code; the secret never reaches logs or exceptions.
- **Create:** `POST {base}` with `buy_order` (max 26; letters, digits and `|_=&%.,~:/?[+!@()>-`; unique per transaction), `session_id` (max 61), `amount` (integer CLP), `return_url` (max 256, HTTPS when deployed). Returns `token` (64 characters) and `url`.
- **Commit:** `PUT {base}/{token}` without body. **Status:** `GET {base}/{token}`, for 7 days after creation. Both return `vci`, `amount`, `status`, `buy_order`, `session_id`, `card_detail.card_number` (last 4), `accounting_date` (MMDD), `transaction_date` (ISO 8601), `authorization_code`, `payment_type_code` (`VD` debit, `VN` normal credit, `VC` installments, `SI` 3 without interest, `S2` 2 without interest, `NC` N without interest, `VP` prepaid), `response_code`, `installments_number` and, optionally, `installments_amount` and `balance`. **`vci` must not be validated** (official warning).
- **Refund:** `POST {base}/{token}/refunds` with `{"amount": n}`. Returns `type` `REVERSED` (full, within 3 hours of the commit; nothing else in the body) or `NULLIFIED` (after 3 hours, up to 90 days; partial only for credit cards; with `authorization_code`, `authorization_date`, `nullified_amount`, `balance` and `response_code`, success when 0). Refund business codes: 304, 245, 22, 316, 308, 274, 16, 292, 284 (period exceeded), 310 (already nullified), 311 (amount over balance), 312, 315, 53 (no partial refund with installments).
- **Errors:** HTTP 400, 401 (bad credentials), 404 (transaction not found), 405, 406, 415, 422 (business rule) and 500, with body `{"error_message": "..."}`. Create, commit and status have no documented business codes; the observed 422 messages are listed in *Integration environment experiments*.
- **Client rules** (from the SDK traps): short timeouts (a few seconds to connect, well under the commit window in total); every failure, including network errors and malformed 2xx bodies, becomes one gateway exception without credentials; responses are parsed into typed objects that reject missing required fields; the token is validated before it goes into a URL.
- **Card brand retry limits:** Mastercard blocks the 8th rejected retry within 24 hours and Visa the 16th within 30 days (code -11), so payment attempts per order are capped.
- Not documented anywhere: idempotency (measured: commit is idempotent), retries, HTTP timeouts, behavior on a duplicate `buy_order`, minimum and maximum amounts.

## Transbank requirements for the store (official getting-started guide)

- **Result page.** Webpay Plus REST has no Transbank voucher: the customer lands on the store, which must show the result. Recommended content when approved: order number, store name, amount and currency, authorization code, date, payment type (debit or credit), installment type, number of installments and their amount, last 4 card digits and a description of what was bought. When rejected: "Orden de Compra XXXXXXX rechazada" plus the possible causes (wrong card data, insufficient funds, card not enabled). The commit response carries these fields (`authorization_code`, `payment_type_code`, `installments_number`, `installments_amount`, `card_detail.card_number`, `transaction_date`), so they are stored with the payment. The page itself belongs to the storefront (phase 4).
- **Check what Transbank returns**: amount and `buy_order` must match what the store sent; approved means exactly `response_code` 0 and `status` `AUTHORIZED`.
- **`buy_order` must be unique per transaction** (max 26 characters; letters, digits and `|_=&%.,~:/?[+!@()>-`). An order can have several payment attempts (the customer cancels in Webpay and tries again), so each attempt needs its own `buy_order`, e.g. the order reference plus an attempt number (`NK-7QF2K9-2`). Design input for the payments table.
- **HTTPS** is required for the store's endpoints and `return_url`, in integration too (redirects fail on Safari without it). Local development worked over plain HTTP in the experiments, but the deployed demo needs HTTPS.
- **Going to production** requires a productive commerce code, Transbank's validation form with test evidence, the store logo (130x59 px) and one real transaction. Not applicable: NextKeys stays in the integration environment (fictitious payments).
- General security recommendations (OWASP, updated dependencies, quarterly vulnerability scans, restricted admin access, backups, monitoring) apply to any deployment; nothing payment-specific to build.

## Chile

- Amounts are integer CLP (already the case in `products.price` and `orders.total`).
- **Boleta electrónica** (SII) is mandatory for real sales. NextKeys is fictitious: at most a simulated receipt, never a real tax document.
- **Derecho a retracto** (Ley 19.496 art. 3 bis): 10 days for distance purchases, 90 if the store does not send a written confirmation of the conditions. An exclusion (Decreto 52, in force since 2025-02-28) must be shown **before payment**, prominently (font no smaller than the price) and in the terms. Decided 2026-11-20: delivered digital keys are excluded and the legal warranty remains, as the Chilean key stores reviewed do ([ReveChl](https://revechl.cl/politicas-de-reembolso/), [Soft Store](https://softstore.cl/reembolso), [CJM Digitales](https://cjmdigitales.cl/policies/refund-policy), [ChileCodes](https://chilecodes.cl/terminos-y-condiciones/)); several cite "Decreto 52 de 2022", but it was published on 2024-08-27. The storefront (phase 4) shows the exclusion before payment, next to the price and with the words "derecho a retracto"; the confirmation email states the conditions (see [`orders.md`](orders.md#confirmation-email-and-key-delivery)).

## Integration environment experiments (2026-11-20)

No official page states what happens to an authorized payment the store never commits, so it was measured against `webpay3gint.transbank.cl` with the public test credentials of the official SDK (`597055555532`) and the approved VISA test card, using plain HTTP calls (the scripts were throwaway, not part of the repo):

| Case | Result |
|---|---|
| Paid, not committed | `status` = `INITIALIZED`, with `vci` = `TSY` and the card's last digits. It **stays `INITIALIZED`** (polled every 2 minutes for an hour), even after the commit window closed. |
| Commit 20, 30, 35 and 42 s after the customer is back on `return_url` | `AUTHORIZED`, `response_code` 0. |
| Commit 50 s, 60 s, 2, 3, 4, 6 and 20 min, and 1 hour after the return | 422 `Transaction has an invalid finished state: aborted`. |
| Commit twice | The second call returns 200 with the same body: commit is idempotent, so retrying after a network error is safe. |
| Refund before commit | 422 `Transaction is unfinished, it is not possible to refund it yet`: an uncommitted payment cannot be refunded, and does not need to be. |
| Refund after commit | `REVERSED` (within the 3 hours); a second refund returns 422 `Transaction already REVERSED`. |
| Commit of a transaction never paid | 422 `Invalid status '0' for transaction while authorizing`. |

Conclusions:

- **The commit window is under a minute** (between 42 and 50 s after the return in integration, close to the 30-second acknowledgement of the old API). Production was not measured; the design commits at once, so the exact number does not matter.
- **Not committing is a clean refusal.** Transbank aborts the transaction by itself and the store is never paid; there is nothing to refund. Whether the issuing bank shows a temporary hold to the cardholder cannot be observed in integration (customer complaints mention holds released within 24 to 48 hours).
- **`status` cannot tell an aborted payment from one still committable**: both read `INITIALIZED`. Only a commit attempt answers, and it is safe to repeat.
- **The only payment that needs a refund** is one committed (`AUTHORIZED`) whose order was not recorded as paid, e.g. the server failed between the commit and `MarkOrderPaid`. The reconciliation job looks for those through `status` (within its 7 days) and either records the order as paid, if it can still take it, or refunds automatically (see *Decisions*).
- Refunds: within 3 hours of the commit a full refund is a reversal (`REVERSED`); afterwards an annulment (`NULLIFIED`), partial for credit cards, full only for debit and prepaid, up to 90 days (official product docs).

## Resolved questions

- **Return parameters** (resolved 2026-11-20): the attempt is found by `token_ws`, `TBK_TOKEN` or `TBK_ORDEN_COMPRA`, never by `TBK_ID_SESION`, so its two spellings in the docs do not matter (see *Return route*).

## Research sources (2026-11-20)

- Transbank: [Webpay Plus docs](https://www.transbankdevelopers.cl/documentacion/webpay-plus), [API reference](https://www.transbankdevelopers.cl/referencia/webpay).
- Stripe: [Manage limited inventory](https://docs.stripe.com/payments/checkout/managing-limited-inventory) (sessions last 30 min to 24 h), [Expire a session](https://docs.stripe.com/api/checkout/sessions/expire).
- Mercado Pago: [Preference expiration](https://mercadopago.cl/developers/en/docs/checkout-pro/additional-settings/preferences/expiration-date), [Binary mode](https://mercadopago.cl/developers/en/docs/checkout-pro/additional-settings/preferences/binary-mode), [Refunds](https://www.mercadopago.cl/developers/en/docs/checkout-pro-preferences/additional-settings/refunds-and-cancellations).
- Chile: [SII boleta electrónica](https://www.sii.cl/preguntas_frecuentes/bol_electr_vtas_serv/001_380_7666.htm), [SERNAC retracto](https://sernac.cl/portal/609/w3-article-52791.html), [CCS guide to Decreto 52](https://www.ccs.cl/wp-content/uploads/2024/09/GuiaRegulacionExclusionesalDerechoRetracto.pdf), [Decreto 52](https://www.diariooficial.interior.gob.cl/publicaciones/2024/08/27/43935/01/2535265.pdf).
