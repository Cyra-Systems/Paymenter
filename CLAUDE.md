# Paymenter — Multi-Tenant SaaS Conversion

This repository is being converted from the upstream single-tenant Paymenter
billing platform into a **multi-tenant SaaS** ("Paymenter-as-a-Service") that
can host many independent hosting-company customers on one codebase.

If you are an AI assistant working in this repo, read this file first, then
read `docs/multi-tenant/README.md` for the index of conversion docs.

---

## 1. What Paymenter is (upstream)

- **Stack**: Laravel 12, PHP 8.3+, Filament v5 (admin panel), Livewire v4 (client UI),
  Laravel Passport (OAuth), MariaDB, qirolab/laravel-themer (themes).
- **Domain**: open-source billing for hosting companies — products, orders,
  services, invoices, gateways, tickets, extensions.
- **Models** (53): see `app/Models/`. All extend the thin `App\Models\Model` base
  at `app/Models/Model.php:5`.
- **Migrations** (71): see `database/migrations/`.
- **Admin panel**: `app/Providers/Filament/AdminPanelProvider.php` mounted at
  `/admin`, configured via Filament v5 PanelProvider.
- **Client UI**: Livewire routes in `routes/web.php`.
- **Settings**: polymorphic via `settingable_type` / `settingable_id`
  (`database/migrations/2024_02_15_122225_create_settings_table.php`). Loaded
  into config by `app/Providers/SettingsProvider.php`.
- **Extensions**: `extensions/` directory (Gateways, Servers, Others), tracked
  in the `extensions` table, booted in `AppServiceProvider::boot()` at
  `app/Providers/AppServiceProvider.php:140`.

## 2. What "multi-tenant SaaS" means here

Each **tenant** is an independent hosting-company customer of the SaaS:

- Their own URL (`acme.paymenter.io` and/or `billing.acme.com`).
- Their own database/schema (full isolation).
- Their own users, products, orders, invoices, gateways, themes, extensions.
- Their own admin Filament panel — they cannot see other tenants' data.
- Their own background jobs and cache namespace.

The **central** (a.k.a. "landlord") app:

- Lists tenants, plans, signups, central billing (we eat our own dog food —
  the central app runs Paymenter itself to bill the tenants).
- Provisions a new tenant DB on signup, runs migrations + seeders.
- Owns the operator-side Filament panel at `central.paymenter.io/admin`.

See `docs/multi-tenant/ARCHITECTURE.md` for the full decision record.

## 3. Conventions for this conversion

- **Tenancy package**: `stancl/tenancy` v4 (database-per-tenant). Do not
  introduce `tenant_id` columns on existing models — switch the connection
  instead.
- **Branch policy**: feature work lives on `claude/multi-tenant-*` branches and
  merges into `main` only after the docs in `docs/multi-tenant/` are updated.
- **Migrations split**:
  - `database/migrations/` — central (landlord) tables only after the split.
  - `database/migrations/tenant/` — per-tenant tables (everything Paymenter
    today has, minus central-only concerns).
  - See `docs/multi-tenant/MIGRATION_GUIDE.md` for the exact split.
- **No silent data merging** between tenants. Treat cross-tenant access as a
  security incident, not a feature.
- **Never** commit secrets, tenant DB passwords, or per-tenant `.env` files.

## 4. Where to look

| Need | File |
| --- | --- |
| Overview / table of contents | `docs/multi-tenant/README.md` |
| Why we chose this architecture | `docs/multi-tenant/ARCHITECTURE.md` |
| Phased roadmap | `docs/multi-tenant/IMPLEMENTATION_PLAN.md` |
| Data, cache, queue, storage isolation | `docs/multi-tenant/TENANT_ISOLATION.md` |
| Tenant signup & provisioning | `docs/multi-tenant/PROVISIONING.md` |
| Subdomains & custom domains | `docs/multi-tenant/DOMAIN_ROUTING.md` |
| Extensions & themes per tenant | `docs/multi-tenant/EXTENSIONS_AND_THEMES.md` |
| How we bill tenants | `docs/multi-tenant/BILLING_THE_TENANTS.md` |
| Migrating an existing instance | `docs/multi-tenant/MIGRATION_GUIDE.md` |
| One-shot implementation prompt | `docs/multi-tenant/PROMPT.md` |
| Reusable Claude skill | `.claude/skills/multi-tenant-paymenter/SKILL.md` |

## 5. Quick start for an AI assistant

When asked to "make this multi-tenant" or work on any related task:

1. **Read** `docs/multi-tenant/README.md` and the relevant section doc.
2. **Check phase**: `docs/multi-tenant/IMPLEMENTATION_PLAN.md` — do not skip
   ahead.
3. **Prefer editing** Paymenter's existing code over building parallel
   structures. The conversion should feel like an evolution, not a fork.
4. **Run** the test suite (`php artisan test`) and `./vendor/bin/pint` before
   handing back.
5. **Update the docs** when the implementation diverges from what they
   describe — the docs are source of truth, not aspiration.

## 6. Out of scope

- Replacing Filament, Livewire, Passport, or the underlying ORM.
- Forking upstream Paymenter — we stay rebase-compatible with `master`.
- Building our own payment gateway — we use Paymenter's existing gateway
  extensions on the central app.
