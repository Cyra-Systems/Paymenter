# Tenant Isolation

Every shared resource in Paymenter has to be scoped to a tenant. This doc
enumerates them, explains how isolation is wired, and lists the audit
checks. Anything missing here is a future bug.

> The package doing most of the heavy lifting is `stancl/tenancy` v4. We
> compose its built-in bootstrappers with two custom ones (mail, Passport).

---

## 1. Database

**Mechanism.** Database-per-tenant. The `tenant` Laravel connection's
database name is set at request boundary by
`Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper`.

**Required code changes.**

- `config/database.php` — add the `tenant` connection (see
  `IMPLEMENTATION_PLAN.md` Phase 1).
- `database/migrations/tenant/` — every existing Paymenter migration moves
  here.
- `App\Models\Model` base class (`app/Models/Model.php:5`) — set
  `protected $connection = 'tenant';` so every Paymenter model talks to
  the tenant DB by default.

**Audit.** Every model that should be tenant-scoped extends `App\Models\Model`
already (e.g. `User`, `Order`, `Service`, `Invoice`, `Ticket`,
`Extension`, `Setting`...). Central-only models (e.g. `Tenant`,
`CentralUser`, `CentralPlan`) **must** override the connection to the
default `mysql` connection:

```php
class Tenant extends \Stancl\Tenancy\Database\Models\Tenant
{
    protected $connection = 'mysql';
}
```

---

## 2. Cache

**Mechanism.** `Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper`
prefixes every cache key with `tenant_{id}::` when tenancy is initialised.

**Why this matters here.** `App\Providers\SettingsProvider::getSettings()`
caches under the literal key `"settings"` at
`app/Providers/SettingsProvider.php:31`:

```php
$settings = Cache::get('settings', []);
```

Without the bootstrapper this would mean tenant A's settings end up in
the central or other tenants' settings cache.

**Required code changes.**

- Enable the `CacheTenancyBootstrapper` in `config/tenancy.php`.
- **Audit point**: any call to `Cache::store('xxx')->...` with a specific
  store. The bootstrapper rewrites the **default** store; if Paymenter ever
  reaches for a specific store, it bypasses tenancy. Currently
  Paymenter does not — but extensions might. Document this in
  `EXTENSIONS_AND_THEMES.md`.

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
tags every dispatched job with the originating tenant id, then
re-initialises tenancy on the worker before running the job.

**Required code changes.**

- Enable the bootstrapper in `config/tenancy.php`.
- Audit jobs and listeners that touch shared state. Most Paymenter jobs
  (`app/Jobs/`) are safe because they only touch Eloquent — the connection
  swap is enough. Exceptions:
  - `App\Mail\Mail` — see `app/Providers/AppServiceProvider.php:155`. The
    `Queue::after` listener writes `EmailLog` updates. After tenancy is
    on, the worker is already in tenant context, so this just works — but
    the listener must not catch a job from a different tenant and write
    into the wrong DB. Sanity-check with a test.

**Test.**

```php
test('a job dispatched in tenant A runs in tenant A', function () {
    [$a, $b] = Tenant::factory()->count(2)->create();
    $a->run(fn () => MyJob::dispatch());

    $worker->processOne();

    $a->run(fn () => expect(Result::count())->toBe(1));
    $b->run(fn () => expect(Result::count())->toBe(0));
});
```

---

## 4. Filesystem / storage

**Mechanism.** `Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper`
rewrites the root of every configured disk to
`storage/app/tenant{id}/...` (local) or `tenant{id}/...` (S3 prefix).

**Why this matters here.**

- Paymenter writes ticket attachments via the `TicketAttachmentController`
  (`app/Http/Controllers/TicketAttachmentController.php`).
- Invoice PDFs from `barryvdh/laravel-dompdf` are cached on disk.
- Extension uploads (e.g. server config files).

All of these go through the default disk; the bootstrapper makes them
tenant-scoped automatically.

**Required code changes.**

- Enable the bootstrapper.
- For S3: configure a single bucket; the prefix is the isolation. Do
  **not** create one bucket per tenant unless you genuinely need
  per-tenant KMS keys (a future feature).

**Audit.** Anything calling `Storage::disk('local')->put(...)` with a
hardcoded path under `storage/app/` (not `storage/app/public/`). Currently
clean — Paymenter uses the conventional disk API.

---

## 5. Mail

**Mechanism.** Custom bootstrapper
`App\Tenancy\Bootstrappers\MailTenancyBootstrapper`:

```php
class MailTenancyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        config([
            'mail.from.address' => setting('mail_from_address'),
            'mail.from.name'    => setting('mail_from_name'),
            'mail.mailers.smtp.host'     => setting('mail_smtp_host'),
            'mail.mailers.smtp.port'     => setting('mail_smtp_port'),
            'mail.mailers.smtp.username' => setting('mail_smtp_username'),
            'mail.mailers.smtp.password' => setting('mail_smtp_password'),
            'mail.mailers.smtp.encryption' => setting('mail_smtp_encryption'),
        ]);
    }

    public function revert(): void { /* let stancl restore */ }
}
```

**Why this matters here.** Paymenter already stores mail settings as
`settings` rows on the tenant DB — we just have to push them into config
on each tenancy initialisation.

**Audit.**

- `app/Mail/Mail.php` and the queue listeners in
  `app/Providers/AppServiceProvider.php:155-175` — they use the standard
  Laravel mail facade, so the bootstrapper covers them.
- Notification templates (`notification_templates` table) are per-tenant,
  also fine.

**Central mail** uses the `.env` SMTP — that's plain Laravel default
behaviour because tenancy is not initialised on the central app.

---

## 6. Passport (OAuth)

**Mechanism.** Custom bootstrapper that points Passport at tenant-scoped
keys:

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
`storage/app/tenant{id}/oauth-private.key` automatically.

**Required actions on tenant create.**

- After `php artisan tenants:migrate`, run `php artisan passport:keys`
  inside the tenant context (the `CreateTenantAction` does this).

**Audit.** `app/Models/OauthClient.php` and Passport routes. Paymenter
calls `Passport::useClientModel(OauthClient::class)` in
`AppServiceProvider::boot()` at line 188 — `OauthClient` extends
`App\Models\Model`, so its queries hit the tenant DB. Tokens are tenant-
scoped because they live in tenant tables (`oauth_*`).

---

## 7. Sessions

**Mechanism.** Laravel sessions are stored in the `sessions` table
(default driver in Paymenter). Because `sessions` lives in the tenant DB
and cookies are scoped by domain, sessions are naturally tenant-scoped.

**Central sessions** use a `central_sessions` table on the central DB —
configured via a second session store. Simpler alternative: file-based
sessions for the central app while DB sessions for tenants.

---

## 8. Settings (special case)

Paymenter has a polymorphic `settings` table
(`database/migrations/2024_02_15_122225_create_settings_table.php`) with
`settingable_type` / `settingable_id`. It already supports per-entity
settings; per-tenant settings work because the **rows live in the tenant
DB**.

The only landmine is the cache key. Fix in `SettingsProvider::getSettings()`
to use a tenant-aware key (see Phase 6 in `IMPLEMENTATION_PLAN.md`).

---

## 9. Extensions

Code is shared, configuration is per tenant — see
`EXTENSIONS_AND_THEMES.md`.

---

## 10. Things that are explicitly shared

These are intentionally **not** tenant-scoped:

- The `tenants`, `domains`, `central_users`, `central_plans` tables (the
  central DB).
- Laravel framework views compiled to `storage/framework/views/` —
  template cache is shared because views are stateless.
- `vendor/` and `node_modules/` — code, not data.
- The `extensions/` directory on disk — code, not data. (Per-tenant
  *configuration* of those extensions is in tenant DBs.)
- The Filament asset publish output in `public/` — these are static
  assets.

Anything else discovered later → add it to this doc, decide *shared* or
*isolated*, and write a test.

---

## Cross-tenant access (never do this)

The only place we walk the tenant set is **central** code that intentionally
needs to (provisioning, billing, deletion). Even there, we never let
arbitrary user input drive tenant lookup:

- All cross-tenant code paths run from `central` guard + central panel.
- Customer code paths run inside `tenancy()->initialize($tenant)` blocks.

A grep for `tenancy()->initialize` outside of the central app's
`App\Central\` namespace should turn up only the framework itself.
