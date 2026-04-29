# Pelican Server Extension — Maintenance Guide

A Paymenter Server-extension that provisions servers on a [Pelican](https://pelican.dev) panel, optionally fronted by an [Nginx Proxy Manager](https://nginxproxymanager.com) instance. The customer picks one egg at checkout from a list configured by the admin; per-egg env vars (IP / port) are hardcoded by Egg ID.

---

## File layout

```
extensions/Servers/Pelican/
├── Pelican.php   # The entire extension (single class)
└── README.md     # This file
```

There is no separate config file, no migrations, no service container bindings. Everything lives on the `Pelican` class.

---

## How Paymenter wires this in

Paymenter loads any class under `extensions/Servers/<Name>/<Name>.php` that extends `App\Classes\Extension\Server`. The framework then calls these methods at well-defined moments:

| Method | When it fires |
|---|---|
| `getConfig()` | Admin opens the **Server gateway** edit screen (Admin → Settings → Servers). |
| `testConfig()` | "Test connection" button on that same screen. |
| `getProductConfig()` | Admin opens a **Product** that uses this gateway, on the *Server* tab. |
| `getCheckoutConfig()` | Customer reaches the checkout page for that product. |
| `createServer()` | A paid order needs provisioning. |
| `suspendServer()` | Service overdue / suspended. |
| `unsuspendServer()` | Service paid back / reactivated. |
| `terminateServer()` | Service cancelled / terminated. |
| `upgradeServer()` | Plan changed. (Currently a no-op.) |
| `getActions()` | Client-area service page renders action buttons. |
| `startEgg{N}` / `restartEgg{N}` / `reinstallEgg{N}` | Customer clicks an action button. |

Every lifecycle method receives `(Service $service, $settings, $properties)`:
- `$settings` — admin-configured product settings (from `getProductConfig`).
- `$properties` — service properties, including the customer's checkout selection (from `getCheckoutConfig`). The customer's egg pick lives in `$properties['selected_egg']`.

---

## Configuration surfaces

### 1. Server gateway config — `getConfig()`
Set once per Pelican instance at Admin → Settings → Servers.

| Field | Purpose |
|---|---|
| `host` | Pelican panel URL, e.g. `https://panel.example.com`. |
| `api_key` | Application API key (Bearer). |
| `client_api_key` | Client API key. Optional — required only for Start / Restart buttons in the client area. |
| `npm_url` | Nginx Proxy Manager URL. Leave blank to disable proxy creation entirely. |
| `npm_email` / `npm_password` | NPM admin credentials. |
| `base_domain` | Wildcard root, e.g. `calyrean.com`. The full subdomain becomes `{user}{serviceId}.{eggslug}.labs.{base_domain}`. |

### 2. Product config — `getProductConfig()`
Set per product on the *Server* tab.

| Field | Purpose |
|---|---|
| `node_tag` | A Pelican node tag — the extension picks any node carrying that tag. |
| `eggs` | Multi-select. **The list of eggs the customer can choose from at checkout.** |
| `start_on_completion` | Auto-start once installation finishes. |
| `skip_scripts` | Skip the egg's install script. |

### 3. Checkout config — `getCheckoutConfig()`
A single dropdown rendered to the customer at checkout: **Select Environment**. Options are derived from the `eggs` setting and labelled with each egg's name. The chosen egg ID is persisted as the service property `selected_egg`.

---

## Provisioning flow

```
createServer()
 ├─ validate $properties['selected_egg'] against $settings['eggs']
 ├─ acquire Cache::lock('pelican_create_<service>') so retries don't double-provision
 ├─ findNodeByTag()
 ├─ getOrCreateUser()
 ├─ findAvailableAllocation()  → ip + port
 ├─ fetchEggDefaults()          → seed every egg variable from default_value
 ├─ eggEnvironment()            → hardcoded per-egg IP/port overrides (see below)
 ├─ POST /api/application/servers
 ├─ cache the (ip, port) under pelican_alloc_<service>_<egg>
 └─ if NPM enabled: create proxy host, request LE cert, cache pelican_npm_/pelican_domain_
```

### Why `fetchEggDefaults` exists
Pelican's `VariableValidatorService` only uses each variable's `default_value` for **validation**, not persistence. Any egg variable not present in the `environment` payload of `POST /api/application/servers` gets persisted as `null`. So we explicitly read every variable's default and include it in the payload. The hardcoded `eggEnvironment()` overrides are then merged on top.

The helper tries three sources in order:
1. `attributes.relationships.variables.data` (Pterodactyl-style nesting).
2. `relationships.variables.data` (top-level Fractal nesting).
3. `/api/application/eggs/{id}/export` — the egg-export JSON, which always exposes a flat `variables` array.

---

## Hardcoded per-egg env vars — the part you'll edit most often

Look for `eggEnvironment()` in `Pelican.php`:

```php
private function eggEnvironment(int $eggId, string $ip, string $port): array
{
    return match ($eggId) {
        4 => [ // N8N
            'N8N_HOST' => $ip,
            'N8N_PORT' => $port,
        ],
        5 => [ // Clawbot / OpenClaw
            'EXTERNAL_IP'           => $ip,
            'OPENCLAW_GATEWAY_PORT' => $port,
        ],
        default => [],
    };
}
```

Each `match` arm corresponds to one Egg ID. The keys are the env-variable names defined on that egg in the Pelican panel; the values are the just-allocated IP and port.

### To add a new egg
1. Create / import the egg in the Pelican panel and note its Egg ID.
2. Add the egg to the product's *Available Eggs* list (admin form).
3. Add a new arm to `eggEnvironment()`:
   ```php
   7 => [ // Foobar
       'FOO_HOST' => $ip,
       'FOO_PORT' => $port,
   ],
   ```
4. That's it — the customer dropdown auto-includes it because the options are built from `$settings['eggs']`.

### To change variable names for an existing egg
Just edit the strings inside the relevant `match` arm. The env vars must match exactly what's defined on the egg in the panel — anything else gets dropped.

### To inject more than IP/port for an egg
Add more keys to the array:
```php
4 => [
    'N8N_HOST' => $ip,
    'N8N_PORT' => $port,
    'N8N_PROTOCOL' => 'https',
],
```
Anything you put here overrides the egg's default for that key. Keys you don't list still get the egg's `default_value` via `fetchEggDefaults()`.

### To make a value depend on the service / customer
The signature is `eggEnvironment(int $eggId, string $ip, string $port)`. Extend it — pass `Service $service` in and read off `$service->user`, `$service->id`, etc. Update the call site in `createServer()`.

---

## NPM proxy integration

If `npm_url`, `npm_email`, `npm_password` and `base_domain` are all set:
- After server creation, a proxy host is created at `{user}{id}.{eggslug}.labs.{base_domain}` → `{ip}:{port}`.
- A Let's Encrypt cert + force-SSL + HTTP/2 are requested in a best-effort second call. If DNS hasn't propagated yet, this can fail silently — the proxy still works on HTTP, and you can retry from the NPM UI.
- The proxy ID and full domain are cached so suspend / unsuspend / terminate can manage it.

If any of those fields are blank, NPM is skipped entirely and the customer's "Open" button just links to `http://{ip}:{port}`.

---

## State / cache keys

| Key | TTL | What it stores |
|---|---|---|
| `pelican_alloc_<serviceId>_<eggId>` | 90 days | `['ip' => ..., 'port' => ...]` for the allocation. Used by the Open button when NPM isn't set. |
| `pelican_npm_<serviceId>_<eggId>` | 10 years | NPM proxy-host ID (so we can disable / enable / delete it later). |
| `pelican_domain_<serviceId>_<eggId>` | 10 years | The full domain assigned via NPM. |
| `pelican_egg_<host-md5>_<eggId>` | 5 min | Cached egg name (used for select labels and server names). |
| `pelican_npm_token_<host-email-md5>` | 55 min | NPM session token. |
| `pelican_create_<serviceId>` | 2 min lock | Prevents concurrent provisioning of the same service. |

If anything desyncs between Pelican and Paymenter, clear the relevant key from the cache and retry.

---

## Client-area actions

`getActions()` returns up to 8 sets of buttons per service:
- **Open** — public URL (NPM domain over HTTPS if available, otherwise raw `http://ip:port`).
- **Panel** — link to the server's page in the Pelican panel.
- **Start / Restart / Reinstall** — only shown if `client_api_key` is set on the gateway.

The Start / Restart / Reinstall buttons are dispatched by Paymenter through 24 explicit stub methods (`startEgg0` … `reinstallEgg7`). They exist purely because Paymenter's dispatcher uses `method_exists()` and won't call dynamic names. **Don't delete them.** They each forward to `eggPower()` or `eggReinstall()` with a fixed index — and now that only one egg is provisioned per service, only `*Egg0` is ever reached. The 1–7 stubs are kept for future-proofing.

---

## Common maintenance recipes

### "Provisioning fails with `Server creation already in progress`"
The lock didn't release (e.g. the worker died mid-create). Clear `pelican_create_<serviceId>` from cache.

### "Open button points to plain http even though NPM is configured"
The `pelican_domain_<serviceId>_<eggId>` cache key is missing. Check NPM logs for the create / certificate request, then either re-cache manually or terminate + re-provision.

### "Customer sees 'Selected egg X is not in the allowed list'"
The product's `eggs` list was changed after the customer ordered. Either re-add that egg ID to the product, or terminate and have them re-order.

### "I want to change which eggs a customer can choose without redeploying"
Just edit the *Available Eggs* multi-select on the product. No code change needed.

### "I want to change the IP/port var names for egg 4"
Edit the relevant arm of `eggEnvironment()`. No DB migration needed.

### "I added a brand-new egg and the customer's checkout dropdown doesn't show it"
Two things to check: (1) the egg was added to the product's *Available Eggs* list, and (2) `eggName()`'s 5-minute cache hasn't gone stale. The dropdown labels come from `eggName(int $eggId)`; if the panel API was unreachable when the cache was last filled, you'll see `Egg <id>` instead of the proper name. Clear `pelican_egg_*` keys to refresh.

### "I want to provision more than one egg per service again"
This is exactly what the original bundled-mode did. Revert the `selectedEggId` validation in `createServer()` to iterate `parseEggIds($settings)` instead of `[$selectedEgg]`, and revert `selectedEggIds()` to return the full admin list. The lifecycle methods are already loop-shaped and will follow.

---

## Debugging tips

- All API errors thrown from `request()` carry the raw Pelican error detail; check Paymenter's exception log first.
- To see what's actually being sent in `environment`, drop a `Log::debug('Pelican env', $environment);` just before the `POST /api/application/servers` call.
- To inspect the egg as Pelican returns it, hit `GET {host}/api/application/eggs/{id}?include=variables` with the application API key. Compare against `GET {host}/api/application/eggs/{id}/export`.
- The customer's selection is stored on the `service_properties` table under key `selected_egg`. SQL: `SELECT * FROM service_properties WHERE service_id = ? AND key = 'selected_egg'`.

---

## Things to avoid

- Don't rename `startEgg{N}` / `restartEgg{N}` / `reinstallEgg{N}`. Paymenter dispatches by exact method name.
- Don't move `relationships` parsing out of `fetchEggDefaults()` without keeping the `/eggs/{id}/export` fallback — the include-shape varies between Pelican builds.
- Don't drop the `Cache::lock` in `createServer()` — Paymenter retries failed jobs, and concurrent runs will create duplicate servers.
- Don't bypass `selectedEggIds()` in lifecycle methods. Reading raw `$settings['eggs']` would cause suspend/terminate to also touch eggs that were never provisioned for this service.
