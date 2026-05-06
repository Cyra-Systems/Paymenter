<?php

namespace Paymenter\Extensions\Servers\Creators;

use App\Classes\Extension\Server;
use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Cyra Creators provisioning extension.
 *
 * Provisions a tenant on a Cyra Creators platform when a Paymenter customer
 * orders a Creator-* plan. Lifecycle (create/suspend/unsuspend/terminate/
 * upgrade) is forwarded to the Creators provisioning REST API. All requests
 * are HMAC-signed:
 *
 *   Authorization:        Bearer <api_key>
 *   X-Creators-Sig:       hex_hmac_sha256(timestamp + raw_body, hmac_secret)
 *   X-Creators-Timestamp: unix
 *
 * Idempotent on the Paymenter Service.id (sent as `external_id`).
 */
class Creators extends Server
{
    public function getConfig($values = []): array
    {
        return [
            [
                'name' => 'host',
                'label' => 'Creators API URL',
                'type' => 'text',
                'description' => 'Base URL of the Creators platform (e.g. https://creators.cyra.app).',
                'required' => true,
                'validation' => 'url',
            ],
            [
                'name' => 'api_key',
                'label' => 'Creators API Key (Bearer)',
                'type' => 'text',
                'description' => 'CREATORS_PROV_TOKEN from the Creators .env.',
                'required' => true,
                'encrypted' => true,
            ],
            [
                'name' => 'hmac_secret',
                'label' => 'HMAC Signing Secret',
                'type' => 'text',
                'description' => 'CREATORS_PROV_SECRET from the Creators .env.',
                'required' => true,
                'encrypted' => true,
            ],
            [
                'name' => 'default_processing_fee_bps',
                'label' => 'Default Processing Fee (basis points)',
                'type' => 'number',
                'description' => '500 = 5.00% — overrideable per product.',
                'default' => 500,
            ],
        ];
    }

    public function testConfig(): bool|string
    {
        try {
            $this->request('/_health', 'GET');

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getProductConfig($values = []): array
    {
        return [
            ['name' => 'plan_code', 'label' => 'Plan code', 'type' => 'text', 'required' => true, 'description' => 'e.g. creator-pro, creator-basic'],

            // Caps (hard limits)
            ['name' => 'storage_gb', 'label' => 'Storage GB', 'type' => 'number', 'required' => true, 'default' => 100],
            ['name' => 'bandwidth_gb_month', 'label' => 'Bandwidth GB/mo', 'type' => 'number', 'required' => true, 'default' => 1000],
            ['name' => 'max_moderators', 'label' => 'Max moderators', 'type' => 'number', 'default' => 5],
            ['name' => 'max_groups', 'label' => 'Max groups', 'type' => 'number', 'default' => 50],
            ['name' => 'max_subgroups_per_group', 'label' => 'Max subgroups per group', 'type' => 'number', 'default' => 10],
            ['name' => 'max_content_items', 'label' => 'Max content items', 'type' => 'number', 'default' => 100000],
            ['name' => 'max_live_concurrent', 'label' => 'Max concurrent live streams', 'type' => 'number', 'default' => 1],
            ['name' => 'max_dms_per_day', 'label' => 'Max DMs per user per day', 'type' => 'number', 'default' => 5000],
            ['name' => 'max_products', 'label' => 'Max products in storefront', 'type' => 'number', 'default' => 500],

            // Features (toggles)
            ['name' => 'feature_live_streaming', 'label' => 'Live streaming', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_pay_per_view', 'label' => 'Pay-per-view', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_subscriptions', 'label' => 'Subscriptions', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_groups', 'label' => 'Groups', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_user_to_user_dms', 'label' => 'User-to-user DMs allowed', 'type' => 'checkbox', 'default' => false],
            ['name' => 'feature_affiliate_links', 'label' => 'Affiliate links', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_custom_domain', 'label' => 'Custom domain', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_rest_api', 'label' => 'REST API', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_storefront', 'label' => 'Storefront (master)', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_storefront_physical', 'label' => 'Storefront: physical goods', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_storefront_digital', 'label' => 'Storefront: digital downloads', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_storefront_services', 'label' => 'Storefront: services / bookings', 'type' => 'checkbox', 'default' => true],
            ['name' => 'feature_storefront_subscriptions', 'label' => 'Storefront: recurring subscriptions', 'type' => 'checkbox', 'default' => true],

            // Rate limits
            ['name' => 'rate_uploads_per_minute', 'label' => 'Uploads per minute', 'type' => 'number', 'default' => 30],
            ['name' => 'rate_api_per_minute', 'label' => 'API requests per minute', 'type' => 'number', 'default' => 600],

            // Billing
            ['name' => 'processing_fee_bps', 'label' => 'Processing fee (basis points)', 'type' => 'number', 'default' => 500, 'description' => '500 = 5.00%'],

            // Domain
            ['name' => 'domain_mode', 'label' => 'Domain mode', 'type' => 'select', 'options' => ['subdomain' => 'Subdomain on creators.cyra.app', 'custom' => 'Custom domain'], 'default' => 'subdomain'],
            ['name' => 'domain_value', 'label' => 'Domain value', 'type' => 'text', 'description' => 'Subdomain slug or full custom hostname', 'required' => true],
        ];
    }

    public function createServer(Service $service, $settings, $properties): bool
    {
        $this->request('/api/v1/provisioning/tenants', 'POST', $this->servicePayload($service, $settings, $properties));

        return true;
    }

    public function suspendServer(Service $service, $settings, $properties): bool
    {
        $this->request('/api/v1/provisioning/tenants/' . $service->id . '/suspend', 'POST');

        return true;
    }

    public function unsuspendServer(Service $service, $settings, $properties): bool
    {
        $this->request('/api/v1/provisioning/tenants/' . $service->id . '/unsuspend', 'POST');

        return true;
    }

    public function terminateServer(Service $service, $settings, $properties): bool
    {
        $this->request('/api/v1/provisioning/tenants/' . $service->id, 'DELETE');

        return true;
    }

    public function upgradeServer(Service $service, $settings, $properties): bool
    {
        $this->request('/api/v1/provisioning/tenants/' . $service->id, 'PATCH', $this->servicePayload($service, $settings, $properties));

        return true;
    }

    public function getActions(Service $service, $settings, $properties): array
    {
        return [
            [
                'name' => 'admin_panel',
                'label' => 'Open Creator Admin',
                'url' => $this->resolveDomain($settings, $properties)['admin_url'] ?? rtrim($this->config('host'), '/') . '/admin',
            ],
        ];
    }

    /**
     * HMAC-signed request to the Creators provisioning API.
     */
    protected function request(string $path, string $method = 'GET', array $body = []): array
    {
        $url = rtrim($this->config('host'), '/') . $path;
        $timestamp = (string) time();
        $rawBody = $body === [] ? '' : (string) json_encode($body);
        $sig = hash_hmac('sha256', $timestamp . $rawBody, (string) $this->config('hmac_secret'));

        $req = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('api_key'),
            'X-Creators-Sig' => $sig,
            'X-Creators-Timestamp' => $timestamp,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(15);

        $response = match (strtoupper($method)) {
            'GET' => $req->get($url),
            'POST' => $req->post($url, $body),
            'PATCH' => $req->patch($url, $body),
            'DELETE' => $req->delete($url),
            default => throw new Exception("Unsupported HTTP method {$method}"),
        };

        if (!$response->successful()) {
            $detail = $response->json('error') ?? $response->body();
            throw new Exception("Creators API error {$response->status()} on {$method} {$path}: {$detail}");
        }

        return (array) ($response->json() ?? []);
    }

    /**
     * Build the payload Creators expects on createServer/upgradeServer.
     *
     * Accepts any object exposing `id`, `product`, `plan`, `user` properties —
     * not strictly typed to App\Models\Service so the test suite can pass
     * lightweight stdClass fixtures without booting the full DB.
     */
    protected function servicePayload($service, $settings, $properties): array
    {
        $config = $this->mergedConfig($settings, $properties);

        return [
            'external_id' => (string) $service->id,
            'owner' => [
                'email' => $service->user->email,
                'first_name' => $service->user->first_name ?? $service->user->name ?? 'Creator',
                'last_name' => $service->user->last_name ?? '',
            ],
            'plan' => [
                'code' => (string) ($config['plan_code'] ?? 'creator-basic'),
                'label' => (string) ($service->product->name ?? 'Creator Plan'),
                'billing_cycle' => $this->billingCycle($service),
            ],
            'caps' => [
                'storage_gb' => (int) ($config['storage_gb'] ?? 100),
                'bandwidth_gb_month' => (int) ($config['bandwidth_gb_month'] ?? 1000),
                'max_moderators' => (int) ($config['max_moderators'] ?? 5),
                'max_groups' => (int) ($config['max_groups'] ?? 50),
                'max_subgroups_per_group' => (int) ($config['max_subgroups_per_group'] ?? 10),
                'max_content_items' => (int) ($config['max_content_items'] ?? 100000),
                'max_live_concurrent' => (int) ($config['max_live_concurrent'] ?? 1),
                'max_dms_per_day' => (int) ($config['max_dms_per_day'] ?? 5000),
                'max_products' => (int) ($config['max_products'] ?? 500),
            ],
            'features' => [
                'live_streaming' => (bool) ($config['feature_live_streaming'] ?? true),
                'pay_per_view' => (bool) ($config['feature_pay_per_view'] ?? true),
                'subscriptions' => (bool) ($config['feature_subscriptions'] ?? true),
                'tips' => true,
                'groups' => (bool) ($config['feature_groups'] ?? true),
                'user_to_user_dms' => (bool) ($config['feature_user_to_user_dms'] ?? false),
                'affiliate_links' => (bool) ($config['feature_affiliate_links'] ?? true),
                'custom_domain' => (bool) ($config['feature_custom_domain'] ?? true),
                'rest_api' => (bool) ($config['feature_rest_api'] ?? true),
                'client_transcoding' => true,
                'playlist_label_override' => true,
                'course_mode' => false,
                'storefront' => (bool) ($config['feature_storefront'] ?? true),
                'storefront_physical' => (bool) ($config['feature_storefront_physical'] ?? true),
                'storefront_digital' => (bool) ($config['feature_storefront_digital'] ?? true),
                'storefront_services' => (bool) ($config['feature_storefront_services'] ?? true),
                'storefront_subscriptions' => (bool) ($config['feature_storefront_subscriptions'] ?? true),
            ],
            'rate_limits' => [
                'uploads_per_minute' => (int) ($config['rate_uploads_per_minute'] ?? 30),
                'api_per_minute' => (int) ($config['rate_api_per_minute'] ?? 600),
            ],
            'branding' => ['playlist_label' => 'Playlists'],
            'processing_fee_bps' => (int) ($config['processing_fee_bps'] ?? $this->config('default_processing_fee_bps') ?? 500),
            'domain' => $this->resolveDomain($settings, $properties),
        ];
    }

    /**
     * Merge product->settings + service_configs into a flat array. The
     * `$settings` (product-level) and `$properties` (service-level) shapes
     * vary by Paymenter version; we accept either array or object collections.
     */
    protected function mergedConfig($settings, $properties): array
    {
        return array_merge($this->toAssoc($settings), $this->toAssoc($properties));
    }

    protected function toAssoc($input): array
    {
        if (is_object($input) && method_exists($input, 'toArray')) {
            $input = $input->toArray();
        }

        if (!is_iterable($input)) {
            return [];
        }

        // Detect the "list of {name, value}" shape used by Paymenter's
        // service_configs / product->settings collections. If the first row
        // looks like {name, value}, walk every row and key by name.
        $rows = is_array($input) ? $input : iterator_to_array($input);

        if ($rows === []) {
            return [];
        }

        $first = reset($rows);
        $isNameValueList = (is_array($first) && array_key_exists('name', $first))
            || (is_object($first) && property_exists($first, 'name'));

        if ($isNameValueList) {
            $out = [];
            foreach ($rows as $row) {
                if (is_array($row) && array_key_exists('name', $row)) {
                    $out[$row['name']] = $row['value'] ?? null;
                } elseif (is_object($row) && property_exists($row, 'name')) {
                    $out[$row->name] = $row->value ?? null;
                }
            }

            return $out;
        }

        // Otherwise assume it's already an associative array (key => value).
        return $rows;
    }

    protected function resolveDomain($settings, $properties): array
    {
        $config = $this->mergedConfig($settings, $properties);
        $mode = (string) ($config['domain_mode'] ?? 'subdomain');
        $value = (string) ($config['domain_value'] ?? '');

        return ['mode' => $mode, 'value' => $value];
    }

    protected function billingCycle($service): string
    {
        $period = strtolower((string) ($service->plan->billing_period ?? $service->billing_period ?? 'monthly'));

        return match (true) {
            str_contains($period, 'year') => 'yearly',
            str_contains($period, 'lifetime') => 'lifetime',
            default => 'monthly',
        };
    }
}
