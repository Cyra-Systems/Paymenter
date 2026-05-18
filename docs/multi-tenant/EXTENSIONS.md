# Curated Extensions — Isolation & Hardening

Paymenter's extension surface (`extensions/Gateways`, `extensions/Servers`,
`extensions/Others`) becomes a **curated marketplace**:

- **Operator-curated.** Only extensions reviewed and approved by the SaaS
  operator are in the catalogue. Tenants cannot upload PHP.
- **Per-tenant enable + configure.** Tenants pick from the catalogue and
  configure with their own credentials. Configuration is scoped by the
  Postgres RLS `tenant_id` so leakage is a database-level impossibility.
- **HTML / CSS hardened on output.** Any string an extension renders into a
  Blade view is sanitised before display.
- **Permission-declared.** Each extension declares the capabilities it
  needs (HTTP egress, mail, queue, storage, settings keys) in a manifest;
  Paymenter denies access to anything not declared.

This doc explains the catalogue, the manifest, the sanitisation pipeline,
and the runtime sandbox.

---

## 1. Catalogue (operator side)

A new central-only table tracks the curated set:

```php
Schema::create('extension_catalogue', function (Blueprint $t) {
    $t->id();
    $t->string('slug')->unique();            // "stripe", "pterodactyl", ...
    $t->string('type');                      // "gateway" | "server" | "other"
    $t->string('display_name');
    $t->string('version');
    $t->string('author');
    $t->text('description');
    $t->jsonb('manifest');                   // see § 2
    $t->jsonb('plans')->nullable();          // ["pro","scale"] — null = all
    $t->string('status')->default('beta');   // "alpha" | "beta" | "stable" | "deprecated"
    $t->boolean('listed')->default(false);   // hide from tenants without delisting
    $t->timestamp('approved_at')->nullable();
    $t->foreignId('approved_by')->nullable()->constrained('central_users');
    $t->timestamps();
});
```

The central Filament panel has a `CentralExtensionCatalogueResource` for
review, approval, delisting, and version pinning.

Source code for extensions still lives under `extensions/{Type}/{Slug}/`
exactly as upstream Paymenter ships them — it is the **manifest + the
catalogue row** that turns shipping code into a tenant-visible offering.

---

## 2. Manifest (`extension.json`)

Every extension ships a manifest at the root of its directory:

```json
{
  "slug": "stripe",
  "type": "gateway",
  "version": "3.4.1",
  "min_paymenter": "1.0.0",
  "author": "Paymenter",
  "license": "MIT",
  "capabilities": {
    "http_egress": ["api.stripe.com", "*.stripe.com"],
    "mail":        false,
    "queue":       ["default"],
    "storage":     ["local"],
    "settings": {
      "writes": ["stripe_publishable_key", "stripe_webhook_secret"],
      "reads":  ["mail_from_address", "currency"]
    },
    "renders_html": false,
    "renders_markdown": true
  },
  "hooks": ["boot", "cron", "checkout", "webhook"],
  "settings_schema": [
    { "key": "stripe_secret_key",        "type": "secret",   "required": true },
    { "key": "stripe_publishable_key",   "type": "string",   "required": true },
    { "key": "stripe_webhook_secret",    "type": "secret",   "required": true }
  ]
}
```

The manifest is the **contract**. At boot, `ExtensionHelper` parses it and
configures sandbox limits before invoking any hook.

### 2.1 Capabilities, enforced

| Capability | Enforcement |
| ---------- | ----------- |
| `http_egress` | Guzzle is wrapped in `ExtensionHttpClient` that rejects any host not on the allow-list. |
| `mail` | A `MailGate` middleware throws if extension code calls `Mail::*` without the capability. |
| `queue` | `Queue::push` is intercepted; only declared queue names are allowed. |
| `storage` | `Storage::disk('local')` is rebound to a per-extension subdir inside the tenant's disk root. |
| `settings.writes` | `setting([key => value])->save()` throws if key not declared. |
| `settings.reads` | Same check on read (soft-deny: returns `null` and logs). |
| `renders_html` | If `false`, the safe-HTML purifier strips ALL HTML; extension can only render text. |
| `renders_markdown` | Markdown rendered via league/commonmark with the strict extension list (§ 4.2). |

Capabilities not declared in the manifest are **denied by default**. There
is no `*` wildcard.

### 2.2 Settings schema → tenant config UI

The `settings_schema` field drives the Filament form a tenant fills in when
enabling the extension. Field types:

- `string` — text input.
- `secret` — text input, stored encrypted via Laravel `Crypt`, never echoed.
- `bool` — toggle.
- `int` — numeric.
- `select` — needs `options: [...]`.
- `markdown` — textarea with markdown preview.

Required fields gate the "enable" button.

---

## 3. Runtime isolation

### 3.1 Database (RLS)

Extensions only ever query through Eloquent models that extend
`App\Models\Model`. Those models have RLS policies in Postgres scoped to
`current_setting('app.tenant_id')::uuid` (see `TENANT_ISOLATION.md`).

If an extension goes off-script and issues `DB::statement(...)` raw SQL:

- The query runs against the tenant app connection (`paymenter_app` role).
- That role does **not** have `BYPASSRLS`.
- RLS strips rows belonging to other tenants before the result is returned.
- INSERTs with a wrong `tenant_id` are rejected by the `WITH CHECK` clause.

There is no path from an enabled extension to another tenant's data.

### 3.2 Filesystem

The filesystem bootstrapper rewrites the local disk to
`storage/app/tenant/{tenant_id}/...`. Extensions get an additional
suffix: `storage/app/tenant/{tenant_id}/extensions/{slug}/`. They cannot
escape upward; Paymenter's `Storage::disk` binding refuses paths that
contain `..`.

### 3.3 Cache & queue

The cache prefix is `t{tenant_id}::`, applied by a `CacheTenancyBootstrapper`.
Queue jobs serialise the tenant_id and re-bootstrap RLS context inside the
worker.

### 3.4 Secrets

Extension secrets (`type: secret` in the manifest) are encrypted at rest
with Laravel `Crypt::encrypt()`. The application key is shared but secrets
are tagged with `tenant_id` so a key rotation re-encrypts per tenant.

For higher-security plans we can layer envelope encryption with a
per-tenant data key fetched from KMS at request time.

### 3.5 HTTP egress

```php
class ExtensionHttpClient
{
    public function __construct(
        protected array $allowedHosts
    ) {}

    public function request(string $method, string $url, array $opts = [])
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $this->matches($host, $this->allowedHosts)) {
            throw new ExtensionEgressDenied($host);
        }
        return Http::send($method, $url, $opts);
    }

    protected function matches(string $host, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $host)) return true;
        }
        return false;
    }
}
```

Loopback (`127.0.0.0/8`, `::1`, `169.254.169.254` for cloud metadata) is
**always** denied even if requested in the manifest — SSRF defence.

### 3.6 Process-level

For v1, we rely on application-level enforcement. For v2 we can run
extension code in a separate PHP worker pool with `disable_functions`
hardening, lower `memory_limit`, and a tighter `open_basedir`. Out of
scope for this doc.

---

## 4. HTML / CSS / Markdown hardening

### 4.1 HTML — `mews/purifier` (HTMLPurifier wrapper)

Every string an extension wants to render as raw HTML goes through:

```php
$safe = app(ExtensionHtmlSanitizer::class)->purify($html, profile: 'extension');
```

The `extension` profile:

- Allowed tags: `p, br, strong, em, ul, ol, li, a, code, pre, h2, h3, h4,
  blockquote, hr, table, thead, tbody, tr, th, td, img`.
- Allowed attributes: `a[href|title|rel], img[src|alt|width|height], code[class],
  pre[class], th[scope|colspan], td[colspan]`.
- `href` schemes: `http, https, mailto`. No `javascript:`, no `data:`,
  no `vbscript:`.
- `img src` schemes: `https, data:image/png, data:image/jpeg, data:image/webp`.
- All `<a>` get `rel="nofollow noopener"` added.
- All inline `style` and `on*` attributes are stripped (always).
- All `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, `<meta>`,
  `<link>` are stripped.

If an extension's manifest sets `renders_html: false`, the purifier
returns plain text (HTML tags are stripped, not escaped — escaped HTML in
a tenant's admin is noise).

### 4.2 Markdown — `league/commonmark`

```php
$converter = new CommonMarkConverter([
    'html_input' => 'strip',           // raw HTML inside markdown → stripped
    'allow_unsafe_links' => false,     // strip javascript: etc.
    'max_nesting_level' => 8,
]);
$html = $converter->convert($markdown)->getContent();
$html = app(ExtensionHtmlSanitizer::class)->purify($html);   // belt + braces
```

We ship a strict default set of CommonMark extensions: tables, autolinks,
strikethrough. We do **not** load `InlinesOnlyExtension` permissively;
we do **not** load any HTML passthrough extension.

### 4.3 CSS — `sabberworm/php-css-parser` + property allow-list

When an extension provides CSS (e.g. an invoice template style):

1. Parse with `Sabberworm\CSS\Parser`. Reject unparseable input.
2. Walk declarations and strip any property not in
   `config('extension.css.allowed_properties')`.
3. Strip any value matching `expression(`, `behavior:`, `-moz-binding:`,
   `url(javascript:`, `url(vbscript:`, `url(data:` (except
   `url(data:image/...)` which is OK).
4. Strip `@import`, `@font-face url(...)` of non-https origins.
5. Re-serialise. The output of the serialiser is what we send to the
   browser.

### 4.4 Content-Security-Policy

Tenant pages set a strict CSP header:

```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'nonce-{nonce}';
  style-src 'self' 'nonce-{nonce}';
  img-src 'self' data: https:;
  font-src 'self' data:;
  connect-src 'self';
  frame-ancestors 'none';
  base-uri 'self';
  form-action 'self';
  object-src 'none';
```

The nonce is per-request, generated in middleware, exposed to Blade via
`csp_nonce()` and injected into any `<script>` or `<style>` tag we render
ourselves. Extension-rendered tags must use the same nonce or be blocked.

For tenant-customised landing pages we tighten further (no inline
scripts at all on public pages).

---

## 5. Webhooks (gateway extensions)

Gateway extensions almost always have inbound webhooks. The pattern:

- Route: `POST /webhook/{extensionSlug}` lives in the **central** routes
  file (not tenant) and the body contains the tenant identifier
  (typically the `account` field on Stripe events, an `accountId` in
  PayPal IPN, etc.).
- Central handler verifies the signature using the extension's allowlist
  of webhook keys (which are stored per tenant, encrypted), then resolves
  the tenant and `tenancy()->initialize($tenant)` before handing the
  payload to `ExtensionHelper::call($ext, 'webhook', $payload)`.

This keeps webhook endpoints on stable URLs that don't change as tenants
rotate custom domains.

---

## 6. Submission & approval workflow

Adding a new extension to the curated marketplace:

1. **Submit.** Author opens a PR against the SaaS repo with the
   extension source and manifest.
2. **CI gate.** Pipeline runs:
   - Manifest schema validation.
   - Static analysis (`larastan` level 7).
   - A `manifest:audit` Artisan command that scans for forbidden calls
     (`eval`, `system`, `exec`, `passthru`, `proc_open`, `popen`,
     direct `\PDO`, `mail()`, `file_get_contents` to untrusted URLs,
     `Cache::store()`, `DB::connection('xxx')`, …).
3. **Security review.** A central operator reviews and clicks
   "Approve" in the catalogue resource. The row's `approved_at` and
   `approved_by` are written.
4. **Stage rollout.** Status starts at `alpha` (only operator-owned
   tenants can enable), then `beta` (opt-in for tenants on Pro+),
   then `stable` (all eligible plans).
5. **Deprecate.** When a replacement ships, status flips to
   `deprecated`; existing enablements continue, but new tenants cannot
   enable.

---

## 7. Audit log

Every extension action that touches tenant data is logged:

```php
extension_audit_log:
  id, tenant_id, extension_slug, action, payload (jsonb), occurred_at
```

`action` is one of: `enabled`, `disabled`, `settings_changed`,
`webhook_received`, `egress_blocked`, `permission_denied`,
`secret_rotated`.

The tenant admin can browse their own log; central can browse all logs
(with RLS bypassed by the `paymenter_admin` role).

---

## 8. Test checklist for a new extension

Block merging until all pass:

- Manifest parses, all referenced settings exist in the schema.
- Static analysis clean.
- A feature test that enables the extension, runs one hook, and asserts
  no rows landed in the wrong tenant.
- A test that calls a denied capability and asserts the extension throws.
- A test that submits malicious HTML / CSS to any user-facing field and
  asserts the sanitiser stripped it.
- A test that submits a webhook signed with the **wrong** tenant's key
  and asserts 401.

---

## 9. Upstream Paymenter compatibility

Upstream's `ExtensionHelper` is the foundation. The changes for the SaaS
are:

- Wrap `ExtensionHelper::call()` so it loads the manifest, applies
  capability gates, and routes HTTP / Mail / Storage / Cache through the
  sandboxed proxies.
- Add a `BeforeExtensionHook` event so the audit log gets a record
  without each extension being modified.
- Leave the on-disk file layout under `extensions/` exactly as upstream
  ships it — no fork.

The manifest is **the only contract**. Extensions without a manifest are
treated as legacy and refused at boot (with a clear error pointing at
this doc).
