<?php

namespace Paymenter\Extensions\Servers\Pelican;

use App\Classes\Extension\Server;
use App\Models\Product;
use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Pelican extends Server
{
    public function getConfig($values = []): array
    {
        return [
            ['name' => 'host',           'label' => 'Pelican URL',           'type' => 'text', 'required' => true,  'validation' => 'url', 'description' => 'e.g. https://panel.example.com'],
            ['name' => 'api_key',        'label' => 'Application API Key',   'type' => 'text', 'required' => true,  'encrypted' => true],
            ['name' => 'client_api_key', 'label' => 'Client API Key',        'type' => 'text', 'required' => false, 'encrypted' => true, 'description' => 'Required for Start / Restart buttons.'],
            ['name' => 'npm_url',        'label' => 'NPM URL',               'type' => 'text', 'required' => false, 'description' => 'e.g. http://npm.internal:81 — leave blank to disable proxy provisioning.'],
            ['name' => 'npm_email',      'label' => 'NPM Admin Email',       'type' => 'text', 'required' => false],
            ['name' => 'npm_password',   'label' => 'NPM Admin Password',    'type' => 'text', 'required' => false, 'encrypted' => true],
            ['name' => 'base_domain',    'label' => 'Base Domain',           'type' => 'text', 'required' => false, 'description' => 'e.g. calyrean.com → {user}{id}.{eggslug}.labs.calyrean.com'],
        ];
    }

    public function testConfig(): bool|string
    {
        try {
            $this->request('/api/application/nodes');
        } catch (Exception $e) {
            return 'Pelican: ' . $e->getMessage();
        }

        if ($this->npmEnabled()) {
            try {
                $this->npmToken();
            } catch (Exception $e) {
                return 'NPM: ' . $e->getMessage();
            }
        }

        return true;
    }

    // =========================================================================
    // Pelican HTTP
    // =========================================================================

    public function request(string $url, string $method = 'get', array $data = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('api_key'),
            'Accept'        => 'application/json',
        ])->$method(rtrim($this->config('host'), '/') . $url, $data);

        if (! $response->successful()) {
            $errors = $response->json()['errors'] ?? null;
            throw new Exception(
                (is_array($errors) && isset($errors[0]['detail'])) ? $errors[0]['detail'] : $response->body()
            );
        }

        return $response->json() ?? [];
    }

    private function clientRequest(string $url, string $method = 'get', array $data = []): array
    {
        $key = $this->config('client_api_key');
        if (empty($key)) {
            throw new Exception('Client API key not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Accept'        => 'application/json',
        ])->$method(rtrim($this->config('host'), '/') . $url, $data);

        if (! $response->successful()) {
            $errors = $response->json()['errors'] ?? null;
            throw new Exception(
                (is_array($errors) && isset($errors[0]['detail'])) ? $errors[0]['detail'] : $response->body()
            );
        }

        return $response->json() ?? [];
    }

    // =========================================================================
    // Nginx Proxy Manager
    // =========================================================================

    private function npmEnabled(): bool
    {
        return ! empty($this->config('npm_url'))
            && ! empty($this->config('npm_email'))
            && ! empty($this->config('npm_password'));
    }

    private function npmToken(): string
    {
        $cacheKey = 'pelican_npm_token_' . md5($this->config('npm_url') . $this->config('npm_email'));

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $response = Http::post(
            rtrim($this->config('npm_url'), '/') . '/api/tokens',
            ['identity' => $this->config('npm_email'), 'secret' => $this->config('npm_password')]
        );

        if (! $response->successful()) {
            throw new Exception('NPM login failed (' . $response->status() . '): ' . $response->body());
        }

        $token = $response->json()['token'] ?? null;
        if (! $token) {
            throw new Exception('NPM returned no token — check NPM credentials.');
        }

        Cache::put($cacheKey, $token, now()->addMinutes(55));
        return $token;
    }

    private function npmRequest(string $endpoint, string $method = 'get', array $data = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->npmToken(),
            'Accept'        => 'application/json',
        ])->$method(rtrim($this->config('npm_url'), '/') . $endpoint, $data);

        if (! $response->successful()) {
            throw new Exception('NPM ' . strtoupper($method) . ' ' . $endpoint . ' (' . $response->status() . '): ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function npmCreateProxy(string $domain, string $forwardHost, int $forwardPort): int
    {
        $proxy = $this->npmRequest('/api/nginx/proxy-hosts', 'post', [
            'domain_names'            => [$domain],
            'forward_host'            => $forwardHost,
            'forward_port'            => $forwardPort,
            'forward_scheme'          => 'http',
            'enabled'                 => 1,
            'caching_enabled'         => true,
            'block_exploits'          => true,
            'allow_websocket_upgrade' => true,
            'ssl_forced'              => false,
            'http2_support'           => false,
            'certificate_id'          => 0,
            'access_list_id'          => 0,
            'advanced_config'         => '',
            'locations'               => [],
            'meta'                    => ['letsencrypt_agree' => false, 'dns_challenge' => false],
        ]);

        $proxyId = (int) ($proxy['id'] ?? 0);
        if ($proxyId === 0) {
            throw new Exception('NPM returned no proxy ID for ' . $domain);
        }

        // Request LE cert + force-SSL + HTTP/2. Best-effort: may fail before DNS propagates.
        try {
            $this->npmRequest('/api/nginx/proxy-hosts/' . $proxyId, 'put', [
                'domain_names'            => [$domain],
                'forward_host'            => $forwardHost,
                'forward_port'            => $forwardPort,
                'forward_scheme'          => 'http',
                'enabled'                 => 1,
                'caching_enabled'         => true,
                'block_exploits'          => true,
                'allow_websocket_upgrade' => true,
                'ssl_forced'              => true,
                'http2_support'           => true,
                'certificate_id'          => 'new',
                'access_list_id'          => 0,
                'advanced_config'         => '',
                'locations'               => [],
                'meta'                    => ['letsencrypt_agree' => true, 'dns_challenge' => false],
            ]);
        } catch (Exception $e) {
            // Retry via NPM admin UI once DNS has propagated.
        }

        return $proxyId;
    }

    // =========================================================================
    // Product configuration
    // =========================================================================

    public function getProductConfig($values = []): array
    {
        $eggList  = [];
        $tagParts = [];

        try {
            foreach ($this->request('/api/application/eggs', 'get', ['per_page' => 100])['data'] as $egg) {
                $eggList[$egg['attributes']['id']] = $egg['attributes']['name'];
            }
        } catch (Exception $e) {}

        try {
            foreach ($this->request('/api/application/nodes', 'get', ['per_page' => 100])['data'] as $node) {
                foreach ($node['attributes']['tags'] ?? [] as $tag) {
                    $tagParts[] = $tag . ' (' . $node['attributes']['name'] . ')';
                }
            }
        } catch (Exception $e) {}

        return [
            ['name' => 'node_tag', 'label' => 'Node Tag',      'type' => 'text',   'required' => true,  'description' => empty($tagParts) ? 'No tagged nodes found.' : 'Available: ' . implode(', ', $tagParts)],
            ['name' => 'eggs',     'label' => 'Available Eggs','type' => 'select', 'required' => true,  'options' => $eggList, 'multiple' => true, 'database_type' => 'array', 'description' => 'Eggs the customer can choose from at checkout. Per-egg env vars are hardcoded (egg 4 = N8N_HOST/N8N_PORT, egg 5 = EXTERNAL_IP/OPENCLAW_GATEWAY_PORT).'],
            ['name' => 'start_on_completion', 'label' => 'Auto-start after install', 'type' => 'checkbox', 'description' => 'Start the server automatically once installation finishes.'],
            ['name' => 'skip_scripts',        'label' => 'Skip Install Script',      'type' => 'checkbox', 'description' => 'Check to skip the egg install script. Leave unchecked to run it.'],
        ];
    }

    // =========================================================================
    // Checkout configuration (customer-facing)
    // =========================================================================

    public function getCheckoutConfig($product = null, $values = [], $settings = []): array
    {
        $eggIds  = $this->parseEggIds($settings);
        $options = [];

        foreach ($eggIds as $eggId) {
            $options[(string) $eggId] = $this->eggName($eggId);
        }

        return [[
            'name'     => 'selected_egg',
            'label'    => 'Select Environment',
            'type'     => 'select',
            'options'  => $options,
            'required' => true,
            'default'  => (string) (array_key_first($options) ?? ''),
        ]];
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function parseEggIds(array $settings): array
    {
        $raw = $settings['eggs'] ?? [];
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }
        return array_map('intval', (array) $raw);
    }

    private function selectedEggId($properties): int
    {
        if (is_array($properties)) {
            return (int) ($properties['selected_egg'] ?? 0);
        }
        if (is_object($properties) && isset($properties->selected_egg)) {
            return (int) $properties->selected_egg;
        }
        return 0;
    }

    private function selectedEggIds(array $settings, $properties): array
    {
        $selected = $this->selectedEggId($properties);
        $allowed  = $this->parseEggIds($settings);
        if ($selected > 0 && in_array($selected, $allowed, true)) {
            return [$selected];
        }
        return [];
    }

    // Hardcoded per-egg environment-variable injection.
    // Each egg has its own isolated install section: 2 lines for IP and port.
    private function eggEnvironment(int $eggId, string $ip, string $port): array
    {
        return match ($eggId) {
            4 => [ // N8N
                'N8N_HOST' => $ip,
                'N8N_PORT' => $port,
            ],
            5 => [ // Clawbot / OpenClaw
                'EXTERNAL_IP'            => $ip,
                'OPENCLAW_GATEWAY_PORT'  => $port,
            ],
            default => [],
        };
    }

    // Pelican's API requires every egg variable to be present in the
    // `environment` payload — it stores null for any missing key (the
    // VariableValidatorService only uses default_value for *validation*,
    // not persistence). So we must explicitly send every var.
    //
    // Three sources tried in order (first non-empty wins):
    //   1. attributes.relationships.variables.data   (Pterodactyl/Fractal-nested shape)
    //   2. relationships.variables.data              (Fractal top-level shape)
    //   3. /api/application/eggs/{id}/export         (egg export JSON; always has flat `variables` array)
    private function fetchEggDefaults(int $eggId, array $eggData): array
    {
        $variables = $eggData['attributes']['relationships']['variables']['data']
            ?? $eggData['relationships']['variables']['data']
            ?? null;

        if (! is_array($variables) || empty($variables)) {
            try {
                $export    = $this->request('/api/application/eggs/' . $eggId . '/export');
                $variables = $export['variables'] ?? [];
            } catch (Exception $e) {
                $variables = [];
            }
        }

        $defaults = [];
        foreach ((array) $variables as $var) {
            $envName = $var['attributes']['env_variable']
                ?? $var['env_variable']
                ?? null;
            if ($envName !== null && $envName !== '') {
                $defaults[$envName] = (string) (
                    $var['attributes']['default_value']
                    ?? $var['default_value']
                    ?? ''
                );
            }
        }

        return $defaults;
    }

    private function eggName(int $eggId): string
    {
        return Cache::remember('pelican_egg_' . md5((string) $this->config('host')) . '_' . $eggId, 300, function () use ($eggId) {
            try {
                return $this->request('/api/application/eggs/' . $eggId)['attributes']['name'] ?? ('Egg ' . $eggId);
            } catch (Exception $e) {
                return 'Egg ' . $eggId;
            }
        });
    }

    private function eggSlug(int $eggId): string
    {
        return Str::slug($this->eggName($eggId));
    }

    private function buildSubdomain(Service $service, int $eggId): string
    {
        $username = preg_replace('/[^a-z0-9]/', '', strtolower(
            Str::transliterate($service->user->name ?? '') ?? ''
        ));
        return ($username ?: 'user') . $service->id
            . '.' . $this->eggSlug($eggId)
            . '.labs.' . $this->config('base_domain');
    }

    private function getOrCreateUser(Service $service): int
    {
        $user  = $service->user;
        $found = $this->request('/api/application/users', 'get', [
            'filter' => ['email' => $user->email],
        ])['data'][0]['attributes']['id'] ?? null;

        if ($found) {
            return (int) $found;
        }

        $base = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(Str::transliterate($user->name) ?? ''));
        return (int) $this->request('/api/application/users', 'post', [
            'email'      => $user->email,
            'username'   => ($base ?: Str::random(8)) . '_' . Str::random(4),
            'first_name' => $user->first_name ?? 'Unknown',
            'last_name'  => $user->last_name  ?? 'User',
        ])['attributes']['id'];
    }

    private function findNodeByTag(string $tag): array
    {
        foreach ($this->request('/api/application/nodes', 'get', ['per_page' => 100])['data'] as $node) {
            if (in_array($tag, $node['attributes']['tags'] ?? [], true)) {
                return $node['attributes'];
            }
        }
        throw new Exception("No node found with tag '{$tag}'");
    }

    private function findAvailableAllocation(int $nodeId): array
    {
        $page = 1;
        do {
            $response = $this->request('/api/application/nodes/' . $nodeId . '/allocations', 'get', ['per_page' => 100, 'page' => $page]);
            foreach ($response['data'] as $alloc) {
                if (! $alloc['attributes']['assigned']) {
                    return $alloc['attributes'];
                }
            }
            $meta = $response['meta']['pagination'] ?? [];
            $page++;
        } while (($meta['current_page'] ?? 1) < ($meta['total_pages'] ?? 1));

        throw new Exception("No available allocations on node {$nodeId}.");
    }

    private function getPanelServer(int $serviceId, int $eggId, bool $failIfNotFound = true): array|false
    {
        try {
            return $this->request('/api/application/servers/external/' . $serviceId . '_' . $eggId)['attributes'];
        } catch (Exception $e) {
            if ($failIfNotFound) {
                throw new Exception("Server not found (egg {$eggId}, service {$serviceId})");
            }
            return false;
        }
    }

    // =========================================================================
    // Server lifecycle
    // =========================================================================

    public function createServer(Service $service, $settings, $properties): array
    {
        $allowedEggIds = $this->parseEggIds($settings);
        $nodeTag       = trim($settings['node_tag'] ?? '');
        $selectedEgg   = $this->selectedEggId($properties);

        if (empty($allowedEggIds)) { throw new Exception('No eggs available for this plan.'); }
        if ($nodeTag === '')       { throw new Exception('No node tag configured for this plan.'); }
        if ($selectedEgg <= 0)     { throw new Exception('No environment selected — customer must pick one at checkout.'); }
        if (! in_array($selectedEgg, $allowedEggIds, true)) {
            throw new Exception('Selected egg ' . $selectedEgg . ' is not in the allowed list for this plan.');
        }

        $lock = Cache::lock('pelican_create_' . $service->id, 120);
        if (! $lock->get()) {
            throw new Exception('Server creation already in progress — please wait.');
        }

        try {
            $node   = $this->findNodeByTag($nodeTag);
            $userId = $this->getOrCreateUser($service);

            $skipScripts       = (bool) ($settings['skip_scripts']        ?? false);
            $startOnCompletion = (bool) ($settings['start_on_completion'] ?? false);

            $results = [];

            foreach ([$selectedEgg] as $eggId) {
                if ($this->getPanelServer($service->id, $eggId, false) !== false) {
                    continue;
                }

                // Get allocation — same source used for NPM
                $allocation = $this->findAvailableAllocation($node['id']);
                $ip   = (! empty($allocation['ip_alias'])) ? $allocation['ip_alias'] : $allocation['ip'];
                $port = (string) $allocation['port'];

                // Pull egg with its variables. Pelican's API persists null for
                // any variable missing from the `environment` payload, so we
                // must seed every default from the egg definition.
                $eggData = $this->request('/api/application/eggs/' . $eggId, 'get', ['include' => 'variables']);
                if (! isset($eggData['attributes'])) {
                    throw new Exception('Could not fetch egg data for egg ' . $eggId);
                }

                // Seed every egg variable with its default_value, then merge our
                // hardcoded per-egg IP/port overrides on top (dispatched by Egg ID).
                $environment = array_merge(
                    $this->fetchEggDefaults((int) $eggId, $eggData),
                    $this->eggEnvironment((int) $eggId, $ip, $port)
                );

                $created = $this->request('/api/application/servers', 'post', [
                    'external_id'         => $service->id . '_' . $eggId,
                    'name'                => $this->eggName($eggId) . ' — ' . $service->product->name . ' #' . $service->id,
                    'user'                => $userId,
                    'egg'                 => $eggId,
                    'docker_image'        => $eggData['attributes']['docker_image'],
                    'startup'             => $eggData['attributes']['startup'],
                    'environment'         => $environment,
                    'skip_scripts'        => $skipScripts,
                    'oom_killer'          => false,
                    'limits'              => ['memory' => 0, 'swap' => 0, 'disk' => 0, 'io' => 500, 'threads' => null, 'cpu' => 0],
                    'feature_limits'      => ['databases' => 0, 'allocations' => 0, 'backups' => 0],
                    'allocation'          => ['default' => $allocation['id']],
                    'start_on_completion' => $startOnCompletion,
                ]);

                Cache::put('pelican_alloc_' . $service->id . '_' . $eggId, ['ip' => $ip, 'port' => $port], now()->addDays(90));

                $npmStatus = 'skipped (NPM not configured)';
                if ($this->npmEnabled() && ! empty($this->config('base_domain'))) {
                    try {
                        $domain     = $this->buildSubdomain($service, $eggId);
                        $npmProxyId = $this->npmCreateProxy($domain, $ip, (int) $port);
                        Cache::put('pelican_npm_'    . $service->id . '_' . $eggId, $npmProxyId, now()->addYears(10));
                        Cache::put('pelican_domain_' . $service->id . '_' . $eggId, $domain,     now()->addYears(10));
                        $npmStatus = 'ok — ' . $domain . ' (proxy #' . $npmProxyId . ')';
                    } catch (Exception $e) {
                        $npmStatus = 'error — ' . $e->getMessage();
                    }
                }

                $results[] = [
                    'egg_id'     => $eggId,
                    'name'       => $this->eggName($eggId),
                    'ip'         => $ip,
                    'port'       => $port,
                    'server_id'  => $created['attributes']['id'],
                    'identifier' => $created['attributes']['identifier'],
                    'uuid'       => $created['attributes']['uuid'],
                    'npm'        => $npmStatus,
                ];
            }

            return ['servers' => $results, 'link' => rtrim($this->config('host'), '/')];
        } finally {
            $lock->release();
        }
    }

    public function suspendServer(Service $service, $settings, $properties): bool
    {
        foreach ($this->selectedEggIds($settings, $properties) as $eggId) {
            $server = $this->getPanelServer($service->id, $eggId, false);
            if ($server !== false) {
                $this->request('/api/application/servers/' . $server['id'] . '/suspend', 'post');
            }
            $npmId = Cache::get('pelican_npm_' . $service->id . '_' . $eggId);
            if ($npmId && $this->npmEnabled()) {
                try { $this->npmRequest('/api/nginx/proxy-hosts/' . $npmId . '/disable', 'post'); } catch (Exception $e) {}
            }
        }
        return true;
    }

    public function unsuspendServer(Service $service, $settings, $properties): bool
    {
        foreach ($this->selectedEggIds($settings, $properties) as $eggId) {
            $server = $this->getPanelServer($service->id, $eggId, false);
            if ($server !== false) {
                $this->request('/api/application/servers/' . $server['id'] . '/unsuspend', 'post');
            }
            $npmId = Cache::get('pelican_npm_' . $service->id . '_' . $eggId);
            if ($npmId && $this->npmEnabled()) {
                try { $this->npmRequest('/api/nginx/proxy-hosts/' . $npmId . '/enable', 'post'); } catch (Exception $e) {}
            }
        }
        return true;
    }

    public function terminateServer(Service $service, $settings, $properties): bool
    {
        foreach ($this->selectedEggIds($settings, $properties) as $eggId) {
            $server = $this->getPanelServer($service->id, $eggId, false);
            if ($server !== false) {
                try {
                    $this->request('/api/application/servers/' . $server['id'] . '/force', 'delete');
                } catch (Exception $e) {
                    try { $this->request('/api/application/servers/' . $server['id'], 'delete'); } catch (Exception $e2) {}
                }
            }
            $npmId = Cache::get('pelican_npm_' . $service->id . '_' . $eggId);
            if ($npmId && $this->npmEnabled()) {
                try { $this->npmRequest('/api/nginx/proxy-hosts/' . $npmId, 'delete'); } catch (Exception $e) {}
            }
            Cache::forget('pelican_alloc_'  . $service->id . '_' . $eggId);
            Cache::forget('pelican_npm_'    . $service->id . '_' . $eggId);
            Cache::forget('pelican_domain_' . $service->id . '_' . $eggId);
        }
        return true;
    }

    public function upgradeServer(Service $service, $settings, $properties): bool
    {
        return true;
    }

    // =========================================================================
    // Client-area actions
    // =========================================================================

    public function getActions(Service $service, $settings, $properties): array
    {
        $eggIds       = $this->selectedEggIds($settings, $properties);
        $hasClientKey = ! empty($this->config('client_api_key'));
        $actions      = [];

        foreach ($eggIds as $index => $eggId) {
            if ($index >= 8) break;

            $server = $this->getPanelServer($service->id, $eggId, false);
            if ($server === false) continue;

            $name     = $this->eggName($eggId);
            $panelUrl = rtrim($this->config('host'), '/') . '/server/' . $server['identifier'];

            $cachedDomain = Cache::get('pelican_domain_' . $service->id . '_' . $eggId);
            if ($cachedDomain) {
                $openUrl = 'https://' . $cachedDomain;
            } else {
                $cached  = Cache::get('pelican_alloc_' . $service->id . '_' . $eggId);
                $openUrl = $cached ? 'http://' . $cached['ip'] . ':' . $cached['port'] : $panelUrl;
            }

            $actions[] = ['type' => 'button', 'label' => 'Open '  . $name, 'url' => $openUrl];
            $actions[] = ['type' => 'button', 'label' => 'Panel ' . $name, 'url' => $panelUrl];

            if ($hasClientKey) {
                $i = $index;
                $actions[] = ['type' => 'button', 'label' => 'Start '     . $name, 'function' => 'startEgg'     . $i];
                $actions[] = ['type' => 'button', 'label' => 'Restart '   . $name, 'function' => 'restartEgg'   . $i];
                $actions[] = ['type' => 'button', 'label' => 'Reinstall ' . $name, 'function' => 'reinstallEgg' . $i];
            }
        }

        return $actions;
    }

    // =========================================================================
    // Power / reinstall
    // =========================================================================

    private function eggPower(Service $service, array $settings, $properties, int $index, string $signal): void
    {
        $eggIds = $this->selectedEggIds($settings, $properties);
        if (! isset($eggIds[$index])) throw new Exception('Egg index ' . $index . ' not configured.');
        $server = $this->getPanelServer($service->id, $eggIds[$index]);
        $this->clientRequest('/api/client/servers/' . $server['uuid'] . '/power', 'post', ['signal' => $signal]);
    }

    private function eggReinstall(Service $service, array $settings, $properties, int $index): void
    {
        $eggIds = $this->selectedEggIds($settings, $properties);
        if (! isset($eggIds[$index])) throw new Exception('Egg index ' . $index . ' not configured.');
        $server = $this->getPanelServer($service->id, $eggIds[$index]);
        $this->request('/api/application/servers/' . $server['id'] . '/reinstall', 'post');
    }

    // Explicit stubs required by Paymenter's method_exists() dispatcher (indices 0-7)
    public function startEgg0(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 0, 'start');   }
    public function restartEgg0(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 0, 'restart'); }
    public function reinstallEgg0(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 0); }
    public function startEgg1(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 1, 'start');   }
    public function restartEgg1(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 1, 'restart'); }
    public function reinstallEgg1(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 1); }
    public function startEgg2(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 2, 'start');   }
    public function restartEgg2(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 2, 'restart'); }
    public function reinstallEgg2(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 2); }
    public function startEgg3(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 3, 'start');   }
    public function restartEgg3(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 3, 'restart'); }
    public function reinstallEgg3(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 3); }
    public function startEgg4(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 4, 'start');   }
    public function restartEgg4(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 4, 'restart'); }
    public function reinstallEgg4(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 4); }
    public function startEgg5(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 5, 'start');   }
    public function restartEgg5(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 5, 'restart'); }
    public function reinstallEgg5(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 5); }
    public function startEgg6(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 6, 'start');   }
    public function restartEgg6(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 6, 'restart'); }
    public function reinstallEgg6(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 6); }
    public function startEgg7(Service $s, $set, $p): void     { $this->eggPower($s, $set, $p, 7, 'start');   }
    public function restartEgg7(Service $s, $set, $p): void   { $this->eggPower($s, $set, $p, 7, 'restart'); }
    public function reinstallEgg7(Service $s, $set, $p): void { $this->eggReinstall($s, $set, $p, 7); }
}
