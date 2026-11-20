# Catalog domain model

Status: **implemented** (enums, migrations with every database rule, models, factories, seeders, tests). The Action layer ("In the Action") arrives with the product CRUD. See *Implementation notes* at the end.

## Overview

```
Title (the work: Starfall, Office Suite Pro)
 └─ Product (what is sold, has its own page)       ← cart and orders reference it
     ├─ ActivationService + Region                 ← where the code is redeemed / the game's region
     ├─ Platforms (pivot)                          ← the set of platforms the purchase covers
     └─ ProductKeys                                ← codes to deliver (key-delivered types only)
```

This is the product/variant pattern used by Shopify, Saleor, Sylius and Medusa, renamed: our `Title` is their product, our `Product` is their variant. `Game` was rejected as a name because software licenses are not games.

## Enums

- `ProductType`: `digital_key`, `license`, `physical`. `isDeliveredByKey()` is true for `digital_key` and `license`.
- `ProductKeyStatus`: `available`, `reserved`, `sold`.

## Tables

### Reference tables: `activation_services`, `platforms`, `regions`

Same shape: `id`, `name`, `slug` (unique), timestamps. Tables, not enums, so the admin can add entries without a deploy. Not deletable while in use (`restrictOnDelete`).

- **Activation service**: where a code is redeemed or activated. Named this way (not "store") because NextKeys is itself a store and licenses are activated on accounts. Seeded with the labels buyers recognize from key stores: Steam, PlayStation Store, Nintendo eShop, **Xbox** (console and Play Anywhere games), **Microsoft Store** (PC-only games) and **Microsoft Account** (software such as Office). The demo seeder adds the fictitious **Shield Account** used in the example. All Microsoft codes are redeemed on the same account; the split is a label, not a technical difference.
- **Platform**: a device or OS. Seeded with Windows, macOS, PS4, PS5, Switch, Switch 2, Xbox One, Xbox Series, Android, iOS. Xbox One and Xbox Series are separate because key stores sell them as separate listings.
- **Region**: Global, Europe, LATAM, North America…

### `titles`

The work itself: shared content for all the ways it is sold. No slug, no page, no price, no publish flag.

| Column | Notes |
|---|---|
| `name` | Used to generate product slugs |
| `description` | Shared by all its products |

Starts with these two columns. Images, genres, developer/publisher and release date are added later as new columns, without moving data.

Editions (Standard, Deluxe) and license variations (device count, duration) are **separate titles**: what changes is the content description. Linking them (e.g. `parent_title_id`) can be added later without moving data.

### `products`

What gets sold. Has a page, is listed, goes into the cart.

| Column | Notes |
|---|---|
| `title_id` | FK → titles, `restrictOnDelete` |
| `type` | `ProductType`. **Immutable** after creation |
| `slug` | Globally unique. Generated on creation, editable by the admin, does not change when the title is renamed |
| `activation_service_id` | FK, nullable (see rules) |
| `region_id` | FK, nullable (see rules) |
| `price` | Integer CLP (no decimals): `19990` = $19.990. `CHECK (price > 0)` |
| `stock` | Integer, nullable. Only for `physical`. `CHECK (stock >= 0)` |
| `is_published` | Admin decision: draft or for sale. Default `false` |

Postgres has no unsigned integers (`unsignedInteger()` creates a plain `integer`), so the sign rules are explicit CHECKs. `UNIQUE (id, type)` is also required: it is redundant with the primary key, but Postgres needs it as the target of the composite FK from `product_keys`.

### `product_platforms`

Pivot `product_id` + `platform_id` (composite PK). `product_id` cascades on delete; `platform_id` is `restrictOnDelete`.

**The set of platforms the purchase covers**, for every type, and the only place platforms are stored. It is part of the product's identity: real stores sell "(PS5)" and "(PS4/PS5)" keys, Xbox console-only vs Play Anywhere (Xbox Series + Windows), Office for Mac only vs a multi-OS antivirus as different listings.

Incidental compatibility (a PS4 disc running on PS5, extra OS support of a Steam game) is not modeled; it goes in the description.

### `product_keys`

One row per code to deliver.

| Column | Notes |
|---|---|
| `product_id` | FK → products, `restrictOnDelete` |
| `product_type` | Copy of the product's type, for the composite FK |
| `code` | Stored as entered, `encrypted` cast, hidden from serialization, never logged |
| `code_hash` | HMAC-SHA256 of the **normalized** code (trimmed, uppercased) with `KEY_HASH_SECRET` (not `APP_KEY`, so key rotation does not break it). Hidden |
| `status` | `ProductKeyStatus`, default `available` |

Indexes:

- `unique(code_hash)`, **global**: a real code is unique worldwide, so the same code cannot be imported twice, not even into two different products. Checked without decrypting.
- `(product_id, status)` for reservation with `SKIP LOCKED` (phase 3).

## Rules

### Per product type (Postgres CHECK on `products`)

| type | activation_service_id | region_id | stock column |
|---|---|---|---|
| `digital_key` | required | required | null |
| `license` | required | required | null |
| `physical` | optional | optional | required |

For `digital_key` and `license` the region is an activation lock. For `physical` it is the game's region, informational: current consoles do not region-lock discs.

A `physical` product with an activation service is a **code-in-box** product (a PS5 box that only contains a download code, a Switch 2 Game-Key Card): it is shipped and counted by the `stock` column like any box, but the game must be downloaded from that service, and its region may lock activation. The code inside the box is never stored: physical products never have rows in `product_keys`. A full-disc product and a code-in-box product of the same game are told apart by the activation service.

Slug format: `CHECK (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$')`.

### Identity and platforms (constraint trigger)

A product's identity is `title + type + activation service + region + the exact set of platforms`. No two products may share it: "PS5" and "PS4/PS5" in the same region are different products, the same set twice is a duplicate.

A unique index cannot express this (it only compares columns of one row, and the set lives in the pivot), and a copied signature column was rejected (duplicated data that can drift from the pivot). It is enforced in the database with a **constraint trigger**, `DEFERRABLE INITIALLY DEFERRED`, so it runs at commit, when the product and all its platforms are already written:

- Fires `AFTER INSERT OR UPDATE OF title_id, type, activation_service_id, region_id` on `products` (price or stock changes do not affect identity) and `AFTER INSERT OR UPDATE OR DELETE` on `product_platforms` (an `UPDATE` of `platform_id` would otherwise bypass it; both the old and the new `product_id` are checked).
- If the product no longer exists (it was deleted and its pivot rows cascaded), it does nothing.
- Checks: at least one platform; exactly one for `physical`; no other product with the same identity (`NULLS NOT DISTINCT` semantics for the nullable columns).
- Before checking, takes a transaction-scoped lock on the title with the two-argument form, `pg_advisory_xact_lock(<constant for products>, title_id)`, so two concurrent transactions creating the same product cannot both pass, and the lock cannot collide with future advisory locks on other tables.

Rejected alternatives: a regular (non-deferred) trigger (it fires per pivot row and would see half-written sets), a Postgres `RULE` (legacy, discouraged by the docs), a stored procedure (direct inserts would bypass it), an Action-only check (any other write path would bypass it).

A `BEFORE UPDATE` trigger rejects changing `products.type` (immutable).

### In the Action

The Action that creates or edits a product runs the same checks first and returns validation errors ("this product already exists") for the admin panel. The triggers are the safety net for every other write path (seeders, tinker, future code), and raise a database exception. Tests cover both layers.

### Keys

- Only `digital_key` and `license` products have keys. Enforced in the database: composite FK `product_keys(product_id, product_type)` → `products(id, type)` plus `CHECK (product_type IN ('digital_key', 'license'))`.
- Each key is sold once: `available` → `reserved` → `sold`, never back to `available` after `sold`.

### Stock and visibility

- Stock of `digital_key` / `license` = count of `available` keys. Stock of `physical` = the `stock` column. Never both: one source of truth per type. Exposed through a single `availableStock()`.
- A product is visible in the storefront when `is_published`. Out-of-stock products stay visible with an "out of stock" notice; no rule hides them for now.

## Storefront and API

- Listing and search show products directly (like G2A, Kinguin, Eneba).
- Each product has a page, `/{product.slug}`: title content plus a selector of its **published** sibling products; switching updates the URL. There is no title page.
- API (phase 2): `GET /api/v1/products` (filterable listing) and `GET /api/v1/products/{slug}` (product with its title and published siblings).

## Slugs

Deterministic suggestion on creation: title name + activation service (or `physical` + activation service if any) + all platforms (sorted by name) + region, skipping the parts that are null. The admin can shorten it; uniqueness is guaranteed by the slug's unique index, not by the rule.

Examples: `starfall-steam-macos-windows-global`, `starfall-playstation-store-ps4-ps5-latam`, `starfall-physical-ps5-north-america`, `neon-heist-physical-playstation-store-ps5-europe`. No SKU for now.

## Example

**products** (activation services, platforms and regions by name)

| id | title | type | slug | activation service | region | platforms | price | stock | published |
|---|---|---|---|---|---|---|---|---|---|
| 1 | Starfall | digital_key | starfall-steam-macos-windows-global | Steam | Global | Windows, macOS | 19990 | — | yes |
| 2 | Starfall | digital_key | starfall-playstation-store-ps5-latam | PlayStation Store | LATAM | PS5 | 21990 | — | yes |
| 3 | Starfall | digital_key | starfall-playstation-store-ps4-ps5-latam | PlayStation Store | LATAM | PS4, PS5 | 24990 | — | yes |
| 4 | Starfall | digital_key | starfall-xbox-windows-xbox-series-global | Xbox | Global | Xbox Series, Windows | 20990 | — | yes |
| 5 | Starfall | physical | starfall-physical-ps5-north-america | — | North America | PS5 | 34990 | 5 | yes |
| 6 | Neon Heist | physical | neon-heist-physical-playstation-store-ps5-latam | PlayStation Store | LATAM | PS5 | 79990 | 12 | yes |
| 7 | Neon Heist | digital_key | neon-heist-playstation-store-ps5-latam | PlayStation Store | LATAM | PS5 | 74990 | — | yes |
| 8 | Pixel Kart | digital_key | pixel-kart-nintendo-eshop-switch-latam | Nintendo eShop | LATAM | Switch | 39990 | — | yes |
| 9 | Pixel Kart | physical | pixel-kart-physical-nintendo-eshop-switch-2 | Nintendo eShop | — | Switch 2 | 44990 | 3 | no |
| 10 | Office Suite Pro | license | office-suite-pro-microsoft-account-macos-global | Microsoft Account | Global | macOS | 49990 | — | yes |
| 11 | Shield Antivirus | license | shield-antivirus-shield-account-android-ios-macos-windows-global | Shield Account | Global | Windows, macOS, Android, iOS | 14990 | — | yes |

Products 6 and 7 share title, activation service, region and platforms but differ in type: a code-in-box PS5 copy and the same game as a delivered key. Product 9 is a Switch 2 Game-Key Card (physical with an activation service, no region).

**Rejected by the database**

| Attempt | Blocked by |
|---|---|
| Another Starfall · PlayStation Store · LATAM · PS5 | constraint trigger (same identity as 2) |
| Physical with two platforms, or any product with none | constraint trigger |
| Changing a product's type | `BEFORE UPDATE` trigger |
| Physical without stock, or with negative stock | CHECK |
| Price 0 or negative | CHECK |
| License without region | CHECK |
| Slug with spaces or uppercase | CHECK |
| Key for product 5 (physical) | composite FK + CHECK |
| The same code imported into two products | unique `code_hash` |

## Out of scope (for now)

- Gift cards, wallet top-ups and subscriptions (Steam Wallet, Game Pass): would need new types (face value, duration). Possible phase 6 extra; the enum can grow.
- Languages per product.

## Implementation notes

- **Deferred triggers and tests.** Feature tests use `RefreshDatabase`, which wraps each test in a transaction that is rolled back and never committed, so `INITIALLY DEFERRED` triggers would never fire and their tests would pass without them. Tests of the constraint trigger must run `SET CONSTRAINTS ALL IMMEDIATE` right after the operation, and must be shown to fail without the trigger.
- Every database rule above (CHECKs, composite FK, triggers, unique `code_hash`) gets a test that hits the database directly, bypassing the Action.
- `KEY_HASH_SECRET` is a new environment variable; the user adds it to `.env` (agents do not edit `.env`). Tests read it from `phpunit.xml`; `ProductKey::hashCode()` throws if it is empty.
- **Products and their platforms are written in one transaction.** Under autocommit the product `INSERT` commits alone and the deferred trigger rejects it for having no platforms. `DatabaseSeeder` and `CatalogDemoSeeder` wrap their work in `DB::transaction`.
- **`code_hash` is written by the `App\Casts\ProductKeyCode` cast**, which encrypts `code` and sets `code_hash` in the same assignment, so a code cannot be stored without its hash. A `saving` event was rejected because seeders run `WithoutModelEvents`.
- **Database errors**: CHECKs have explicit names (`products_price_positive`, `products_slug_format`, …) and the triggers raise `check_violation`, or `unique_violation` for a duplicate identity (Laravel turns it into `UniqueConstraintViolationException`, which the Action can catch).
- Trigger functions use `CREATE OR REPLACE`: `migrate:fresh` drops tables but not functions.
- `SET CONSTRAINTS ALL IMMEDIATE` stays in effect for the rest of the transaction; the test helper `checkDeferredConstraints()` (`tests/Pest.php`) sets them back to `DEFERRED`.
- The advisory lock's concurrency is not covered by tests (it needs two simultaneous connections).

### Advisory lock namespaces

The first argument of `pg_advisory_xact_lock(namespace, id)`. Add a row before using a new one.

| Namespace | Used by | Second argument |
|---|---|---|
| 1 | `products_assert_identity()` | `title_id` (cast to `integer`) |

## Phase 3 reminders (do not block the catalog)

- Reserved keys need an expiry (`reserved_until`) and a link to the order; otherwise an abandoned checkout keeps a key `reserved` forever.
- Physical stock must decrease atomically (`UPDATE … SET stock = stock - 1 WHERE stock > 0`, or `lockForUpdate`), or two simultaneous purchases can sell the last box twice.
- Physical products have no "reserved" state like keys; decide whether a box is held during checkout.
