# Users domain model

Status: **agreed, not implemented yet**.

## Model

A single `users` table with a `role` column backed by a `UserRole` enum: `admin`, `customer`.

- **Admins** manage the catalog and orders from the admin panel (Inertia, `routes/web.php`). The first admin is created by a seeder; further admins are created from the panel. They authenticate with the Fortify session, and the panel is restricted to admins by a role middleware or gate.
- **Customers** register from the storefront (Nuxt, phase 4) and authenticate against `/api/v1` with Sanctum, installed in phase 4.

## Rejected alternatives

- Separate `admins` and `customers` tables with their own guards: duplicates authentication, factories and tests for no real isolation gain.
- `spatie/laravel-permission`: granular roles and permissions are overkill for two roles and add a dependency.

## Open questions

- Should Fortify's registration on the panel be disabled, since customers do not register there?
- Can an admin also act as a customer (buy from the storefront with the same account)?
