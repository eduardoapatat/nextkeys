# Roadmap

Overall project status. Updated in the same commit that closes each task.
Finished phases collapse to one line; details live in the git history.

## Current status

- **Current phase:** 2. Public catalog API
- **Next task:** design the read-only `/api/v1` catalog (filtered listing and product detail).

## Phases

- [x] **0. Foundation:** repository, Docker (Postgres/Redis/Mailpit), `apps/api` with the Vue starter kit, PHPStan `max`, tests on Postgres, CI and Dependabot.
- [x] **1. Catalog:** domain model ([`docs/domain/catalog.md`](domain/catalog.md)), users with roles ([`docs/domain/users.md`](domain/users.md)), admin CRUD for titles and products, bulk key import from CSV ([`catalog.md` → Admin panel (key import)](domain/catalog.md#admin-panel-key-import)), per-variant cover and key hint ([`catalog.md` → Variant cover and key hint](domain/catalog.md#variant-cover-and-key-hint)), and product status ([`catalog.md` → Product status](domain/catalog.md#product-status)).
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
- The user interface is Spanish only, for now: UI strings are Spanish literals (no `__()` keys), the app locale defaults to `es`, `lang/es/validation.php` holds the messages of the validation rules in use, and data tables format with `es-CL` and CLP (`config/inertia-datatable.php`). No other language is planned. Product keys are called **claves** in the UI ("Claves", "Gestionar claves", "Clave digital"), never "keys"; code, routes and docs keep `keys`.
- Catalog domain: see [`docs/domain/catalog.md`](domain/catalog.md).
- Users: one `users` table with a `role` enum (admin, customer); admins use the panel session, customers use Sanctum (phase 4). See [`docs/domain/users.md`](domain/users.md).
