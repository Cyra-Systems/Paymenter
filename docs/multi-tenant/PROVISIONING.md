# Provisioning

How a tenant goes from a credit-card submit to a working Paymenter
instance, and how an existing tenant is suspended or torn down.

> Implementation note: because we use **single-database Postgres with
> RLS** (not database-per-tenant), provisioning is much faster than the
> classic "spin up a DB" SaaS model — usually under 5 seconds.

---

## 1. Signup flow

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
│  3. SET app.tenant_id …     │  (RLS context)
│  4. seed tenant defaults    │
│  5. passport:keys (on disk) │
│  6. send welcome email      │
└─────────────────────────────┘
```

No `CREATE DATABASE`, no `migrate` per tenant — the schema and policies
already exist. We just create the tenant row, set the RLS context, and
INSERT the seed rows; Postgres fills `tenant_id` automatically via the
column default.

---

## 2. Inputs

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
names (`www`, `central`, `admin`, `api`, `mail`, `status`, …) from a
configurable list in `config/tenancy.php`.

---

## 3. `CreateTenantAction` (canonical reference implementation)

```php
namespace App\Central\Actions;

use App\Models\Tenant;
use App\Models\Domain;
use Illuminate\Support\Facades\{Artisan, DB};
use Illuminate\Support\Str;

class CreateTenantAction
{
    public function __invoke(array $input): Tenant
    {
        // Central DB transaction — RLS-bypassed via the pg_admin connection.
        return DB::connection('pg_admin')->transaction(function () use ($input) {
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

            // Switch into the tenant's RLS context for seeding.
            $tenant->run(function () use ($input) {
                // Passport keys go onto the tenant-prefixed local disk.
                Artisan::call('passport:keys', ['--force' => true]);

                \App\Models\Role::create([
                    'name' => 'Owner',
                    'permissions' => ['*'],
                ]);

                \App\Models\User::create([
                    'first_name' => $input['admin_first_name'],
                    'last_name'  => $input['admin_last_name'],
                    'email'      => $input['admin_email'],
                    'password'   => bcrypt(Str::random(40)),  // overwritten by setup link
                    'role_id'    => 1,
                ]);

                \App\Models\Currency::firstOrCreate([
                    'code' => $input['currency'],
                ], ['name' => $input['currency']]);

                setting(['app_name'           => $input['company_name']])->save();
                setting(['mail_from_address'  => $input['admin_email']])->save();
                setting(['theme.active'       => 'default:curated'])->save();
            });

            $tenant->update(['data->status' => 'active']);

            \App\Central\Mail\TenantWelcome::send($tenant);
            return $tenant;
        });
    }
}
```

Why this works without a `migrate` step: the `users`, `roles`,
`currencies`, `settings` (etc.) tables already exist, are RLS-guarded,
and have a `tenant_id` DEFAULT of `current_setting('app.tenant_id', true)::uuid`.
Inside `$tenant->run(...)`, the RLS bootstrapper has executed `SET LOCAL
app.tenant_id = '<uuid>'`, so every INSERT picks up the right tenant
id automatically.

---

## 4. Async vs. sync

The action runs inside a queued job (`CreateTenantJob`) on the central
queue. Signup returns a "we're provisioning" page that polls. End-to-end
in production: about 3-8 seconds (no per-tenant DB creation; mostly the
seed inserts and the welcome email).

---

## 5. Tenant defaults

Seeded inline (above). For richer defaults — sample products, email
templates, etc. — call `database/seeders/TenantDefaultsSeeder` inside
the same `$tenant->run(...)` block. It must **not** require any prior
tenant data and must not assume any environment variable.

Explicitly **not** seeded for production tenants:

- Demo products, fake orders, fake users.
- Any extension enablement (tenant picks from the catalogue).
- Any payment gateway config (tenant fills credentials).

---

## 6. Welcome email + magic link

`App\Central\Mail\TenantWelcome` sends from the **central** SMTP to the
admin. Contains a signed URL good for 24 hours:

```
https://{subdomain}.paymenter.io/setup?token={hmac}
```

The setup page (Livewire) sets the password and 2FA, then redirects to
`/admin`.

---

## 7. Suspension

Triggered by:

- An overdue central invoice past grace.
- Manual operator action in the central panel.
- A T&C violation flag.
- Stripe Connect account being deauthorised AND the plan requires
  Stripe Connect for SaaS subscription (rare edge case).

Mechanics: flip `tenants.data->status` to `suspended`. The
`EnforceTenantStatus` middleware checks this on every request and
returns the appropriate response.

```php
class EnforceTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $status = tenant()->data['status'] ?? 'active';

        return match ($status) {
            'active'      => $next($request),
            'suspended'   => response()->view('central.suspended', [], 402),
            'terminating' => response()->view('central.terminating', [], 503),
            default       => abort(404),
        };
    }
}
```

Suspension does **not** delete data; it just blocks request handling
and pauses Stripe charges (we cancel any pending subscription renewals
to prevent surprise charges).

---

## 8. Termination

Two-step with a grace window:

1. **Mark for termination** — `status = 'terminating'`, set
   `terminate_at = now()->addDays(30)`. Tenant cannot log in. Operator
   can re-activate from the central panel.
2. **Purge** — daily scheduler runs `php artisan tenants:purge`:
   - Dump the tenant's rows from every tenant table to
     `s3://paymenter-archive/{tenant_uuid}/final-{date}.sql.gz` (use
     `pg_dump --table` per table with a `WHERE tenant_id = '...'`
     filter, or a single `COPY (SELECT ...) TO STDOUT`).
   - `DELETE FROM tenants WHERE id = ?` — cascades through every FK
     thanks to `ON DELETE CASCADE` on the `tenant_id` columns,
     removing all tenant data atomically in a single transaction.
   - Remove `storage/app/tenant/{id}/`.
   - Remove `domains` rows (TLS certs auto-expire).
   - Log to `central_audit`.

Because the data is in a single Postgres database, the `DELETE` is
atomic and quick — no partial-cleanup state.

---

## 9. Operational commands

```bash
# create
php artisan tenants:create --subdomain=acme --email=ops@acme.test --plan=pro

# list
php artisan tenants:list

# inspect (run code in tenant context)
php artisan tinker
> Tenant::find('uuid')->run(fn () => User::count())

# seed one tenant
php artisan tenants:seed --tenants=uuid --class=NewTenantSeeder

# suspend / activate
php artisan tenants:suspend uuid
php artisan tenants:activate uuid

# terminate (grace window starts)
php artisan tenants:terminate uuid

# purge (after grace, destroys data; archives first)
php artisan tenants:purge uuid
```

All Artisan commands are thin wrappers around `App\Central\Actions`.
The signup flow, the central Filament panel, and the Server extension
all call the same Actions.

---

## 10. Test data for local dev

`database/seeders/CentralDevSeeder.php`:

- A central operator (`admin@central.test` / `password`).
- Three plans (free, pro, scale).
- Two tenants seeded with Paymenter defaults plus a couple of demo
  products each: `alpha.paymenter.test`, `beta.paymenter.test`.

```bash
php artisan migrate:fresh --seed --seeder=CentralDevSeeder
```

`/etc/hosts` (or dnsmasq) maps `*.paymenter.test` to 127.0.0.1.

---

## 11. Provisioning failure modes

| Failure | Handling |
| ------- | -------- |
| Subdomain race (two signups for same subdomain in flight) | DB unique constraint on `domains.domain` rejects the second; Action surfaces a friendly error. |
| Welcome email transport failure | Provisioning still succeeds; an admin-only operator alert fires; tenant can resend from the central panel. |
| Seed failure (e.g. role insert blew up) | `DB::connection('pg_admin')->transaction()` rolls back the central rows; tenant row never exists. |
| First invoice never paid | `tenants` row stays at `status = 'pending'`; nothing is provisioned (the Server extension's `createServer` never fires); a cleanup job removes stale pending tenants after 7 days. |
| Stripe webhook arrives before `createServer` finishes | The webhook handler waits for the tenant row to exist, with a short backoff. After 60s it dead-letters. |

---

## 12. Re-provisioning

If a tenant resurrects a terminated subdomain within the 30-day grace
window, `tenants:activate` flips the status back. After purge, the
subdomain is reusable; the new signup is independent (new UUID, new
data).

For a tenant who wants to migrate to a different subdomain, we add a
new `domains` row (primary = true) and demote the old one. No data
migration needed.
