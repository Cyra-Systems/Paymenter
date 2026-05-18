# Domain Routing & TLS

Two flavours of URL serve a tenant:

1. **Subdomain on the SaaS apex** — `acme.paymenter.io`. Always available,
   no DNS work for the customer beyond signup.
2. **Custom domain** — `billing.acme.com`. The customer points DNS to us
   and we serve TLS for it.

The `central.paymenter.io` host is reserved for the operator panel.

---

## 1. DNS

| Host pattern | Record | Pointed at |
| ------------ | ------ | ---------- |
| `paymenter.io` | A / AAAA | Load balancer |
| `central.paymenter.io` | A / AAAA | Load balancer |
| `*.paymenter.io` | A / AAAA | Load balancer (wildcard) |
| `{custom}.{customer-tld}` | CNAME | `proxy.paymenter.io` |

The wildcard A record is required so we don't have to add a record per
tenant. For custom domains the customer creates a CNAME at
`billing.acme.com → proxy.paymenter.io` (or an A record if their DNS
host doesn't support CNAME flattening at apex).

We refuse the following subdomains at signup (configurable list in
`config/tenancy.php`):

```
www, central, admin, api, auth, mail, status, docs, blog,
help, support, public, static, cdn, app, paymenter
```

---

## 2. Identifying the tenant

`stancl/tenancy` v4 ships
`Stancl\Tenancy\Middleware\InitializeTenancyByDomain` which:

1. Reads `$request->getHost()`.
2. Looks it up in `domains.domain`.
3. Resolves `domains.tenant_id` → `Tenant` model.
4. Calls `tenancy()->initialize($tenant)`, which fires the bootstrappers.

If no row matches and the host is not in `central_domains`, we fall
through to a 404 (or a "this domain isn't pointed at us yet" landing
page — see § 5).

The middleware is registered in `bootstrap/app.php` and applied to the
`tenant` group used by all of `routes/web.php`.

The `PreventAccessFromCentralDomains` middleware is also in the group; it
guarantees tenant routes return 404 if accidentally reached via
`central.paymenter.io`.

---

## 3. Reverse proxy

Recommended setup: **Caddy** with on-demand TLS.

```caddy
{
    # global options
    on_demand_tls {
        ask https://central.paymenter.io/internal/tls/ask
        interval 2m
        burst 5
    }
}

central.paymenter.io {
    reverse_proxy app:8000
}

# Catch-all for tenants (subdomains + custom domains)
:443 {
    tls {
        on_demand
    }
    reverse_proxy app:8000
}
```

The `ask` endpoint returns 200 if the requested host is in the `domains`
table (and `ssl_status != 'blocked'`), 404 otherwise. This prevents
unbounded cert issuance from random hosts pointed at us.

```php
// routes/web.php (central group)
Route::get('/internal/tls/ask', function (Request $r) {
    abort_unless($r->query('domain'), 400);
    abort_unless(
        Domain::where('domain', $r->query('domain'))
            ->where('ssl_status', '!=', 'blocked')
            ->exists(),
        404
    );
    return response()->noContent();
})->middleware(\App\Http\Middleware\InternalOnly::class);
```

Alternatives: nginx + certbot per domain (operator-heavy), or
Cloudflare-for-SaaS (commercial, slick).

---

## 4. Custom domain workflow

1. **Tenant adds domain** in their `/admin` panel. We insert a `domains`
   row with `ssl_status = 'pending'` and `verification_token = random`.
2. **Display verification record**: a TXT record at
   `_paymenter.{domain}` whose value is `paymenter-verify={token}`.
3. **Tenant adds the TXT and a CNAME** `{domain} → proxy.paymenter.io`.
4. **Background job** polls DNS every 5 minutes for up to 24 hours:
   - On success → `ssl_status = 'active'`. The first HTTPS request
     triggers on-demand TLS, which calls our `ask` endpoint, sees the
     row, says yes, and Caddy issues a Let's Encrypt cert.
   - On timeout → `ssl_status = 'failed'`; tenant is notified.
5. **Operator override** in central panel: force-mark a domain `active`
   without DNS check (for staging).

```php
// app/Jobs/VerifyCustomDomainJob.php (sketch)
public function handle(): void
{
    $records = dns_get_record('_paymenter.' . $this->domain->domain, DNS_TXT);
    $expected = 'paymenter-verify=' . $this->domain->verification_token;

    if (collect($records)->pluck('txt')->contains($expected)) {
        $this->domain->update(['ssl_status' => 'active', 'verified_at' => now()]);
        return;
    }

    if ($this->attempts() >= 288) { // 24h at 5min intervals
        $this->domain->update(['ssl_status' => 'failed']);
        return;
    }

    $this->release(300);
}
```

---

## 5. Unknown-host landing page

When someone points DNS at us before adding the domain in the panel (or
points a stranger's domain at us), the request reaches Caddy, the `ask`
returns 404, Caddy serves no cert, and the user sees a TLS error.

For HTTP requests we render a friendly page:

```php
// routes/web.php (top-level, outside tenant group)
Route::fallback(function (Request $request) {
    if (! in_array($request->getHost(), config('tenancy.central_domains'))) {
        return response()->view('central.unknown_host', [
            'host' => $request->getHost(),
        ], 404);
    }
    abort(404);
});
```

---

## 6. Cookies, OAuth redirects, signed URLs

These are all affected by the host:

- **Session cookies** are scoped to the request host automatically.
  `config('session.domain')` should be `null` (the default).
- **OAuth callback URLs** (Discord, GitHub via socialite, Passport
  redirects) are built from `url()` — they pick up the request host, so
  no change.
- **Stripe Connect OAuth callback** is the special case: it hits the
  central host, not the tenant. The `state` parameter must carry the
  tenant id (encrypted, short TTL) so the callback can re-bootstrap
  context. See [`STRIPE_CONNECT.md`](./STRIPE_CONNECT.md) § 2.1.
- **Signed URLs** (`AppServiceProvider::register`'s
  `alternateHasCorrectSignature` macro at
  `app/Providers/AppServiceProvider.php:46`) sign against the request
  URL — host-aware. Tested.

The one trap: jobs that build URLs do **not** have a request. The job's
tenancy bootstrapper has to call `URL::forceRootUrl(...)` with the
tenant's primary domain. Add this to the queue bootstrapper:

```php
URL::forceRootUrl('https://' . $tenant->primaryDomain());
URL::forceScheme('https');
```

---

## 7. `central_domains` configuration

In `config/tenancy.php`:

```php
'central_domains' => array_filter([
    env('CENTRAL_DOMAIN', 'central.paymenter.io'),
    env('CENTRAL_MARKETING_DOMAIN', 'paymenter.io'),
]),
```

The two are deliberate: the **marketing site** (apex `paymenter.io`) is a
separate static site or a small Laravel app — it can live in the same
codebase if you want, on its own routes file.

---

## 8. Edge cases & gotchas

- **`localhost` and `.test` in dev**: add `paymenter.test` and
  `central.paymenter.test` to `/etc/hosts`. Tenants in dev use
  `{tenant}.paymenter.test`. Either install
  [dnsmasq](https://thekelleys.org.uk/dnsmasq/doc.html) to resolve
  `*.paymenter.test` to 127.0.0.1, or add lines to `/etc/hosts` per
  tenant.
- **Punycode** for IDN custom domains: store the punycode form in
  `domains.domain`; we never compare display-form to header.
- **Case sensitivity**: hostnames are case-insensitive; lowercase on
  insert and on lookup.
- **Trailing dot**: `$request->getHost()` strips it. Lookup is safe.
- **HSTS** — only on `central.paymenter.io` for v1. Adding it on tenant
  domains commits us to TLS for the customer's domain forever; let the
  tenant turn it on.
- **CSP headers** are emitted per-response (see `EXTENSIONS.md` § 4.4
  for the policy). The same middleware applies on both tenant and
  central domains; nonces are per-request.
