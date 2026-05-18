# Architecture Decisions

This is the decision record for the multi-tenant conversion. Each section
captures: the choice, the alternatives we rejected, and **why**. Treat it as
binding — deviations require updating this file.

---

## AD-001 — Tenancy package: `stancl/tenancy` v4

**Choice.** Use [`stancl/tenancy`](https://tenancyforlaravel.com) v4 as the
tenancy framework. It is the most mature Laravel-native multi-tenancy package
and supports database-per-tenant out of the box with first-class
bootstrappers for cache, filesystem, queue, and mail.

**Alternatives rejected.**

- *Roll our own* — Paymenter touches DB, cache, queue, mail, storage,
  Passport, and Filament; recreating `stancl/tenancy`'s bootstrappers would
  add months of work and a long tail of subtle bugs.
- `spatie/laravel-multitenancy` — lighter but expects more glue code,
  weaker queue bootstrapping, and less idiomatic with Filament v5.
- `archtechx/tenancy` — same author as `stancl/tenancy`, predecessor.
- Adding `tenant_id` columns ("row-level tenancy") — would touch all 71
  migrations, all 53 models, every query, every Filament resource, and
  still leak data via raw SQL or extension code. Not worth it.

**Implication.** All tenant tables live in a separate database; the central
DB only holds central concerns (tenants, plans, signups, custom domains,
central invoices).

---

## AD-002 — Isolation model: database-per-tenant

**Choice.** One MySQL/MariaDB **database** per tenant, named `tenant_{uuid}`
(uuid because hostnames change). The Laravel connection is named `tenant`
and is swapped at request boundary by `stancl/tenancy`.

**Alternatives rejected.**

- Schema-per-tenant on a single DB — MySQL has no schemas (just databases),
  so this is academic on Paymenter's stack.
- Row-level tenancy with `tenant_id` — see AD-001.
- Single DB with a shared connection and global query scopes — fragile, and
  every extension that runs raw SQL becomes a leak vector.

**Implication.** Onboarding requires creating a DB, running tenant
migrations, and seeding defaults. Backups are per-tenant — easy restore,
easy GDPR delete. The MySQL user used by the app must have privileges to
`CREATE DATABASE`, or provisioning happens via a privileged helper.

---

## AD-003 — Tenant identification: domain, never path

**Choice.** Tenants are identified by `Host` header:

- Subdomain: `acme.paymenter.io`.
- Custom domain: `billing.acme.com` (after the customer points DNS at us).

`stancl/tenancy`'s `InitializeTenancyByDomain` middleware does the lookup.

**Alternatives rejected.**

- Path prefix (`paymenter.io/acme/...`) — breaks Paymenter's existing
  route structure (`/admin`, `/dashboard`, `/invoices`, etc.) and means
  cookies and OAuth redirects must be path-aware. Massive refactor.
- Header (`X-Tenant: acme`) — fine for APIs, awful for browsers.

**Implication.** We need wildcard DNS for `*.paymenter.io`, a wildcard or
ACME-managed TLS cert, and a custom-domain workflow that issues a per-
domain cert (typically via Let's Encrypt). See `DOMAIN_ROUTING.md`.

---

## AD-004 — Migrations: split central vs tenant

**Choice.** Split `database/migrations/` into:

- `database/migrations/` — **central only** (`tenants`, `domains`,
  `central_plans`, `central_users`, `signups`, custom-domain certs).
- `database/migrations/tenant/` — **everything Paymenter has today**:
  users, products, orders, services, invoices, gateways, tickets,
  extensions, settings, etc. All 71 existing migrations move here.

`stancl/tenancy` is told both paths in `config/tenancy.php`.

**Alternatives rejected.**

- Keep one folder, mark migrations with attributes — works in
  `stancl/tenancy` v4, but reviewers can't see at a glance which tables
  belong to which DB. Split folders is the clearer convention.

**Implication.** When upstream Paymenter adds a new migration on `master`,
the rebase moves it from `database/migrations/` into
`database/migrations/tenant/`. Document this in `MIGRATION_GUIDE.md`.

---

## AD-005 — Two Filament panels

**Choice.** Two Filament v5 `PanelProvider` classes:

- `AdminPanelProvider` (existing, in
  `app/Providers/Filament/AdminPanelProvider.php`) — **tenant admin panel**.
  Mounted at `/admin` on each tenant domain.
- `CentralPanelProvider` (new) — **operator panel** for SaaS staff to
  manage tenants, plans, custom domains, system-wide settings. Mounted on
  `central.paymenter.io/admin` using `Panel::domain('central.paymenter.io')`.

The tenant panel stays exactly as upstream Paymenter ships it.

**Alternatives rejected.**

- One panel, role-gated — collapses the security boundary and lets a
  misconfigured permission expose tenant data to operators.
- Two separate Laravel apps — duplication is worse than one repo with two
  panels.

**Implication.** The central panel has its own resources, its own user
model (`CentralUser`), and its own auth guard. Tenant users cannot log
into the central panel and vice versa.

---

## AD-006 — Authentication scope

**Choice.** Authentication is per-tenant. The `users` table lives in the
tenant DB; sessions and password resets are scoped by domain (cookies are
domain-scoped, so this is automatic once tenancy initialises).

The central app uses a separate `central_users` table and a separate auth
guard called `central` with its own session cookie name.

**Implication.** A single human who runs three hosting companies on us has
three separate logins, one per tenant — same as Shopify, Vercel, etc.
Acceptable trade-off; cross-tenant SSO is a future feature, not phase 1.

---

## AD-007 — Cache, queue, storage, mail, Passport isolation

**Choice.** Use `stancl/tenancy`'s bootstrappers:

- **Cache** — `CacheTenancyBootstrapper` adds tenant-id prefix to cache keys.
  Required because `App\Providers\SettingsProvider` caches under
  `cache key "settings"` (see `app/Providers/SettingsProvider.php:31`).
- **Queue** — `QueueTenancyBootstrapper` tags jobs with the tenant and
  re-initialises tenancy inside the worker.
- **Filesystem** — `FilesystemTenancyBootstrapper` rewrites the disk roots
  to `storage/app/tenant{id}/...`.
- **Mail** — custom bootstrapper that loads the tenant's mail-from-name,
  mail-from-address, and SMTP credentials (already stored as
  `settings` rows on the tenant DB) into `config('mail.*')`.
- **Passport** — custom bootstrapper that points
  `config('passport.private_key')` / `public_key` at files inside the
  tenant's filesystem.

**Implication.** Settings caching keys, queue connections, and storage
paths become tenant-aware automatically. Code that bypasses these (raw
`Cache::store('redis')->get('foo')`, `Storage::disk('s3')->put(...)`)
must be audited — see `TENANT_ISOLATION.md` for the audit list.

---

## AD-008 — Extensions: shared code, per-tenant configuration

**Choice.** Extension PHP code stays on disk under `extensions/`, shared
across all tenants. Each tenant has its own row in their own `extensions`
table marking which are enabled and storing their config (via the
polymorphic `settings` rows). The boot loop in
`AppServiceProvider::boot()` at `app/Providers/AppServiceProvider.php:140`
is guarded to only call `ExtensionHelper::call(..., 'boot')` when tenancy
is initialised.

**Alternatives rejected.**

- Per-tenant extension code on disk — a customisation playground, but
  becomes a code-execution-as-a-service nightmare. Out of scope for v1.

**Implication.** The central operator decides which extensions appear in
the marketplace catalogue. Tenants pick from that catalogue; they can't
upload arbitrary PHP. See `EXTENSIONS_AND_THEMES.md`.

---

## AD-009 — Billing the tenants

**Choice.** The central app **is itself** a Paymenter instance. It uses
Paymenter's existing Product / Order / Service / Invoice / Gateway code to
sell "Paymenter SaaS plans" to the tenants. A custom **Server extension**
(`extensions/Servers/PaymenterTenant`) provisions a tenant on order
activation, suspends on overdue invoice, and terminates on cancellation.

**Alternatives rejected.**

- Stripe Billing directly — fine, but means central operators run two
  billing systems. Using Paymenter for itself is dog-fooding and proves
  the product.
- A bespoke billing module — pure NIH.

**Implication.** The Paymenter Server-extension interface
(`extensions/Servers/*`) is the contract for provisioning. See
`BILLING_THE_TENANTS.md`.

---

## AD-010 — Branching & upstream compatibility

**Choice.** Stay rebase-compatible with upstream `paymenter/paymenter`.
The conversion lives in *additive* providers / middleware / config; we
**do not rewrite** core controllers, Livewire components, Filament
resources, or models unless strictly necessary. When we must change a
core file, the diff is minimal and documented in this file.

**Implication.** A weekly rebase against upstream is part of operations.
`MIGRATION_GUIDE.md` documents the rebase ritual.

---

## Decisions deferred

These are explicitly **not** in v1:

- **Cross-tenant SSO / "Paymenter ID"** — login once, switch tenants.
- **Tenant-specific extension code uploads** (sandbox required).
- **Multi-region tenant placement** — single region for now.
- **Read replicas per tenant** — performance work; not needed until we
  have hundreds of tenants.
- **Tenant data export to a Paymenter standalone install** — useful for
  exit ramps, but a v2 feature.

Add them here when the time comes; don't smuggle them into earlier phases.
