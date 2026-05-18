# Billing

A billing platform for hosting companies, packaged as a multi-tenant SaaS.

This repository is a fork of the upstream [Paymenter](https://github.com/Paymenter/paymenter)
open-source billing platform, evolved into a multi-tenant Software-as-a-Service:
one codebase, one Postgres database (isolated by Row-Level Security), many
hosting-company customers, automatic platform fees through Stripe Connect.

The original Paymenter copyright is preserved in `LICENSE` as required by the
MIT license. The multi-tenant conversion, central operator app, theme
sandbox, Stripe Connect integration, and hardened extension catalogue are
the contributions of this fork.

## Stack

- PHP 8.3+, Laravel 12
- Filament v5 — admin panels (tenant + central operator)
- Livewire v4 — client UI
- Postgres 16+ with Row-Level Security
- Stripe Connect — tenant payments + platform fee

## Documentation

The multi-tenant conversion is documented end-to-end in
[`docs/multi-tenant/`](docs/multi-tenant/README.md):

| Topic | File |
| ----- | ---- |
| Architecture decisions | [`docs/multi-tenant/ARCHITECTURE.md`](docs/multi-tenant/ARCHITECTURE.md) |
| Phased implementation plan | [`docs/multi-tenant/IMPLEMENTATION_PLAN.md`](docs/multi-tenant/IMPLEMENTATION_PLAN.md) |
| Postgres RLS + isolation | [`docs/multi-tenant/TENANT_ISOLATION.md`](docs/multi-tenant/TENANT_ISOLATION.md) |
| Tenant signup & provisioning | [`docs/multi-tenant/PROVISIONING.md`](docs/multi-tenant/PROVISIONING.md) |
| Subdomain + custom domain routing | [`docs/multi-tenant/DOMAIN_ROUTING.md`](docs/multi-tenant/DOMAIN_ROUTING.md) |
| Curated extensions + HTML/CSS hardening | [`docs/multi-tenant/EXTENSIONS.md`](docs/multi-tenant/EXTENSIONS.md) |
| Theme system (curated + BYO sandbox) | [`docs/multi-tenant/THEMES.md`](docs/multi-tenant/THEMES.md) |
| Stripe Connect platform fee | [`docs/multi-tenant/STRIPE_CONNECT.md`](docs/multi-tenant/STRIPE_CONNECT.md) |
| Billing the tenants | [`docs/multi-tenant/BILLING_THE_TENANTS.md`](docs/multi-tenant/BILLING_THE_TENANTS.md) |
| Migrating a live single-tenant instance | [`docs/multi-tenant/MIGRATION_GUIDE.md`](docs/multi-tenant/MIGRATION_GUIDE.md) |

## Requirements

- PHP 8.3 or 8.4
- Composer
- A reverse proxy (Caddy recommended, for on-demand TLS on custom domains)
- Postgres 16+
- Redis (cache + queue)

## License

MIT — see [`LICENSE`](LICENSE).
