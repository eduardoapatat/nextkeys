# Payments domain model

Status: **direction decided** (2026-11-20), detailed design pending. Phase 3.

## Decisions

- **First gateway: Transbank Webpay Plus** (REST API, integration environment with Transbank's public test credentials), behind the `PaymentGateway` interface. Replaces Stripe, planned until 2026-11-20. Reasons:
  - It is the expected gateway for a Chilean store; Stripe is, per most sources, not available to businesses registered in Chile.
  - Its **commit** model fits the stock reservation: the charge is only confirmed when the store commits it, so the store decides with the order locked.
  - The payment form expires after **4 minutes** in production (10 in integration), well inside the 15-minute hold.
- **PHP client: Laravel's HTTP client**, not the official `transbank/transbank-sdk` (decided 2026-11-20). Webpay Plus needs four calls (create, commit, status, refund); they live in the Transbank implementation of `PaymentGateway` and are tested with `Http::fake`, without network. The SDK was rejected: no release since 5.1.0 (2025-06-17), untested on PHP 8.5, and one more dependency for four endpoints.
- **A second gateway may come later** (Mercado Pago Checkout Pro, or Stripe) as another `PaymentGateway` implementation, to prove the interface. Not planned yet.
- **General rule for any gateway:** its payment window must be no longer than the order's reservation (`orders.expires_at`), so a late payment only happens when the gateway misbehaves.

## Checkout flow (target)

1. `PlaceOrder` creates the `pending` order and reserves its stock (done, see [`orders.md`](orders.md)).
2. The gateway creates a transaction for the order's `reference` and **server-side `total`**; the customer is redirected to Webpay.
3. Webpay sends the customer back to `return_url` with `token_ws` (paid or rejected), with `TBK_TOKEN` (cancelled by the customer) or without a token (form timed out). Each case is handled; the official docs disagree on the timeout parameters, so the integration environment decides.
4. On `token_ws`: lock the order. If it can still be paid (`pending`, or `expired` with stock that can be reserved again), **commit**, check the amount and the response code, then `MarkOrderPaid`. Otherwise **do not commit**: the customer is not charged and sees that the order expired.
5. The return page only shows the result; nothing is delivered from the browser's word alone.
6. Delivery: keys by email through the queue (to be designed).

## Chile

- Amounts are integer CLP (already the case in `products.price` and `orders.total`).
- **Boleta electrónica** (SII) is mandatory for real sales. NextKeys is fictitious: at most a simulated receipt, never a real tax document.
- **Derecho a retracto** (Ley 19.496 art. 3 bis): 10 days for distance purchases, 90 if the store does not send a written confirmation of the conditions. An exclusion (Decreto 52, in force since 2025-02-28) must be shown **before payment**, prominently (font no smaller than the price) and in the terms. Whether delivered digital keys qualify was **not confirmed** in the sources read; the storefront (phase 4) shows the notice before payment and the confirmation email states the conditions.

## Open questions

- **Commit deadline and uncommitted authorizations (researched 2026-11-20, not settled by the docs).** The REST docs only say "Se elimina el concepto de reversa realizada por Webpay" (control passes to the store) and give no commit deadline. The 30-second automatic reversal comes from the old SOAP API and Transbank's customer portal. A third-party report says an authorized but uncommitted payment stays `INITIALIZED` and can still be committed from the server. The token lives 5 minutes; `status` can be queried for 7 days. **The design does not depend on it:** decide with the order locked and commit right away on return, never later; if a payment ever ends up committed for an order that cannot take it, `refund` it (full reversal, or annulment; partial amounts allowed). First implementation step: authorize without committing in the integration environment and query `status` after some minutes to record the real behavior here.
- Refunds (Webpay `refund`, which returns `REVERSED` or `NULLIFIED`) and a `refunded` order status: only as the safety net above, or for a second gateway that charges before asking the store.
- Reconciliation job for orders left mid-payment (status query by token, within the 7 days).
- Return parameters to handle: `token_ws` (normal), `TBK_TOKEN` + `TBK_ID_SESION` + `TBK_ORDEN_COMPRA` (cancelled), only `TBK_ID_SESION` + `TBK_ORDEN_COMPRA` (form timeout); the docs spell `TBK_ID_SESION` two ways, so confirm in integration.

## Research sources (2026-11-20)

- Transbank: [Webpay Plus docs](https://www.transbankdevelopers.cl/documentacion/webpay-plus), [API reference](https://www.transbankdevelopers.cl/referencia/webpay).
- Stripe: [Manage limited inventory](https://docs.stripe.com/payments/checkout/managing-limited-inventory) (sessions last 30 min to 24 h), [Expire a session](https://docs.stripe.com/api/checkout/sessions/expire).
- Mercado Pago: [Preference expiration](https://mercadopago.cl/developers/en/docs/checkout-pro/additional-settings/preferences/expiration-date), [Binary mode](https://mercadopago.cl/developers/en/docs/checkout-pro/additional-settings/preferences/binary-mode), [Refunds](https://www.mercadopago.cl/developers/en/docs/checkout-pro-preferences/additional-settings/refunds-and-cancellations).
- Chile: [SII boleta electrónica](https://www.sii.cl/preguntas_frecuentes/bol_electr_vtas_serv/001_380_7666.htm), [SERNAC retracto](https://sernac.cl/portal/609/w3-article-52791.html), [CCS guide to Decreto 52](https://www.ccs.cl/wp-content/uploads/2024/09/GuiaRegulacionExclusionesalDerechoRetracto.pdf), [Decreto 52](https://www.diariooficial.interior.gob.cl/publicaciones/2024/08/27/43935/01/2535265.pdf).
