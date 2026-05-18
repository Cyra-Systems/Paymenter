# Themes — Curated + Bring-Your-Own

Two tiers of theme:

1. **Curated themes** ship in `themes/` on disk. Vetted by the operator,
   identical for every tenant that picks them. Fast, safe, boring.
2. **Bring-Your-Own (BYO) themes** uploaded by a tenant (or their
   designer) as a zip. Lives in the tenant's filesystem prefix. Runs
   inside a Blade sandbox. Available on Pro+ plans.

Both are rendered by `qirolab/laravel-themer` with custom resolvers that
respect the tenant context and refuse anything not on the safe path.

---

## 1. Selection

The active theme for a tenant is a `settings` row:

```
key:   theme.active
value: "default:curated"        # or "myshop:byo"
```

The tenant bootstrapper sets `Qirolab\Theme\Theme::set($name)` from this
setting. The renderer prefixes lookups with `byo:` for tenant-uploaded
themes and `curated:` for shipped themes, so a tenant cannot accidentally
pick a curated theme that happens to share a slug with their BYO theme.

A tenant admin UI lets them browse, preview (in an iframe with the
sandbox CSP), and switch themes.

---

## 2. Curated themes

- Live on disk under `themes/{slug}/`.
- Catalogue tracked in `theme_catalogue` (central-only table).
- Listed in a tenant's theme picker if the catalogue row is `listed`
  and the tenant's plan is in `plans`.
- Updates ship via deploy. The operator owns version bumps.

```
themes/
  default/
    theme.json
    layouts/app.blade.php
    components/...
    public/css/theme.css
    public/js/theme.js
    public/img/logo.svg
```

Curated theme rendering bypasses the sandbox — they are operator-vetted
code and run with the same trust as core Blade templates.

---

## 3. BYO themes

### 3.1 Upload pipeline

1. Tenant goes to `/admin/themes/upload`.
2. Drops a zip ≤ 5 MB.
3. We extract to a temp directory.
4. **Manifest** required at `theme.json`:
   ```json
   {
     "slug":        "myshop",
     "name":        "My Shop",
     "version":     "1.0.0",
     "author":      "Acme Design",
     "license":     "MIT",
     "min_paymenter": "1.0.0",
     "supports":    ["client", "checkout", "invoice"],
     "css_vars":    ["--accent", "--bg", "--fg"],
     "slots":       ["header", "footer", "sidebar"],
     "js":          false
   }
   ```
   Validated against a JSON schema; missing or extra top-level keys fail.
5. **File allow-list** sweep over every entry in the zip:
   - **Allowed**: `.blade.php, .css, .js, .json, .svg, .png, .jpg,
     .jpeg, .webp, .gif, .woff, .woff2, .ico`.
   - **Denied** (immediate reject): any of `.php` (non-`.blade.php`),
     `.phar`, `.htaccess`, `.env`, `.git*`, `.sh`, `.exe`, `.bat`, `.dll`,
     symlinks, files containing `..` or starting with `/`, files larger
     than 1 MB individually, more than 200 files total.
6. **Per-file sanitiser**:
   - `.blade.php` → Blade sandbox (§ 4).
   - `.css` → CSS sanitiser (§ 5).
   - `.js` → JS allow-list (§ 6), only if `js: true` in manifest **and**
     tenant on Pro+ plan.
   - `.svg` → SVG sanitiser (strip `<script>`, `on*` attributes,
     external `xlink:href`).
7. Move the surviving files to
   `storage/app/tenant/{tenant_id}/themes/{slug}/` (the local disk is
   already tenant-prefixed by the filesystem bootstrapper).
8. Insert a row in `tenant_themes` so the picker lists it.

Failure at any step shows the tenant the offending file + reason; nothing
lands on disk.

### 3.2 Activation

Setting `theme.active` to `{slug}:byo` flips the active theme on the
next request. The first request after activation also recompiles Blade
templates from `themes/byo/{slug}/` and stores the compiled cache under
`storage/framework/views/byo/{tenant_id}/{slug}/...` (so cache is
tenant-scoped — never reused across tenants).

### 3.3 Lifecycle

- Tenants can upload a new version (zip with a higher `version` in
  manifest) — appears as v2 in the picker; old version stays available
  for one-click rollback.
- Up to **5** BYO themes per tenant; uploading a 6th fails until one is
  deleted.
- A 30-day soft delete window before files are purged.

---

## 4. Blade sandbox

The dangerous Blade features:

- `@php ... @endphp` — arbitrary PHP.
- `{!! $x !!}` — raw output, bypasses escaping.
- `@include('/abs/path')`, `@each('/abs/path', ...)` — path traversal.
- `@inject('var', SomeClass::class)` — instantiates anything.
- Calling `app()`, `request()`, `auth()`, `session()`, `config()`,
  `env()` inside an interpolation.
- `eval($var)` (extremely rare in Blade but conceivable via injected
  variables).

The sandbox is a **custom Blade compiler subclass** + a **scan pass
before compile**:

```php
class SandboxBladeCompiler extends BladeCompiler
{
    protected array $forbiddenDirectives = ['php', 'endphp', 'inject', 'eval'];

    protected array $forbiddenTokens = [
        T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO,        // raw <?php
        '`',                                       // backtick exec
    ];

    public function compileString($value)
    {
        $this->rejectForbiddenDirectives($value);
        $this->rejectRawOutput($value);
        $this->rejectPathTraversal($value);
        $this->rejectForbiddenFunctions($value);
        return parent::compileString($value);
    }
}
```

Specifically:

- **Directives**: a regex pre-scan rejects `@php`, `@inject`,
  `@useExtension`, and any directive not in the allow-list:

  ```
  if, elseif, else, endif, isset, endisset, empty, endempty,
  unless, endunless, switch, case, break, default, endswitch,
  for, endfor, foreach, endforeach, forelse, empty, endforelse, while, endwhile,
  include, includeIf, includeWhen, includeUnless, includeFirst,
  extends, section, endsection, yield, parent, show, stop, append, overwrite,
  push, endpush, prepend, endprepend, stack,
  component, endcomponent, slot, endslot,
  csrf, method, json
  ```
  `@include` / `@extends` arguments must be **relative paths within the
  theme directory**; a regex rejects `..`, leading `/`, leading scheme
  (`http://`), or a string that does not match `[a-z0-9._-]+(/[a-z0-9._-]+)*`.

- **Raw output**: `{!! ... !!}` is forbidden entirely. The compiler
  rewrites it to `{{ ... }}` (escape) and emits a warning the tenant can
  see in the theme audit log.

- **Forbidden function calls** inside interpolations: a token scan
  rejects identifiers `app, request, auth, session, env, eval, system,
  exec, shell_exec, passthru, proc_open, popen, pcntl_*, dl, include,
  include_once, require, require_once`. The scan walks the Blade-emitted
  PHP after compile and re-rejects if any slipped through.

- **Allowed helpers** (provided to themes as a controlled vocabulary):
  `tenant_setting(key, default)`, `t(slot)`, `theme_asset(path)`,
  `csrf_field()`, `route('client.*')`, `url('/path')`, plus Paymenter's
  client-side view models (`$invoice`, `$service`, etc.) passed in by
  the controller.

- **No global state writes**: `setting([...])->save()` is unreachable
  from theme code because the helper is not exposed; themes are
  presentation-only.

### 4.1 What a safe BYO template looks like

```blade
@extends('layouts.app')

@section('content')
  <header style="color: var(--accent)">
    <h1>{{ tenant_setting('app_name') }}</h1>
  </header>

  <main>
    @foreach($invoices as $invoice)
      <article>
        <h2>{{ $invoice->number }}</h2>
        <p>{{ $invoice->total }}</p>
        <a href="{{ route('client.invoices.show', $invoice) }}">Open</a>
      </article>
    @endforeach
  </main>

  @t('footer')
@endsection
```

No `@php`, no raw output, only allow-listed helpers. Reviewed by the
compiler, not by a human, on every upload.

---

## 5. CSS sanitiser

```php
$parser = new Sabberworm\CSS\Parser($css);
$doc    = $parser->parse();

foreach ($doc->getAllRuleSets() as $ruleSet) {
    foreach ($ruleSet->getRules() as $rule) {
        if (! in_array($rule->getRule(), $allowedProperties)) {
            $ruleSet->removeRule($rule);
            continue;
        }
        $val = (string) $rule->getValue();
        if (preg_match('/(expression\(|behavior:|-moz-binding|javascript:|vbscript:)/i', $val)) {
            $ruleSet->removeRule($rule);
            continue;
        }
        // url(...) → only https or data:image/(png|jpe?g|webp|gif|svg+xml)
        $val = preg_replace_callback('/url\(\s*["\']?([^"\')]+)["\']?\s*\)/i', function ($m) {
            $u = $m[1];
            if (str_starts_with($u, 'data:image/')) return $m[0];
            if (preg_match('#^https://#i', $u)) return $m[0];
            return '/* url-stripped */';
        }, $val);
        $rule->setValue($val);
    }
}

return $doc->render();
```

`$allowedProperties` is a long but explicit list: layout (display, flex*,
grid*, position, top/right/…, margin*, padding*, width, height, …),
typography (color, font-*, line-height, text-*, letter-spacing, …),
backgrounds (background-color, background-image, background-size,
background-position, background-repeat), borders (border*, border-radius,
outline*), effects (box-shadow, opacity, filter, transform, transition,
animation, will-change), CSS custom properties (`--*`).

Explicitly **denied**: `position: fixed` (anti-clickjack on tenant
admin), `pointer-events`, `user-select` (UX traps), `-webkit-touch-callout`
(mobile traps). Removed silently with an entry in the theme audit log.

`@import` and `@font-face url(...)` to non-https sources are stripped.

---

## 6. JavaScript — Pro+ only, allow-listed

JS in BYO themes is **off by default**. Tenants on Pro+ can flip it on,
but with constraints:

- One JS file per theme, ≤ 50 KB minified.
- Served from `storage/app/tenant/{tenant_id}/themes/{slug}/theme.js`
  via a route that adds a CSP nonce and an `integrity` SRI hash.
- The CSP header on theme pages includes the JS file's SRI hash; the
  file is **immutable** once uploaded (uploading a new version creates
  a new file with a new hash and the CSP is re-emitted).
- Disallowed bytes by regex pre-scan: `<script`, `eval(`, `Function(`,
  `setTimeout(\s*["']`, `setInterval(\s*["']` (only when first arg is a
  string), `document.write`, `innerHTML\s*=`, `outerHTML\s*=`,
  `insertAdjacentHTML`. These will catch most injection vectors; for
  v2 a proper JS parser-based linter replaces the regex.
- No third-party CDN imports — `connect-src 'self'` in CSP enforces it.

If a tenant needs richer JS (analytics, chat widget), they integrate it
through a curated extension — not the theme.

---

## 7. Slots & sections

The manifest declares `slots`. A theme can render `@t('header')` and
`@t('footer')` etc. Tenants fill slots in `/admin/themes/customise`:

- Each slot is a markdown editor (rendered with the same hardened
  pipeline as extension Markdown, § 4.2 in `EXTENSIONS.md`).
- Slots are stored as `settings` rows (`theme.slot.header`, …) — tenant
  scoped automatically.
- Slot content is the **only** tenant-authored HTML in the rendered
  page; it goes through `purify(profile: 'theme_slot')` which is even
  stricter than `extension` (no `<table>`, no `<img>` width/height
  attrs).

---

## 8. CSS variables

The manifest's `css_vars` list (e.g. `["--accent", "--bg", "--fg"]`)
becomes a colour picker form in the tenant admin. Saved values are
emitted as a `<style nonce="...">` block before the theme's main CSS:

```html
<style nonce="abc">
  :root { --accent: #c00; --bg: #fff; --fg: #111; }
</style>
```

Validated as CSS colour values (`#hex`, `rgb()`, `hsl()`, `oklch()`,
named colours from the CSS4 colour spec). Anything else rejected at
form submit.

---

## 9. Preview mode

Before activating a BYO theme, the tenant can preview:

- `/?_theme=byo:myshop&_preview=<signed-token>` renders the homepage
  with the candidate theme but without persisting the change.
- The signed token (`URL::signedRoute(...)`, expires in 30 minutes)
  ensures only the admin who initiated preview sees it.
- Preview pages get an extra X-Robots-Tag: noindex and a CSP that
  forbids form submissions to non-tenant origins.

---

## 10. Tenant theme audit log

Every BYO upload writes a row to `tenant_theme_audit_log`:

```
id, tenant_id, theme_slug, version, action, files_accepted (int),
files_rejected (jsonb {file: reason}), uploaded_by, occurred_at
```

The tenant admin sees their own log. Operators can search across all
tenants when investigating an incident.

---

## 11. Out of scope for v1

- **Public theme marketplace** with paid themes. Documented for v2 in
  `ARCHITECTURE.md` AD-009 deferrals.
- **Live designer UI** (in-browser editor like Shopify). Tenants edit
  files locally and re-upload.
- **Per-page theme overrides**. The active theme is a single setting.
- **Twig sandbox** as an alternative templating engine. Considered;
  rejected for v1 to keep one template language.

---

## 12. Failure modes & responses

| What | Response |
| ---- | -------- |
| Compile error in a Blade file | Upload rejected, tenant sees file:line. |
| Runtime error inside a theme | Tenant page falls back to `default:curated` and we email the tenant admin. |
| CSS sanitiser silently strips a rule | Logged to audit log; tenant sees diff in upload report. |
| Extension trying to render unsafe HTML | Sanitiser strips, audit row written, no user-visible breakage. |
| Operator-side recall (theme found malicious post-approval) | Operator flips catalogue row to `recalled`; all tenants on that theme auto-revert to `default:curated` on next request. |
