# Implementation Plan

Phased roadmap. Each phase ends with a **"Done when"** checklist; do
not start the next phase until the previous one's checklist passes. Each
phase is sized to ship in roughly a working week by a single engineer.

> **Conventions:** all changes live on `claude/multi-tenant-*` branches.
> Migrations live in `database/migrations/`. Tenant-scoped tables use
> the `TenantScoped` migration trait described in `ARCHITECTURE.md`
> AD-005.

---

## Phase 0 — Pre-flight

**Goal.** Make sure the repo is healthy before we touch tenancy.

1. Switch dev/CI DB from MariaDB to Postgres 16+. Update `.env.example`,
   `docker-compose.example.yml`, `phpunit.xml`.
2. Confirm `php artisan test` passes on Postgres.
3. Confirm `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse`
   clean.
4. Snapshot baseline: number of routes, number of migrations, number of
   Filament resources. Record below.

**Done when.** Green CI on Postgres, baseline recorded.

### Baseline (fill in after Phase 0)

- Migrations: 71 (`ls database/migrations/ | wc -l`).
- Models: 53.
- Filament resources: _TBD_.
- Livewire routes: _TBD_.

---

## Phase 1 — Install `stancl/tenancy`, define `Tenant`, central tables

**Goal.** Get the tenancy package and Postgres roles wired without
changing app behaviour.

1. `composer require stancl/tenancy:^4.0`.
2. `php artisan tenancy:install` — generates `config/tenancy.php` and
   the `TenancyServiceProvider`.
3. Configure `config/tenancy.php` for **single-database** mode (no DB
   creation per tenant).
4. Create central migrations (all in `database/migrations/`):
   - `tenants` (id uuid, data jsonb, status, timestamps).
   - `domains` (id, tenant_id fk, domain unique, primary, ssl_status).
   - `central_users` (id, name, email, password, …).
   - `central_plans` (id, slug, name, monthly_price_cents,
     `platform_fee_bps`, `platform_fee_flat_cents`, `included_users`,
     `included_services`, `included_extensions`, `byo_themes_allowed`,
     `js_in_themes_allowed`, …).
   - `central_sessions` (default Laravel sessions schema).
   - `extension_catalogue` (see `EXTENSIONS.md`).
   - `theme_catalogue` (see `THEMES.md`).
   - `stripe_platform_ledger` (see `STRIPE_CONNECT.md`).
5. Create Postgres roles `paymenter_app` (NOBYPASSRLS) and
   `paymenter_admin` (BYPASSRLS); grant privileges.
6. Add the `pg` and `pg_admin` connections in `config/database.php`
   (see `TENANT_ISOLATION.md` § 1.1).
7. `App\Models\Tenant extends Stancl\Tenancy\Database\Models\Tenant`
   with `protected $connection = 'pg_admin'`.

**Done when.** `composer install` works; `php artisan migrate`
creates only central tables; creating a tenant row in tinker does not
break the app; tests still green.

---

## Phase 2 — RLS migration helper + retrofit existing tables

**Goal.** Add `tenant_id` + RLS policy to every existing tenant-scoped
table.

1. Create the `App\Database\TenantScoped` trait that exposes
   `$this->scopeToTenant('table_name')` (adds column, FK, RLS, policy,
   default, index — see `ARCHITECTURE.md` AD-005).
2. Add a new migration `2026_xx_xx_add_tenant_id_to_paymenter_tables.php`
   that calls `$this->scopeToTenant(...)` on every existing tenant table
   (users, products, prices, orders, services, invoices, tickets,
   extensions, settings, …). All 71 upstream tables need this except
   the framework-level ones (`jobs`, `failed_jobs`, `migrations`,
   `oauth_*` keys aside — see § 6 in `TENANT_ISOLATION.md`).
3. For tables that **must not** be tenant-scoped (`migrations`,
   `oauth_personal_access_clients` perhaps), document why in a comment
   in the migration.
4. Add a smoke test that creates two tenants, inserts rows under each,
   asserts cross-tenant SELECT returns nothing.

**Done when.** Fresh `php artisan migrate`, `tenants:seed` two tenants,
RLS cross-tenant test green.

---

## Phase 3 — Domain identification + RLS bootstrapper

**Goal.** Make Paymenter's existing routes serve from a tenant subdomain
with full RLS context.

1. In `bootstrap/app.php`, register the tenancy middleware group:
   ```php
   $middleware->group('tenant', [
       \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
       \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
       \App\Http\Middleware\EnforceTenantStatus::class,
   ]);
   ```
2. Implement `App\Tenancy\Bootstrappers\PostgresRlsBootstrapper` and
   register it in `config/tenancy.php` under `bootstrappers`.
3. Wrap **all** existing routes in `routes/web.php` in the `tenant`
   middleware group.
4. Set `central_domains` in `config/tenancy.php` to
   `[central.paymenter.io, paymenter.io]`.
5. Update `AdminPanelProvider` to include the `tenant` middleware on
   the panel and leave domain unset (so it serves on any tenant
   domain).

**Done when.** Hitting `acme.test` (with a row in `domains`) serves
Paymenter home from the tenant context (RLS sees only Acme's data);
hitting `central.test` does not.

---

## Phase 4 — Central Filament panel

**Goal.** Build the operator-facing panel.

1. Create `App\Providers\Filament\CentralPanelProvider`:
   ```php
   $panel->id('central')
       ->path('admin')
       ->domain('central.paymenter.io')
       ->authGuard('central')
       ->databaseConnection('pg_admin')
       ->resources([
           CentralTenantResource::class,
           CentralPlanResource::class,
           CentralDomainResource::class,
           CentralSignupResource::class,
           CentralExtensionCatalogueResource::class,
           CentralThemeCatalogueResource::class,
           CentralStripeLedgerResource::class,
       ]);
   ```
2. Create the seven resources. Tenant resource shows id, primary
   domain, plan, status, monthly recurring revenue from subscription,
   trailing-30-day platform-fee revenue, signup date.
3. Add a `CentralUser` model + factory + seeder.
4. Add the `central` guard to `config/auth.php` backed by
   `central_users` on the `pg_admin` connection.

**Done when.** A central user signs in at
`central.paymenter.io/admin`, sees all tenants, can suspend / activate
a tenant; the change is reflected by `EnforceTenantStatus`.

---

## Phase 5 — Bootstrappers (cache, queue, storage, mail, Passport)

**Goal.** Lock down isolation across cache, queue, storage, mail, and
Passport.

For each, ship a feature test that:

- Creates two tenants.
- Performs the operation inside tenant A.
- Asserts tenant B does not see it.

See `TENANT_ISOLATION.md` for the bootstrapper code.

**Done when.** All five isolation tests green; manual sanity sweep on
staging shows no cross-tenant leakage.

---

## Phase 6 — Settings, Extensions boot, Themes selection

**Goal.** Make Paymenter's pluggable surface tenant-aware.

1. **Settings**: confirm `SettingsProvider` plays nicely with the
   prefixed cache (Phase 5 covers most of it). Add a `config:clear`
   from the RLS bootstrapper if `config('settings')` is set.
2. **Extensions boot**: guard the loop in
   `AppServiceProvider::boot()` at
   `app/Providers/AppServiceProvider.php:140` so it returns early when
   tenancy is not initialised. Move the boot call into the tenant
   middleware group.
3. **Themes**: hook `Qirolab\Theme\Theme::set(setting('theme.active'))`
   into the tenant bootstrapper.

**Done when.** Tenant A on Stripe + theme `dark`, Tenant B on
PayPal + theme `light`, no cross-pollination across a full smoke test.

---

## Phase 7 — Provisioning flow

**Goal.** Tenants self-serve signup on the central app.

1. Build marketing signup form at `central.paymenter.io/signup`:
   plan, company name, desired subdomain, admin email, timezone,
   currency.
2. On submission: create a central Order on the chosen plan; on first
   invoice paid, `PaymenterTenant::createServer` runs
   `CreateTenantAction` (see `PROVISIONING.md`).
3. `CreateTenantAction` inserts the `tenants` row, the `domains` row,
   seeds tenant defaults (role, currency, settings, first admin user),
   runs `passport:keys` inside the tenant filesystem prefix, and
   triggers a welcome email with a magic link.
4. Provide `php artisan tenants:create|list|suspend|activate|terminate|purge`
   commands wrapping the same Actions.

**Done when.** A new signup creates a working Paymenter at
`{subdomain}.paymenter.io` within 60 seconds with no manual steps.

---

## Phase 8 — Custom domains + TLS

**Goal.** Let a tenant point `billing.acme.com` at us.

1. Central UI: tenant adds a custom domain → we generate a TXT
   verification record → tenant points DNS → we verify → request a
   Let's Encrypt cert.
2. TLS termination via Caddy with on-demand TLS gated by an `ask`
   endpoint that checks `domains.ssl_status`.
3. Document the CNAME requirement for tenants.

**Done when.** A tenant adds `billing.acme.com`, points a CNAME, and
within 5 minutes serves their Paymenter on HTTPS with no operator
intervention.

---

## Phase 9 — Stripe Connect

**Goal.** Take a platform fee on every tenant sale.

1. Register the Stripe Connect platform (test mode first), set
   `STRIPE_CONNECT_CLIENT_ID` env.
2. Build `extensions/Gateways/StripeConnect` Paymenter gateway
   extension. Differences from the legacy `Stripe` gateway:
   - OAuth onboarding flow (central-side controller routes).
   - PaymentIntent created with
     `transfer_data.destination + on_behalf_of + application_fee_amount`.
   - Webhook signature verification with the platform secret.
3. Add the `stripe_platform_ledger` reconciliation job (daily).
4. Add `platform_fee_bps` / `platform_fee_flat_cents` to
   `central_plans` and to the `CentralPlanResource` form.
5. Add operator dashboards: platform revenue by plan, top tenants by
   fees, refunds-clawed-back chart.
6. Document Connect setup in `STRIPE_CONNECT.md` (done — link).

**Done when.** End-to-end test: tenant onboards via OAuth, processes a
test charge, our platform balance shows the fee, tenant's balance shows
the rest, refund returns both correctly.

---

## Phase 10 — Curated extension hardening

**Goal.** Replace the open extension loader with the manifest-driven,
sandboxed loader described in `EXTENSIONS.md`.

1. Define the manifest JSON schema (`schemas/extension.schema.json`).
2. Update every shipped extension to include a manifest.
3. Wrap `ExtensionHelper::call` with the capability gates:
   `ExtensionHttpClient` for egress, `Mail` middleware, `Cache` /
   `Storage` proxies, `setting()` reads/writes gate, HTML / Markdown /
   CSS sanitisers on output.
4. Build `extension_catalogue` migration + `CentralExtensionCatalogueResource`.
5. Add the `extension_audit_log` migration and writer.
6. Add the manifest:audit Artisan static-analysis check.
7. Ship CSP middleware that emits a per-request nonce and the full
   policy from `EXTENSIONS.md` § 4.4.

**Done when.** A shipped extension refuses to make an HTTP call to a
non-allow-listed host; a malicious snippet of HTML rendered through an
extension comes out sanitised; CSP header passes `securityheaders.com`
with an A+.

---

## Phase 11 — Themes (curated + BYO)

**Goal.** Ship the BYO theme uploader with the Blade sandbox, CSS
sanitiser, JS allow-list, and preview mode (see `THEMES.md`).

1. `theme_catalogue` migration + central panel resource.
2. `tenant_themes` migration + tenant panel uploader.
3. `SandboxBladeCompiler` + view resolver picking it for BYO themes
   only.
4. CSS sanitiser, file allow-list, manifest schema validator.
5. CSP nonce + SRI hash issuance for tenant JS.
6. Preview signed-URL flow.
7. Per-tenant compiled view cache directory.
8. Operator recall flag (auto-revert to `default:curated`).

**Done when.** A tenant uploads a sample BYO theme, the file allow-list
rejects a `.php` file, the Blade sandbox rejects `@php`, the CSS
sanitiser strips `expression()`, the preview URL works only for the
uploading admin, and the catalogue recall flag flips all tenants back
to the default theme within one request.

---

## Phase 12 — Hardening

**Goal.** Production readiness.

- Per-tenant rate limiting (Laravel rate limiters keyed by tenant id).
- Audit logs of central actions (`owen-it/laravel-auditing` already in
  use; extend to `central_users` + Stripe-related events).
- Backup strategy: nightly logical dumps + Postgres PITR.
- Tenant data export endpoint ("download your data").
- Tenant deletion grace window (soft-delete tenants for 30 days, then
  RLS-aware purge — `DELETE FROM tenants WHERE id = ?` cascades thanks
  to the FKs).
- Monitoring: tenant-scoped metrics in Horizon / Telescope.

**Done when.** Each item documented, tested in staging, runbook signed
off.

---

## Out of scope for v1

See [`ARCHITECTURE.md` § Decisions deferred](./ARCHITECTURE.md#decisions-deferred).
