# Provisioning

How a new tenant goes from a credit-card submit to a working Paymenter
instance, and how an existing tenant is suspended or torn down.

## 1. Tenant signup flow

```
┌─────────────────────────────┐
│ Visitor                     │
│ central.paymenter.io        │
└──────────────┬──────────────┘
               │ POST /signup
               ▼
┌─────────────────────────────┐
│ Central app                 │
│  - validate                 │
│  - check subdomain free     │
│  - create Order on plan     │  (central Paymenter Product)
└──────────────┬──────────────┘
               │ pay first invoice
               ▼
┌─────────────────────────────┐
│ Server extension:           │
│ PaymenterTenant::create     │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ CreateTenantAction          │
│  1. INSERT tenants          │
│  2. INSERT domains          │
│  3. CREATE DATABASE         │
│  4. php artisan tenants:    │
│     migrate {id}            │
│  5. seed defaults           │
│  6. passport:keys           │
│  7. send welcome email      │
└─────────────────────────────┘
```

## 2. Inputs

The signup form collects:

| Field | Validation | Example |
| ----- | ---------- | ------- |
| company_name | required, max 100 | "Acme Hosting" |
| subdomain | required, lowercase, 3-32, `[a-z0-9-]`, unique in `domains` | `acme` |
| admin_email | required, email, unique on central side | `ops@acme.test` |
| admin_first_name, admin_last_name | required | "Jane", "Doe" |
| plan_slug | required, exists on `central_plans` | `pro` |
| timezone | required, valid timezone | `Europe/Amsterdam` |
| currency | required, ISO 4217 | `EUR` |

The `subdomain` becomes `${subdomain}.paymenter.io`. We refuse reserved
names (`www`, `central`, `admin`, `api`, `mail`, `status`, plus a
configurable list in `config/tenancy.php`).

## 3. `CreateTenantAction` (canonical reference implementation)

```php
namespace App\Central\Actions;

use App\Models\Tenant;
use App\Models\Domain;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class CreateTenantAction
{
    public function __invoke(array $input): Tenant
    {
        return \DB::transaction(function () use ($input) {
            $tenant = Tenant::create([
                'id'   => (string) Str::uuid(),
                'data' => [
                    'company_name' => $input['company_name'],
                    'plan'         => $input['plan_slug'],
                    'status'       => 'provisioning',
                    'timezone'     => $input['timezone'],
                    'currency'     => $input['currency'],
                ],
            ]);

            Domain::create([
                'tenant_id' => $tenant->id,
                'domain'    => $input['subdomain'] . '.' . config('tenancy.central_domain'),
                'primary'   => true,
            ]);

            // stancl/tenancy: creates the DB and runs migrations
            $tenant->runForCurrent();
            Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);
            Artisan::call('tenants:seed',    ['--tenants' => [$tenant->id], '--class' => 'TenantDefaultsSeeder']);

            $tenant->run(function () use ($input) {
                Artisan::call('passport:keys', ['--force' => true]);
                \App\Models\User::create([
                    'first_name' => $input['admin_first_name'],
                    'last_name'  => $input['admin_last_name'],
                    'email'      => $input['admin_email'],
                    'password'   => bcrypt(Str::random(40)),  // overwritten by setup link
                    'role_id'    => 1,
                ]);
                setting(['mail_from_address' => $input['admin_email']])->save();
                setting(['app_name' => $input['company_name']])->save();
            });

            $tenant->update(['data' => array_merge($tenant->data, ['status' => 'active'])]);

            \App\Central\Mail\TenantWelcome::send($tenant);
            return $tenant;
        });
    }
}
```

> The transaction is in the **central** DB only. `CREATE DATABASE` is not
> transactional; if step 3 succeeds and step 6 fails, the central row is
> rolled back but the tenant DB is left behind. The compensating action
> (drop DB) is in the catch path of the queue job that wraps this action.

## 4. Async vs. sync

The action runs inside a queued job (`CreateTenantJob`) on the central
queue, so the signup form returns immediately with a "we're provisioning"
page that polls. Provisioning in production takes 10–30s on a healthy DB
server.

## 5. The `TenantDefaultsSeeder`

Lives at `database/seeders/TenantDefaultsSeeder.php`. Seeds:

- A default `Role` named `Owner` with all permissions.
- A default `Currency` (the one chosen in signup).
- A default empty `Setting` set (app_name, etc).
- A default email template set.
- **Does not** seed sample products, demo orders, or test data — tenants
  see a clean slate.

## 6. Welcome email + magic link

`App\Central\Mail\TenantWelcome` sends an email from the **central** SMTP
to the admin. It contains a signed URL good for 24 hours:

```
https://{subdomain}.paymenter.io/setup?token={hmac}
```

The setup page (Livewire) sets the password and 2FA, then redirects to
`/admin`.

## 7. Suspension

Triggered by:

- An overdue central invoice (after grace period).
- Manual operator action in the central panel.
- A terms-of-service violation flag.

Mechanics: flip `tenant->data['status'] = 'suspended'`. The tenant
middleware checks this on every request and returns the suspended page
template (HTTP 402 or 503 depending on cause).

```php
// app/Http/Middleware/EnforceTenantStatus.php
public function handle(Request $request, Closure $next): Response
{
    $status = tenant()->data['status'] ?? 'active';

    return match ($status) {
        'active'         => $next($request),
        'suspended'      => response()->view('central.suspended', [], 402),
        'terminating'    => response()->view('central.terminating', [], 503),
        default          => abort(404),
    };
}
```

## 8. Termination

Two-step, with a grace window:

1. **Mark for termination** — `status = 'terminating'`, set
   `terminate_at = now()->addDays(30)`. Tenant cannot log in. Operator
   can still re-activate from central panel.
2. **Drop** — a daily scheduler runs `php artisan tenants:purge` which:
   - Snapshots a final DB dump to `s3://paymenter-archive/{tenant}/`.
   - `DROP DATABASE tenant_{uuid}`.
   - Removes `storage/app/tenant{id}/`.
   - Deletes the `domains` rows.
   - Deletes the `tenants` row.
   - Logs to `central_audit`.

This is **destructive** and **irreversible**; we always require a
re-confirmation from a central operator before the purge runs, unless the
tenant explicitly opted into auto-purge on signup.

## 9. Operational commands

```bash
# create
php artisan tenants:create --subdomain=acme --email=ops@acme.test --plan=pro

# list
php artisan tenants:list

# inspect
php artisan tinker
> Tenant::find('uuid')->run(fn () => User::count())

# migrate all tenants (after upstream rebase adds a migration)
php artisan tenants:migrate

# seed one tenant
php artisan tenants:seed --tenants=uuid --class=NewSeeder

# suspend / activate
php artisan tenants:suspend uuid
php artisan tenants:activate uuid

# delete (grace + dump)
php artisan tenants:terminate uuid
```

The Artisan commands are thin wrappers around the same `App\Central\Actions`
that the central panel and the signup flow call. One code path, three
entry points.

## 10. Test data

For local dev, `database/seeders/CentralDevSeeder.php` creates:

- A central operator (`admin@central.test` / `password`).
- Three plans (free, pro, scale).
- Two tenants (`alpha.paymenter.test`, `beta.paymenter.test`) seeded with
  Paymenter defaults plus a couple of demo products.

Run with `php artisan migrate:fresh --seed --seeder=CentralDevSeeder`.
