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

## Open questions

- Visual design (component library; Tailwind/shadcn-vue shared with the admin): pending.
