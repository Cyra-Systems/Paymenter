# Paymenter — Multi-Tenant SaaS Conversion

This repository is being converted from the upstream single-tenant
Paymenter billing platform into a **multi-tenant SaaS**
("Paymenter-as-a-Service") that hosts many independent hosting-company
customers on one codebase, one Postgres database, and one application
instance.

If you are an AI assistant working in this repo, read this file first,
then read `docs/multi-tenant/README.md` for the index of conversion
docs.

---

## 1. What Paymenter is (upstream)

- **Stack**: Laravel 12, PHP 8.3+, Filament v5 (admin panel), Livewire
  v4 (client UI), Laravel Passport (OAuth), qirolab/laravel-themer
  (themes). Upstream ships on MariaDB; we switch to Postgres (see § 3).
- **Domain**: open-source billing for hosting companies — products,
  orders, services, invoices, gateways, tickets, extensions.
- **Models** (53): `app/Models/`. All extend the thin `App\Models\Model`
  base at `app/Models/Model.php:5`.
- **Migrations** (71): `database/migrations/`.
- **Admin panel**: `app/Providers/Filament/AdminPanelProvider.php`
  mounted at `/admin`.
- **Client UI**: Livewire routes in `routes/web.php`.
- **Settings**: polymorphic via `settingable_type` / `settingable_id`
  (`database/migrations/2024_02_15_122225_create_settings_table.php`),
  loaded into config by `app/Providers/SettingsProvider.php`.
- **Extensions**: `extensions/` directory (Gateways, Servers, Others),
  tracked in the `extensions` table, booted in
  `AppServiceProvider::boot()` at `app/Providers/AppServiceProvider.php:140`.

## 2. What "multi-tenant SaaS" means here

Each **tenant** is an independent hosting-company customer of the SaaS:

- Their own URL (`acme.paymenter.io` and/or `billing.acme.com`).
- Their own data (isolated by Postgres Row-Level Security).
- Their own users, products, orders, invoices, gateways, themes,
  extensions.
- Their own admin Filament panel — they cannot see other tenants'
  data.
- Their own Stripe Connect account, with our **platform fee** taken
  automatically out of every sale.
- Their own theme — either curated from the catalogue or
  **bring-your-own** (sandboxed Blade + safe CSS).
- Their own extension enablement from an **operator-curated** catalogue
  (no tenant-uploaded PHP).

The **central** ("landlord") app:

- Lists tenants, plans, signups, central billing (we eat our own dog
  food — the central app runs Paymenter itself to bill the tenants).
- Owns the Stripe Connect platform identity and the application-fee
  config.
- Curates the extension and theme catalogues.
- Owns the operator-side Filament panel at `central.paymenter.io/admin`.

See `docs/multi-tenant/ARCHITECTURE.md` for the full decision record.

## 3. Conventions for this conversion

- **Database**: Postgres 16+ with **Row-Level Security**. Two roles:
  `paymenter_app` (NOBYPASSRLS, used by all tenant traffic) and
  `paymenter_admin` (BYPASSRLS, used by central code only).
- **Tenancy package**: `stancl/tenancy` v4 in **single-database** mode.
  We add a custom `PostgresRlsBootstrapper` that `SET LOCAL app.tenant_id`
  on every request and queue job.
- **Tenant identification**: by `Host` header, never by URL path.
- **Migrations**: one folder (`database/migrations/`). Tenant-scoped
  tables use the `TenantScoped` migration trait which adds
  `tenant_id uuid` + `FORCE ROW LEVEL SECURITY` + the
  `tenant_isolation` policy + the `current_setting('app.tenant_id')`
  default.
- **No silent data merging** between tenants. Treat cross-tenant access
  as a security incident, not a feature.
- **Stripe Connect** (Standard accounts) is the only sanctioned
  payment gateway path going forward; the legacy plain Stripe gateway
  is deprecated. Platform fee is mandatory.
- **Extensions** ship a manifest (`extension.json`); undeclared
  capabilities are denied at runtime. HTML/CSS/Markdown output goes
  through hardened sanitisers; CSP is enforced.
- **Themes** are curated by default; BYO themes are accepted as a zip
  upload on Pro+ plans and run inside a Blade sandbox (no `@php`, no
  `{!! !!}`, file allow-list, CSS sanitiser, optional JS with SRI).
- **Branch policy**: feature work lives on `claude/multi-tenant-*`
  branches and merges into `main` only after `docs/multi-tenant/` is
  updated.
- **Never** commit secrets, OAuth keys, Stripe keys, or per-tenant
  config files.

## 4. Where to look

| Need | File |
| --- | --- |
| Overview / table of contents | `docs/multi-tenant/README.md` |
| Why this architecture | `docs/multi-tenant/ARCHITECTURE.md` |
| Phased roadmap | `docs/multi-tenant/IMPLEMENTATION_PLAN.md` |
| Postgres RLS, cache/queue/storage/mail/Passport isolation | `docs/multi-tenant/TENANT_ISOLATION.md` |
| Tenant signup & provisioning | `docs/multi-tenant/PROVISIONING.md` |
| Subdomains & custom domains | `docs/multi-tenant/DOMAIN_ROUTING.md` |
| Curated extensions, HTML/CSS hardening | `docs/multi-tenant/EXTENSIONS.md` |
| Curated + BYO themes (Blade sandbox) | `docs/multi-tenant/THEMES.md` |
| Stripe Connect platform fee | `docs/multi-tenant/STRIPE_CONNECT.md` |
| How we bill tenants (SaaS subscription) | `docs/multi-tenant/BILLING_THE_TENANTS.md` |
| Migrating an existing instance | `docs/multi-tenant/MIGRATION_GUIDE.md` |
| One-shot implementation prompt | `docs/multi-tenant/PROMPT.md` |
| Reusable Claude skill | `.claude/skills/multi-tenant-paymenter/SKILL.md` |

## 5. Quick start for an AI assistant

When asked to "make this multi-tenant" or work on any related task:

1. **Read** `docs/multi-tenant/README.md` and the relevant section
   doc(s).
2. **Check phase**: `docs/multi-tenant/IMPLEMENTATION_PLAN.md` — do
   not skip ahead.
3. **Prefer editing** Paymenter's existing code over building parallel
   structures. The conversion should feel like an evolution, not a
   fork.
4. **Run** the test suite (`php artisan test`) and `./vendor/bin/pint`
   before handing back.
5. **Update the docs** when the implementation diverges from what they
   describe — the docs are source of truth, not aspiration.

## 6. Out of scope

- Replacing Filament, Livewire, Passport, or the underlying ORM.
- Forking upstream Paymenter — we stay rebase-compatible with `master`.
- Building our own payment gateway — we use Stripe Connect for
  tenant→customer payments and plain Stripe for the SaaS subscription.
- Tenant-uploaded PHP extensions (deferred to v2 with proper sandbox).
- Cross-tenant SSO (deferred to v2).
