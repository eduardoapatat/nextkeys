# Users domain model

Status: **implemented**.

## Model

A single `users` table with a `role` column backed by a `UserRole` enum: `admin`, `customer`.

- **Admins** manage the catalog and orders from the admin panel (Inertia, `routes/web.php`). The first admin is created by `AdminUserSeeder` from `ADMIN_NAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` (`config/admin.php`; it fails if email or password is missing and never overwrites an existing user); further admins will be created from the panel. They authenticate with the Fortify session, and every panel route sits behind the `access-admin-panel` gate (customers get 403).
- **Customers** register from the storefront (Nuxt, phase 4) and authenticate against `/api/v1` with Sanctum, installed in phase 4.
- **Roles are exclusive.** A user has exactly one role; an admin cannot buy from the storefront with the same account and uses a separate customer account instead. Keeps staff activity out of customer orders and lets the storefront API reject admin accounts outright (phase 4).
- **No registration on the panel.** Fortify's `registration` feature is disabled and `/register` returns 404. Admins are only created by the seeder or by another admin; customer sign-up will be its own `/api/v1` endpoint and Action in phase 4, independent of Fortify's views.
- **The role is never mass assigned.** `role` has no database default and is not fillable: code that creates a user sets it explicitly with `forceFill`. The factory defaults to `customer`; use `->admin()` for panel users.

## Rejected alternatives

- Separate `admins` and `customers` tables with their own guards: duplicates authentication, factories and tests for no real isolation gain.
- `spatie/laravel-permission`: granular roles and permissions are overkill for two roles and add a dependency.
