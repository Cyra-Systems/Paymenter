# Migration Guide

How to take an **existing**, running single-tenant Paymenter instance and
fold it into the SaaS as one tenant. Also covers ongoing operations:
upstream rebases, adding migrations, backups, and the deletion runbook.

---

## 1. Migrate a live Paymenter into the SaaS

You have a customer running Paymenter at `billing.acme.com` today on
their own server. You want them to be tenant #1 on your SaaS.

### 1.1 Prepare

- Inventory their environment: PHP version, MariaDB version, list of
  installed extensions, the active theme, the number of users / services
  / invoices.
- Confirm extension set is on the central catalogue (and on the customer's
  plan).
- Confirm they're on the same Paymenter schema version as the SaaS (the
  `migrations` table on their DB matches `database/migrations/tenant/`).
  If not, upgrade them in place first.

### 1.2 Dump

```bash
# on the customer's server
mysqldump --single-transaction \
          --routines --triggers --events \
          --skip-add-locks \
          -u paymenter -p paymenter > acme-dump.sql

tar -czf acme-storage.tgz storage/app/
```

### 1.3 Create the tenant on the SaaS

On the SaaS host:

```bash
php artisan tenants:create \
    --uuid=acme-... \
    --subdomain=acme \
    --skip-migrate \
    --skip-seed
```

`--skip-migrate --skip-seed` tells `CreateTenantAction` to create the row
+ database + Domain entry, but not run our own migrations or seeders.

### 1.4 Import

```bash
mysql -u root -p tenant_<uuid> < acme-dump.sql
tar -xzf acme-storage.tgz -C storage/app/tenant<id>/
```

### 1.5 Rekey Passport

The customer's Passport keys are useless on the new host (different
encryption key). Inside tenant context:

```bash
php artisan tinker
> Tenant::find('uuid')->run(function () {
>     Artisan::call('passport:keys', ['--force' => true]);
> });
```

Existing access tokens are invalidated. The customer's API clients
re-authenticate. Communicate the cutover.

### 1.6 Cutover DNS

Point `billing.acme.com` to the SaaS proxy (`proxy.paymenter.io`). Add
the domain in the central panel as a custom domain for the tenant (mark
as already-verified to skip TXT verification, or follow the standard
workflow if you have a maintenance window).

### 1.7 Smoke test

- Log in as their admin.
- Create an order, run through to invoice, pay (testmode).
- Verify their extensions are enabled and authenticated.
- Verify email sending uses **their** SMTP setting, not the SaaS one.
- Verify their theme renders.

### 1.8 Decommission the old server

After 7 days of dual-running (DNS TTL + a buffer), tear down the
customer's old instance. Keep the dump for 90 days in cold storage.

---

## 2. Rebasing onto upstream Paymenter

Upstream `paymenter/paymenter` releases land on `master`. Our SaaS lives
on `main` with central additions on top.

### 2.1 Cadence

Once a week (or whenever upstream cuts a release).

### 2.2 Procedure

```bash
git checkout main
git fetch upstream
git rebase upstream/master
```

Conflicts you can predict:

- **`app/Providers/AppServiceProvider.php`** — we added the
  tenancy-aware extension boot guard. Re-apply.
- **`app/Providers/Filament/AdminPanelProvider.php`** — we added tenant
  middleware. Re-apply.
- **`bootstrap/app.php`** — we registered the `tenant` middleware group.
  Re-apply.
- **`config/database.php`** — we added the `tenant` connection.
  Re-apply.
- **`routes/web.php`** — we wrapped routes in the `tenant` group.
  Re-apply (and confirm any new routes get the same wrap).
- **`database/migrations/*.php`** — upstream adds new tenant migrations
  to `database/migrations/` because they don't know about our split.
  Move them to `database/migrations/tenant/`.

After resolving:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

Then deploy to staging, run a tenant smoke test, then prod.

### 2.3 Helper script

Optional: `scripts/rebase-paymenter.sh` that walks the rebase, runs the
checks, and reports.

---

## 3. Adding a new tenant migration

Whenever you add a column to a tenant table (e.g. `services.notes`):

1. Create the migration in `database/migrations/tenant/` with a current
   timestamp.
2. Run **central**: `php artisan migrate` — no-op for tenant migrations.
3. Run **all tenants**: `php artisan tenants:migrate`.
4. For zero-downtime: write expand-then-contract migrations; never drop
   a column the running code still reads.

The CI pipeline includes a check that runs `php artisan tenants:migrate`
against the test seed of tenants and fails on errors.

---

## 4. Adding a new central migration

Whenever you add a column to a central table (e.g. `tenants.plan`):

1. Create the migration in `database/migrations/` (not `/tenant/`) with a
   current timestamp.
2. Run `php artisan migrate`.
3. **Do not** run `tenants:migrate` — central migrations are not for
   tenant DBs.

If a CI check complains the migration somehow tries to run on a tenant DB,
re-check that `config/tenancy.php` `migration_parameters` excludes it
(it does, by way of the `--path` flag pointing at `tenant/`).

---

## 5. Backups

### 5.1 Strategy

- Central DB: nightly dump → S3, 30-day retention.
- Each tenant DB: nightly dump → `s3://paymenter-backups/{tenant_uuid}/`,
  30-day retention.
- `storage/app/tenant{id}/` rsync to the same prefix nightly.

### 5.2 Restore drill

Quarterly. Pick a random tenant, restore the latest backup into a
sandbox SaaS instance, smoke test. Document the time and any glitches.

### 5.3 Pre-deletion archive

When a tenant is terminated, the purge step writes a final dump to
`s3://paymenter-archive/{tenant_uuid}/final-{date}.sql.gz` and keeps it
for **one year** before lifecycle-deleting. This is the regulatory
window for billing data.

---

## 6. Tenant deletion runbook

> Destructive. Pair with another operator.

1. `php artisan tenants:terminate {uuid}` — flips status, sets grace.
2. Wait the grace window (or shorten for `requested-deletion`).
3. Operator confirms deletion in central panel (records audit row).
4. `php artisan tenants:purge {uuid}`:
   - dumps DB to `paymenter-archive`,
   - drops the database,
   - removes `storage/app/tenant{id}/`,
   - removes `domains` rows (TLS certs auto-expire),
   - removes the `tenants` row.
5. Email the operator who initiated, with archive URL and SHA256.

A monthly job audits orphans: directories without DB rows, DB rows
without tenants, etc., and surfaces them.

---

## 7. Disaster recovery

| Failure | Plan |
| ------- | ---- |
| Central DB corrupted | Restore last nightly to a fresh DB; replay app-level audit logs for the day. |
| One tenant DB corrupted | Restore that tenant only; other tenants unaffected. |
| Whole MySQL host gone | Restore central + all tenant DBs onto a new host; update connection config; pre-warm cache. |
| Storage gone | Restore from S3 backup; missing newest day of attachments is the cost of nightly backups. |
| TLS provider outage | Caddy continues serving cached certs; tenant adds for new domains pause until restored. |

The runbook for each of these lives in `ops/runbooks/` (not in this
repo; private ops repo).

---

## 8. Common pitfalls

- **Forgetting to wrap a new route in the `tenant` middleware group** —
  the route serves on `central.paymenter.io` and 404s on tenant domains,
  or vice versa. Lint check: a CI test that walks `routes/web.php` AST
  and asserts every non-central route has the `tenant` group.
- **Caching a setting in module scope** — leaks. Use the tenant-aware
  cache.
- **A job that calls `URL::route()` from inside an extension hook** —
  works in request context, breaks in queue context if you forgot
  `URL::forceRootUrl` in the tenancy bootstrapper.
- **Running `php artisan migrate` and forgetting `tenants:migrate`** —
  central is up to date, tenants are stale, mysterious errors.
- **`config:cache` in dev with a tenant initialised** — bakes tenant
  config into the cache. Do `config:clear` after.
