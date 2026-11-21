# Roadmap

Overall project status. Updated in the same commit that closes each task.
Finished phases collapse to one line; details live in the git history.

## Current status

- **Current phase:** 5. Physical products (phase 4 closed, including the storefront's visual design).
- **Next task:** phase 5 (addresses, shipments and shipping status).

## Phases

- [x] **0. Foundation:** repository, Docker (Postgres/Redis/Mailpit), `apps/api` with the Vue starter kit, PHPStan `max`, tests on Postgres, CI and Dependabot.
- [x] **1. Catalog:** domain model ([`docs/domain/catalog.md`](domain/catalog.md)), users with roles ([`docs/domain/users.md`](domain/users.md)), admin CRUD for titles and products, bulk key import from CSV ([`catalog.md` → Admin panel (key import)](domain/catalog.md#admin-panel-key-import)), per-variant cover and key hint ([`catalog.md` → Variant cover and key hint](domain/catalog.md#variant-cover-and-key-hint)), and product status ([`catalog.md` → Product status](domain/catalog.md#product-status)).
- [x] **2. Public catalog API:** read-only `/api/v1` (filtered listing, product detail with its variants, filter options) with API Resources ([`catalog.md` → Public catalog API (phase 2)](domain/catalog.md#public-catalog-api-phase-2)). Price range, name search and in-stock filter deferred.
- [x] **3. Orders and payments:** orders with stock reservation and expiry ([`orders.md`](domain/orders.md)), Transbank Webpay Plus behind `PaymentGateway` with reconciliation ([`payments.md`](domain/payments.md)), and the payment confirmation email with the keys through the queue ([`orders.md` → Confirmation email and key delivery](domain/orders.md#confirmation-email-and-key-delivery)).
- [x] **4. Nuxt storefront (`apps/web`):** with its visual design ([`storefront.md` → Visual design](domain/storefront.md#visual-design), `apps/web/DESIGN.md`): catalog and product page, cart in a cookie, customer auth with Sanctum ([`users.md`](domain/users.md#customer-authentication-phase-4)), checkout with Webpay and one pending order per customer ([`orders.md` → Storefront checkout](domain/orders.md#storefront-checkout-phase-4)), payment result page, and "My library" with keys revealed on request ([`orders.md` → My library](domain/orders.md#my-library-phase-4)).
- [ ] **5. Physical products:** addresses, shipments and shipping status.
- [ ] **6. Extras:** Meilisearch search, wishlist, reviews, coupons and sales dashboard.
- [ ] **7. Launch:** deployment, public demo and portfolio README (screenshots, GIF, diagram).

## Decisions

Domain model decisions live in `docs/domain/*.md`; this list keeps project-wide ones.

- Monorepo with two independent apps, no pnpm workspace at the root (revisit if types get shared).
- Postgres in tests too: key reservation relies on row locks that SQLite lacks.
- Agent instructions live in `AGENTS.md`; `CLAUDE.md` is not used.
- Everything in English: code comments, docs and commits.
- The user interface is Spanish only, for now: UI strings are Spanish literals (no `__()` keys), the app locale defaults to `es`, `lang/es/validation.php` holds the messages of the validation rules in use, `lang/es/pagination.php` the page link labels (plain text, they reach the API JSON), and data tables format with `es-CL` and CLP (`config/inertia-datatable.php`). No other language is planned. Product keys are called **claves** in the UI ("Claves", "Gestionar claves", "Clave digital"), never "keys"; code, routes and docs keep `keys`.
- `apps/web` was scaffolded on 2026-11-20, ahead of phase 4, with the minimal Nuxt template (no Nuxt UI). Its visual design (2026-11-21) uses Tailwind v4 with its own tokens and components, not shared with the panel: see [`storefront.md` → Visual design](domain/storefront.md#visual-design).
- Storefront: types, data layer and tests decided for the prototype, shared with the API through `contracts/api/v1/` fixtures. See [`docs/domain/storefront.md`](domain/storefront.md).
- Orders: one order per checkout (mixed digital/physical allowed), lines with quantity (max 5) and a snapshot, `NK-XXXXXX` references, 15-minute stock hold released by the scheduled `orders:expire`. See [`docs/domain/orders.md`](domain/orders.md).
- Payments: Transbank Webpay Plus is the first gateway (replaces Stripe, not available to Chilean businesses); its commit model lets the store refuse an expired order without charging. See [`docs/domain/payments.md`](domain/payments.md).
- Catalog domain: see [`docs/domain/catalog.md`](domain/catalog.md).
- Withdrawal: delivered digital keys are excluded from the derecho a retracto (shown before payment, warranty kept); physical products keep 10 days. See [`docs/domain/payments.md`](domain/payments.md#chile).
- Users: one `users` table with a `role` enum (admin, customer); admins use the panel session, customers use Sanctum in SPA mode on a `customer` guard that cannot see admins; the storefront and the API must share the registrable domain in production. See [`docs/domain/users.md`](domain/users.md).
