# Roadmap

Overall project status. Updated in the same commit that closes each task.
Finished phases collapse to one line; details live in the git history.

## Current status

- **Current phase:** 1. Catalog
- **Next task:** the bulk key import from CSV.

## Phases

- [x] **0. Foundation:** repository, Docker (Postgres/Redis/Mailpit), `apps/api` with the Vue starter kit, PHPStan `max`, tests on Postgres, CI and Dependabot.
- [ ] **1. Catalog**
  - [x] Domain model designed: [`docs/domain/catalog.md`](domain/catalog.md) (implemented)
  - [x] Enums: `ProductType` (`digital_key`, `license`, `physical`) and `ProductKeyStatus`
  - [x] Models and migrations: ActivationService, Platform, Region, Title, Product (+ `product_platforms` pivot), ProductKey (encrypted key)
  - [x] Factories and seeders with fictitious data
  - [x] Users: `UserRole` enum, `role` column, seeded admin, panel restricted to admins, registration disabled: [`docs/domain/users.md`](domain/users.md) (implemented)
  - [x] Product and title CRUD in the admin panel: [`catalog.md` → Admin panel](domain/catalog.md#admin-panel-product-crud) (implemented)
  - [ ] Bulk key import from CSV
- [ ] **2. Public catalog API:** read-only `/api/v1` (filtered listing and product detail) with API Resources.
- [ ] **3. Orders and payments:** Order/OrderItem, stock reservation with `SKIP LOCKED`, `PaymentGateway` interface + Stripe in test mode, webhooks, and key delivery via queue and email.
- [ ] **4. Nuxt storefront (`apps/web`):** catalog, product page, cart, Sanctum auth, checkout and "My library".
- [ ] **5. Physical products:** addresses, shipments and shipping status.
- [ ] **6. Extras:** Meilisearch search, wishlist, reviews, coupons and sales dashboard.
- [ ] **7. Launch:** deployment, public demo and portfolio README (screenshots, GIF, diagram).

## Decisions

Domain model decisions live in `docs/domain/*.md`; this list keeps project-wide ones.

- Monorepo with two independent apps, no pnpm workspace at the root (revisit if types get shared).
- Postgres in tests too: key reservation relies on row locks that SQLite lacks.
- Agent instructions live in `AGENTS.md`; `CLAUDE.md` is not used.
- Everything in English: code comments, docs and commits.
- The user interface is Spanish only, for now: UI strings are Spanish literals (no `__()` keys), the app locale defaults to `es`, `lang/es/validation.php` holds the messages of the validation rules in use, and data tables format with `es-CL` and CLP (`config/inertia-datatable.php`). No other language is planned.
- Catalog domain: see [`docs/domain/catalog.md`](domain/catalog.md).
- Users: one `users` table with a `role` enum (admin, customer); admins use the panel session, customers use Sanctum (phase 4). See [`docs/domain/users.md`](domain/users.md).
