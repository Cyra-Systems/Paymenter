# Migration Guide

How to take an **existing**, running single-tenant Paymenter instance and
fold it into the SaaS as one tenant. Also: ongoing operations — upstream
rebases, adding migrations, backups, and the deletion runbook.

> Reminder: the SaaS uses **single Postgres + RLS**, not database-per-
> tenant. Migration is mostly a Postgres dump + a `tenant_id` injection
> step. No `CREATE DATABASE` dance.

---

## 1. Migrate a live Paymenter into the SaaS

You have a customer running Paymenter at `billing.acme.com` today on
their own server. You want them to be tenant #1 on your SaaS.

### 1.1 Prepare

- Inventory their environment: PHP version, **database engine** (likely
  MariaDB — we need to convert), installed extensions, active theme,
  number of users / services / invoices.
- Confirm extension set is on the central catalogue and on the
  customer's plan.
- Confirm they're on the same Paymenter schema version as the SaaS
  (their `migrations` table matches the SaaS `database/migrations/`
  baseline). If behind, upgrade them in place first.

### 1.2 Convert MariaDB → Postgres

Most live customers run on MariaDB. Convert with `pgloader`:

```bash
# on a migration host with both DBs reachable
pgloader \
  --type mysql \
  --with "preserve index names" \
  --cast 'type tinyint to boolean drop typemod' \
  --cast 'type bigint to bigint drop typemod' \
  mysql://user:pwd@old-host/paymenter \
  postgresql://user:pwd@migration-host/acme_staging
```

The result is a Postgres DB with the same shape as the customer's
MariaDB, **without** RLS, **without** `tenant_id` columns.

### 1.3 Inject the tenant context

Generate a UUID for the tenant up-front, then add `tenant_id` to every
table and backfill in one transaction:

```sql
DO $$
DECLARE
  tid uuid := 'YOUR-NEW-UUID-HERE';
BEGIN
  -- For each tenant-scoped table:
  ALTER TABLE users     ADD COLUMN tenant_id uuid;
  UPDATE      users     SET tenant_id = tid;
  ALTER TABLE users     ALTER COLUMN tenant_id SET NOT NULL;
  -- ...repeat for every tenant-scoped table

  -- Then re-apply the RLS scaffolding (the TenantScoped trait does
  -- this on greenfield migrations; for an imported tenant we apply it
  -- directly):
  ALTER TABLE users ENABLE ROW LEVEL SECURITY;
  ALTER TABLE users FORCE  ROW LEVEL SECURITY;
  CREATE POLICY tenant_isolation ON users
    USING       (tenant_id = current_setting('app.tenant_id', true)::uuid)
    WITH CHECK  (tenant_id = current_setting('app.tenant_id', true)::uuid);
  ALTER TABLE users
    ALTER COLUMN tenant_id
    SET DEFAULT NULLIF(current_setting('app.tenant_id', true), '')::uuid;
END $$;
```

Wrap this in `database/seeders/MigrateLiveTenantSeeder.php` so it's
repeatable and reviewable. A loop over `information_schema.tables`
makes it bearable.

### 1.4 Merge the rows into the SaaS DB

Now the imported DB has the right shape. To merge into the SaaS:

```bash
# Dump tenant rows from the staging DB (one big multi-table dump).
pg_dump --data-only --no-owner --no-privileges \
  --table=users --table=products --table=services \
  --table=invoices --table=tickets ...etc \
  postgresql://user:pwd@migration-host/acme_staging \
  > acme-tenant-rows.sql

# Apply into the SaaS DB using the admin role (RLS-bypassed).
psql "postgresql://paymenter_admin:pwd@saas-host/paymenter" < acme-tenant-rows.sql
```

The `tenant_id` is already on every row, so RLS-bypassed insert works.

Insert the central rows:

```sql
INSERT INTO tenants (id, data, status) VALUES (
  'YOUR-NEW-UUID-HERE',
  '{"company_name":"Acme","plan":"pro",...}'::jsonb,
  'active'
);
INSERT INTO domains (tenant_id, domain, primary, ssl_status) VALUES
  ('YOUR-NEW-UUID-HERE', 'acme.paymenter.io',  true,  'active'),
  ('YOUR-NEW-UUID-HERE', 'billing.acme.com',   false, 'pending');
```

### 1.5 Storage

```bash
tar -czf acme-storage.tgz storage/app/    # on the old host
scp acme-storage.tgz saas-host:/tmp/
mkdir -p storage/app/tenant/YOUR-NEW-UUID-HERE
tar -xzf /tmp/acme-storage.tgz \
    -C storage/app/tenant/YOUR-NEW-UUID-HERE/ \
    --strip-components=2   # strip "storage/app/"
```

### 1.6 Re-key Passport

The customer's Passport keys are useless on the new host (different
`APP_KEY`). Inside the tenant context:

```bash
php artisan tinker
> Tenant::find('YOUR-NEW-UUID-HERE')->run(fn () =>
>     Artisan::call('passport:keys', ['--force' => true])
> );
```

Existing access tokens are invalidated. Customer's API clients
re-authenticate. Communicate the cutover in writing.

### 1.7 Cutover DNS

Point `billing.acme.com` to the SaaS proxy (`proxy.paymenter.io`). Add
the domain as a custom domain (or mark `ssl_status = 'active'` directly
if you can verify in a maintenance window).

### 1.8 Smoke test

- Log in as their admin.
- Verify their extensions show enabled with the original credentials.
- Verify email sends from their SMTP setting, not the SaaS one.
- Verify their theme renders.
- Verify a test order → invoice → payment flow.
- Verify Stripe Connect onboarding for the new platform-fee model
  (legacy Stripe gateway stays available for 90 days — see
  `STRIPE_CONNECT.md` § 11).

### 1.9 Decommission

After 7 days of dual-running (DNS TTL + buffer), tear down the
customer's old instance. Keep the dump for 90 days in cold storage.

---

## 2. Rebasing onto upstream Paymenter

Upstream `paymenter/paymenter` releases land on `master`. Our SaaS
lives on `main` with central additions on top.

### 2.1 Cadence

Weekly, or whenever upstream cuts a release.

### 2.2 Procedure

```bash
git checkout main
git fetch upstream
git rebase upstream/master
```

Conflicts you can predict:

- **`app/Providers/AppServiceProvider.php`** — we guarded the extension
  boot loop for tenancy. Re-apply the guard.
- **`app/Providers/Filament/AdminPanelProvider.php`** — we added
  tenant middleware. Re-apply.
- **`bootstrap/app.php`** — we registered the `tenant` middleware group.
  Re-apply.
- **`config/database.php`** — we added `pg` and `pg_admin` connections;
  upstream might have changed the default connection name. Re-apply.
- **`routes/web.php`** — we wrapped routes in the `tenant` group.
  Re-apply, and confirm any new routes get the same wrap.
- **`database/migrations/*.php`** — upstream adds new migrations
  without the `TenantScoped` trait. Patch each new tenant-scoped
  migration with:

  ```php
  use App\Database\TenantScoped;

  return new class extends Migration {
      use TenantScoped;
      // ...
      public function up(): void {
          Schema::create('new_table', function (Blueprint $t) { /* ... */ });
          $this->scopeToTenant('new_table');
      }
  };
  ```

After resolving:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

Then deploy to staging, run a tenant smoke test, then production.

### 2.3 Helper script

Optional: `scripts/rebase-paymenter.sh` walks the rebase, runs the
checks, reports. The first phase that adds it can include the script.

---

## 3. Adding a new tenant migration

Whenever you add a column to a tenant table (e.g. `services.notes`):

1. Create the migration with the `TenantScoped` trait if the table is
   new; if extending an existing table, just `ALTER TABLE` — the RLS
   policy stays in force.
2. Run `php artisan migrate` against the single Postgres DB. No
   `tenants:migrate` step exists — there's one DB.
3. For zero-downtime: write expand-then-contract migrations; never
   drop a column the running code still reads.

---

## 4. Adding a central-only migration

Whenever you add a column to a central table (e.g. `tenants.plan` or
`central_plans.platform_fee_bps`):

1. Create the migration in `database/migrations/`. Do **not** call
   `scopeToTenant` — central tables are not tenant-scoped.
2. Run `php artisan migrate`.

The same Postgres DB holds central and tenant rows; the only difference
is whether the table has a `tenant_id` column and a policy.

---

## 5. Backups

### 5.1 Strategy

- **Logical dumps**: nightly `pg_dump` to S3, 30-day retention.
- **Point-in-time recovery (PITR)**: continuous WAL archiving to S3
  (or managed Postgres equivalent). Lets us restore to any second in
  the last 7 days.
- **Storage**: `storage/app/tenant/*/` rsynced to S3 nightly.

### 5.2 Restore drill

Quarterly. Pick a random tenant, restore the latest backup into a
sandbox DB, run the smoke test inside that tenant. Document time and
glitches.

### 5.3 Per-tenant restore (the killer feature of single DB + RLS)

```bash
# Spin up a temporary DB from the nightly dump
createdb paymenter_temp
psql paymenter_temp < nightly.dump

# Export just the tenant's rows
pg_dump --data-only --no-owner --table=users --table=services \
  --table=invoices ... \
  --set "tenant_uuid='THE-UUID'" \
  --where "tenant_id = :'tenant_uuid'" \
  paymenter_temp > acme-restore.sql

# Apply back into prod, replacing the affected rows
psql "postgresql://paymenter_admin@prod/paymenter" -c \
  "BEGIN; DELETE FROM ... WHERE tenant_id = '...'; \\i acme-restore.sql; COMMIT;"
```

Only that tenant's rows touch prod; other tenants unaffected. With
DB-per-tenant this used to be free; with RLS it requires the row filter
in `pg_dump`, but it's still per-tenant clean.

### 5.4 Pre-deletion archive

When a tenant is terminated, the purge step writes a final per-tenant
dump (rows filtered by `tenant_id`) to
`s3://paymenter-archive/{tenant_uuid}/final-{date}.sql.gz` and keeps it
for **one year** before lifecycle deletion. Regulatory window for
billing data.

---

## 6. Tenant deletion runbook

> Destructive. Pair with another operator.

1. `php artisan tenants:terminate {uuid}` — flips status, sets grace.
2. Wait the grace window (or shorten on explicit "requested-deletion").
3. Operator confirms deletion in the central panel (records audit
   row).
4. `php artisan tenants:purge {uuid}`:
   - dumps tenant rows to `paymenter-archive`,
   - `DELETE FROM tenants WHERE id = ?` — cascades through every
     `tenant_id` FK with `ON DELETE CASCADE`, removing all tenant
     data atomically.
   - removes `storage/app/tenant/{id}/`,
   - removes `domains` rows (TLS certs auto-expire),
   - removes `tenants` row.
5. Email the operator who initiated with archive URL and SHA256.

A monthly job audits orphans (FS without DB rows, etc.) and surfaces
them.

---

## 7. Disaster recovery

| Failure | Plan |
| ------- | ---- |
| Whole Postgres host gone | Restore latest dump + replay WAL to last-write second; pre-warm cache; verify all tenants reachable on a randomised sample. |
| One tenant's data corrupted | Per-tenant restore (§ 5.3); other tenants unaffected. |
| WAL archive corruption | Fall back to nightly dump → small data-loss window; alert on first failed WAL ship. |
| Storage gone | Restore from S3; up to one day of attachments lost (frequency of rsync). |
| TLS provider outage | Caddy continues serving cached certs; new-domain issuance paused until restored. |
| Stripe Connect outage | New charges fail; subscription-side billing continues via plain Stripe (separate gateway). |

Per-failure runbooks live in `ops/runbooks/` in the ops repo.

---

## 8. Common pitfalls

- **Forgetting `scopeToTenant()` on a new tenant table.** That table
  has no RLS and no `tenant_id` — a cross-tenant leak. A CI test
  walks every table in `information_schema.tables`, asserts each
  tenant-scoped table has the `tenant_id` column AND a forced RLS
  policy, lists exceptions explicitly.
- **Running a maintenance query as `paymenter_admin` outside a
  transaction with an explicit `WHERE tenant_id = ...`.** Easy to
  nuke all tenants by accident; require a paired-operator approval
  for any RLS-bypassed mutation in production.
- **Caching a setting in module scope.** Leaks across tenants. Use the
  tenant-aware cache (Phase 5 fix).
- **A job that calls `URL::route()` from inside an extension hook.**
  Works in request context; in queue context, you need
  `URL::forceRootUrl($tenant->primaryDomain())` in the bootstrapper.
- **Running `php artisan config:cache` in dev with a tenant
  initialised.** Bakes tenant config into the build. Always
  `config:clear` after tenancy work.
- **Importing the legacy MariaDB schema and assuming a default
  `tenant_id`.** The Postgres column default only fires on INSERT;
  for the initial bulk load you must set `tenant_id` explicitly (§ 1.3).
