# NextKeys

Portfolio e-commerce: digital game keys, software licenses and physical games. All data and payments are fictitious.

- `apps/api`: Laravel 13 + Inertia v3 + Vue + TypeScript. REST API for the storefront and the admin panel (Vue starter kit).
- `apps/web`: Nuxt 4 with SSR + TypeScript, the public storefront. Reads the API base from `runtimeConfig.public.apiBase` (`NUXT_PUBLIC_API_BASE`).
- Services in `docker-compose.yml`: PostgreSQL 18, Redis 8 and Mailpit.

## Project status

@docs/ROADMAP.md

Record every decision as soon as it is made, not only when closing a task: domain model decisions in `docs/domain/<area>.md` (model, rules, examples, open questions), project-wide ones in `docs/ROADMAP.md` (Decisions). Keep the roadmap checklist pointing to each domain doc and its status. When closing a task: the `ci:check` of every app touched green, update `docs/ROADMAP.md` (status and checklist) and commit everything together.

## Commands

- Services: `docker compose up -d` (from the repo root).
- API dev server: `composer dev` in `apps/api`.
- **Verification (single command):** `composer ci:check` in `apps/api`. Runs ESLint, vue-tsc, Pint, PHPStan and Pest — the same command CI runs.
- Storefront dev server: `pnpm dev` in `apps/web` (port 3000).
- **Storefront verification:** `pnpm ci:check` in `apps/web`. Runs ESLint, Prettier, `nuxt typecheck` and Vitest — the same command CI runs.

## Architecture and boundaries

- Laravel is the single source of truth. Nuxt holds no business logic; it only consumes `/api/v1`.
- Routes: `routes/web.php` is the admin panel (Inertia); `routes/api.php` (`/api/v1`) is the storefront, using API Resources.
- Thin controllers. Business logic lives in `app/Actions` (one use case per class).
- Product type is an enum (`digital_key`, `license`, `physical`), not subclasses. Each type defines how it is delivered.
- Payments sit behind a `PaymentGateway` interface. The domain knows nothing about Transbank (the first gateway) or any other provider.
- Keys are stored encrypted (`encrypted` cast) and are never exposed in listings or logs.
- Key reservation and assignment use Postgres row locks (`lockForUpdate` / `SKIP LOCKED`), never an unlocked read-then-write.
- Each app is an independent pnpm project. There is no workspace at the root.
- `contracts/api/v1/*.json` is the only folder shared between the apps: data, never code. Laravel writes the fixtures (`ApiContractTest`), and `apps/web` parses them with its valibot schemas in tests. See `docs/domain/storefront.md`.

## Conventions

- Everything is written in English: code comments, PHPDoc, docs, AGENTS.md and commit messages.
- Commits follow Conventional Commits: `type(scope): description`, e.g. `feat(api): ...`.
- Inject the authenticated user with `#[CurrentUser] User $user`, not `$request->user()` (nullable).
- Inertia routes use `Route::get(..., fn () => Inertia::render(...))`, not `Route::inertia()`, which PHPStan types as `mixed`.

## Existing checks

- PHPStan with Larastan at level `max`, no baseline (`apps/api/phpstan.neon`).
- Pint, ESLint/Prettier and vue-tsc, all included in `composer ci:check`.
- Catalog database rules (CHECKs, composite FK, triggers) live in the migrations and are covered by `tests/Feature/Catalog/*DatabaseRulesTest.php` and `ProductIdentityTriggerTest.php`.
- Catalog Actions turn every database rule into a validation error before writing (per type, platforms, slug, identity, delete guards): `tests/Feature/Catalog/{Create,Update,Delete}ProductTest.php`, `DeleteTitleTest.php` and `GenerateProductSlugTest.php` (checks the doc's example slugs from the demo seeder).
- Key import: per-line rules, duplicates (in the file and in the database) and limits in `tests/Feature/Catalog/ImportProductKeysTest.php`; upload validation and "codes never reach a response" in `ProductKeyControllerTest.php`.
- Product cover (stored before the transaction, cleaned up on failure, replace and remove) and key hint: `tests/Feature/Catalog/ProductCoverTest.php` and `ProductKeyTest.php`.
- Orders: CHECKs, the `orders_status_transition` trigger, the key lifecycle and quantity triggers on `product_keys` in `tests/Feature/Orders/OrderDatabaseRulesTest.php` and `ProductKeyOrderRulesTest.php`; reservation, cancel, expiry and payment in `tests/Feature/Orders/*Test.php`; the admin panel (search, filter, no codes, reference URLs, manual payment flag) in `OrderControllerTest.php`.
- Payments: `PaymentAttemptDatabaseRulesTest.php` (CHECKs and the `payment_attempts_status_transition` trigger), `WebpayPlusGatewayTest.php` (requests, typed parsing, one exception without credentials, token validation, commit retry), `StartPaymentAttemptTest.php`, `WebpayReturnTest.php` (the four return cases, "never commit an unpayable order") and `ReconcilePaymentAttemptsTest.php` in `tests/Feature/Payments`; `tests/Feature/Orders/LockPayableOrderTest.php`.
- `tests/Unit/PaymentsArchTest.php`: only the gateway binding and the Webpay return controller use `App\Payments\Transbank`, and only it uses the `Http` facade.
- Payment confirmation email: `tests/Feature/Orders/OrderPaidEmailTest.php` (sent once per payment from the three paths, nothing on rollback, content, "no code in the job payload, the logs or `failed_jobs`", refuses the `log` mailer); resend limits in `OrderControllerTest.php`. `tests/Unit/OrdersArchTest.php`: `SendOrderPaidEmail` is only dispatched by `MarkOrderPaid` and `ResendOrderPaidEmail`, and `OrderPaidMail` is never queued.
- User role rule: `users_role_check` CHECK in the migration, covered by `tests/Feature/Users/UserRoleDatabaseRulesTest.php`.
- Public catalog API (`/api/v1`): visibility, facets, sorting, pagination, `in_stock`, 422s, constant query count, variants, 404s, filter options, "no key data, stock, status or ids in any response", rate limit and CORS in `tests/Feature/Api/V1/*Test.php`.
- Customer auth: `tests/Feature/Api/V1/Auth/*Test.php` (registration, sign in, sign out, `me`, verification, password reset, rate limits) and `StorefrontAuthBoundaryTest.php` (an admin never signs in to the storefront, a panel session gets 401 on `/api/v1`); `ApiAuthRoutesTest.php` fails when an `/api/v1` route uses a guard other than `auth:sanctum` or is neither guarded nor listed in `PUBLIC_API_ROUTES`.
- Storefront checkout: `tests/Feature/Api/V1/Orders/CustomerCheckoutTest.php` (verified email, slugs, one pending order, ownership 404s, Webpay start and its errors, no keys/ids/buy order), the one-pending rule in `tests/Feature/Orders/PlaceOrderTest.php`, the attempt origin in `WebpayReturnTest.php` and `PaymentAttemptDatabaseRulesTest.php`; `apps/web/test/nuxt/checkout.test.ts` (verification gate, withdrawal notice, pending order, form to Webpay).
- Payment result page: `apps/web/test/nuxt/order-result.test.ts` (receipt, rejection and retry, cancel, unidentified return).
- Every authenticated app route must sit behind the `can:access-admin-panel` gate: `tests/Feature/AdminPanelRoutesTest.php`.
- API contract: `tests/Feature/Api/V1/ApiContractTest.php` fails when an `/api/v1` response changes shape against `contracts/api/v1/*.json`; `apps/web/test/unit/contract.test.ts` parses those fixtures with the valibot schemas (`app/api/schemas.ts`).
- `apps/web` ESLint forbids value imports of `valibot` and of `app/api/schemas.ts` outside the schemas and the tests (`eslint.config.mjs`): use `import type`.
- CI in `.github/workflows/api.yml`, filtered by `apps/api/**` and `contracts/**`, with Postgres as a service; `.github/workflows/web.yml`, filtered by `apps/web/**` and `contracts/**`, runs `pnpm ci:check`.
- Storefront cart (`nk_cart` cookie): `apps/web/test/unit/cart.test.ts` (caps, invalid cookie) and `test/nuxt/cart.test.ts` (add, edit, remove, unavailable line blocks checkout).
- `apps/web`: strict TypeScript (`nuxt typecheck`), ESLint (`@nuxt/eslint` + `eslint-config-prettier`), Prettier and Vitest (`test/unit` in node, `test/nuxt` in the Nuxt environment).
- Dependabot in `.github/dependabot.yml` for actions, composer and npm in `apps/api`, and npm in `apps/web`.

## Known pitfalls

- Tests run against Postgres (`nextkeys_test`), not SQLite. Docker must be running.
- Product covers live on the `public` disk: run `php artisan storage:link` once after cloning, or covers return 404. Tests use `Storage::fake('public')`.
- `make:migration` names files with the current time, which can sort before the catalog migrations (`2026_11_20_1000xx`); rename new ones to follow the last number.
- The `nextkeys_test` database is created by `docker/postgres/init/` only on a fresh volume.
- PHPStan needs `--memory-limit=1G`, already set in the `types:check` script.
- GitHub only reads workflows from the root `.github/`, never from inside `apps/*`.
- The Boost MCP server (`apps/api/.mcp.json`) only loads when the session starts inside `apps/api`.
- Do not create `CLAUDE.md` or `CLAUDE.local.md` anywhere: Claude Code then stops reading `AGENTS.md`. `apps/api/AGENTS.md` is generated by Boost (`boost:update`); do not edit it by hand.
- Boost output in `apps/api` (`AGENTS.md`, `.claude/`, `.agents/`, `.mcp.json`, `opencode.json`, `boost.json`) is git-ignored. After a fresh clone, run `php artisan boost:install` there.
- Write a product and its platforms in one transaction: the identity constraint trigger is deferred to commit and rejects a product with no platforms. Tests never commit, so call `checkDeferredConstraints()` to fire it.
- CORS reads `STOREFRONT_URL` (the Nuxt origin): without it in `.env`, browsers block client-side calls to `/api/v1` (SSR calls are not affected).
- `apps/web` pins TypeScript 5.9: `vue-tsc`/Volar and typescript-eslint do not support TypeScript 7 yet. Its `pnpm-workspace.yaml` only holds `allowBuilds` (esbuild); it is not a workspace of the repo.
- The starter kit frontend uses Vite+ (`vp`), not `vite`.
- Vite caches the types imported by `defineProps<...>()`: after changing one (e.g. `ProductFormOptions` in `resources/js/types`), restart `composer dev`, or the page drops the new props silently while `vue-tsc` and the tests pass.
- `components/ui/native-select` is patched: options render `selected` from the select's value (`context.ts`), because Vue's SSR does not mark the selected option of a slotted select and the page shows the first option until hydration. Do not overwrite it when re-adding shadcn components.
- `shadcn-vue add` writes `import { cn } from "cn"` in new `components/ui` files; change it to `@/lib/utils`, or the build fails.
- A data table `transform()` override stops the package from resolving relation columns (`from('title.name')`); add computed values as SQL aliases in `query()` instead.

- A reserved or sold key must belong to an order line (`product_keys_order_item_matches_status`): use `ProductKey::factory()->reserved()`/`sold()`, never just set `status`.
- "Marcar como pagado" and "Pagar con Webpay (prueba)" in the panel only exist with `ORDERS_MANUAL_PAYMENTS=true` (local); otherwise their routes return 404. With it on, the Webpay return redirects to the order in the panel instead of the storefront. `phpunit.xml` pins it to `false`, so tests do not depend on `.env`.
- Payment tests fake Transbank with `Http::fake` and the `webpay*()` helpers in `tests/Pest.php`; `Http::preventStrayRequests()` breaks Inertia page assertions, so panel tests do not use it.
- The payment confirmation email carries the keys: `MAIL_MAILER=log` would write them to `laravel.log`, so `SendOrderPaidEmail` fails instead. Locally use `smtp` to Mailpit (`127.0.0.1:1025`). Run `wayfinder:generate --with-form` (the Vite plugin uses `formVariants`), or `vue-tsc` loses the `.form()` helpers.
- `composer dev` also runs `schedule:work` (registered in `AppServiceProvider`), which expires pending orders every minute.

- Customers authenticate with Sanctum cookies on the `customer` guard, whose provider only sees `role = customer`. Use `actingAs($user, 'customer')` and `fromStorefront()` headers (in `tests/Pest.php`) in API tests; without them Sanctum does not start the session. Storefront and API must share the registrable domain in production (`SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_SECURE_COOKIE`); see `docs/domain/users.md`.
- Starter-kit panel auth tests (`tests/Feature/Auth`) use `->admin()` users: customers get the storefront's verification and reset emails instead of Fortify's.
- After changing an API Resource, regenerate the fixtures with `CONTRACTS_UPDATE=1 php artisan test --filter=ApiContract` (in `apps/api`) and update the valibot schemas in `apps/web`.
- `registerEndpoint` in `apps/web` tests only intercepts relative routes: the nuxt test project sets `apiBase` to `/api/v1` (`vitest.config.ts`). In the nuxt environment `import.meta.url` is not a `file:` URL; use `import.meta.dirname`.

## Definition of done

- `composer ci:check` (and `pnpm ci:check` in `apps/web` when touched) is green.
- New behavior has a test that fails without the change.
- UI changes are verified in the browser.
