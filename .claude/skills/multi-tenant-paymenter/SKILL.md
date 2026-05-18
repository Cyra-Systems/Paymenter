---
name: multi-tenant-paymenter
description: Use when the user asks to convert Paymenter to multi-tenant SaaS, provision tenants, add tenant-aware features (DB/cache/queue/storage/auth/extensions/themes), wire up subdomain or custom-domain routing, build the central landlord app, or migrate an existing single-tenant Paymenter instance into the SaaS. Triggers include phrases like "multi-tenant", "SaaS", "tenant", "landlord", "central app", "Paymenter as a service", "per-tenant database", "subdomain routing", "stancl/tenancy".
---

# Multi-Tenant Paymenter

You are working in a Paymenter fork being turned into a multi-tenant SaaS
("Paymenter-as-a-Service"). Each tenant is a hosting-company customer with
their own database, URL, users, billing, extensions, and theme. A **central**
("landlord") app provisions tenants and bills them — itself running Paymenter.

## When to use this skill

Invoke when the user wants to:

- Add a tenant model, tenant identification middleware, or routing.
- Split migrations into central vs tenant.
- Wire up cache / queue / storage / mail isolation per tenant.
- Build the tenant signup or provisioning flow.
- Add subdomain or custom-domain support.
- Make Paymenter's existing features (Filament admin, Livewire client,
  extensions, themes, Passport, settings) tenant-aware.
- Migrate a live single-tenant Paymenter into the SaaS.

## Operating procedure

1. **Read the docs first.** Open these in order; do not skip:
   - `CLAUDE.md` (repo root) — orientation.
   - `docs/multi-tenant/README.md` — index.
   - `docs/multi-tenant/ARCHITECTURE.md` — decisions you must respect.
   - `docs/multi-tenant/IMPLEMENTATION_PLAN.md` — phase you are in.
   - The topic-specific doc closest to the user's request.

2. **Confirm the phase.** Implementation is phased; do not jump phases. If
   the user's request belongs to a later phase, tell them what must land
   first.

3. **Use `stancl/tenancy` v4.** Do not invent a parallel tenancy mechanism
   and do not bolt `tenant_id` columns onto existing Paymenter models.
   Tenancy is realised by swapping the DB connection.

4. **Edit existing Paymenter files** instead of building parallel ones.
   The conversion stays rebase-compatible with upstream `master`. Examples:
   - Extend, do not replace, `app/Providers/AppServiceProvider.php`.
   - Extend, do not replace, `app/Providers/Filament/AdminPanelProvider.php`.
   - Add the central panel as a **second** Filament panel provider.

5. **Keep the central app minimal.** Tenants get the full Paymenter feature
   surface; the central app only needs tenants, plans, signups, custom
   domains, and billing.

## Architectural invariants (do not break)

- **Tenant identification** is by domain (subdomain `*.paymenter.io` or
  custom domain like `billing.acme.com`). Never by URL path prefix.
- **Database** is per-tenant. The tenant connection is named `tenant`; the
  central connection stays as `mysql` (the Laravel default).
- **Cache** uses a per-tenant prefix; tags are not assumed.
- **Queues** are dispatched on a per-tenant connection with tenancy
  bootstrapped inside the job (see `stancl/tenancy`'s queue bootstrapper).
- **Storage**: tenant uploads land in `storage/app/tenant{id}/...`; never
  share a bucket prefix between tenants.
- **Mail**: from-address, transport, and template overrides live on the
  tenant's `settings` rows. The central app uses its own SMTP.
- **Passport**: each tenant has its own keys (`storage/oauth-*.key`
  inside the tenant disk).
- **Extensions & themes** live globally on disk (in `extensions/` and
  `themes/`) but each tenant enables/configures them through their own
  `extensions` and `settings` tables.
- **Filament panels**: keep the existing `/admin` panel as the **tenant**
  admin (mounted on the tenant subdomain). Add a **second** PanelProvider
  for the central "operator" panel mounted on `central.paymenter.io`.

## Things that are easy to get wrong

- **Filament v5 panel domain scoping.** Use `Panel::domain()` on the central
  panel so it does not collide with tenant routes. Tenant panel is the
  default (no domain restriction); the tenancy middleware short-circuits
  unknown hosts.
- **Settings caching.** `App\Providers\SettingsProvider` caches settings
  globally under `cache key "settings"`. Make this cache key
  per-tenant-aware before enabling tenancy, or settings from tenant A will
  bleed into tenant B.
- **Extension boot loop.** `AppServiceProvider::boot()` iterates extensions
  from DB. This runs before tenancy initialises on early requests — guard
  the call so it only runs inside an initialised tenant.
- **Passport keys.** `php artisan passport:keys` writes to `storage/`. After
  switching tenancy on, point Passport at the tenant disk or the keys leak
  across tenants.
- **`url()->forceRootUrl()`** and the signature macros in
  `AppServiceProvider` use `request()` — fine inside web flow, but jobs
  must call `tenancy()->initialize($tenant)` before generating URLs.

## What "done" looks like for a typical task

When the user asks for a tenant-aware feature, the change is done when:

- New migrations are in the **correct** folder (`database/migrations/` for
  central, `database/migrations/tenant/` for tenant).
- The feature works on at least two seeded tenants without cross-leaks.
- `php artisan test` passes, including a test that asserts isolation.
- `./vendor/bin/pint` and `./vendor/bin/phpstan analyse` are clean.
- The relevant `docs/multi-tenant/*.md` file reflects the change.

## Where to find the long-form details

| Topic | File |
| --- | --- |
| Why this architecture | `docs/multi-tenant/ARCHITECTURE.md` |
| Phased roadmap | `docs/multi-tenant/IMPLEMENTATION_PLAN.md` |
| DB / cache / queue / storage isolation | `docs/multi-tenant/TENANT_ISOLATION.md` |
| Signup + provisioning flow | `docs/multi-tenant/PROVISIONING.md` |
| Subdomain & custom domain wiring | `docs/multi-tenant/DOMAIN_ROUTING.md` |
| Per-tenant extensions / themes | `docs/multi-tenant/EXTENSIONS_AND_THEMES.md` |
| Billing the tenants | `docs/multi-tenant/BILLING_THE_TENANTS.md` |
| Live-instance migration | `docs/multi-tenant/MIGRATION_GUIDE.md` |
| One-shot implementation prompt | `docs/multi-tenant/PROMPT.md` |

Stay within these docs. If you find a gap, update the doc as part of the
change — the doc set is source of truth.
