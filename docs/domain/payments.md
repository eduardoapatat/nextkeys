# Payments domain model

Status: **direction decided** (2026-11-20), detailed design pending. Phase 3.

## Decisions

- **First gateway: Transbank Webpay Plus** (REST API, integration environment with Transbank's public test credentials), behind the `PaymentGateway` interface. Replaces Stripe, planned until 2026-11-20. Reasons:
  - It is the expected gateway for a Chilean store; Stripe is, per most sources, not available to businesses registered in Chile.
  - Its **commit** model fits the stock reservation: the charge is only confirmed when the store commits it, so the store decides with the order locked.
  - The payment form expires after **4 minutes** in production (10 in integration), well inside the 15-minute hold.
- **PHP client: Laravel's HTTP client**, not the official `transbank/transbank-sdk` (decided 2026-11-20). Webpay Plus needs four calls (create, commit, status, refund); they live in the Transbank implementation of `PaymentGateway` and are tested with `Http::fake`, without network. The SDK was rejected: no release since 5.1.0 (2025-06-17), its CI only tests PHP 8.2 (nothing newer, so untested on our 8.5), it requires Guzzle 7 while this app runs Guzzle 8 (a `composer require --dry-run` only resolves by downgrading `guzzlehttp/guzzle`, `psr7` and `promises` one major version), and it is one more dependency for four endpoints.
- **A second gateway may come later** (Mercado Pago Checkout Pro, or Stripe) as another `PaymentGateway` implementation, to prove the interface. Not planned yet.
- **General rule for any gateway:** its payment window must be no longer than the order's reservation (`orders.expires_at`), so a late payment only happens when the gateway misbehaves.

## Checkout flow (target)

1. `PlaceOrder` creates the `pending` order and reserves its stock (done, see [`orders.md`](orders.md)).
2. The gateway creates a transaction for the order's `reference` and **server-side `total`**; the customer is redirected to Webpay.
3. Webpay sends the customer back to `return_url` with `token_ws` (paid or rejected), with `TBK_TOKEN` (cancelled by the customer) or without a token (form timed out). Each case is handled; the official docs disagree on the timeout parameters, so the integration environment decides.
4. On `token_ws`: lock the order. If it can still be paid (`pending`, or `expired` with stock that can be reserved again), **commit**, check the amount and the response code, then `MarkOrderPaid`. Otherwise **do not commit**: the customer is not charged and sees that the order expired. The commit happens **in the same request, within seconds** of the return (the window is under a minute, see *Integration environment experiments*); it is never queued. The order lock waits with a short timeout so a busy lock cannot push the commit past the window.
5. The return page only shows the result; nothing is delivered from the browser's word alone.
6. Delivery: keys by email through the queue (to be designed).

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
- **Derecho a retracto** (Ley 19.496 art. 3 bis): 10 days for distance purchases, 90 if the store does not send a written confirmation of the conditions. An exclusion (Decreto 52, in force since 2025-02-28) must be shown **before payment**, prominently (font no smaller than the price) and in the terms. Whether delivered digital keys qualify was **not confirmed** in the sources read; the storefront (phase 4) shows the notice before payment and the confirmation email states the conditions.

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
- **The only payment that needs a refund** is one committed (`AUTHORIZED`) whose order was not recorded as paid, e.g. the server failed between the commit and `MarkOrderPaid`. The reconciliation job looks for those through `status` (within its 7 days) and either records the order as paid, if it can still take it, or refunds.
- Refunds: within 3 hours of the commit a full refund is a reversal (`REVERSED`); afterwards an annulment (`NULLIFIED`), partial for credit cards, full only for debit and prepaid, up to 90 days (official product docs).

## Open questions

- Refund policy for the one case left (see below: committed but not recorded): refund automatically through `PaymentGateway` or flag it in the panel. Needs a `refunded` order status either way.
- Return parameters to handle: `token_ws` (normal), `TBK_TOKEN` + `TBK_ID_SESION` + `TBK_ORDEN_COMPRA` (cancelled), only `TBK_ID_SESION` + `TBK_ORDEN_COMPRA` (form timeout); the docs spell `TBK_ID_SESION` two ways, so confirm in integration.

## Research sources (2026-11-20)

- Transbank: [Webpay Plus docs](https://www.transbankdevelopers.cl/documentacion/webpay-plus), [API reference](https://www.transbankdevelopers.cl/referencia/webpay).
- Stripe: [Manage limited inventory](https://docs.stripe.com/payments/checkout/managing-limited-inventory) (sessions last 30 min to 24 h), [Expire a session](https://docs.stripe.com/api/checkout/sessions/expire).
- Mercado Pago: [Preference expiration](https://mercadopago.cl/developers/en/docs/checkout-pro/additional-settings/preferences/expiration-date), [Binary mode](https://mercadopago.cl/developers/en/docs/checkout-pro/additional-settings/preferences/binary-mode), [Refunds](https://www.mercadopago.cl/developers/en/docs/checkout-pro-preferences/additional-settings/refunds-and-cancellations).
- Chile: [SII boleta electrónica](https://www.sii.cl/preguntas_frecuentes/bol_electr_vtas_serv/001_380_7666.htm), [SERNAC retracto](https://sernac.cl/portal/609/w3-article-52791.html), [CCS guide to Decreto 52](https://www.ccs.cl/wp-content/uploads/2024/09/GuiaRegulacionExclusionesalDerechoRetracto.pdf), [Decreto 52](https://www.diariooficial.interior.gob.cl/publicaciones/2024/08/27/43935/01/2535265.pdf).
