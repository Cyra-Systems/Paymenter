# Cyra Creators — Paymenter Server Extension

Provisions a tenant on a Cyra Creators platform when a Paymenter customer orders a Creator-* plan.

## Setup

1. In Paymenter Admin → Extensions → Servers, install **Creators**.
2. Set:
   - **Creators API URL**: `https://creators.cyra.app` (or wherever you host it)
   - **Creators API Key (Bearer)**: the value of `CREATORS_PROV_TOKEN` from the Creators `.env`
   - **HMAC Signing Secret**: the value of `CREATORS_PROV_SECRET` from the Creators `.env`
   - **Default Processing Fee**: `500` (= 5.00%)
3. Click "Test Configuration" — should report green if Creators is reachable.

## Per-product configuration

When you attach this server to a product (e.g. "Creator Pro"), set:

- `plan_code` — internal slug (`creator-pro`, `creator-basic`, etc.)
- Caps — storage, bandwidth, max moderators, max groups, content items, etc.
- Features — toggle live streaming, PPV, subscriptions, storefront, etc.
- Rate limits — uploads/min, API/min
- Processing fee in basis points (overrides the global default for this product)
- Domain mode — `subdomain` (uses `<value>.creators.cyra.app`) or `custom` (uses `<value>` as the full hostname)

## Lifecycle

| Paymenter event | HTTP call to Creators |
|---|---|
| `createServer` | `POST /api/v1/provisioning/tenants` |
| `upgradeServer` | `PATCH /api/v1/provisioning/tenants/{external_id}` |
| `suspendServer` | `POST /api/v1/provisioning/tenants/{external_id}/suspend` |
| `unsuspendServer` | `POST /api/v1/provisioning/tenants/{external_id}/unsuspend` |
| `terminateServer` | `DELETE /api/v1/provisioning/tenants/{external_id}` |
| `getActions` (action button) | `GET /api/v1/provisioning/tenants/{external_id}` |

`external_id` is the Paymenter `Service.id`. Idempotent — POSTing the same `external_id` twice returns the existing tenant.

## Authentication

Every request carries:

```
Authorization: Bearer <api_key>
X-Creators-Sig: <hex_hmac_sha256(timestamp + raw_body, hmac_secret)>
X-Creators-Timestamp: <unix>
```

The Creators side rejects with 401 if any header is missing, the timestamp is older than 5 minutes, or the signature does not verify.
