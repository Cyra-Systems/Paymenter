# Extensions & Themes — Per-Tenant Catalogue

Paymenter has two pluggable surfaces:

1. **Extensions** under `extensions/` (Gateways, Servers, Others) — PHP
   classes booted via `App\Helpers\ExtensionHelper`.
2. **Themes** under `themes/` — Blade view sets selected via
   `qirolab/laravel-themer`.

We want tenants to **enable / configure** these without uploading code. The
operator (central) curates the catalogue.

---

## 1. The model

- **Code** lives on disk, shipped with the application image. Updated by
  the operator via deploy.
- **Catalogue** of what's available is implicit: anything in
  `extensions/` is installable; anything in `themes/` is selectable. The
  central panel can override this with a `central_extension_catalogue`
  allow-list (default: all of them).
- **Per-tenant state** is in the tenant's own `extensions` and `settings`
  tables — already polymorphic. No schema change needed.

```
Disk (shared)            Central DB (allow-list)        Tenant DB (state)

extensions/             ┌─────────────────────────┐    ┌────────────────────────┐
  Gateways/Stripe       │ central_extension_      │    │ extensions             │
  Gateways/PayPal       │   catalogue             │    │   - extension          │
  Servers/Pterodactyl   │   - extension           │    │   - type               │
  Others/Affiliates     │   - allowed (bool)      │    │   - enabled            │
themes/                 │   - included_in_plan    │    │ settings               │
  default               └─────────────────────────┘    │   (config rows,        │
  dark                                                 │    polymorphic on the  │
  arctic                                               │    extension row)      │
                                                       └────────────────────────┘
```

---

## 2. Extensions

### 2.1 Boot order

`App\Providers\AppServiceProvider::boot()` currently does this at
`app/Providers/AppServiceProvider.php:140`:

```php
foreach (
    collect(Extension::where(fn ($q) =>
        $q->where('enabled', true)
          ->orWhere('type', 'server')
          ->orWhere('type', 'gateway')
    )->get())->unique('extension') as $extension
) {
    ExtensionHelper::call($extension, 'boot', mayFail: true);
}
```

After tenancy lands, that loop will run **before** the tenant DB
connection is set on early requests, and it will explode trying to query
the tenant `extensions` table.

**Fix.** Guard the loop:

```php
if (! tenancy()->initialized) {
    return;
}
```

Then move the loop into a small `BootExtensionsAction` invoked from the
`tenant` middleware group, after `InitializeTenancyByDomain`. The
central app never boots tenant extensions; it boots its own (the
`PaymenterTenant` Server extension and central-side gateways).

### 2.2 Central-side allow-list

The central catalogue is in a new table:

```php
Schema::create('central_extension_catalogue', function (Blueprint $t) {
    $t->id();
    $t->string('extension');     // e.g. "Stripe"
    $t->string('type');           // "gateway" | "server" | "other"
    $t->boolean('allowed')->default(true);
    $t->json('plans')->nullable(); // null = all plans
    $t->timestamps();
});
```

A `CentralExtensionCatalogueResource` in the central Filament panel lets
operators toggle `allowed` and pin extensions to plans (e.g. "PayPal
gateway is Pro plan only").

The tenant admin sees only allowed extensions. The list is exposed via
a shared service the tenant admin queries:

```php
// app/Services/AvailableExtensions.php
public function forCurrentTenant(): Collection
{
    $plan = tenant()->data['plan'] ?? null;

    return CentralExtensionCatalogue::query()
        ->where('allowed', true)
        ->when($plan, fn ($q) => $q->where(fn ($q) =>
            $q->whereNull('plans')->orWhereJsonContains('plans', $plan)
        ))
        ->get();
}
```

### 2.3 Configuration

When a tenant enables an extension, Paymenter already creates an
`extensions` row and `settings` rows polymorphically tied to it. That
behaviour does not need to change. The settings live in the tenant DB →
isolation is automatic.

### 2.4 Cron / scheduled tasks

Some Paymenter extensions register cron callbacks (`ExtensionHelper::call(
$ext, 'cron')`). After multi-tenancy, the scheduler must run those for
**each tenant** rather than globally. Pattern:

```php
// app/Console/Kernel.php
$schedule->call(function () {
    Tenant::active()->each(fn (Tenant $t) =>
        $t->run(fn () =>
            Extension::enabled()->each(fn ($ext) =>
                ExtensionHelper::call($ext, 'cron', mayFail: true)
            )
        )
    );
})->everyMinute()->withoutOverlapping();
```

For large fleets, batch this into a queued job per tenant.

### 2.5 Extension authoring rules (revised)

Documented in `extensions/README.md` (to be created) and enforced by
review:

- **Never** call `Cache::store(...)` with an explicit store — use the
  default store so the cache bootstrapper sees you.
- **Never** call `Storage::disk('local')` with a hardcoded path under
  `storage/app/` — let the filesystem bootstrapper rewrite the root.
- **Never** call `DB::connection('mysql')` or `DB::connection('default')`
  — use the implicit connection (`DB::table('foo')`).
- **Never** read `env(...)` at runtime — read `config(...)`, populated
  from tenant settings.
- Long-running jobs **must** be dispatched via Laravel's queue API, not
  `dispatch_now` from a request handler with global state.

---

## 3. Themes

Paymenter uses `qirolab/laravel-themer` (see `composer.json`). The
selected theme is in `config('theme.active')`.

### 3.1 Per-tenant override

In the tenant bootstrapper (or in the `tenant` middleware group):

```php
// after tenancy is initialised
$theme = setting('theme', 'default');
\Qirolab\Theme\Theme::set($theme);
```

`setting()` already returns tenant-scoped values because the `settings`
table lives in the tenant DB. Storing the chosen theme as a setting row
is one line.

### 3.2 Central allow-list

Same idea as extensions: a `central_theme_catalogue` table flags which
themes are user-selectable. The default is "all themes shipped in
`themes/`", but operators can hide work-in-progress themes.

### 3.3 Tenant-specific custom CSS

We allow the tenant admin to upload a small CSS file (size-limited) that
gets included after the theme's own CSS. The file is stored on the
tenant filesystem disk (so it's already isolated) and included in the
layout via a Blade `@stack('tenant-css')` push.

> Out of scope for v1: per-tenant Blade view overrides. Themes stay
> identical across tenants; only colours / CSS vary.

### 3.4 Theme assets

Theme assets ship in `public/themes/{name}/...` — these are shared
across tenants because they're code, not data (see
`TENANT_ISOLATION.md` § 10).

---

## 4. Marketplace (future)

For v1 the catalogue is a Filament resource the operator manages by hand.
v2 wishlist:

- Public marketplace at `marketplace.paymenter.io` so partner gateways
  can list their extensions.
- Plan-based extension entitlements (already half-built with `plans`
  JSON on the catalogue row).
- One-click install from a remote source — requires a signature
  verification and runs into the "code execution as a service" trap.
  Skip until we have signing infrastructure.

---

## 5. Testing

Three tests are non-negotiable:

1. **Isolation**: tenant A enables Stripe with key `sk_A`, tenant B
   enables Stripe with key `sk_B`; settings do not bleed.
2. **Catalogue gate**: operator hides PayPal; tenant on the Free plan
   cannot enable PayPal even by API.
3. **Theme bootstrap**: request to tenant A renders with theme `dark`,
   request to tenant B with theme `light`, same process.

All three sit in `tests/Feature/MultiTenant/`.
