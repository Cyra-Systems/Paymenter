# Tenant Isolation

Every shared resource has to be scoped to a tenant. This doc enumerates
them, shows the mechanism, and lists the audit checks.

The two pillars are:

1. **Postgres Row-Level Security** — the database itself refuses to
   return or accept rows that don't match the current tenant context.
2. **`stancl/tenancy` bootstrappers** (+ three custom ones) — wire up
   cache prefixes, queue serialisation, storage paths, mail config, and
   Passport key paths around each request and queue job.

Anything missing here is a future bug.

---

## 1. Database — Postgres RLS

### 1.1 Roles & connections

Two Postgres roles and two Laravel connections:

```sql
CREATE ROLE paymenter_app   LOGIN PASSWORD '...' NOBYPASSRLS;
CREATE ROLE paymenter_admin LOGIN PASSWORD '...' BYPASSRLS;
GRANT CONNECT ON DATABASE paymenter TO paymenter_app, paymenter_admin;
GRANT USAGE ON SCHEMA public TO paymenter_app, paymenter_admin;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public
  TO paymenter_app, paymenter_admin;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES
  TO paymenter_app, paymenter_admin;
```

`config/database.php`:

```php
'pg' => [
    'driver'   => 'pgsql',
    'host'     => env('DB_HOST'),
    'database' => env('DB_DATABASE', 'paymenter'),
    'username' => env('DB_USERNAME', 'paymenter_app'),
    'password' => env('DB_PASSWORD'),
    'search_path' => 'public',
    'sslmode'  => 'prefer',
],
'pg_admin' => [
    'driver'   => 'pgsql',
    'host'     => env('DB_HOST'),
    'database' => env('DB_DATABASE', 'paymenter'),
    'username' => env('DB_ADMIN_USERNAME', 'paymenter_admin'),
    'password' => env('DB_ADMIN_PASSWORD'),
    'search_path' => 'public',
    'sslmode'  => 'prefer',
],
```

`default` stays `pg`. Anything that legitimately needs to escape RLS
uses `DB::connection('pg_admin')->...` and is grep-auditable.

### 1.2 Per-table setup

Every tenant-scoped table gets four things, applied by the
`TenantScoped` migration trait:

```sql
ALTER TABLE invoices ADD COLUMN tenant_id uuid
  NOT NULL DEFAULT NULLIF(current_setting('app.tenant_id', true), '')::uuid;
ALTER TABLE invoices ADD CONSTRAINT invoices_tenant_id_fk
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoices FORCE  ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON invoices
  USING       (tenant_id = current_setting('app.tenant_id', true)::uuid)
  WITH CHECK  (tenant_id = current_setting('app.tenant_id', true)::uuid);

CREATE INDEX invoices_tenant_id_idx ON invoices (tenant_id);
```

Notes:

- `FORCE ROW LEVEL SECURITY` makes the policy apply even to the table
  owner; without it the owner bypasses RLS.
- `NULLIF(..., '')` handles the unset-setting case so reads outside a
  tenant context simply see nothing (rather than crashing on a cast).
- The `DEFAULT` populates `tenant_id` on INSERT automatically from the
  session variable; model code does not need to set it.

### 1.3 Setting the context

On every request inside the tenant middleware, after
`InitializeTenancyByDomain` has resolved the tenant, a custom
`PostgresRlsBootstrapper` runs:

```php
class PostgresRlsBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        DB::connection('pg')->statement(
            "SET LOCAL app.tenant_id = '" . $tenant->id . "'"
        );
    }

    public function revert(): void
    {
        DB::connection('pg')->statement("RESET app.tenant_id");
    }
}
```

`SET LOCAL` scopes the variable to the current transaction; since
Laravel transactions wrap requests in PHP-FPM's connection-per-request
model, this is the safe choice. For long-lived workers (Octane, Roadrunner)
the bootstrapper is invoked per request; for queue workers it's invoked
per job (see § 3).

### 1.4 Models

`App\Models\Model` (base) sets:

```php
protected $connection = 'pg';
```

Central models (`Tenant`, `CentralUser`, `CentralPlan`,
`ExtensionCatalogue`, `ThemeCatalogue`, `StripePlatformLedger`) extend
Eloquent directly and set `protected $connection = 'pg_admin'`.

A `BelongsToTenant` trait can be added for the rare case where we want
to set `tenant_id` explicitly in the application; otherwise the column
DEFAULT does it.

### 1.5 Why RLS over scopes alone

A query like:

```php
DB::statement('UPDATE services SET status = ? WHERE id = ?', ['cancelled', $id]);
```

bypasses Eloquent global scopes. With RLS it cannot affect another
tenant's row — the `WITH CHECK` clause makes Postgres reject the write.
This matters because extensions, raw SQL audits, future imports, and
careless refactors are all real failure modes.

### 1.6 Test

```php
test('rls prevents cross-tenant reads', function () {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $a->run(fn () => Invoice::factory()->create(['number' => 'A-1']));
    $b->run(fn () => Invoice::factory()->create(['number' => 'B-1']));

    $a->run(fn () => expect(Invoice::pluck('number'))->toEqual(['A-1']));
    $b->run(fn () => expect(Invoice::pluck('number'))->toEqual(['B-1']));
});

test('rls blocks writes with wrong tenant id', function () {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $a->run(function () use ($b) {
        $thrown = false;
        try {
            DB::statement(
                'INSERT INTO invoices (number, tenant_id) VALUES (?, ?)',
                ['CROSS', $b->id]
            );
        } catch (QueryException $e) {
            $thrown = true;
        }
        expect($thrown)->toBeTrue();
    });
});
```

---

## 2. Cache

**Mechanism.** `Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper`
prefixes every cache key with `t{tenant_id}::` when tenancy is
initialised.

**Why this matters here.** `App\Providers\SettingsProvider::getSettings()`
caches under the literal key `"settings"` at
`app/Providers/SettingsProvider.php:31`. Without the bootstrapper one
tenant's settings would land in another's cache.

**Required code changes.**

- Enable the `CacheTenancyBootstrapper` in `config/tenancy.php`.
- **Audit point**: any call to `Cache::store('xxx')->...` with a
  specific store bypasses the default-store prefix. Currently Paymenter
  does not — but extensions might. Documented in `EXTENSIONS.md`.

**Test.**

```php
test('cache is isolated between tenants', function () {
    [$a, $b] = Tenant::factory()->count(2)->create();

    $a->run(fn () => Cache::put('foo', 'a'));
    $b->run(fn () => expect(Cache::get('foo'))->toBeNull());
});
```

---

## 3. Queue & jobs

**Mechanism.** `Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper`
tags every dispatched job with the originating tenant id. The worker
re-initialises tenancy (which re-runs the bootstrappers, including
RLS context) before running the job.

**Why this matters here.**

- Paymenter's `App\Mail\Mail` is dispatched to the queue. The
  `Queue::after` listener at
  `app/Providers/AppServiceProvider.php:155-175` writes `EmailLog`
  updates. After tenancy is on, those writes go to the correct tenant
  rows because the worker is bootstrapped in the originating tenant's
  context.

**Required code changes.**

- Enable the bootstrapper.
- Ensure long-running supervisors restart on deploy (no stale
  bootstrap state).

**Test.**

```php
test('queued job runs in originating tenant', function () {
    [$a, $b] = Tenant::factory()->count(2)->create();

    $a->run(fn () => RecordSomething::dispatch());
    $this->artisan('queue:work --once');

    $a->run(fn () => expect(Something::count())->toBe(1));
    $b->run(fn () => expect(Something::count())->toBe(0));
});
```

---

## 4. Filesystem / storage

**Mechanism.** `Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper`
rewrites the root of every configured disk to
`storage/app/tenant/{id}/...` (local) or `tenant/{id}/...` (S3 prefix).

**Why this matters here.** Paymenter writes ticket attachments
(`app/Http/Controllers/TicketAttachmentController.php`), DOMPDF caches,
and extension uploads through the default disk.

**Required code changes.**

- Enable the bootstrapper.
- For S3: one bucket, the prefix is the isolation. Do **not** create one
  bucket per tenant unless you need per-tenant KMS keys.
- Forbid `..` in any path the application accepts from user input
  (Laravel does this for `Storage::put`, but custom code should also).

**Audit.** Anything calling `Storage::disk('local')->put(...)` with a
hardcoded path under `storage/app/` (not `storage/app/public/`).
Currently clean.

---

## 5. Mail

**Mechanism.** Custom bootstrapper:

```php
class MailTenancyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        config([
            'mail.from.address'           => setting('mail_from_address'),
            'mail.from.name'              => setting('mail_from_name'),
            'mail.mailers.smtp.host'      => setting('mail_smtp_host'),
            'mail.mailers.smtp.port'      => setting('mail_smtp_port'),
            'mail.mailers.smtp.username'  => setting('mail_smtp_username'),
            'mail.mailers.smtp.password'  => setting('mail_smtp_password'),
            'mail.mailers.smtp.encryption'=> setting('mail_smtp_encryption'),
        ]);
    }

    public function revert(): void { /* let stancl restore */ }
}
```

Paymenter already stores mail settings as `settings` rows — we just push
them into config on each tenancy initialisation.

**Central mail** uses the `.env` SMTP — that's plain Laravel default
behaviour because tenancy is not initialised on the central app.

---

## 6. Passport (OAuth)

**Mechanism.** Custom bootstrapper that points Passport at tenant-scoped
key paths:

```php
class PassportTenancyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        config([
            'passport.private_key' => Storage::disk('local')
                ->path('oauth-private.key'),
            'passport.public_key'  => Storage::disk('local')
                ->path('oauth-public.key'),
        ]);
    }
}
```

Because the filesystem bootstrapper already rewrote the local disk root,
`Storage::disk('local')->path('oauth-private.key')` resolves to
`storage/app/tenant/{tenant_id}/oauth-private.key`.

`CreateTenantAction` runs `php artisan passport:keys` inside the new
tenant's context.

**Audit.** `app/Models/OauthClient.php` and Passport routes are
tenant-scoped: tokens live in tenant tables (`oauth_*`) which are RLS-
guarded, and Paymenter calls `Passport::useClientModel(OauthClient::class)`
in `AppServiceProvider::boot()` — `OauthClient` extends `App\Models\Model`
so it sees only the current tenant's rows.

---

## 7. Sessions

**Mechanism.** Laravel sessions use the database `sessions` table
(default in Paymenter). The table is tenant-scoped (RLS-guarded) and
cookies are domain-scoped — sessions are naturally tenant-scoped.

**Central sessions** use a separate `central_sessions` table on the
`pg_admin` connection.

---

## 8. Settings (special case)

Paymenter has a polymorphic `settings` table
(`database/migrations/2024_02_15_122225_create_settings_table.php`) with
`settingable_type` / `settingable_id`. It already supports per-entity
settings; per-tenant settings work because the **rows live in the tenant
DB partition** (RLS).

The one landmine is the cache key. Fix in
`App\Providers\SettingsProvider::getSettings()` so the `Cache::get(...)`
call goes through the prefixed default store (no change needed if the
`CacheTenancyBootstrapper` is enabled), and *also* defensively
nuke the static `config('settings')` short-circuit when tenant context
changes:

```php
public static function getSettings($force = false): void
{
    if (config('settings') && ! empty(config('settings')) && ! $force) {
        return;
    }
    // ...existing logic, unchanged...
}
```

…by clearing `config('settings')` in the same bootstrapper that sets up
the RLS context:

```php
config(['settings' => null]);
SettingsProvider::getSettings(force: true);
```

---

## 9. Extensions

Code is shared; configuration is per tenant; per-tenant config rows are
RLS-guarded. Details in [`EXTENSIONS.md`](./EXTENSIONS.md).

---

## 10. Themes

Curated theme files are shared on disk; per-tenant BYO themes are stored
in `storage/app/tenant/{id}/themes/...`. Compiled Blade caches are
also per-tenant (`storage/framework/views/byo/{tenant_id}/...`).
Details in [`THEMES.md`](./THEMES.md).

---

## 11. Things that are explicitly shared

Intentionally **not** tenant-scoped:

- The `tenants`, `domains`, `central_users`, `central_plans`,
  `extension_catalogue`, `theme_catalogue`, `stripe_platform_ledger`
  tables (central concerns).
- Laravel framework view caches for shared layouts.
- `vendor/`, `node_modules/`, `public/` build artefacts — code, not data.
- The `extensions/` directory on disk — code, not data.
- Curated `themes/` directory on disk.
- Static assets published by Filament under `public/filament/`.

Anything else discovered later → add it here, decide shared or isolated,
and write a test.

---

## 12. Operator cross-tenant code

When central code legitimately needs to see across tenants (provisioning,
billing aggregation, the Stripe platform ledger), it explicitly uses the
`pg_admin` connection:

```php
DB::connection('pg_admin')
    ->table('invoices')
    ->selectRaw('tenant_id, SUM(total_cents) as total')
    ->groupBy('tenant_id')
    ->get();
```

That role has `BYPASSRLS`. A grep for `connection('pg_admin')` is the
audit surface — every match should be in the central app's
`App\Central\` namespace (or in framework code that we control). Anything
else is a finding.

---

## 13. Defence-in-depth summary

| Layer | What it stops |
| ----- | ------------- |
| Postgres RLS policies | Cross-tenant reads/writes via SQL, including raw queries |
| `pg_admin` role gating | Accidental cross-tenant reads from regular code |
| Cache key prefix | Cache-key collisions across tenants |
| Filesystem path prefix | File reads/writes outside the tenant's directory |
| Queue payload tenant id | Worker running a job in the wrong tenant context |
| Mail config swap | Sending tenant emails from the wrong From/SMTP |
| Per-tenant Passport keys | Token forgery across tenants |
| Domain-based session cookies | Session leak across tenants |
| CSP + HTML sanitiser (`EXTENSIONS.md`) | XSS from extension output |
| Blade sandbox (`THEMES.md`) | PHP execution from tenant-uploaded themes |
| Manifest capability gates (`EXTENSIONS.md`) | SSRF, mail spoofing, hidden file writes by extensions |

A successful breach has to defeat **all of the above**. Treat any
finding that defeats one layer as a high-priority bug — the rest of the
stack is supposed to be uninteresting in isolation.
