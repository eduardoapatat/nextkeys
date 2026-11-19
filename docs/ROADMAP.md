# Roadmap

Overall project status. Updated in the same commit that closes each task.
Finished phases collapse to one line; details live in the git history.

## Current status

- **Current phase:** 1. Catalog
- **Next task:** design the catalog domain in `/plan` (enums, models, migrations, seeders).

## Phases

- [x] **0. Foundation:** repository, Docker (Postgres/Redis/Mailpit), `apps/api` with the Vue starter kit, PHPStan `max`, tests on Postgres, CI and Dependabot.
- [ ] **1. Catalog**
  - [ ] Enums: `ProductType` (`digital_key`, `license`, `physical`), platforms and regions
  - [ ] Models and migrations: Platform, Region, Product, GameKey (encrypted key)
  - [ ] Factories and seeders with fictitious data
  - [ ] Product CRUD in the admin panel
  - [ ] Bulk key import from CSV
- [ ] **2. Public catalog API:** read-only `/api/v1` (filtered listing and product detail) with API Resources.
- [ ] **3. Orders and payments:** Order/OrderItem, stock reservation with `SKIP LOCKED`, `PaymentGateway` interface + Stripe in test mode, webhooks, and key delivery via queue and email.
- [ ] **4. Nuxt storefront (`apps/web`):** catalog, product page, cart, Sanctum auth, checkout and "My library".
- [ ] **5. Physical products:** addresses, shipments and shipping status.
- [ ] **6. Extras:** Meilisearch search, wishlist, reviews, coupons and sales dashboard.
- [ ] **7. Launch:** deployment, public demo and portfolio README (screenshots, GIF, diagram).

## Decisions

- Monorepo with two independent apps, no pnpm workspace at the root (revisit if types get shared).
- Postgres in tests too: key reservation relies on row locks that SQLite lacks.
- Agent instructions live in `AGENTS.md`; `CLAUDE.md` is not used.
- Everything in English: code comments, docs and commits.
