# Users domain model

Status: **implemented**.

## Model

A single `users` table with a `role` column backed by a `UserRole` enum: `admin`, `customer`.

- **Admins** manage the catalog and orders from the admin panel (Inertia, `routes/web.php`). The first admin is created by `AdminUserSeeder` from `ADMIN_NAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` (`config/admin.php`; it fails if email or password is missing and never overwrites an existing user); further admins will be created from the panel. They authenticate with the Fortify session, and every panel route sits behind the `access-admin-panel` gate (customers get 403).
- **Customers** register from the storefront (Nuxt) and authenticate against `/api/v1` with Sanctum (see [Customer authentication](#customer-authentication-phase-4)).
- **Roles are exclusive.** A user has exactly one role; an admin cannot buy from the storefront with the same account and uses a separate customer account instead. Keeps staff activity out of customer orders and lets the storefront API reject admin accounts outright (phase 4).
- **No registration on the panel.** Fortify's `registration` feature is disabled and `/register` returns 404. Admins are only created by the seeder or by another admin; customer sign-up is its own `/api/v1` endpoint and Action (`RegisterCustomer`), independent of Fortify's views.
- **The role is never mass assigned.** `role` has no database default and is not fillable: code that creates a user sets it explicitly with `forceFill`. The factory defaults to `customer`; use `->admin()` for panel users.

## Customer authentication (phase 4)

Decided and implemented on 2026-11-20. Tests: `tests/Feature/Api/V1/Auth/*Test.php` (including `StorefrontAuthBoundaryTest`) and `ApiAuthRoutesTest.php`.

- **Sanctum in SPA mode (cookie session + CSRF)**, not tokens. The cookie is `HttpOnly`, so no token lives in JS or `localStorage`; remember-me, session regeneration and CSRF come with Laravel. Tokens would need Nuxt to store them and proxy every call (a BFF), which is logic in Nuxt.
- **A `customer` guard that cannot see admins.** The `customer` guard (session driver) uses the `customers` provider, a `CustomerUserProvider` scoped to `role = customer`. `sanctum.guard` is `['customer']` and the `customers` password broker uses the same provider. An admin does not exist for the storefront: login fails with the generic error, a panel session gets 401 on `/api/v1/auth/me`, and "forgot password" sends nothing. Fortify stays panel-only, on the `web` guard.
- **Endpoints** under `/api/v1/auth`: `csrf-cookie` (Sanctum prefix `api/v1/auth`), `register`, `login` (remember-me), `logout`, `me`, `email/verification-notification`, `email/verify/{id}/{hash}` (signed, no session needed, idempotent), `forgot-password` and `reset-password`. Use cases in `app/Actions/Customers`; emails are lowercased and trimmed before validation (`NormalizesEmail`), because Postgres compares them case-sensitively. `CustomerResource` is `{name, email, email_verified}`, without the id.
- **Registration** asks for name, email and password (with confirmation). The role is always `customer`, set with `forceFill`.
- **Email verification is required to pay** (enforced by the checkout task), not to sign in, browse or fill a cart. The emails link to the storefront (`STOREFRONT_URL`): `/cuenta/verificar-email` and `/cuenta/restablecer`; admins keep Fortify's notifications.
- **Rate limits:** login 5/min per email and IP, registration 3/min per IP, password reset 5/min per IP, verification 6/min.
- **Shared session cookie.** The panel and the API share a host, so the storefront logout (which invalidates the session) also signs the panel out in that browser. Acceptable: admin and customer are different accounts.
- **Deployment constraint (phase 7).** The storefront and the API must share the registrable domain (e.g. `nextkeys.cl` and `api.nextkeys.cl`): two platform domains (`*.vercel.app` and `*.laravel.cloud`) are different sites and the `SameSite=Lax` cookie does not travel. Production sets `SESSION_DOMAIN=.nextkeys.cl`, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS=nextkeys.cl` and HTTPS. Locally, `localhost:3000` and `localhost:8000` are the same site.

## Rejected alternatives

- Separate `admins` and `customers` tables with their own guards: duplicates authentication, factories and tests for no real isolation gain.
- `spatie/laravel-permission`: granular roles and permissions are overkill for two roles and add a dependency.
