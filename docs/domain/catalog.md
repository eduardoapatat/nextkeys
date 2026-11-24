# Catalog domain model

Status: **implemented** (enums, migrations with every database rule, models, factories, seeders, tests, and the admin CRUD of titles and products with its Actions in `app/Actions/Catalog`). See _Implementation notes_ at the end.

## Overview

```
Title (the work: Hades, Microsoft Office Home 2024)
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

- **Activation service**: where a code is redeemed or activated. Named this way (not "store") because NextKeys is itself a store and licenses are activated on accounts. Seeded with the labels buyers recognize from key stores: Steam, PlayStation Store, Nintendo eShop, **Xbox** (console and Play Anywhere games), **Microsoft Store** (PC-only games) and **Microsoft Account** (software such as Office). The demo seeder adds **Norton Account** for the Norton license of the example, and **Kaspersky Account** and **ESET HOME** for the extra demo licenses (Windows 11 Home and Pro, Microsoft 365 Personal and Family, Kaspersky Standard, ESET NOD32 Antivirus) that fill the home's "Programas" section. All Microsoft codes are redeemed on the same account; the split is a label, not a technical difference.
- **Platform**: a device or OS. Seeded with Windows, macOS, PS4, PS5, Switch, Switch 2, Xbox One, Xbox Series, Android, iOS. Xbox One and Xbox Series are separate because key stores sell them as separate listings.
- **Region**: Global, Europe, LATAM, North America…

### `titles`

The work itself: shared content for all the ways it is sold. No slug, no page, no price, no publish flag.

| Column        | Notes                          |
| ------------- | ------------------------------ |
| `name`        | Used to generate product slugs |
| `description` | Shared by all its products     |

Starts with these two columns. Images, genres, developer/publisher and release date are added later as new columns, without moving data.

Editions (Standard, Deluxe) and license variations (device count, duration) are **separate titles**: what changes is the content description. Linking them (e.g. `parent_title_id`) can be added later without moving data.

### `products`

What gets sold. Has a page, is listed, goes into the cart.

| Column                  | Notes                                                                                                                      |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `title_id`              | FK → titles, `restrictOnDelete`                                                                                            |
| `type`                  | `ProductType`. **Immutable** after creation                                                                                |
| `slug`                  | Globally unique. Generated on creation, editable by the admin, does not change when the title is renamed                   |
| `activation_service_id` | FK, nullable (see rules)                                                                                                   |
| `region_id`             | FK, nullable (see rules)                                                                                                   |
| `price`                 | Integer CLP (no decimals): `19990` = $19.990. `CHECK (price > 0)`                                                          |
| `stock`                 | Integer, nullable. Only for `physical`. `CHECK (stock >= 0)`                                                               |
| `status`                | `ProductStatus`: `draft`, `published` or `archived` (see _Product status_). Default `draft`. CHECK `products_status_check` |
| `cover_image`           | Optional path of the variant's cover on the `public` disk (see _Variant cover and key hint_)                               |

Postgres has no unsigned integers (`unsignedInteger()` creates a plain `integer`), so the sign rules are explicit CHECKs. `UNIQUE (id, type)` is also required: it is redundant with the primary key, but Postgres needs it as the target of the composite FK from `product_keys`.

### `product_platforms`

Pivot `product_id` + `platform_id` (composite PK). `product_id` cascades on delete; `platform_id` is `restrictOnDelete`.

**The set of platforms the purchase covers**, for every type, and the only place platforms are stored. It is part of the product's identity: real stores sell "(PS5)" and "(PS4/PS5)" keys, Xbox console-only vs Play Anywhere (Xbox Series + Windows), Office for Mac only vs a multi-OS antivirus as different listings.

Incidental compatibility (a PS4 disc running on PS5, extra OS support of a Steam game) is not modeled; it goes in the description.

### `product_keys`

One row per code to deliver.

| Column         | Notes                                                                                                                                          |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `product_id`   | FK → products, `restrictOnDelete`                                                                                                              |
| `product_type` | Copy of the product's type, for the composite FK                                                                                               |
| `code`         | Stored trimmed (case preserved), `encrypted` cast, hidden from serialization, never logged                                                     |
| `code_hash`    | HMAC-SHA256 of the **normalized** code (trimmed, uppercased) with `KEY_HASH_SECRET` (not `APP_KEY`, so key rotation does not break it). Hidden |
| `code_hint`    | Plain-text mask of the code shown in the admin instead of it, e.g. `XXXXX-XXXXX-X7QF2`. Not hidden                                             |
| `status`       | `ProductKeyStatus`, default `available`                                                                                                        |

Indexes:

- `unique(code_hash)`, **global**: a real code is unique worldwide, so the same code cannot be imported twice, not even into two different products. Checked without decrypting.
- `(product_id, status)` for reservation with `SKIP LOCKED` (phase 3).

## Rules

### Per product type (Postgres CHECK on `products`)

| type          | activation_service_id | region_id | stock column |
| ------------- | --------------------- | --------- | ------------ |
| `digital_key` | required              | required  | null         |
| `license`     | required              | required  | null         |
| `physical`    | optional              | optional  | required     |

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
- A product is visible in the storefront when its `status` is `published`. Out-of-stock products stay visible with an "out of stock" notice; no rule hides them for now.

## Storefront and API

- Listing and search show products directly (like G2A, Kinguin, Eneba).
- Each product has a page, `/{product.slug}`: title content plus a selector of its **published** sibling products; switching updates the URL. There is no title page.
- API (phase 2): `GET /api/v1/products` (filterable listing) and `GET /api/v1/products/{slug}` (product with its title and published siblings).

### Public catalog API (phase 2)

Decided on 2026-11-20. Read-only, no authentication.

- **Endpoints.** `GET /api/v1/products`, `GET /api/v1/products/{slug}` and `GET /api/v1/catalog/filters`.
- **Visibility.** Only `published` products, in the listing, the detail and the `variants`. A `draft` or `archived` slug returns **404**, the same as an unknown slug (410 for archived was rejected: it reveals the slug existed). Out-of-stock products are listed (see _Stock and visibility_). Purchased archived products will be shown in "My library" (phase 4) through its own endpoint.
- **Filters (now).** Catalog facets only, by slug, several values comma-separated: `platform`, `type`, `activation_service`, `region` (e.g. `?platform=ps4,ps5&region=latam`). Values within one filter are OR; different filters are AND. `platform` matches products whose platform set contains any of the given platforms. **Deferred:** price range, name search, in-stock only.
- **Sorting.** `sort` = `newest` (default, `created_at desc`), `price`, `-price`, `name` (title name, ignoring case). Always `id` as the final tiebreaker so pages are stable (descending for `newest`, ascending otherwise).
- **Pagination.** Offset (`?page=N`), fixed 24 per page, with `total` and `last_page` (numbered pages and SEO-friendly URLs). Cursor pagination was rejected: no total, no page jumps, and the catalog is small.
- **Invalid input.** An unknown filter value, a nonexistent slug in a filter, an unknown `sort` or an invalid `page` returns **422** in Laravel's JSON format (`{message, errors}`), through a FormRequest. Silently ignoring them was rejected. The summary `message` is Spanish too (`lang/es.json`, parameter names via `attributes()`: `sort` → «orden», `page` → «página»). Any 404 under `/api/*` answers `{"message": "Producto no encontrado."}` for a product and `"Recurso no encontrado."` otherwise (custom renderer in `bootstrap/app.php`), never Laravel's default, which names the model class.
- **Product fields (listing and `variants`).** `slug`, `type {slug, name}` (`name` is the enum label; changed on 2026-11-20 for the storefront, see [`storefront.md`](storefront.md)), `price` (integer CLP), `in_stock` (boolean), `cover_band` (boolean: whether the storefront frames the cover in its platform's case), `cover_url` (absolute URL or `null`; Nuxt shows the placeholder), `title {name}`, `platforms [{slug, name}]`, `region {slug, name}` and `activation_service {slug, name}` (each nullable for `physical`). **Never** `stock`, key counts, `code`, `code_hash`, `code_hint`, `status`, ids or timestamps.
- **Stock.** Only `in_stock`: exact stock was rejected because it shows competitors and bots how many keys are left. Computed in SQL per type (available keys or the `stock` column), never one query per product.
- **Detail.** The product fields, plus `title {name, description}` and `variants`: every published product of the title, **including the current one**, with the listing fields, cheapest first (then `id`). Nuxt builds the selector without another request and marks the current variant by comparing slugs.
- **Filter options.** `GET /api/v1/catalog/filters` returns `{slug, name, count}` lists of platforms, activation services and regions from the reference tables, sorted by name ignoring case (the database collation sorts `macOS` after `Xbox`), plus the product types from the enum. It takes the listing's facets (same parameters and 422s, `ValidatesListingFacets`): `count` is how many published products the option would list with the **other** facets applied (disjunctive facets: a facet's own selection is left out, its values being alternatives), and the storefront hides options that count zero unless checked, so it never offers an empty combination (Steam + Android). One grouped query per facet plus the reference lists, a fixed number whatever the data; what each facet matches lives in one place, `ProductListingFilters::apply()`, shared with the listing. Counts were first rejected (extra queries per request) and adopted on 2026-11-24, when the user hit empty combinations. Hardcoding the options in Nuxt was rejected (the admin edits them).
- **Cross-cutting.** Versioning by the `/api/v1` prefix. Rate limit of 120 requests per minute per IP. No application or HTTP cache for now. CORS allows only `GET` from `STOREFRONT_URL` (a new environment variable; with a single allowed origin the CORS middleware always answers with that origin, and browsers block any other). Sanctum was installed with `install:api` at the user's request (its `personal_access_tokens` migration renumbered to follow the catalog ones), but stays unused until customer auth in phase 4.
- **Implementation.** `app/Actions/Catalog/Storefront`: `ListPublishedProducts` (paginates 24), `FindPublishedProduct` (sets the `variants` relation) and `ListCatalogFilters`. They share the `Product::storefront()` scope (published, `in_stock` and the eager loads); `withInStock()` adds `in_stock` as a SQL `CASE` with an `EXISTS` on available keys, so the listing runs a fixed number of queries. `ProductSort` (enum) applies each order. `ListProductsRequest` splits the comma-separated facets and validates each slug against its table. `ProductResource` / `ProductDetailResource` are explicit allowlists. Routes in `routes/api.php` (`api.v1.*`), throttle via `throttleApi()` and the `api` limiter in `AppServiceProvider`.

## Slugs

Deterministic suggestion on creation: title name + activation service (or `physical` + activation service if any) + all platforms (sorted by name) + region, skipping the parts that are null. The admin can shorten it; uniqueness is guaranteed by the slug's unique index, not by the rule.

Examples: `hades-steam-macos-windows-global`, `hades-playstation-store-ps4-ps5-latam`, `hades-physical-ps5-north-america`, `elden-ring-physical-playstation-store-ps5-europe`. No SKU for now.

## Example

**products** (activation services, platforms and regions by name). The demo seeder seeds these 11 and, for fuller shelves, 24 more real games (Cyberpunk 2077, God of War Ragnarök, Marvel's Spider-Man 2, Super Mario Bros. Wonder, Mario Kart World, Halo Infinite, Forza Horizon 5, Baldur's Gate 3, Hollow Knight, Stardew Valley, Red Dead Redemption 2, The Witcher 3: Wild Hunt, Animal Crossing: New Horizons) across every platform; `GenerateProductSlugTest` checks all 35 slugs.

| id  | title                                     | type        | slug                                                                      | activation service | region        | platforms                    | price  | stock | status    |
| --- | ----------------------------------------- | ----------- | ------------------------------------------------------------------------- | ------------------ | ------------- | ---------------------------- | ------ | ----- | --------- |
| 1   | Hades                                     | digital_key | hades-steam-macos-windows-global                                          | Steam              | Global        | Windows, macOS               | 19990  | —     | published |
| 2   | Hades                                     | digital_key | hades-playstation-store-ps5-latam                                         | PlayStation Store  | LATAM         | PS5                          | 21990  | —     | published |
| 3   | Hades                                     | digital_key | hades-playstation-store-ps4-ps5-latam                                     | PlayStation Store  | LATAM         | PS4, PS5                     | 24990  | —     | published |
| 4   | Hades                                     | digital_key | hades-xbox-windows-xbox-series-global                                     | Xbox               | Global        | Xbox Series, Windows         | 20990  | —     | published |
| 5   | Hades                                     | physical    | hades-physical-ps5-north-america                                          | —                  | North America | PS5                          | 34990  | 5     | published |
| 6   | Elden Ring                                | physical    | elden-ring-physical-playstation-store-ps5-latam                           | PlayStation Store  | LATAM         | PS5                          | 44990  | 12    | published |
| 7   | Elden Ring                                | digital_key | elden-ring-playstation-store-ps5-latam                                    | PlayStation Store  | LATAM         | PS5                          | 39990  | —     | published |
| 8   | The Legend of Zelda: Tears of the Kingdom | digital_key | the-legend-of-zelda-tears-of-the-kingdom-nintendo-eshop-switch-latam      | Nintendo eShop     | LATAM         | Switch                       | 59990  | —     | published |
| 9   | The Legend of Zelda: Tears of the Kingdom | physical    | the-legend-of-zelda-tears-of-the-kingdom-physical-nintendo-eshop-switch-2 | Nintendo eShop     | —             | Switch 2                     | 64990  | 3     | draft     |
| 10  | Microsoft Office Home 2024                | license     | microsoft-office-home-2024-microsoft-account-macos-global                 | Microsoft Account  | Global        | macOS                        | 129990 | —     | published |
| 11  | Norton 360 Deluxe                         | license     | norton-360-deluxe-norton-account-android-ios-macos-windows-global         | Norton Account     | Global        | Windows, macOS, Android, iOS | 34990  | —     | published |

Products 6 and 7 share title, activation service, region and platforms but differ in type: a code-in-box PS5 copy and the same game as a delivered key. Product 9 is a Switch 2 Game-Key Card (physical with an activation service, no region).

**Rejected by the database**

| Attempt                                               | Blocked by                              |
| ----------------------------------------------------- | --------------------------------------- |
| Another Hades · PlayStation Store · LATAM · PS5       | constraint trigger (same identity as 2) |
| Physical with two platforms, or any product with none | constraint trigger                      |
| Changing a product's type                             | `BEFORE UPDATE` trigger                 |
| Physical without stock, or with negative stock        | CHECK                                   |
| Price 0 or negative                                   | CHECK                                   |
| License without region                                | CHECK                                   |
| Slug with spaces or uppercase                         | CHECK                                   |
| Key for product 5 (physical)                          | composite FK + CHECK                    |
| The same code imported into two products              | unique `code_hash`                      |

## Admin panel (product CRUD)

Decisions taken before planning the CRUD:

- **Deleting products: guarded hard delete.** A product can be deleted only while nothing references it (no `product_keys` rows now; no orders from phase 3). Otherwise the Action returns a readable validation error suggesting to archive it (`status = archived`, see _Product status_). `SoftDeletes` was rejected: the identity trigger and every query would have to decide whether a deleted product still blocks an identical new one.
- **Titles have their own section.** A small CRUD (name, description). The product form only selects an existing title; it never creates one inline. A title cannot be deleted while it has products (`restrictOnDelete`), and the Action reports it as a validation error.
- **Slug generation.** The slug field is optional. Left empty on creation, the server builds the suggestion from the rule in _Slugs_ and, if it is taken, appends `-2`, `-3`… until it is free. A slug typed by the admin that is already taken is a validation error (no suffix). On edit the slug is kept unless the admin changes it; renaming the title or other fields never regenerates it. The rule lives only on the server; the form shows no live preview.
- **Product listing: one filter, the status.** A paginated list that shows the data and filters by status, to find drafts and archived products. More filters can be added later.
- **Admin listings use `eduardoapatat/laravel-inertia-datatable`.** Pagination and sorting are resolved on the server and the state lives in the URL; adding filters later is a column method, not a rewrite. Table classes live in `app/DataTables`.
- **Implementation.** `SaveProduct` (shared by `CreateProduct` and `UpdateProduct`) validates the per-type rules, the slug and the identity, writes the product and its platforms in one transaction and runs the identity constraint triggers before it ends (`SET CONSTRAINTS … IMMEDIATE`), turning a unique violation from a race into the same validation error. `DeleteProduct` and `DeleteTitle` lock the row (`lockForUpdate`) before checking it is unreferenced. The list's stock is one correlated subquery (`coalesce(stock, count of available keys)`, valid because of `products_stock_only_for_physical`).
- **The product type cannot change by design.** The data object the create and update Actions receive has no `type` field: the type is a separate argument of the create Action only. The `products_type_immutable` trigger stays as the safety net.

## Admin panel (key import)

Decisions taken before planning the bulk import from CSV:

- **CSV format: one column, no header.** One code per line; blank lines are ignored. If the first line is exactly `code` (case-insensitive), it is treated as a header and skipped. No extra columns.
- **Invalid rows: import the valid ones and report the rest.** Valid codes are inserted in one transaction; the result lists every rejected row with its line number and reason (invalid format, repeated in the file, already exists). A code that collides with `unique(code_hash)` in a race (inserted by another import between the check and the write) is reported as "already exists", never as a Postgres exception.
- **Normalization and format.** The code is trimmed and stored with its original case; the hash normalizes it further (trim + uppercase), so `abc-1` and `ABC-1` are duplicates. A valid code has 5 to 100 characters from `[A-Za-z0-9-]`, no inner spaces. No per-activation-service format (real formats vary); it can be added later as a field on `activation_services`.
- **Size and processing: 1 MB and 5,000 codes per file, processed in the request.** A batched insert is fast enough at that size; no queue, worker or import status table. Larger batches are split into several files.
- **Only key-delivered products.** The import rejects `physical` products with a validation error before reading the file; the composite FK stays as the safety net.

- **Implementation.** `ImportProductKeys` validates the file and classifies each line, then inserts the valid codes in one transaction, in chunks of 1,000, with `INSERT … ON CONFLICT (code_hash) DO NOTHING RETURNING code_hash`: a code whose hash does not come back already existed, also when a concurrent import committed it after the file was read. Inserting a key takes `FOR KEY SHARE` on its product row, so the import serializes with `DeleteProduct`'s `lockForUpdate`. A bulk insert skips the `ProductKeyCode` cast, so both use `ProductKey::storedCodeAttributes()`, the only place that encrypts a code and computes its hash. The result carries line numbers and reasons, never codes; the controller flashes at most 100 rejected lines plus the total. The keys list (`ProductKeysTable`) selects neither `code` nor `code_hash`.

## Variant cover and key hint

Decisions taken on 2026-11-20, after closing the key import:

- **Titles stay.** A title is the game: it groups its products (variants) for the storefront, the identity rule (`title_id` + activation service + region + platforms) and the slugs. Removing it was rejected: identity and grouping would depend on a free-text name, and the name and description would be repeated in every variant.
- **Each product gets its own cover** (`cover_image`, a path on the `public` disk): the PS5 box is not the Switch one. Optional: a product without a cover shows a placeholder in the storefront. Titles have no cover and there is no fallback, so each variant has one source of truth. JPG, PNG or WebP, at most 2 MB, at least 300×400, stored as uploaded (no conversion or thumbnails, which would need a new dependency). The code uses `Storage::disk()`, so moving to S3/R2 later is configuration.
- **Cover band** (2026-11-21). `cover_band` (boolean, default `true`) says whether the storefront dresses the cover as its platform's retail case (band with the official mark plus frame, see [`storefront.md`](storefront.md)). The panel turns it off for an uploaded cover that already carries its own band; a product without a cover always gets the case. Covered by `ProductControllerTest` and the API contract.
- **The description stays on the title only.** It describes the game, which every variant shares. A Deluxe or Gold edition is a separate title with its own description, so a variant never needs its own. A per-product description was considered and reverted the same day.
- **Key hint.** A new plain-text column `code_hint` holds a mask of the code computed at import, e.g. `XXXXX-XXXXX-7QF2`: every letter and digit becomes `X` except the last 4, capped at a third of the code's length (rounded down), so short codes are not almost exposed. Hyphens are kept. It shows the length and hyphen positions, which is acceptable (a 15-character Steam key still hides 11). The last group after the last hyphen was rejected: a code without hyphens would be shown whole. The full code stays encrypted, never hashed only: phase 3 must deliver it to the buyer. The keys list shows the hint and can search by its visible characters.

- **Implementation.** `ProductKey::storedCodeAttributes()` also returns `code_hint` (`ProductKey::hintFor()`), so the cast and the bulk import both write it; the migration backfills existing rows through the model before making the column `NOT NULL`. The keys list shows the hint and searches it. `SaveProduct` validates the cover with the other catalog rules, stores the file **before** the transaction (files are not transactional), deletes it if the save throws, and deletes a replaced or removed cover only after the save commits; `DeleteProduct` deletes the cover after the row. The edit form sends a cover as `POST` with `_method=put`, because PHP does not parse multipart `PUT` bodies.

## Product status

Decided and implemented on 2026-11-20: `is_published` (boolean) was replaced by a `status` column with a `ProductStatus` enum, the same pattern as `ProductType`.

| Value       | UI label  | Meaning                                                                                         |
| ----------- | --------- | ----------------------------------------------------------------------------------------------- |
| `draft`     | Borrador  | Being prepared; not in the storefront. Default for new products.                                |
| `published` | Publicado | For sale; the only status the storefront shows.                                                 |
| `archived`  | Archivado | No longer sold and hidden from the storefront, but kept (it has keys, and orders from phase 3). |

- Transitions are free: the admin can move a product between any two statuses, including out of `archived`. Real restrictions, if any, come with orders in phase 3.
- `archived` replaces the "unpublish it" suggestion: deleting a product with keys fails with an error suggesting to archive it.
- "Out of stock" is not a status: it is derived from the stock.
- **Implementation.** The migration maps `is_published = true` to `published` and `false` to `draft`, adds `products_status_check` and drops the flag. The admin form uses a status select and the product list filters by status.

## Out of scope (for now)

- Gift cards, wallet top-ups and subscriptions (Steam Wallet, Game Pass): would need new types (face value, duration). Possible phase 6 extra; the enum can grow.
- Languages per product.

## Demo catalog and cover art (IGDB)

Decided on 2026-11-21 with the user:

- **The demo catalog is real.** `CatalogDemoSeeder` seeds real games (Hades, Elden Ring, The Legend of Zelda: Tears of the Kingdom) and programs (Microsoft Office Home 2024, Norton 360 Deluxe) so the store reads as believable. Prices, stock and keys stay fictitious, as the storefront footer says.
- **Covers come from IGDB, never from the repository.** Cover art is copyrighted by the publishers (stores show it under their distribution agreements), so the repo does not redistribute it: `php artisan catalog:import-covers` downloads each title's official cover at 1080p (about 810 × 1080) into `covers/` on the `public` disk. One lookup per title (exact name; when several games share it, the most rated one, e.g. Hades 2020 over a 1990s namesake), one file per product (deleting a product never breaks another), `cover_band` set to `true` (IGDB art carries no platform band). Options: `--force` replaces existing covers (old files deleted after the save), `--title="Hades"` limits it to one title. IGDB has no programs: the licenses keep the generated cover until one is uploaded in the panel.
- **Credentials.** A Twitch application's `IGDB_CLIENT_ID` and `IGDB_CLIENT_SECRET` (`config/services.php` → `igdb`). Without them the command only warns. The app token is cached until shortly before it expires; errors never echo the credentials or the token.
- **Boundary.** `App\Catalog\CoverArt\CoverArtSource` is the interface; `App\Catalog\CoverArt\Igdb\IgdbCoverArt` the only implementation and, with the Transbank gateway, the only code allowed to use the `Http` facade (`tests/Unit/PaymentsArchTest.php`). `StoreProductCover` writes covers for both the panel upload and the import. Covered by `tests/Feature/Catalog/ImportCoversCommandTest.php`.

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

| Namespace | Used by                      | Second argument                |
| --------- | ---------------------------- | ------------------------------ |
| 1         | `products_assert_identity()` | `title_id` (cast to `integer`) |

## Phase 3 reminders (do not block the catalog)

All four are resolved and implemented in [`orders.md`](orders.md) (2026-11-20).

- Reserved keys need an expiry (`reserved_until`) and a link to the order; otherwise an abandoned checkout keeps a key `reserved` forever. Resolved in [`orders.md`](orders.md#reservation): `order_item_id` on the key and `orders.expires_at`.
- Physical stock must decrease atomically (`UPDATE … SET stock = stock - 1 WHERE stock > 0`, or `lockForUpdate`), or two simultaneous purchases can sell the last box twice.
- Physical products have no "reserved" state like keys; decide whether a box is held during checkout.
- Enforce the key lifecycle in the database: a `BEFORE UPDATE OF status` trigger on `product_keys` that rejects leaving `sold` (a sold key never goes back to `available` or `reserved`). Allowed transitions: `available` → `reserved`, `reserved` → `available` (expired or cancelled checkout), `reserved` → `sold`. Like the other rules, it gets a test that updates the row directly, bypassing the Action, and fails without the trigger.
