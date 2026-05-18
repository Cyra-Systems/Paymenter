# Architecture Decisions

Decision record for the multi-tenant conversion. Each section captures
the choice, the rejected alternatives, and **why**. Treat it as binding —
deviations require updating this file.

---

## AD-001 — Tenancy package: `stancl/tenancy` v4 (single-database mode)

**Choice.** Use [`stancl/tenancy`](https://tenancyforlaravel.com) v4 in
**single-database mode**: it owns tenant identification (by domain),
queue serialisation, cache prefixing, filesystem prefixing, and
bootstrappers. We supply a custom **RLS bootstrapper** that sets the
Postgres session variable on tenancy initialisation.

We are **not** using its database-per-tenant mode.

**Alternatives rejected.**

- Roll our own tenancy layer — `stancl/tenancy` already solves domain
  identification, the queue-payload tenant tag, and the bootstrapper
  lifecycle. Skipping it just to save a dependency is short-sighted.
- `spatie/laravel-multitenancy` — viable, but smaller community and
  less idiomatic with Filament v5.

**Implication.** Tenancy is identified at the edge, but the isolation
that keeps a misbehaving query from leaking across tenants comes from
Postgres itself (see AD-002). The package provides the orchestration;
Postgres provides the guarantees.

---

## AD-002 — Isolation: Postgres + Row-Level Security

**Choice.** Single Postgres 16+ database. Every tenant-scoped table has
a `tenant_id uuid` column. Each such table has Row-Level Security
**enabled and forced** with a policy:

```sql
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoices FORCE  ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON invoices
  USING       (tenant_id = current_setting('app.tenant_id', true)::uuid)
  WITH CHECK  (tenant_id = current_setting('app.tenant_id', true)::uuid);

ALTER TABLE invoices
  ALTER COLUMN tenant_id
  SET DEFAULT NULLIF(current_setting('app.tenant_id', true), '')::uuid;
```

The `DEFAULT` makes the database fill `tenant_id` automatically on every
INSERT from the application; even if model code forgets, Postgres covers
us. The policy makes SELECT/UPDATE/DELETE invisible across tenants.

**Alternatives rejected.**

- **Database-per-tenant.** Operationally heavier (one DB to back up,
  monitor, schema-migrate, vacuum per tenant), slower provisioning
  (`CREATE DATABASE` + 71 migrations = 10–30s), harder analytics, and
  unnecessary at our scale curve. The original draft of these docs
  chose this; the pivot to Postgres + RLS supersedes it.
- **Schema-per-tenant.** Same operational headaches as DB-per-tenant
  without the clean blast-radius story.
- **`tenant_id` columns with global query scopes only.** Soft
  enforcement — a single `DB::statement(raw SQL)` or a third-party
  package that bypasses the scope leaks across tenants. RLS makes the
  guarantee a Postgres-enforced one.

**Implication.** Choice of DB engine is now load-bearing on Postgres;
MariaDB is out of the picture. See `TENANT_ISOLATION.md` for full
mechanics.

---

## AD-003 — Two Postgres roles, two Laravel connections

**Choice.**

| Postgres role | `BYPASSRLS` | Laravel connection | Used by |
| ------------- | ----------- | ------------------ | ------- |
| `paymenter_app` | **No** | `pg` (default) | All tenant traffic |
| `paymenter_admin` | **Yes** | `pg_admin` | Central operator panel, provisioning, billing aggregations, ledger reconciliation |

The default connection name stays `pg` so most code paths need no
changes. Central code that genuinely needs cross-tenant data **must**
explicitly target the `pg_admin` connection.

**Implication.** Code that should be tenant-scoped never has to opt in to
RLS — it gets it by default. Code that wants to escape RLS has to ask in
writing (`DB::connection('pg_admin')->...`), which is greppable.

---

## AD-004 — Tenant identification: domain, never path

**Choice.** Tenants are identified by `Host` header:

- Subdomain: `acme.paymenter.io`.
- Custom domain: `billing.acme.com` (after the customer points DNS at us).

`stancl/tenancy`'s `InitializeTenancyByDomain` middleware does the lookup.

**Alternatives rejected.**

- Path prefix (`paymenter.io/acme/...`) — breaks Paymenter's existing
  route structure (`/admin`, `/dashboard`, etc.) and forces cookies +
  OAuth redirects to be path-aware. Massive refactor.
- Header (`X-Tenant: acme`) — fine for APIs, awful for browsers.

**Implication.** Wildcard DNS for `*.paymenter.io`, a wildcard or
ACME-managed TLS cert, and a custom-domain workflow that issues
per-domain certs (typically via Let's Encrypt). See `DOMAIN_ROUTING.md`.

---

## AD-005 — Migrations: single folder, `BelongsToTenant` mixin

**Choice.** All migrations live in `database/migrations/`. A migration
helper trait `TenantScoped` adds:

- the `tenant_id uuid` column,
- a foreign key to `tenants(id)` with `ON DELETE CASCADE`,
- the `ENABLE / FORCE ROW LEVEL SECURITY` statements,
- the `tenant_isolation` policy, and
- the `DEFAULT current_setting('app.tenant_id', true)::uuid`.

Usage in a migration:

```php
use App\Database\TenantScoped;

return new class extends Migration {
    use TenantScoped;

    public function up(): void {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            // ...
            $t->timestamps();
        });
        $this->scopeToTenant('orders');
    }
};
```

Central-only tables (`tenants`, `central_users`, `central_plans`,
`extension_catalogue`, `theme_catalogue`, `stripe_platform_ledger`)
simply do not call `scopeToTenant()`.

**Alternatives rejected.**

- Two migration folders (one for central, one for tenant) — was the
  original design when we planned DB-per-tenant. Unnecessary in a
  single-DB world; one folder is the simpler convention.

**Implication.** When upstream Paymenter adds a migration on `master`,
the rebase only needs us to add a `use TenantScoped` + `$this->scopeToTenant(...)`
call. The diff per upstream migration is two lines.

---

## AD-006 — Two Filament panels

**Choice.** Two Filament v5 `PanelProvider` classes:

- `AdminPanelProvider` (existing, in
  `app/Providers/Filament/AdminPanelProvider.php`) — **tenant admin
  panel**. Mounted at `/admin` on each tenant domain. Uses the `pg`
  connection.
- `CentralPanelProvider` (new) — **operator panel** for SaaS staff to
  manage tenants, plans, custom domains, the extension and theme
  catalogues, and the Stripe platform ledger. Mounted at
  `central.paymenter.io/admin` using
  `Panel::domain('central.paymenter.io')` and the `pg_admin` connection.

**Alternatives rejected.**

- One panel, role-gated — collapses the security boundary and a
  misconfigured permission would expose tenant data to operators.
- Two separate Laravel apps — duplication is worse than one repo with
  two panels.

**Implication.** The central panel has its own resources, its own user
model (`CentralUser`), and its own auth guard (`central`). Tenant users
cannot log into the central panel and vice versa.

---

## AD-007 — Authentication scope

**Choice.** Authentication is per-tenant. The `users` table lives in the
shared DB but carries `tenant_id`; RLS prevents cross-tenant lookup.
Sessions and password resets are scoped by domain (cookies are
domain-scoped, automatic once tenancy initialises).

The central app uses a separate `central_users` table and a separate
auth guard called `central` with its own session cookie name.

**Implication.** A single human running three hosting companies on us
has three separate logins, one per tenant — same as Shopify, Vercel,
etc. Cross-tenant SSO is a future feature, not phase 1.

---

## AD-008 — Cache, queue, storage, mail, Passport isolation

**Choice.** Use `stancl/tenancy`'s bootstrappers, plus three custom ones:

| Concern | Bootstrapper |
| ------- | ------------ |
| **RLS context** | Custom `PostgresRlsBootstrapper`: `SET LOCAL app.tenant_id = '...'` on the `pg` connection. |
| **Cache** | `CacheTenancyBootstrapper` — prefixes keys with `t{tenant_id}::`. Required because `SettingsProvider` caches under the bare key `"settings"`. |
| **Queue** | `QueueTenancyBootstrapper` — serialises tenant id in the job payload; worker re-bootstraps tenancy (including RLS context) before running. |
| **Filesystem** | `FilesystemTenancyBootstrapper` — rewrites disk root to `storage/app/tenant/{id}/...`. |
| **Mail** | Custom `MailTenancyBootstrapper` — pushes tenant `settings` rows (`mail_from_address`, SMTP creds, …) into `config('mail.*')`. |
| **Passport** | Custom `PassportTenancyBootstrapper` — points key paths at the tenant filesystem disk. |

**Implication.** Anything that goes through the standard Laravel facades
gets isolation for free. Anything that bypasses them (custom Cache
store, hardcoded path, raw connection) must be audited — see the
checklist in `TENANT_ISOLATION.md`.

---

## AD-009 — Curated extensions

**Choice.** Extension code lives on disk under `extensions/` (shared,
operator-curated). Per-tenant **enablement and configuration** lives in
tenant-scoped tables (`extensions`, `settings`), automatically isolated
by RLS. Tenants pick from a `extension_catalogue` allow-list curated by
the central operator.

Every extension ships an `extension.json` manifest declaring required
capabilities (HTTP egress allow-list, mail, queue, storage, settings
keys, HTML/Markdown rendering). Paymenter's `ExtensionHelper` enforces
the manifest at runtime; undeclared capabilities throw.

Any string an extension renders into HTML goes through a hardened
sanitiser pipeline (HTMLPurifier + CSS allow-list + CommonMark with
strict extensions + a strict CSP header).

**Alternatives rejected.**

- **Tenant-uploaded PHP extensions.** A code-execution-as-a-service
  nightmare absent a real sandbox (e.g. WASM or a separate process pool
  with `disable_functions`). Out of scope for v1; deferred.

**Implication.** Adding an extension is a PR + operator approval; the
catalogue is the source of truth for what tenants can enable. See
[`EXTENSIONS.md`](./EXTENSIONS.md).

---

## AD-010 — Themes: curated + sandboxed BYO

**Choice.** Two-tier theme system:

- **Curated themes** ship under `themes/` on disk. Vetted by the
  operator, fully trusted.
- **Bring-Your-Own (BYO) themes** uploaded by a tenant (or designer) as
  a zip. Validated against a manifest, file-allow-list, Blade sandbox
  compiler, CSS sanitiser, and JS allow-list. Stored in the tenant's
  filesystem prefix. Available on Pro+ plans.

**Alternatives rejected.**

- **Allow arbitrary Blade in BYO themes.** Blade resolves PHP, so
  arbitrary Blade = arbitrary PHP. Refused.
- **Switch templating to Twig sandbox.** Possible, but adds a second
  template language. Sandboxed Blade is enough.
- **JSON-only theme config (Shopify Sections model).** Considered;
  rejected as too restrictive for the typography-heavy invoice / order
  templates Paymenter ships.

**Implication.** BYO themes are constrained but real — full Blade
template authoring within a clear safety envelope. See [`THEMES.md`](./THEMES.md).

---

## AD-011 — Stripe Connect platform fee

**Choice.** We register as a Stripe **Connect platform**. Every tenant
links their own Stripe account via OAuth (**Standard** connected
accounts). When a tenant's customer pays, we create the PaymentIntent
on **our** platform account with `transfer_data.destination` pointing
at the tenant's connected account and `application_fee_amount` set per
the tenant's plan.

The platform fee is **mandatory** and configured at the central level
(`central_plans.platform_fee_bps`, `central_plans.platform_fee_flat_cents`).
Tenants cannot turn it off.

**Alternatives rejected.**

- **Stripe Connect Custom** (we are merchant of record) — much heavier
  compliance burden (KYC, 1099-K, dispute resolution). Reject for v1;
  can offer to Scale-plan tenants on request later.
- **No platform fee, charge a flat per-tenant fee** — kills the
  scale-with-customer revenue story; harder to land enterprise plans.
- **Roll our own payments rail** — life is short.

**Implication.** A new built-in gateway `StripeConnect` replaces the
plain `Stripe` gateway for new tenants. The legacy Stripe gateway stays
available for 90 days for existing tenants, then is hard-disabled. See
[`STRIPE_CONNECT.md`](./STRIPE_CONNECT.md).

---

## AD-012 — Billing the tenants: SaaS subscription via Paymenter itself

**Choice.** The central app **is itself** a Paymenter instance using its
own product / order / invoice flow to sell "Paymenter SaaS plans". A
custom Server extension (`extensions/Servers/PaymenterTenant`) provisions
the tenant on order activation, suspends on overdue invoice, terminates
on cancellation.

Tenants pay their SaaS subscription via Stripe directly (no Connect on
this side; we collect normally). On top of the subscription, they owe
the platform fee on every sale (AD-011).

**Implication.** Two revenue streams, both surfaced in the central
panel. See [`BILLING_THE_TENANTS.md`](./BILLING_THE_TENANTS.md).

---

## AD-013 — Branching & upstream compatibility

**Choice.** Stay rebase-compatible with upstream `paymenter/paymenter`.
The conversion lives in *additive* providers / middleware / config /
migrations; we **do not rewrite** core controllers, Livewire components,
Filament resources, or models unless strictly necessary. When we must
change a core file, the diff is minimal and documented here.

**Implication.** A weekly rebase against upstream is part of operations.
[`MIGRATION_GUIDE.md`](./MIGRATION_GUIDE.md) documents the rebase
ritual, including the per-migration `TenantScoped` patch.

---

## Decisions deferred

These are explicitly **not** in v1:

- **Cross-tenant SSO / "Paymenter ID"** — login once, switch tenants.
- **Tenant-uploaded PHP extensions** — sandbox required.
- **Public theme marketplace** with paid themes.
- **Multi-region tenant placement** — single region for now.
- **Read replicas with role-aware routing** — performance work; not
  needed until hundreds of tenants.
- **Tenant data export → standalone Paymenter install** — useful for
  exit ramps; v2 feature.
- **Stripe Connect Custom accounts** for enterprise tenants.

Add them here when the time comes; don't smuggle them into earlier
phases.
