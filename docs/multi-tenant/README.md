# Multi-Tenant Paymenter — Documentation Index

This directory describes the conversion of Paymenter from a single-
tenant open-source billing platform into a **multi-tenant SaaS**
("Paymenter-as-a-Service"). Read top-down on a first pass; jump by
topic afterwards.

## What we are building

A SaaS where:

- Each customer ("**tenant**") is an independent hosting company.
- Tenants get their own URL, their own users / products / gateways /
  themes / extensions, and their own Filament admin panel.
- Tenant data is isolated by **Postgres Row-Level Security** on a
  single shared database — one DB to back up and operate, hard
  isolation enforced by the engine itself.
- Tenants connect their own Stripe account via **Stripe Connect**;
  every sale they make ships a **platform fee** to us automatically.
- Themes are **curated** by default, with a **bring-your-own** (BYO)
  upload pipeline that runs through a Blade sandbox + CSS sanitiser
  on Pro+ plans.
- Extensions are **curated** by the operator; every extension ships a
  manifest declaring allowed capabilities; HTML/CSS/Markdown output
  goes through hardened sanitisers.
- A **central** ("landlord") app provisions and bills the tenants. It
  is itself a Paymenter instance — we eat our own dog food.

## Reading order

| # | Doc | Purpose |
| - | --- | ------- |
| 1 | [`ARCHITECTURE.md`](./ARCHITECTURE.md) | Decisions: Postgres + RLS, Connect, panel split, sandboxes. Read first. |
| 2 | [`IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md) | Phased roadmap. Tells you what to land in what order. |
| 3 | [`TENANT_ISOLATION.md`](./TENANT_ISOLATION.md) | RLS roles + policies, cache / queue / storage / mail / Passport isolation. |
| 4 | [`PROVISIONING.md`](./PROVISIONING.md) | Signup, RLS-context seeding, suspend, terminate, purge. |
| 5 | [`DOMAIN_ROUTING.md`](./DOMAIN_ROUTING.md) | Subdomain on `*.paymenter.io` + custom domains with on-demand TLS. |
| 6 | [`EXTENSIONS.md`](./EXTENSIONS.md) | Curated catalogue, manifest, capability gates, HTML/CSS/Markdown hardening, CSP. |
| 7 | [`THEMES.md`](./THEMES.md) | Curated themes + BYO uploader, Blade sandbox, CSS sanitiser, JS allow-list. |
| 8 | [`STRIPE_CONNECT.md`](./STRIPE_CONNECT.md) | Stripe Connect platform identity, destination charges, application-fee config, ledger. |
| 9 | [`BILLING_THE_TENANTS.md`](./BILLING_THE_TENANTS.md) | How the central app charges tenants for the SaaS subscription. |
| 10 | [`MIGRATION_GUIDE.md`](./MIGRATION_GUIDE.md) | Moving a running single-tenant instance into the SaaS; upstream rebase. |
| 11 | [`PROMPT.md`](./PROMPT.md) | Master prompt to hand to Claude / another LLM to drive the conversion. |

## Two apps in one repo, one database

```
                              ┌──────────────────────────┐
   central.paymenter.io ─────►│  Central (landlord) app  │   pg_admin role (BYPASSRLS)
                              │  - Tenants, plans         │   ─ central tables only
                              │  - Signup & provisioning  │
                              │  - Stripe platform ledger │
                              │  - Extension/theme catalogues
                              │  - Operator Filament panel│
                              └──────────────┬───────────┘
                                             │ provisions
                                             ▼
   acme.paymenter.io       ┌──────────────────────────────┐
   billing.acme.com   ────►│  Tenant app (Paymenter core) │   pg role (RLS-enforced)
                           │  - Products, orders, invoices│   ─ tenant-scoped rows only
                           │  - Tenant Filament admin     │
                           │  - Livewire client UI        │
                           │  - Tenant extensions / theme │
                           │  - Stripe Connect-enabled    │
                           └──────────────────────────────┘
                                             │
                                             ▼
                                       ┌─────────────┐
                                       │  Postgres   │
                                       │  ONE DB     │
                                       │  RLS policies
                                       │  enforce    │
                                       │  isolation  │
                                       └─────────────┘
```

Both apps run from the **same codebase**. Isolation is enforced at
three layers:

1. `stancl/tenancy` resolves the tenant from the request `Host`.
2. The `PostgresRlsBootstrapper` does `SET LOCAL app.tenant_id = '<uuid>'`
   on the `pg` connection (NOBYPASSRLS).
3. Postgres rejects any read or write against a row whose `tenant_id`
   does not match the session variable.

Central code (provisioning, billing aggregations, Stripe ledger
reconciliation) explicitly uses the `pg_admin` connection
(BYPASSRLS) — a greppable opt-out from RLS.

## Status

This doc set is the design. Implementation is tracked in
[`IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md); each phase has a
"done when" checklist. Update this file when phases land.

## For AI assistants

- Top-level guidance: [`../../CLAUDE.md`](../../CLAUDE.md).
- Invokable skill: [`../../.claude/skills/multi-tenant-paymenter/SKILL.md`](../../.claude/skills/multi-tenant-paymenter/SKILL.md).
- Long-form prompt: [`PROMPT.md`](./PROMPT.md).
