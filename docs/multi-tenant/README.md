# Multi-Tenant Paymenter — Documentation Index

This directory describes the conversion of Paymenter from a single-tenant
open-source billing platform into a **multi-tenant SaaS** ("Paymenter-as-a-
Service"). Read top-down on a first pass; jump by topic afterwards.

## What we are building

A SaaS where:

- Each customer ("**tenant**") is an independent hosting company.
- Tenants get their own URL, database, users, products, gateways, themes,
  extensions, Filament admin panel, and Passport OAuth keys.
- A **central** ("landlord") app provisions and bills the tenants. It is
  itself a Paymenter instance — we eat our own dog food.
- Existing Paymenter features (orders, invoices, services, tickets,
  extensions) keep working untouched, just scoped to a tenant.

## Reading order

| # | Doc | Purpose |
| - | --- | ------- |
| 1 | [`ARCHITECTURE.md`](./ARCHITECTURE.md) | Decisions: package, isolation model, panel split. Read first. |
| 2 | [`IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md) | Phased roadmap. Tells you what to land in what order. |
| 3 | [`TENANT_ISOLATION.md`](./TENANT_ISOLATION.md) | DB / cache / queue / storage / mail / Passport isolation. |
| 4 | [`PROVISIONING.md`](./PROVISIONING.md) | Tenant signup, DB creation, seed, teardown. |
| 5 | [`DOMAIN_ROUTING.md`](./DOMAIN_ROUTING.md) | Subdomain on `*.paymenter.io` + custom domains with TLS. |
| 6 | [`EXTENSIONS_AND_THEMES.md`](./EXTENSIONS_AND_THEMES.md) | Per-tenant extension/theme catalogue and toggles. |
| 7 | [`BILLING_THE_TENANTS.md`](./BILLING_THE_TENANTS.md) | How the central app charges tenants. |
| 8 | [`MIGRATION_GUIDE.md`](./MIGRATION_GUIDE.md) | Moving a running single-tenant instance into the SaaS. |
| 9 | [`PROMPT.md`](./PROMPT.md) | Master prompt to hand to Claude / another LLM to drive the conversion. |

## Two apps in one repo

```
                              ┌──────────────────────────┐
   central.paymenter.io ─────►│  Central (landlord) app  │  central DB ('mysql')
                              │  - Tenants, plans         │
                              │  - Signup & provisioning  │
                              │  - Central billing        │
                              │  - Operator Filament panel│
                              └──────────────┬───────────┘
                                             │ provisions
                                             ▼
   acme.paymenter.io       ┌──────────────────────────────┐
   billing.acme.com   ────►│  Tenant app (Paymenter core) │  per-tenant DB ('tenant')
                           │  - Products, orders, invoices│
                           │  - Tenant Filament admin     │
                           │  - Livewire client UI        │
                           │  - Tenant extensions / theme │
                           └──────────────────────────────┘
```

Both run from the **same codebase**. The boundary is enforced by
`stancl/tenancy` v4 — when the inbound `Host` header matches a tenant
domain, the tenancy middleware swaps the DB connection, cache prefix,
filesystem disk, mail config, and Passport key path before any controller
runs.

## Status

This doc set is the design. Implementation is tracked in
[`IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md); each phase has a
"done when" checklist. Update this file when phases land.

## For AI assistants

- Top-level guidance: [`../../CLAUDE.md`](../../CLAUDE.md).
- Invokable skill: [`../../.claude/skills/multi-tenant-paymenter/SKILL.md`](../../.claude/skills/multi-tenant-paymenter/SKILL.md).
- Long-form prompt: [`PROMPT.md`](./PROMPT.md).
