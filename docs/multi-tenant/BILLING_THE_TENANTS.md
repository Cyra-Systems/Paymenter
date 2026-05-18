# Billing the Tenants — SaaS Subscription

We have **two** revenue streams:

1. **SaaS subscription** (this doc): tenants pay us a recurring fee
   for using Paymenter-as-a-Service.
2. **Platform fee on tenant sales** ([`STRIPE_CONNECT.md`](./STRIPE_CONNECT.md)):
   we take a percentage of every charge their customers make.

Both are configured per `central_plans` row. The mix is the SaaS pricing
strategy — high-sub + low-fee for enterprises, low-sub + high-fee for
indie hosters.

This doc covers stream #1: how we charge the tenants for using us.

---

## 1. Dogfooding

The central app **is itself** a Paymenter instance. It uses Paymenter's
existing Product / Order / Service / Invoice / Gateway / Coupon / Tax /
Credits / Cancellations flow to sell "Paymenter SaaS plans" to the
tenants.

Why dogfood:

- We already have all this code; building parallel central-only billing
  is wasted effort.
- The central app shipping bugs forces us to find and fix them; every
  customer benefits.
- New tenants see exactly the billing UX they'll offer their own
  customers — natural product demo.

---

## 2. The plans

Plans are central-side `Product` rows. The combination of subscription
and platform-fee fields makes the pricing table:

| Slug | Monthly sub | Platform fee | Included |
| ---- | ----------- | ------------ | -------- |
| `starter` | €19 | 2.00% + €0.10 / sale | 100 services, 250 invoices/mo, 2 staff, 3 extensions, default theme only |
| `pro`     | €49 | 1.00% / sale | 1 000 services, 2 000 invoices/mo, 10 staff, all stable extensions, all themes, BYO themes, custom domain |
| `scale`   | €199 | 0.50% / sale | 10 000 services, unlimited invoices, 50 staff, all extensions including beta, BYO themes with JS, priority support, daily off-site backup |

Each plan ships these properties on `central_plans`:

| Column | Purpose |
| ------ | ------- |
| `monthly_price_cents` | SaaS subscription price |
| `platform_fee_bps` | Stripe Connect cut, in basis points |
| `platform_fee_flat_cents` | Stripe Connect flat per-transaction |
| `included_services`, `included_users`, `included_extensions` | Entitlement limits |
| `byo_themes_allowed` (bool) | Gate the BYO theme uploader |
| `js_in_themes_allowed` (bool) | Gate JS in BYO themes |
| `custom_domain_allowed` (bool) | Gate the custom-domain UI |
| `beta_extensions_allowed` (bool) | Gate non-stable catalogue entries |

Operator-level overrides on a specific tenant live in
`tenants.data->overrides` and beat the plan defaults; useful for the
"please can we have 1 500 services this month" enterprise dance.

---

## 3. The `PaymenterTenant` Server extension

A new Paymenter Server extension at
`extensions/Servers/PaymenterTenant/` implements the standard
server-extension contract; it is what turns a paid central order into a
provisioned tenant.

```php
namespace Paymenter\Extensions\Servers\PaymenterTenant;

class PaymenterTenant extends \App\Classes\Extension\Server
{
    public function createServer(Service $service): array
    {
        $tenant = app(\App\Central\Actions\CreateTenantAction::class)(
            $service->properties->only([
                'company_name', 'subdomain', 'admin_email',
                'admin_first_name', 'admin_last_name',
                'timezone', 'currency',
            ])->toArray() + ['plan_slug' => $service->product->slug]
        );

        // Bind service ↔ tenant for the lifecycle hooks below.
        $service->properties()->updateOrCreate(
            ['key' => 'tenant_id'],
            ['value' => $tenant->id]
        );

        return ['login_url' => "https://{$tenant->primaryDomain()}/admin"];
    }

    public function suspendServer(Service $service): void
    {
        $this->tenantFor($service)->update(['data->status' => 'suspended']);
    }

    public function unsuspendServer(Service $service): void
    {
        $this->tenantFor($service)->update(['data->status' => 'active']);
    }

    public function terminateServer(Service $service): void
    {
        app(\App\Central\Actions\TerminateTenantAction::class)(
            $this->tenantFor($service)
        );
    }

    public function changePlan(Service $service, Plan $newPlan): void
    {
        $tenant = $this->tenantFor($service);
        $tenant->update(['data->plan' => $newPlan->product->slug]);
    }

    protected function tenantFor(Service $service): Tenant
    {
        return Tenant::findOrFail($service->properties->firstWhere('key', 'tenant_id')->value);
    }
}
```

The extension is shipped in the SaaS repo, enabled on the **central**
Paymenter instance only.

---

## 4. Order lifecycle, mapped

| Paymenter event (central instance) | Tenant effect |
| ---------------------------------- | ------------- |
| Order created, invoice unpaid | `tenants` row at `status = 'pending'`; nothing provisioned yet |
| First invoice paid | `createServer` fires → `CreateTenantAction` runs → tenant live |
| Subsequent invoice overdue past grace | `suspendServer` fires → `status = 'suspended'` |
| Overdue invoice paid | `unsuspendServer` fires → `status = 'active'` |
| Service cancelled at period end | `terminateServer` fires → `status = 'terminating'` + 30-day grace |
| 30-day grace expires | Daily `tenants:purge` deletes data (`ON DELETE CASCADE` does most of the work) |

---

## 5. How we get paid (subscription side)

Central instance accepts payment via Paymenter's normal gateway
extensions. For v1 the central app uses **plain** Stripe (not Stripe
Connect — we are the merchant of record for our own subscription
revenue). Alternative: PayPal, SEPA, manual invoice for enterprise.

The platform-fee revenue (see `STRIPE_CONNECT.md`) lands separately in
our Stripe balance, recorded in `stripe_platform_ledger`. The central
panel surfaces both subscription MRR and trailing-30-day platform-fee
revenue on every tenant card.

---

## 6. Currency & tax

Tenants pay the central instance in our configured currency (typically
EUR). Their **own** customers pay them in whatever currency they
configure on their tenant's `Currency` table — orthogonal.

For VAT we use Paymenter's built-in `tax_rates`. Reverse-charge to
EU-VAT-registered tenants is supported through the existing flow.

---

## 7. Entitlement enforcement

The central plan declares entitlements (`included_services`, etc.).
Enforcement happens **inside the tenant** against `config('entitlements')`,
populated by the tenant bootstrapper from `tenant()->data['plan']`:

```php
// app/Tenancy/Bootstrappers/EntitlementsBootstrapper.php
public function bootstrap(Tenant $tenant): void
{
    $plan = $tenant->plan();   // looks up central_plans on pg_admin
    config([
        'entitlements.services'   => $plan->included_services,
        'entitlements.users'      => $plan->included_users,
        'entitlements.extensions' => $plan->included_extensions,
        'entitlements.byo_themes' => $plan->byo_themes_allowed,
        'entitlements.theme_js'   => $plan->js_in_themes_allowed,
        'entitlements.custom_domain' => $plan->custom_domain_allowed,
        // ...overrides from tenant()->data['overrides']
    ]);
}
```

A thin `Entitlements` service queries the current state and the limits:

```php
public function check(string $key, int $delta = 1): bool
{
    $limit = config("entitlements.$key");
    $current = match ($key) {
        'services'   => Service::count(),
        'users'      => User::whereNotNull('role_id')->count(),
        'extensions' => Extension::where('enabled', true)->count(),
    };
    return $current + $delta <= $limit;
}
```

Filament resources call `Entitlements::check()` in their
`can{Create,Update}` policies. Going over the limit shows an upsell
banner with a one-click plan-upgrade link.

---

## 8. Trial vs. paid-first

Two acceptable signup modes; configured per plan:

- **Pay-first** (default): the central order must be paid before
  `createServer` runs. Spam-resistant; no Free plan needed.
- **Trial**: a Free plan exists; `createServer` runs immediately; the
  central invoice is for €0; tenant goes live in trial mode for 14
  days; if no paid upgrade by day 14, status moves to `suspended`.

Trial uses Paymenter's existing Coupon + an automatic plan-change
cron. No special billing wiring.

---

## 9. Failure modes

| Failure | Behaviour |
| ------- | --------- |
| Gateway timeout during `createServer` | Job retries with idempotency on `service_id` (`tenants.data->service_id` lookup); duplicate insert blocked by domain uniqueness. |
| Partial seed (e.g. role insert failed) | Wrapping `pg_admin` transaction rolls back tenant + domain rows; signup surfaces the error. |
| Subdomain taken | Validation error at signup; never reaches the order. |
| DNS not propagated for custom domain | Tenant is up on the subdomain; custom domain stays `pending`; no impact to billing. |
| Tenant disconnects Stripe Connect | Subscription unaffected (separate gateway); platform-fee revenue stops for new sales; we may downgrade their plan if Connect is required. |

---

## 10. Reporting

The central operator panel surfaces:

- **MRR / ARR** from central Paymenter invoices.
- **Platform fee revenue** (trailing 30 / 90 / 365 days) from
  `stripe_platform_ledger`.
- Tenants by plan, churn rate, signup-to-activation latency.
- Per-plan revenue (sub + fee), average extensions enabled, average
  theme customisation depth.
- Top tenants by fee revenue (potential upsell targets).

Charts use Paymenter's existing Filament widgets +
`flowframe/laravel-trend` (already in `composer.json`).

---

## 11. Pricing — out of scope

This doc covers the **mechanics**. Pricing is a product decision; track
in product notes, not the repo. Open questions:

- Free tier?
- Annual discount %?
- Affiliate / partner programme?
- Volume contracts for `scale`?
- Pricing-page UI on the marketing site?
