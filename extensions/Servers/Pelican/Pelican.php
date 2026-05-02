<?php

namespace Paymenter\Extensions\Servers\Pelican;

use App\Classes\Extension\Server;
use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Paymenter\Extensions\Servers\Pelican\Livewire\Activity;
use Paymenter\Extensions\Servers\Pelican\Livewire\Backups;
use Paymenter\Extensions\Servers\Pelican\Livewire\Console;
use Paymenter\Extensions\Servers\Pelican\Livewire\Databases;
use Paymenter\Extensions\Servers\Pelican\Livewire\Files;
use Paymenter\Extensions\Servers\Pelican\Livewire\Network;
use Paymenter\Extensions\Servers\Pelican\Livewire\Overview;
use Paymenter\Extensions\Servers\Pelican\Livewire\Schedules;
use Paymenter\Extensions\Servers\Pelican\Livewire\Settings as SettingsTab;
use Paymenter\Extensions\Servers\Pelican\Livewire\StartupVars;
use Paymenter\Extensions\Servers\Pelican\Livewire\Subusers;

class Pelican extends Server
{
    /**
     * Map of `show_*` setting key => [Livewire component alias, label].
     * Drives both `getActions()` (tab list) and `getView()` (tab rendering).
     * Order here is the order tabs appear.
     */
    protected const TABS = [
        'show_overview'      => ['pelican.overview',     'Overview'],
        'show_console'       => ['pelican.console',      'Console'],
        'show_files'         => ['pelican.files',        'Files'],
        'show_databases'     => ['pelican.databases',    'Databases'],
        'show_backups'       => ['pelican.backups',      'Backups'],
        'show_schedules'     => ['pelican.schedules',    'Schedules'],
        'show_subusers'      => ['pelican.subusers',     'Subusers'],
        'show_network'       => ['pelican.network',      'Network'],
        'show_startup_vars'  => ['pelican.startup-vars', 'Startup'],
        'show_settings'      => ['pelican.settings',     'Settings'],
        'show_activity'      => ['pelican.activity',     'Activity'],
    ];

    public function boot(): void
    {
        View::addNamespace('pelican', __DIR__ . '/views');

        Livewire::component('pelican.overview',     Overview::class);
        Livewire::component('pelican.console',      Console::class);
        Livewire::component('pelican.files',        Files::class);
        Livewire::component('pelican.databases',    Databases::class);
        Livewire::component('pelican.backups',      Backups::class);
        Livewire::component('pelican.schedules',    Schedules::class);
        Livewire::component('pelican.subusers',     Subusers::class);
        Livewire::component('pelican.network',      Network::class);
        Livewire::component('pelican.startup-vars', StartupVars::class);
        Livewire::component('pelican.settings',     SettingsTab::class);
        Livewire::component('pelican.activity',     Activity::class);
    }

    // =========================================================================
    // Extension configuration (admin → Extensions → Pelican)
    // =========================================================================

    public function getConfig($values = []): array
    {
        return [
            ['name' => 'host',           'label' => 'Pelican URL',           'type' => 'text', 'required' => true,  'validation' => 'url',
             'description' => 'e.g. https://panel.example.com'],

            ['name' => 'api_key',        'label' => 'Application API Key',   'type' => 'text', 'required' => true,  'encrypted' => true,
             'description' => 'Pelican Panel → Admin → API Credentials. Used for provisioning, suspension, deletion.'],

            ['name' => 'client_api_key', 'label' => 'Client API Key',        'type' => 'text', 'required' => true,  'encrypted' => true,
             'description' => 'Pelican Panel → Account → API Credentials, generated from a ROOT ADMIN user account so it can manage every customer\'s server. Required for power, console, files, databases, backups, etc.'],

            ['name' => 'npm_url',        'label' => 'NPM URL',               'type' => 'text', 'required' => false,
             'description' => 'Optional Nginx Proxy Manager URL — leave blank to disable proxy provisioning.'],

            ['name' => 'npm_email',      'label' => 'NPM Admin Email',       'type' => 'text', 'required' => false],
            ['name' => 'npm_password',   'label' => 'NPM Admin Password',    'type' => 'text', 'required' => false, 'encrypted' => true],

            ['name' => 'base_domain',    'label' => 'Base Domain',           'type' => 'text', 'required' => false,
             'description' => 'e.g. example.com → {user}{id}.{eggslug}.labs.example.com'],
        ];
    }

    /**
     * Validates BOTH API keys independently and reports which one is broken.
     * Used by the "Test Configuration" button on the extension settings page.
     */
    public function testConfig(): bool|string
    {
        $errors = [];

        try {
            $this->request('/api/application/nodes', 'get', ['per_page' => 1]);
        } catch (Exception $e) {
            $errors[] = 'Application API Key — ' . $e->getMessage();
        }

        if (! empty($this->config('client_api_key'))) {
            try {
                $this->clientRequest('/api/client');
            } catch (Exception $e) {
                $errors[] = 'Client API Key — ' . $e->getMessage()
                    . ' (must be generated from a ROOT ADMIN user under Account → API Credentials).';
            }
        } else {
            $errors[] = 'Client API Key is empty — generate one in Pelican Panel → Account → API Credentials from a root admin user.';
        }

        if ($this->npmEnabled()) {
            try {
                $this->npmToken();
            } catch (Exception $e) {
                $errors[] = 'NPM — ' . $e->getMessage();
            }
        }

        return empty($errors) ? true : implode("\n", $errors);
    }

    // =========================================================================
    // Pelican HTTP — Application API
    // =========================================================================

    public function request(string $url, string $method = 'get', array $data = []): array
    {
        return $this->httpCall(
            (string) $this->config('api_key'),
            $url,
            $method,
            $data,
            'Application API'
        );
    }

    /**
     * Client API request. Used for power, console, files, databases, backups, etc.
     * The Client API key MUST be issued from a ROOT ADMIN user account so it can
     * manage every customer's server (otherwise it can only see its own user's servers).
     */
    public function clientRequest(string $url, string $method = 'get', array $data = []): array
    {
        $key = (string) $this->config('client_api_key');
        if ($key === '') {
            throw new Exception(
                'Client API key is not configured. Generate one in Pelican Panel → Account → API Credentials from a ROOT ADMIN user account, '
                . 'then paste it into Paymenter → Extensions → Pelican → Client API Key.'
            );
        }

        return $this->httpCall($key, $url, $method, $data, 'Client API');
    }

    /**
     * Client API request with a raw text body — used for file writes where
     * Pelican expects the file content directly as the request body.
     */
    public function clientRequestRaw(string $url, string $body, string $contentType = 'text/plain'): array
    {
        $key = (string) $this->config('client_api_key');
        if ($key === '') {
            throw new Exception('Client API key is not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Accept'        => 'application/json',
            'Content-Type'  => $contentType,
        ])->withBody($body, $contentType)
          ->post(rtrim($this->config('host'), '/') . $url);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status = $response->status();
        throw new Exception("Client API: HTTP {$status} writing file — " . ($response->json()['errors'][0]['detail'] ?? $response->body()));
    }

    /**
     * Shared HTTP execution. Translates HTTP status codes into actionable error messages.
     */
    private function httpCall(string $bearer, string $url, string $method, array $data, string $label): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $bearer,
            'Accept'        => 'application/json',
        ])->$method(rtrim($this->config('host'), '/') . $url, $data);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status  = $response->status();
        $payload = $response->json();
        $detail  = is_array($payload) && isset($payload['errors'][0]['detail'])
            ? $payload['errors'][0]['detail']
            : null;

        $hint = match ($status) {
            401 => "{$label} returned 401 Unauthorized — the API key is invalid, was revoked, or doesn't exist on the Pelican Panel.",
            403 => "{$label} returned 403 Forbidden — the API key lacks permission for this action. "
                . ($label === 'Client API' ? 'Make sure the key was generated from a ROOT ADMIN user account.' : 'Check the key\'s scopes in the Pelican admin panel.'),
            404 => "{$label}: resource not found at {$url} — the server may have been deleted manually in Pelican.",
            422 => "{$label}: validation failed — " . ($detail ?? 'check the parameters being sent.'),
            429 => "{$label}: rate limited — too many requests in a short time, try again shortly.",
            500, 502, 503, 504 => "{$label}: Pelican Panel returned a {$status} error — the panel itself may be down or misconfigured.",
            default => "{$label}: HTTP {$status}" . ($detail ? " — {$detail}" : ''),
        };

        throw new Exception($hint);
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
            // SSL upgrade can fail if DNS hasn't propagated; retry via NPM admin UI later.
        }

        return $proxyId;
    }

    // =========================================================================
    // Product configuration (admin → Products → Pelican product)
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
            // ---- Egg & placement -------------------------------------------------
            ['name' => 'node_tag', 'label' => '— Egg & Placement —', 'type' => 'text', 'required' => true,
             'description' => 'Node Tag. ' . (empty($tagParts) ? 'No tagged nodes found.' : 'Available: ' . implode(', ', $tagParts))],

            ['name' => 'eggs', 'label' => 'Available Eggs', 'type' => 'select', 'required' => true,
             'options' => $eggList, 'multiple' => true, 'database_type' => 'array',
             'description' => 'Eggs the customer can choose from at checkout.'],

            ['name' => 'start_on_completion', 'label' => 'Auto-start After Install', 'type' => 'checkbox',
             'description' => 'Start the server automatically once installation finishes.'],

            ['name' => 'skip_scripts', 'label' => 'Skip Install Script', 'type' => 'checkbox',
             'description' => 'Skip the egg install script entirely.'],

            // ---- Resource limits -------------------------------------------------
            ['name' => 'limit_memory', 'label' => 'Memory Limit (MB)', 'type' => 'number', 'default' => 0,
             'description' => 'RAM in MB. 0 = unlimited.'],

            ['name' => 'limit_swap', 'label' => 'Swap Limit (MB)', 'type' => 'number', 'default' => 0,
             'description' => 'Swap in MB. 0 = unlimited, -1 = disabled.'],

            ['name' => 'limit_disk', 'label' => 'Disk Limit (MB)', 'type' => 'number', 'default' => 0,
             'description' => 'Disk quota in MB. 0 = unlimited.'],

            ['name' => 'limit_cpu', 'label' => 'CPU Limit (%)', 'type' => 'number', 'default' => 0,
             'description' => 'CPU as % of one core. 100 = one core, 200 = two cores, 0 = unlimited.'],

            ['name' => 'limit_threads', 'label' => 'CPU Threads (Pinning)', 'type' => 'text',
             'description' => 'Pinned CPU thread spec (e.g. "0,2-4"). Leave blank for no pinning.'],

            ['name' => 'limit_io', 'label' => 'IO Weight (10–1000)', 'type' => 'number', 'default' => 500,
             'description' => 'Block IO weight, 10–1000 (default 500).'],

            ['name' => 'limit_oom_killer', 'label' => 'Enable OOM Killer', 'type' => 'checkbox',
             'description' => 'Allow Docker to kill the container when it runs out of memory.'],

            // ---- Feature limits --------------------------------------------------
            ['name' => 'feature_databases', 'label' => 'Max Databases', 'type' => 'number', 'default' => 0,
             'description' => 'Number of databases the customer can create.'],

            ['name' => 'feature_allocations', 'label' => 'Extra Allocations', 'type' => 'number', 'default' => 0,
             'description' => 'Additional ports the customer can claim beyond the default one.'],

            ['name' => 'feature_backups', 'label' => 'Max Backups', 'type' => 'number', 'default' => 0,
             'description' => 'Number of backups the customer can create.'],

            // ---- Egg overrides (advanced) ---------------------------------------
            ['name' => 'override_docker_image', 'label' => 'Docker Image Override', 'type' => 'text',
             'description' => 'Optional. Overrides the egg\'s default Docker image at provision time.'],

            ['name' => 'override_startup', 'label' => 'Startup Command Override', 'type' => 'text',
             'description' => 'Optional. Overrides the egg\'s default startup command.'],

            ['name' => 'override_environment', 'label' => 'Environment Variable Overrides (KEY=value, one per line)', 'type' => 'textarea',
             'description' => 'Optional. Extra env vars merged on top of egg defaults. One per line, format KEY=VALUE.'],

            // ---- Customer panel — visibility toggles ---------------------------
            ['name' => 'show_overview',     'label' => 'Tab: Overview',          'type' => 'checkbox', 'default' => true,
             'description' => 'Always recommended — shows status, allocation, basic resource usage.'],

            ['name' => 'show_console',      'label' => 'Tab: Console',           'type' => 'checkbox',
             'description' => 'Power management (Start/Stop/Restart/Kill) and live console output.'],

            ['name' => 'show_send_command', 'label' => '↳ Allow Send Command',   'type' => 'checkbox',
             'description' => 'Within the Console tab, let the customer send arbitrary commands.'],

            ['name' => 'show_files',        'label' => 'Tab: Files',             'type' => 'checkbox',
             'description' => 'File manager — list/read/write/upload/download/compress/decompress/delete.'],

            ['name' => 'show_databases',    'label' => 'Tab: Databases',         'type' => 'checkbox',
             'description' => 'Database manager — list/create/rotate-password/delete.'],

            ['name' => 'show_backups',      'label' => 'Tab: Backups',           'type' => 'checkbox',
             'description' => 'Backup manager — list/create/restore/delete/download.'],

            ['name' => 'show_schedules',    'label' => 'Tab: Schedules',         'type' => 'checkbox',
             'description' => 'Scheduled tasks (cron) — list/create/edit/delete.'],

            ['name' => 'show_subusers',     'label' => 'Tab: Subusers',          'type' => 'checkbox',
             'description' => 'Add/remove subusers with permission scopes.'],

            ['name' => 'show_network',      'label' => 'Tab: Network',           'type' => 'checkbox',
             'description' => 'Manage allocations (ports) bound to the server.'],

            ['name' => 'show_startup_vars', 'label' => 'Tab: Startup Variables', 'type' => 'checkbox',
             'description' => 'Edit user-editable egg variables.'],

            ['name' => 'show_settings',     'label' => 'Tab: Settings',          'type' => 'checkbox',
             'description' => 'Rename server, regenerate SFTP password, reinstall.'],

            ['name' => 'show_reinstall',    'label' => '↳ Allow Reinstall',      'type' => 'checkbox',
             'description' => 'Within the Settings tab, expose the destructive Reinstall button.'],

            ['name' => 'show_activity',     'label' => 'Tab: Activity',          'type' => 'checkbox',
             'description' => 'Read-only activity log of recent server events.'],
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

    private function eggEnvironment(int $eggId, string $ip, string $port): array
    {
        return match ($eggId) {
            4 => ['N8N_HOST' => $ip, 'N8N_PORT' => $port],
            5 => ['EXTERNAL_IP' => $ip, 'OPENCLAW_GATEWAY_PORT' => $port],
            default => [],
        };
    }

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

    /**
     * Parse "KEY=VALUE\nKEY2=VALUE2" textarea into an env array.
     */
    private function parseEnvOverrides(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', trim($raw)) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            $k = trim(substr($line, 0, $eq));
            $v = trim(substr($line, $eq + 1));
            if ($k !== '') $out[$k] = $v;
        }
        return $out;
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
    // Public API surface used by Livewire tab components
    // =========================================================================

    public function getServer(int $serviceId, int $eggId, bool $failIfNotFound = false): array|false
    {
        return $this->getPanelServer($serviceId, $eggId, $failIfNotFound);
    }

    public function getEggName(int $eggId): string
    {
        return $this->eggName($eggId);
    }

    public function getServerResources(string $uuid): array
    {
        return $this->clientRequest('/api/client/servers/' . $uuid . '/resources')['attributes'] ?? [];
    }

    public function powerServer(string $uuid, string $signal): void
    {
        $this->clientRequest('/api/client/servers/' . $uuid . '/power', 'post', ['signal' => $signal]);
    }

    public function sendCommand(string $uuid, string $command): void
    {
        $this->clientRequest('/api/client/servers/' . $uuid . '/command', 'post', ['command' => $command]);
    }

    public function reinstallPanelServer(int $serverId): void
    {
        $this->request('/api/application/servers/' . $serverId . '/reinstall', 'post');
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

            $envOverrides = $this->parseEnvOverrides((string) ($settings['override_environment'] ?? ''));

            $limits = [
                'memory'  => (int)  ($settings['limit_memory']  ?? 0),
                'swap'    => (int)  ($settings['limit_swap']    ?? 0),
                'disk'    => (int)  ($settings['limit_disk']    ?? 0),
                'io'      => (int)  ($settings['limit_io']      ?? 500),
                'cpu'     => (int)  ($settings['limit_cpu']     ?? 0),
                'threads' => trim((string) ($settings['limit_threads'] ?? '')) !== ''
                    ? (string) $settings['limit_threads']
                    : null,
            ];

            $featureLimits = [
                'databases'   => (int) ($settings['feature_databases']   ?? 0),
                'allocations' => (int) ($settings['feature_allocations'] ?? 0),
                'backups'     => (int) ($settings['feature_backups']     ?? 0),
            ];

            $results = [];

            foreach ([$selectedEgg] as $eggId) {
                if ($this->getPanelServer($service->id, $eggId, false) !== false) {
                    continue;
                }

                $allocation = $this->findAvailableAllocation($node['id']);
                $ip   = (! empty($allocation['ip_alias'])) ? $allocation['ip_alias'] : $allocation['ip'];
                $port = (string) $allocation['port'];

                $eggData = $this->request('/api/application/eggs/' . $eggId, 'get', ['include' => 'variables']);
                if (! isset($eggData['attributes'])) {
                    throw new Exception('Could not fetch egg data for egg ' . $eggId);
                }

                $environment = array_merge(
                    $this->fetchEggDefaults((int) $eggId, $eggData),
                    $this->eggEnvironment((int) $eggId, $ip, $port),
                    $envOverrides
                );

                $dockerImage = trim((string) ($settings['override_docker_image'] ?? '')) !== ''
                    ? (string) $settings['override_docker_image']
                    : $eggData['attributes']['docker_image'];

                $startup = trim((string) ($settings['override_startup'] ?? '')) !== ''
                    ? (string) $settings['override_startup']
                    : $eggData['attributes']['startup'];

                $created = $this->request('/api/application/servers', 'post', [
                    'external_id'         => $service->id . '_' . $eggId,
                    'name'                => $this->eggName($eggId) . ' — ' . $service->product->name . ' #' . $service->id,
                    'user'                => $userId,
                    'egg'                 => $eggId,
                    'docker_image'        => $dockerImage,
                    'startup'             => $startup,
                    'environment'         => $environment,
                    'skip_scripts'        => $skipScripts,
                    'oom_killer'          => (bool) ($settings['limit_oom_killer'] ?? false),
                    'limits'              => $limits,
                    'feature_limits'      => $featureLimits,
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
    // Customer-area integration: tabs (type=view) + external links (type=button)
    // =========================================================================

    public function getActions(Service $service, $settings, $properties): array
    {
        $actions = [];
        $eggIds  = $this->selectedEggIds($settings, $properties);

        // External link buttons: deployed app URL via NPM (if configured).
        foreach ($eggIds as $index => $eggId) {
            if ($index >= 8) break;

            $server = $this->getPanelServer($service->id, $eggId, false);
            if ($server === false) continue;

            $cachedDomain = Cache::get('pelican_domain_' . $service->id . '_' . $eggId);
            if ($cachedDomain) {
                $actions[] = [
                    'type'   => 'button',
                    'label'  => 'Open ' . $this->eggName($eggId),
                    'url'    => 'https://' . $cachedDomain,
                    'target' => '_blank',
                ];
            }
        }

        // Inline tabs — driven by `show_*` toggles in product config.
        // Overview defaults to ON when no toggles are saved yet (back-compat).
        $hasAnyToggle = false;
        foreach (self::TABS as $key => $_) {
            if (array_key_exists($key, $settings)) { $hasAnyToggle = true; break; }
        }

        foreach (self::TABS as $key => [$component, $label]) {
            $enabled = $hasAnyToggle ? (bool) ($settings[$key] ?? false) : ($key === 'show_overview');
            if (! $enabled) continue;

            $actions[] = [
                'type'  => 'view',
                'name'  => $component,        // doubles as the Livewire component alias
                'label' => $label,
            ];
        }

        return $actions;
    }

    /**
     * Renders the tab body. Returns a Blade fragment that embeds the matching
     * Livewire component — Show.php's render() converts this to HTML inline.
     */
    public function getView(Service $service, $settings, $properties, $view)
    {
        $enabledKey = null;
        foreach (self::TABS as $key => [$alias, $_]) {
            if ($alias === $view) { $enabledKey = $key; break; }
        }
        if ($enabledKey === null) {
            return view('pelican::tab-error', ['message' => 'Unknown tab: ' . $view]);
        }

        // Defense-in-depth against URL tampering — getActions() already filters.
        $hasAnyToggle = false;
        foreach (self::TABS as $k => $_) {
            if (array_key_exists($k, $settings)) { $hasAnyToggle = true; break; }
        }
        $enabled = $hasAnyToggle ? (bool) ($settings[$enabledKey] ?? false) : ($enabledKey === 'show_overview');

        if (! $enabled) {
            return view('pelican::tab-error', ['message' => 'This tab is disabled for this product.']);
        }

        return view('pelican::tab-mount', [
            'component' => $view,
            'service'   => $service,
        ]);
    }
}
