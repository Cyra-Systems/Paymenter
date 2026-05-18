# Billing the Tenants — Paymenter-as-a-Service

We charge tenants for the SaaS using **Paymenter itself**. The central
app *is* a Paymenter instance whose Products are "Paymenter SaaS plans".
Provisioning, suspension, and termination are driven by a Server
extension that calls our internal `CreateTenantAction` and friends.

This doc explains how to wire that up.

---

## 1. Why dogfood

- We already have Products / Orders / Invoices / Gateways / Coupons /
  Tax / Credits / Cancellations in Paymenter. Re-implementing them on
  the central app would be redundant.
- The central app shipping bugs forces us to find and fix them — every
  customer benefits.
- New tenants see exactly the billing UX they will offer their own
  customers — natural product demo.

---

## 2. The plans

Plans are central-side `Product` rows on the central Paymenter instance.
A typical lineup:

| Slug | Monthly price | Included | Overage |
| ---- | ------------- | -------- | ------- |
| `starter` | €19 | 100 services, 250 invoices/mo, 2 staff users, 3 extensions, default theme only | n/a, hard cap |
| `pro`     | €49 | 1 000 services, 2 000 invoices/mo, 10 staff users, all stable extensions, all themes, custom domain | €0.05/extra service |
| `scale`   | €199 | 10 000 services, unlimited invoices, 50 staff users, all extensions incl. beta, custom domain, priority support, daily off-site backup | volume pricing |

Each plan also has `included_users`, `included_services`, etc. as
**properties** on the Product (Paymenter's `properties` table supports
this natively).

## 3. The `PaymenterTenant` Server extension

A new Paymenter Server extension at
`extensions/Servers/PaymenterTenant/` implements the standard Paymenter
server-extension contract:

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
        // touch entitlement re-eval
    }

    protected function tenantFor(Service $service): Tenant
    {
        return Tenant::findOrFail($service->properties->get('tenant_id'));
    }
}
```

When the central operator creates a Product for "Pro Plan" and ties it
to this Server extension, Paymenter's normal order → invoice → payment
→ provisioning flow Just Works.

## 4. Order lifecycle, mapped

| Paymenter event | Tenant effect |
| --------------- | ------------- |
| Order created, invoice unpaid | Tenant row exists with `status = 'pending'`; no DB yet (or pre-provisioned-paused, see § 7) |
| First invoice paid | `createServer` fires → `CreateTenantAction` runs → tenant goes live |
| Subsequent invoice overdue past grace | `suspendServer` fires → `status = 'suspended'` |
| Overdue invoice paid | `unsuspendServer` fires → `status = 'active'` |
| Service cancelled at period end | `terminateServer` fires → `status = 'terminating'` + 30-day grace |
| 30-day grace expires | Daily `tenants:purge` drops the DB |

## 5. Currency & tax

Tenants pay the central instance in **the central instance's** configured
currency (typically EUR or USD). Their *own* customers pay them in the
currency they configure on their tenant's Currency table — orthogonal.

For VAT we use Paymenter's built-in `tax_rates`. Reverse-charge to
EU-VAT-registered tenants is supported through the existing flow.

## 6. Entitlement enforcement

The central plan declares entitlements (`included_services`, etc.).
Enforcement happens **inside the tenant**, against limits stored on the
tenant model and exposed by a thin "entitlements" service:

```php
// app/Services/Entitlements.php (lives in the tenant app)
public function check(string $key, int $delta = 1): bool
{
    $limit = config("entitlements.$key");
    $current = match ($key) {
        'services'   => Service::count(),
        'staff'      => User::where('role_id', '!=', null)->count(),
        'extensions' => Extension::where('enabled', true)->count(),
    };
    return $current + $delta <= $limit;
}
```

`config('entitlements')` is set by the tenant bootstrapper from
`tenant()->data['plan']` against a static `config/plans.php` table:

```php
return [
    'starter' => ['services' => 100, 'staff' => 2, 'extensions' => 3],
    'pro'     => ['services' => 1000, 'staff' => 10, 'extensions' => PHP_INT_MAX],
    'scale'   => ['services' => 10000, 'staff' => 50, 'extensions' => PHP_INT_MAX],
];
```

The Filament resources call `Entitlements::check()` in their
`can{Create,Update}` policies. Going over the limit shows an upsell
banner.

## 7. Trial vs. paid-first

Two acceptable signup modes:

- **Pay-first** (default): the central order must be paid before
  `createServer` runs. Spam-resistant; no Free plan.
- **Trial**: a Free plan exists; `createServer` runs immediately, the
  central invoice is for €0, tenant goes live in trial mode for 14 days;
  if no paid upgrade by day 14, tenant moves to `suspended`.

Trial requires no special billing wiring — it's just a Paymenter
Coupon + an automatic plan-change task.

## 8. Failure modes

| Failure | Behaviour |
| ------- | --------- |
| Gateway timeout during `createServer` | Job retries with idempotency check on `tenants` + `domains` (lookup by `service_id` stored in `tenants.data`) |
| Partial create (DB exists, seed failed) | `tenants:create --resume` flag re-runs from the failed step |
| Subdomain taken | Validation error at signup; never reaches the order |
| DNS not propagated for custom domain | Tenant is up on the subdomain; custom domain flagged `pending`; no impact to billing |

## 9. Reporting

The central operator panel surfaces:

- MRR / ARR (computed from central Paymenter invoices).
- Tenants by plan, churn rate, signup-to-activation latency.
- Per-plan revenue and average extensions enabled.

Charts use Paymenter's existing Filament widgets where possible
(`flowframe/laravel-trend` is already in `composer.json`).

## 10. Pricing of the SaaS itself — not in this doc

That's a business decision; this doc covers the **mechanics**. Open
questions for product:

- Free tier?
- Annual discount %?
- Affiliate / partner programme?
- Volume contracts for the `scale` plan?

Track them in product notes, not in this repo.
