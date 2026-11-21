# Storefront (`apps/web`)

Status: **prototype implemented** (catalog listing and product page). The first step is a functional prototype that consumes `/api/v1` to validate the data. **The visual design is pending**: the prototype is plain semantic HTML, with only the minimal styles needed to read it, and will be redesigned.

## Prototype scope

Decided on 2026-11-20.

- `/`: the product listing. The filters (`platform`, `type`, `activation_service`, `region`), the sort (`newest`, `price`, `-price`, `name`) and the page all live in the URL query.
- `/{slug}`: the product page with the variant selector. Choosing a variant navigates to its slug.
- The UI is in Spanish. Prices are in CLP, formatted with `es-CL`. "Sin carátula" is shown when `cover_url` is `null`. 404, 422 and an empty listing get simple messages.
- No business logic: stock, visibility and ordering come from the API as they are. No cart or checkout yet (phase 4); customer auth is described below.

## Decisions

Decided on 2026-11-20. These survive the visual redesign.

### API contract: fixtures from Laravel, valibot schemas in Nuxt

- **One shared place.** Example responses live in `contracts/api/v1/*.json` at the repo root. It is the only folder shared between the apps, and it holds data, never code.
  - `products.index.json`, `products.show.json`, `catalog.filters.json`: the three endpoints.
  - `products.index.422.json`, `products.show.404.json`: the error bodies.
- **Laravel writes the fixtures.** They are never hand-written in `apps/web`. `tests/Feature/Api/V1/ApiContractTest.php` seeds the demo catalog and requests each endpoint.
  - It fails when the *shape* of the real response differs from its fixture's: the same keys, recursively, with list items merged and a nullable object accepted as `null`.
  - `CONTRACTS_UPDATE=1` rewrites the fixtures from the real responses.
  - When an API Resource changes, the API CI fails until the fixtures are regenerated.
- **Nuxt has hand-written valibot schemas** (`v.strictObject`) in `app/api/schemas.ts`, and the types are derived from them with `InferOutput`.
  - A unit test parses every fixture: extra keys, missing keys and invalid enum values all fail.
  - The app only uses the types (`import type`); nothing is parsed in production.
- **valibot is a dev dependency.** ESLint (`no-restricted-imports` with `allowTypeImports`) forbids value imports of `valibot` and of the schemas outside `app/api/schemas.ts` and the tests, so it never reaches the bundle.
- Rejected:
  - OpenAPI generation (Scramble + openapi-typescript): more tooling, and it infers computed fields weakly.
  - Fixtures hand-written in `apps/web`: they can agree with the types while both are wrong about the API.

### Data layer

- A Nuxt plugin provides `$api = $fetch.create({ baseURL: runtimeConfig.public.apiBase })`.
- Typed functions per endpoint (`fetchProducts`, `fetchProduct`, `fetchCatalogFilters`) wrap `$api`.
- Pages call them through `useAsyncData`.
  - The key is reactive: it is built from the canonical query (filters, sort, page), which is computed from `route.query`. Any URL change fetches again under a new key, so changing a filter never shows the previous result. A separate `watch` would only duplicate the request.
- Errors:
  - **404** from the API → `createError({ statusCode: 404, fatal: true })`, so SSR answers a real 404 (not a 200 with an error page).
  - **422** (an invalid filter or sort in the URL) → the API's messages inline, with a link that clears the filters.
  - An empty listing → a simple message.
  - Other errors → the generic `error.vue`.
- Rejected: a `useApi` wrapper around `useFetch`. It spreads the endpoint types across the call sites and is harder to test outside Nuxt.

### Tests

- `test/unit` (node): pure functions (URL query ↔ API query, CLP formatting, error mapping, variant label), the fixture parse against the schemas, and the default `apiBase` from `nuxt.config.ts`.
- `test/nuxt`: pages mounted with `mountSuspended`. The API is mocked with `registerEndpoint` serving the contract fixtures.
  - `registerEndpoint` only intercepts relative routes, so `vitest.config.ts` overrides `apiBase` to `/api/v1` in the nuxt project (`environmentOptions.nuxt.overrides`).
- Rejected:
  - MSW: an extra dependency.
  - `mockNuxtImport` of the client: it skips the client and the URLs.

### Variant selector

- A `<nav>` with one `NuxtLink` per variant; the current one has `aria-current="page"`. It works with SSR and without JS, and every variant is a crawlable URL.
- Each link shows the type, activation service, platforms, region and price.
  - The activation service is required: two variants can differ only in it (Steam vs Microsoft Store on Windows/Global).

### Product type name comes from the API

- `ProductResource` returns `type` as `{slug, name}` (`name` = `ProductType::label()`), like `platforms`, `region` and `activation_service`.
- The API had no consumers yet, so changing the contract was cheap. It avoids an extra request and join logic in Nuxt.
- Rejected: joining with `/catalog/filters` in Nuxt.

### Customer session (phase 4)

Decided and implemented on 2026-11-20; the API side is in [`users.md`](users.md#customer-authentication-phase-4).

- **`$api` carries the Sanctum session.** In the browser it uses `credentials: 'include'` and copies the `XSRF-TOKEN` cookie into `X-XSRF-TOKEN` on requests that change state (`app/utils/xsrf.ts`); `app/api/auth.ts` fetches `/auth/csrf-cookie` first when the cookie is missing. During SSR it forwards the browser's `cookie` header and sends `Referer: <storefront origin>`, so Sanctum treats the call as stateful. SSR never forwards `Set-Cookie`: it only reads.
- **State.** `useCustomer()` is a `useState<Customer | null>`. `plugins/session.server.ts` loads it once per request (`/auth/me`, 401 → `null`; an API failure renders the page signed out) and the browser hydrates it. Sign in, registration and sign out update it from the response.
- **Route middleware.** `auth` sends a visitor to `/cuenta/ingresar?redirect=<path>`; `guest` sends a signed in customer to `/cuenta`. `safeRedirect` only accepts paths of this site (no other origin, `//` or `/\`).
- **Pages** under `/cuenta/*` (two segments, so they never clash with `/{slug}`): `ingresar` (remember-me), `registro`, `index` (account, verification notice with resend, sign out), `recuperar`, `restablecer` and `verificar-email` (posts the signed link from the browser, never during SSR). Errors: `formErrorMessages` (422 messages, a Spanish 429 notice, or a generic message).
- **Contracts:** `auth.me.json`, `auth.me.401.json` and `auth.login.422.json`, parsed by `CustomerSchema`, `MessageSchema` and `ValidationErrorSchema`.
- **Tests:** `test/unit/auth-utils.test.ts` and `test/nuxt/account.test.ts` (redirect to sign in, sign in and return, 422 messages, header link). `servePost` and `serveCsrfCookie` in `test/nuxt/api.ts` mock the POST endpoints.

### Cart (phase 4)

Decided on 2026-11-20.

- **A storefront cookie, not an API table.** `nk_cart` (`useCookie`, `SameSite=Lax`, 30 days) holds only `[{slug, quantity}]`: at most 10 lines, 1 to 5 units each (the API's `orders.max_quantity_per_line`). It works with SSR (the header count renders without a flash), needs no session and reserves nothing: the API checks prices, stock and publication when the order is placed.
- The operations are pure functions (`app/utils/cart.ts`: add sums and caps, update, remove, count) behind `useCart()`. An invalid cookie reads as an empty cart.
- **Product page:** "Agregar al carrito" with a 1 to 5 quantity, disabled without stock.
- **`/carrito`** fetches each product with `fetchProduct` (carts are small; no new endpoint). Each line shows the current price, an editable quantity, "Quitar" and the line subtotal; the total is informative, the order's total comes from the API. A product that no longer exists or has no stock is "No disponible" and blocks "Ir a pagar" until it is removed.
- Rejected: `localStorage` (SSR always renders an empty cart), an API cart (needs a session or anonymous carts, more tables and endpoints).

### Payment result and orders (phase 4)

Decided on 2026-11-20.

- **`/pedidos/{reference}`** is where the Webpay return lands (`?estado=` the attempt status). The query only picks the wording; the order from the API (`status`, `last_payment`) is the truth.
  - Paid or completed: the receipt Transbank asks for (order number, store, amount in CLP, authorization code, date, payment type and installments, last 4 digits, what was bought), "Te enviamos las claves a tu correo" and a link to the library.
  - Rejected: "Orden de Compra NK-… rechazada" with the possible causes; "Reintentar el pago" while `can_pay`, and "Cancelar el pedido".
  - Cancelled in Webpay, aborted or without a payment yet: retry or cancel.
  - Expired or cancelled: a notice and a link to the catalog.
- **`/pedidos`** lists the customer's orders; with `?estado=desconocido` (a return the API could not match) it first says the payment could not be identified. `/cuenta` links to it.

### My library page (phase 4)

- **`/biblioteca`** lists the key lines by hint, grouped by order, with "Mostrar claves" per line; the revealed codes live only in the page (never in `useState`, the payload or the cookie). The API side is in [`orders.md` → My library](orders.md#my-library-phase-4).

### Visual design

Decided on 2026-11-21; in progress.

- **Tailwind CSS v4** (the panel's major version, `^4.1`) through `@tailwindcss/vite`. The design tokens (colors, type, spacing, radii, shadows, easing) live in the Tailwind CSS file (`@theme`), not in a JS config.
- **World: "Cibercafé de barrio"** (chosen in Impeccable's direction round; the contract lives in `apps/web/.impeccable/surfaces/app-pages-index-vue.md`, the product record in `apps/web/PRODUCT.md`). The counter of a neighborhood cibercafé at midnight: a lit display case of games, a whiteboard of prices, numbered stations and thermal receipts.
- **Tokens** in `app/assets/css/main.css`: `ink`/`panel` grounds, `chalk` text, `paper` for receipts, `sticker` yellow for prices and the primary action, `crt` blue for focus and active states, six `cover-*` tints, Emil's `--ease-out`/`--ease-in-out`. All text pairs measured at AA or better. `test/unit/design-tokens.test.ts` fails when a `.vue` file uses a raw color.
- **Type, self-hosted** in `public/fonts` (fetched with `impeccable font-fetch`, no font dependency): Barlow Condensed for signage and headings, Barlow for UI, JetBrains Mono for receipts and codes, Permanent Marker only for short whiteboard notes.
- **Icons** are authored SVG paths in `components/ui/Icon.vue` (one 1.75 stroke on a 24px grid), not an icon library.
- **Own Vue components on Tailwind, no shadcn-vue.** The apps share no code (no workspace), so "sharing" would mean copying files, and the panel's light admin look does not fit a dark game store. Menus, tabs and dialogs use native elements and ARIA (`<dialog>`, `role="tablist"`). Any other dependency is asked first.
- **Generated covers.** When `cover_url` is `null` (the demo seeder sets none), Nuxt draws a typographic cover from the title, the platforms and a color derived from the slug. The API is not changed.
- **Structure taken from the Koparion template** (structure only: no style, no content): header with search, account and cart count; navigation; hero; category blocks (platforms and types); deal of the day with a countdown; tabbed cover grid; category side menu; benefits; newsletter; full footer. No promo strip above the hero.
- **Layout** (`layouts/default.vue`): header with logo, search, account and cart count, a merchandising menu and a full footer with the newsletter. The menu's platform families (PlayStation, Xbox, Nintendo, PC y Mac) and types are curated in `app/utils/site-nav.ts` as real catalog filters; every catalog link goes through `catalogLink()` (`app/utils/listing-query.ts`). On small screens the menu is a native modal `<dialog>`. The search box says that name search is coming instead of searching; the newsletter answers "Es una demo: no guardamos tu correo." (`test/nuxt/layout.test.ts`).
- **Sections without API data** stay, with `#` links and clearly static example content. The tabs use real products: "Novedades" is the API's `newest` sort; "En oferta" and "Destacados" are real products in another order, marked "selección de ejemplo". The deal of the day is a real product with a countdown and its real price: no invented discount. The header search has no API behind it yet (name search is deferred).

## Open questions

- None open on the visual design yet; the design system is recorded here as it is built.
