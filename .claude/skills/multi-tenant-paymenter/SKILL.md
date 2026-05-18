---
name: multi-tenant-paymenter
description: Use when the user asks to convert Paymenter to multi-tenant SaaS, provision tenants, add tenant-aware features (Postgres RLS, cache/queue/storage/mail/Passport isolation), wire up subdomain or custom-domain routing, build the central landlord app, set up Stripe Connect platform fees, build the curated extension catalogue with HTML/CSS hardening, build the curated or bring-your-own theme system with a Blade sandbox, or migrate an existing single-tenant Paymenter instance into the SaaS. Triggers include phrases like "multi-tenant", "SaaS", "tenant", "landlord", "central app", "Paymenter as a service", "Postgres RLS", "row-level security", "Stripe Connect", "application fee", "platform fee", "curated extension", "BYO theme", "theme sandbox", "subdomain routing", "stancl/tenancy".
---

# Multi-Tenant Paymenter

You are working in a Paymenter fork being turned into a multi-tenant SaaS
("Paymenter-as-a-Service"). Each tenant is a hosting-company customer
with their own URL, users, billing, extensions, theme, and Stripe
Connect account. A **central** ("landlord") app provisions tenants,
bills them for the SaaS subscription, and takes a platform fee out of
every sale they make.

## When to use this skill

Invoke when the user wants to:

- Add a `Tenant` model, tenant-identification middleware, or routing.
- Add `tenant_id` + Postgres RLS to existing or new tables.
- Wire up cache / queue / storage / mail / Passport isolation per
  tenant.
- Build the tenant signup or provisioning flow.
- Add subdomain or custom-domain support (TLS, on-demand certs).
- Set up Stripe Connect: OAuth onboarding, destination charges with
  `application_fee_amount`, webhooks, refunds, ledger reconciliation.
- Add or harden the curated extension catalogue: manifest, capability
  gates, HTML/CSS/Markdown sanitisers, CSP.
- Add or harden the theme system: curated catalogue, BYO upload with a
  Blade sandbox compiler, CSS sanitiser, file allow-list.
- Migrate a live single-tenant Paymenter into the SaaS.

## Operating procedure

1. **Read the docs first.** Open these in order; do not skip:
   - `CLAUDE.md` (repo root) — orientation.
   - `docs/multi-tenant/README.md` — index.
   - `docs/multi-tenant/ARCHITECTURE.md` — decisions you must respect.
   - `docs/multi-tenant/IMPLEMENTATION_PLAN.md` — phase you are in.
   - The topic-specific doc(s) closest to the user's request.

2. **Confirm the phase.** Implementation is phased; do not jump
   phases. If the user's request belongs to a later phase, tell them
   what must land first.

3. **Use `stancl/tenancy` v4 in single-database mode + custom RLS
   bootstrapper.** Do not invent a parallel tenancy mechanism and do
   not switch to database-per-tenant.

4. **Edit existing Paymenter files** instead of building parallel
   ones. The conversion stays rebase-compatible with upstream
   `master`. Examples:
   - Extend, do not replace, `app/Providers/AppServiceProvider.php`.
   - Extend, do not replace,
     `app/Providers/Filament/AdminPanelProvider.php`.
   - Add the central panel as a **second** Filament panel provider.

5. **Keep the central app minimal.** Tenants get the full Paymenter
   feature surface; central only manages tenants, plans, custom
   domains, the extension/theme catalogues, the Stripe platform
   ledger, and central billing.

## Architectural invariants (do not break)

- **Database**: Postgres 16+, single database, with **Row-Level
  Security** enforced on every tenant-scoped table.
- **Two Postgres roles**: `paymenter_app` (NOBYPASSRLS) for tenant
  traffic; `paymenter_admin` (BYPASSRLS) for central code only. Two
  Laravel connections: `pg` (default) and `pg_admin`.
- **Tenant identification**: by domain (subdomain or custom domain).
  Never by URL path.
- **RLS context**: set with `SET LOCAL app.tenant_id = '<uuid>'` in
  the `PostgresRlsBootstrapper`, after `InitializeTenancyByDomain`
  resolves the tenant. Queue workers re-bootstrap per job.
- **`tenant_id`** is set automatically by a Postgres column DEFAULT of
  `current_setting('app.tenant_id', true)::uuid` — don't sprinkle
  `tenant_id` assignments through application code.
- **Cache** uses a per-tenant prefix; tags are not assumed.
- **Queues** are tagged with the originating tenant; bootstrappers re-
  initialise RLS inside the worker.
- **Storage**: tenant uploads land in `storage/app/tenant/{id}/...`;
  never share a bucket prefix between tenants.
- **Mail**: from-address, transport, template overrides live on the
  tenant's `settings` rows. The central app uses its own SMTP.
- **Passport**: each tenant has its own keys
  (`storage/app/tenant/{id}/oauth-*.key`).
- **Stripe Connect**: Standard accounts; destination charges with
  `application_fee_amount`; webhook signature verified with the
  platform secret; ledger pulled daily into `stripe_platform_ledger`.
  Application fee is mandatory and configured per `central_plans`.
- **Extensions**: ship a manifest (`extension.json`); only declared
  capabilities are allowed at runtime. HTML/CSS/Markdown output goes
  through hardened sanitisers; CSP headers are emitted per response.
- **Themes**: curated by default. BYO themes accepted as a zip on Pro+
  plans, run through a sandboxed Blade compiler (no `@php`, no
  `{!! !!}`, file allow-list, CSS sanitiser, JS allow-list with SRI).
- **Filament panels**: keep the existing `/admin` panel as the
  **tenant** admin. Add a **second** PanelProvider for the central
  "operator" panel mounted on `central.paymenter.io`, using the
  `pg_admin` connection.

## Things that are easy to get wrong

- **Settings caching.** `App\Providers\SettingsProvider` caches
  settings under the bare key `"settings"` (see
  `app/Providers/SettingsProvider.php:31`). The cache bootstrapper
  prefix solves it; also clear `config('settings')` in the tenancy
  bootstrapper, otherwise the static config short-circuits.
- **Extension boot loop.** `AppServiceProvider::boot()` iterates
  extensions from the DB at
  `app/Providers/AppServiceProvider.php:140`. This runs before tenancy
  initialises on early requests — guard the loop so it returns early
  outside tenant context, and re-run it from inside the tenant
  middleware group.
- **Passport keys.** `php artisan passport:keys` writes to
  `storage/`. After tenancy is on, the filesystem bootstrapper
  rewrites the disk root so the keys land in the tenant prefix
  automatically — but you must run the command **inside** the tenant
  context (`$tenant->run(fn () => Artisan::call('passport:keys', ...))`).
- **`pg_admin` connection misuse.** `DB::connection('pg_admin')` is a
  cross-tenant superuser; only central code should ever call it. A
  grep of the codebase for that string is the audit surface.
- **CSP nonces.** If the central panel or tenant admin emits an
  inline `<script>` or `<style>` without the per-request nonce, it
  will be blocked by the CSP. Use the `csp_nonce()` helper.
- **Stripe Connect state parameter.** OAuth callback hits the central
  domain, not the tenant domain. The `state` parameter must carry the
  tenant id (encrypted) so the callback can re-bootstrap context.
- **BYO theme Blade sandbox.** Do not weaken the directive allow-list
  or re-enable `{!! !!}` for tenant input — both equal arbitrary PHP
  execution.

## What "done" looks like for a typical task

When the user asks for a tenant-aware feature, the change is done when:

- New tables (if any) use the `TenantScoped` migration trait or
  document why they don't.
- The feature works on at least two seeded tenants without cross-leaks.
- `php artisan test` passes, including a test that asserts RLS
  isolation or capability denial as appropriate.
- `./vendor/bin/pint` and `./vendor/bin/phpstan analyse` are clean.
- The relevant `docs/multi-tenant/*.md` file reflects the change.

## Where to find the long-form details

| Topic | File |
| --- | --- |
| Why this architecture | `docs/multi-tenant/ARCHITECTURE.md` |
| Phased roadmap | `docs/multi-tenant/IMPLEMENTATION_PLAN.md` |
| Postgres RLS / cache / queue / storage / mail / Passport | `docs/multi-tenant/TENANT_ISOLATION.md` |
| Signup + provisioning | `docs/multi-tenant/PROVISIONING.md` |
| Subdomain & custom domain wiring | `docs/multi-tenant/DOMAIN_ROUTING.md` |
| Curated extensions + HTML/CSS hardening | `docs/multi-tenant/EXTENSIONS.md` |
| Curated + BYO themes + Blade sandbox | `docs/multi-tenant/THEMES.md` |
| Stripe Connect (platform fee) | `docs/multi-tenant/STRIPE_CONNECT.md` |
| Billing the tenants (SaaS sub) | `docs/multi-tenant/BILLING_THE_TENANTS.md` |
| Live-instance migration | `docs/multi-tenant/MIGRATION_GUIDE.md` |
| One-shot implementation prompt | `docs/multi-tenant/PROMPT.md` |

Stay within these docs. If you find a gap, update the doc as part of
the change — the doc set is source of truth.
