# Implementation Plan

A phased roadmap. Each phase ends with a **"Done when"** checklist; do not
start the next phase until the previous one's checklist passes. Phases are
sized so any one can ship to staging in a working week by a single engineer.

> **Conventions:** all changes live on `claude/multi-tenant-*` branches.
> Migrations land in `database/migrations/` (central) or
> `database/migrations/tenant/` (tenant) per AD-004.

---

## Phase 0 — Pre-flight

**Goal.** Make sure the repo is healthy before we touch tenancy.

1. Confirm `php artisan test` passes against a fresh DB.
2. Confirm `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse`
   are clean.
3. Snapshot baseline metrics: number of routes, number of migrations,
   number of Filament resources. Record in this file under "Baseline".
4. Create a staging environment that mirrors production.

**Done when.** Green CI on `main`, staging mirrors prod, baseline recorded.

### Baseline (fill in after Phase 0)

- Migrations: 71 (`ls database/migrations/ | wc -l`).
- Models: 53 (`ls app/Models/ -F | grep -v / | wc -l`).
- Filament resources: _TBD_.
- Livewire routes: _TBD_.

---

## Phase 1 — Install `stancl/tenancy`, define `Tenant` model

**Goal.** Get the tenancy package wired without changing app behaviour.

1. `composer require stancl/tenancy:^4.0`.
2. `php artisan tenancy:install` — generates `config/tenancy.php`, the
   `tenants` migration, and the `TenancyServiceProvider`.
3. Create the central migrations:
   - `tenants` (id `uuid`, `data` json, timestamps) — provided by package.
   - `domains` (id, tenant_id, domain, primary, ssl_status) — provided.
   - `central_users` (id, name, email, password) — new.
   - `central_plans` (id, name, slug, monthly_price, included_users,
     included_services, included_extensions, ...) — new.
4. Create `App\Models\Tenant extends Stancl\Tenancy\Database\Models\Tenant`.
   Wire its `IncrementApiHits` / events later.
5. Configure the tenant connection in `config/database.php`:
   ```php
   'tenant' => [
       'driver' => 'mysql',
       'host'   => env('TENANT_DB_HOST', env('DB_HOST')),
       'port'   => env('TENANT_DB_PORT', env('DB_PORT')),
       // database is set per-request by stancl
       'username' => env('TENANT_DB_USERNAME', env('DB_USERNAME')),
       'password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD')),
       'charset'  => 'utf8mb4',
       'collation' => 'utf8mb4_unicode_ci',
   ],
   ```
6. Set `config/tenancy.php`:
   ```php
   'database' => ['template_tenant_connection' => 'tenant', 'prefix' => 'tenant_'],
   'migration_parameters' => ['--path' => 'database/migrations/tenant', '--realpath' => true],
   ```

**Done when.** `composer install` works, `php artisan tenants:create` (a
short Tinker snippet) creates a row in `tenants`, no app routes are broken.

---

## Phase 2 — Split migrations into central vs tenant

**Goal.** Move all existing Paymenter migrations into the tenant folder.
Keep only central-only tables in `database/migrations/`.

1. `mkdir -p database/migrations/tenant`.
2. Move every existing migration file (all 71) into
   `database/migrations/tenant/`. Do not rename them — they keep their
   original timestamps so rebases against upstream stay clean.
3. Keep in `database/migrations/` **only** the central tables introduced
   in Phase 1.
4. Update `config/tenancy.php` `migration_parameters` to point at
   `database/migrations/tenant`.
5. Drop the dev DB, then:
   ```bash
   php artisan migrate                  # central only
   php artisan tenants:create acme.test # creates DB + runs tenant migrations
   ```

**Done when.** `php artisan migrate:fresh` on the central DB creates only
central tables; creating a tenant runs all 71 migrations on its own DB.

---

## Phase 3 — Domain identification middleware

**Goal.** Make the existing Paymenter routes serve from a tenant subdomain
without a path prefix.

1. In `bootstrap/app.php` (Laravel 12 bootstrap), register the tenancy
   middleware group:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->group('tenant', [
           \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
           \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
       ]);
   })
   ```
2. In `routes/web.php`, wrap **all** existing routes in the `tenant`
   middleware group (this is the bulk of the diff).
3. In `config/tenancy.php`, set `central_domains` to
   `['central.paymenter.io', 'paymenter.io']` (whatever you use for the
   landlord).
4. Update `app/Providers/Filament/AdminPanelProvider.php` panel: leave
   `->domain(...)` **unset** so it serves on any tenant domain. Add the
   `tenant` middleware group to the panel.

**Done when.** Hitting `acme.test` (with a row in `domains`) serves the
Paymenter home page from the tenant DB; hitting `central.test` does not.

---

## Phase 4 — Central Filament panel

**Goal.** Build the operator-facing panel.

1. Create `App\Providers\Filament\CentralPanelProvider` registered in
   `bootstrap/providers.php`. Configure:
   ```php
   $panel->id('central')
       ->path('admin')
       ->domain('central.paymenter.io')
       ->authGuard('central')
       ->resources([
           CentralTenantResource::class,
           CentralPlanResource::class,
           CentralDomainResource::class,
           CentralSignupResource::class,
       ]);
   ```
2. Create the four resources. Tenant resource shows id, primary domain,
   plan, status (active/suspended/terminated), monthly revenue, signup date.
3. Add a `CentralUser` model + factory + seeder.
4. Add `central` guard to `config/auth.php` backed by `central_users`.

**Done when.** A central user can sign in at
`central.paymenter.io/admin`, see all tenants, and toggle a tenant's
status. The toggle reflects in the tenant's actual behaviour
(suspended tenants redirect to a "suspended" page).

---

## Phase 5 — Bootstrappers (cache, queue, storage, mail, Passport)

**Goal.** Lock down isolation so a misbehaving tenant cannot leak into
another's data or settings.

Implement, in order, the bootstrappers described in `TENANT_ISOLATION.md`.
Each ships with a feature test that:

- Creates two tenants.
- Performs the operation (write a cache key, dispatch a job, write a file,
  send a mail) inside tenant A.
- Asserts tenant B does not see it.

**Done when.** All five isolation tests are green, and a manual sanity
sweep shows no cross-tenant leakage on the staging environment.

---

## Phase 6 — Settings, Extensions, Themes

**Goal.** Make Paymenter's pluggable surface area tenant-aware.

1. **Settings**: change `App\Providers\SettingsProvider::getSettings()` to
   key its cache by `tenancy()->initialized ? tenant()->id : 'central'`
   instead of the hard-coded `"settings"` key.
2. **Extensions boot**: guard the `Extension::where(...)` query in
   `app/Providers/AppServiceProvider.php:140` so it returns early when
   tenancy is not initialised. Add it to the tenant middleware boot path
   so extensions still load per-request inside a tenant.
3. **Themes**: `qirolab/laravel-themer` reads `config('theme.active')`.
   Hook a per-tenant override into the tenant bootstrapper that reads
   `setting('theme', 'default')`.

**Done when.** Tenant A using theme `dark` and Stripe gateway, Tenant B
using theme `light` and PayPal gateway — no cross-pollination.

---

## Phase 7 — Provisioning flow

**Goal.** Tenants self-serve signup on the central app.

1. Build the marketing signup form at `central.paymenter.io/signup`:
   plan, company name, desired subdomain, central admin email.
2. On submission: create a `signups` row → run `CreateTenantAction`
   (creates `tenants` row, creates `domains` row, dispatches
   `CreateTenantDatabaseJob` which runs migrations + the default Paymenter
   seeder + a "first admin user" custom seeder).
3. Send a "welcome" email with a magic link to set the first admin
   password on the tenant.
4. Provide a `php artisan tenants:delete {id}` Artisan command that drops
   the tenant DB and storage directory.

**Done when.** A new signup creates a working Paymenter instance at
`{subdomain}.paymenter.io` within 60 seconds, no manual steps.

---

## Phase 8 — Custom domains + TLS

**Goal.** Let a tenant point `billing.acme.com` at us.

1. Central UI: tenant operator adds a custom domain → we generate
   verification record (TXT or CNAME challenge) → tenant points DNS → we
   verify → we request a Let's Encrypt cert.
2. TLS termination at the reverse proxy. Recommended: Caddy with on-demand
   TLS, gated by an `ask` endpoint that checks against the `domains`
   table.
3. Optional CNAME of the apex requires Cloudflare or a similar flattening
   service. Document this in `DOMAIN_ROUTING.md`, do not implement
   ourselves.

**Done when.** A tenant operator can add `billing.acme.com`, point a
CNAME, and within 5 minutes serve their Paymenter on HTTPS at that
domain with no operator intervention.

---

## Phase 9 — Billing the tenants

**Goal.** Charge tenants for the SaaS using Paymenter itself.

1. Build `extensions/Servers/PaymenterTenant` — a Paymenter Server
   extension whose `createServer` calls the same `CreateTenantAction`,
   whose `suspendServer` flips the tenant `status`, whose `terminateServer`
   runs the delete command.
2. Create the central plans (Starter / Pro / Scale) as Paymenter
   Products on the central app, attached to the new Server extension.
3. The central app's checkout becomes the signup flow.

**Done when.** A tenant signs up + pays the first invoice → tenant is
provisioned automatically → late payments suspend the tenant → cancellation
deletes the tenant after retention window.

---

## Phase 10 — Hardening

**Goal.** Production readiness.

- Per-tenant rate limiting (Laravel rate limiters keyed by tenant id).
- Audit logs of central actions (`owen-it/laravel-auditing` already in use;
  extend to `central_users`).
- Backup strategy: per-tenant DB dumps to S3, rotation.
- Tenant data export endpoint ("download your data").
- Tenant deletion grace window (soft-delete tenants for 30 days before
  dropping the DB).
- Monitoring: tenant-scoped metrics in Horizon / Telescope.

**Done when.** All of the above are documented and tested in staging, and
the runbook in `MIGRATION_GUIDE.md` is signed off.

---

## Out of scope for v1

See [`ARCHITECTURE.md` § "Decisions deferred"](./ARCHITECTURE.md#decisions-deferred).
