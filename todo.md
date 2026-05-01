# User API Layer — Remaining Audit Findings

The items below were identified during a security/UX/feature audit of the
reseller API layer. The high-priority items have already been fixed on this
branch. These are the remaining lower-severity findings to address.

---

## Security

### S-02 — IP allowlist silently bypassable if TrustProxies is misconfigured
**Severity:** High (configuration risk)

The per-key IP allowlist relies on `$request->ip()`, which resolves via
Laravel's `TrustProxies` config. Two failure modes:
1. If `TrustProxies` is not configured behind a load balancer, `$request->ip()`
   returns the proxy IP — allowlist is always-fail.
2. If `TrustProxies` is set to `*`, an attacker can spoof `X-Forwarded-For`
   and bypass the allowlist entirely.

**Fix:** Set `TrustProxies` to explicit CIDR ranges for your load balancers,
never `*`. Add a warning in the API Keys UI explaining this dependency.

**File:** `app/Http/Middleware/Api/UserApi.php`, `app/Http/Middleware/TrustProxies.php`

---

### S-11 — Webhook secrets stored in plaintext in the database
**Severity:** Medium

Webhook secrets are stored as plaintext in `webhooks.secret`. A database
breach exposes all secrets, allowing forgery of valid HMAC signatures.

**Fix:** Apply Laravel's `encrypted` cast to the `secret` attribute in
`app/Models/Webhook.php`:
```php
protected $casts = [
    'secret' => 'encrypted',
    // ...
];
```
This encrypts the value at rest using the app key while keeping it fully
readable by the application for HMAC signing.

**File:** `app/Models/Webhook.php`

---

### S-12 — Unregistered permission keys in userAllowedIncludes() are silently open
**Severity:** Low

If an include's permission key is missing from `config('permissions.api.user')`,
the include is allowed for all keys — open by default on misconfiguration.

**Fix:** Invert the fallthrough in `userAllowedIncludes()`: if a permission
mapping is not registered, deny rather than allow.

**File:** `app/Http/Controllers/Api/User/UserApiController.php`

---

### S-13 — Service cancellation type hardcoded to "immediate"; reason ignored
**Severity:** Low

`ServiceController::destroy()` always creates a cancellation with
`'type' => 'immediate'` and a hardcoded reason string. Resellers cannot
request end-of-billing-period cancellations.

**Fix:** Accept and validate `type` (`immediate` | `end_of_period`) and
`reason` from the request body. Default to `end_of_period`.

**File:** `app/Http/Controllers/Api/User/ServiceController.php`

---

### S-14 — Admin API routes have no rate limiting
**Severity:** Low

The user API correctly applies `throttle:user-api`, but the admin API route
group has no `throttle` middleware at all.

**Fix:** Add `throttle:60,1` (or a named `admin-api` limiter) to the admin
route group in `routes/api.php`.

**File:** `routes/api.php`

---

## Missing Features

### F-03 — No idempotency key support on POST /v1/user/orders
**Severity:** Medium

If a `POST /v1/user/orders` request times out, the caller cannot tell whether
the order was created. Retrying risks duplicate orders and services.

**Fix:** Accept an `Idempotency-Key` header. Cache the key + response in Redis
for 24 hours (scoped per user). Return the cached response on replay.

**File:** `app/Http/Controllers/Api/User/OrderController.php`

---

### F-04 — No service renewal or upgrade endpoint
**Severity:** Medium

Resellers have no way to trigger renewals or plan upgrades via the API.

**Proposed endpoints:**
- `POST /v1/user/services/{service}/renew` — extend `expires_at`, generate invoice
- `POST /v1/user/services/{service}/upgrade` — switch to a different plan, call `ExtensionHelper::upgradeServer()`

**New permissions:** `services.renew`, `services.upgrade`

**File:** `app/Http/Controllers/Api/User/ServiceController.php`, `routes/api.php`, `config/permissions.php`

---

### F-05 — No webhook delivery log or replay capability
**Severity:** Medium

`last_called_at` and `last_response_status` only reflect the most recent
attempt. There is no way to inspect past payloads, see response bodies, or
replay a failed delivery.

**Fix:**
1. Create a `webhook_deliveries` table: `webhook_id`, `event`, `payload` (JSON),
   `response_status`, `response_body`, `attempt`, `delivered_at`.
2. Populate it from `WebhookDispatchJob` on every attempt.
3. Add `GET /v1/user/webhooks/{webhook}/deliveries` read endpoint.
4. Add a "View deliveries" / "Redeliver" button in the webhooks UI.

**Files:** new migration, `app/Jobs/WebhookDispatchJob.php`, new controller + route

---

### F-06 — No API key expiry / TTL support
**Severity:** Medium

API keys never expire. Time-limited keys are essential for delegated access
(e.g. short-lived provisioning tokens for sub-customers).

**Fix:**
1. Migration: add `expires_at` (nullable timestamp) to `api_keys`.
2. Middleware: reject keys where `expires_at` is in the past (`UNAUTHENTICATED`).
3. UI: optional expiry date field in the creation form; warning badge for keys
   expiring within 7 days.

**Files:** new migration, `app/Http/Middleware/Api/UserApi.php`,
`app/Livewire/Client/ApiKeys.php`, `themes/default/views/client/account/api-keys.blade.php`

---

### F-07 — No support ticket endpoints in user API
**Severity:** Medium

Resellers cannot open or track support tickets programmatically.

**Proposed endpoints:**
- `GET  /v1/user/tickets` — list own tickets
- `GET  /v1/user/tickets/{ticket}` — show ticket + messages
- `POST /v1/user/tickets` — create ticket
- `POST /v1/user/tickets/{ticket}/messages` — reply

**New permissions:** `tickets.view`, `tickets.create`

**Files:** new `TicketController`, `routes/api.php`, `config/permissions.php`

---

### F-09 — No webhook signature version prefix
**Severity:** Low

The `X-Webhook-Signature` header uses `sha256=<hmac>`. There is no version
prefix, so changing the signing algorithm in future would be a breaking change
for all receivers.

**Fix:** Prefix signatures with a version: `v1,sha256=<hmac>`. Make this
change before the API is in production to avoid a flag day.

**File:** `app/Jobs/WebhookDispatchJob.php`

---

## Status

| ID | Severity | Status |
|----|----------|--------|
| S-02 | High (config) | Open |
| S-11 | Medium | Open |
| S-12 | Low | Open |
| S-13 | Low | Open |
| S-14 | Low | Open |
| F-03 | Medium | Open |
| F-04 | Medium | Open |
| F-05 | Medium | Open |
| F-06 | Medium | Open |
| F-07 | Medium | Open |
| F-09 | Low | Open |
