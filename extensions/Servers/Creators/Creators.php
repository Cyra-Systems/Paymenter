<?php

namespace Paymenter\Extensions\Servers\Creators;

use App\Classes\Extension\Server;
use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Cyra Creators Server extension.
 *
 * Treats a Cyra Creators tenant as a Paymenter "server resource". Ordering a
 * Paymenter product wired to this driver provisions a tenant via the
 * HMAC-signed REST API. Suspending the service blocks tenant logins;
 * terminating soft-deletes it.
 *
 * Authentication: bearer token + HMAC-SHA256 signature over
 * `timestamp + raw_body` using the shared secret. Mirrors
 * App\Http\Middleware\VerifyHmacSignature on the Creators side.
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
                'description' => 'Base URL of the Cyra Creators install (e.g. https://api.creators.cyra.app).',
                'required' => true,
                'validation' => 'url',
            ],
            [
                'name' => 'api_token',
                'label' => 'Provisioning API Token',
                'type' => 'text',
                'description' => 'Bearer token (CREATORS_PROV_TOKEN on the Creators side).',
                'required' => true,
                'encrypted' => true,
            ],
            [
                'name' => 'hmac_secret',
                'label' => 'HMAC Secret',
                'type' => 'text',
                'description' => 'Shared secret for X-Creators-Sig (CREATORS_PROV_SECRET on the Creators side).',
                'required' => true,
                'encrypted' => true,
            ],
            [
                'name' => 'default_plan_code',
                'label' => 'Default Plan Code',
                'type' => 'text',
                'description' => 'Plan code applied when a product does not override.',
                'required' => false,
            ],
        ];
    }

    public function testConfig(): bool|string
    {
        try {
            $response = Http::baseUrl(rtrim($this->config('host'), '/'))
                ->timeout(8)
                ->get('/_health');

            if (!$response->successful()) {
                return "Health check returned {$response->status()}";
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    public function getProductConfig($values = []): array
    {
        return [
            [
                'name' => 'plan_code',
                'label' => 'Plan code',
                'type' => 'text',
                'description' => 'Overrides the default plan_code for this product.',
            ],
            [
                'name' => 'features_storefront',
                'label' => 'Storefront enabled',
                'type' => 'checkbox',
                'description' => 'Enable the WhatsApp-style storefront on tenants ordering this product.',
            ],
            [
                'name' => 'features_lives',
                'label' => 'Live streaming enabled',
                'type' => 'checkbox',
            ],
            [
                'name' => 'features_pay_per_view',
                'label' => 'Pay-per-view enabled',
                'type' => 'checkbox',
            ],
            [
                'name' => 'caps_max_storage_gb',
                'label' => 'Max storage (GB)',
                'type' => 'number',
            ],
            [
                'name' => 'caps_max_lives_per_month',
                'label' => 'Max lives per month',
                'type' => 'number',
            ],
            [
                'name' => 'processing_fee_bps',
                'label' => 'Processing fee (basis points)',
                'type' => 'number',
                'description' => 'Cyras per-charge skim. 500 = 5.00%.',
            ],
        ];
    }

    public function createServer(Service $service, $settings, $properties)
    {
        $payload = [
            'external_id' => 'paymenter-svc-' . $service->id,
            'plan_code' => $settings['plan_code'] ?? $this->config('default_plan_code') ?? 'creator-basic',
            'host' => $properties['host'] ?? null,
            'processing_fee_bps' => isset($settings['processing_fee_bps']) ? (int) $settings['processing_fee_bps'] : 500,
            'caps' => [
                'max_storage_gb' => (int) ($settings['caps_max_storage_gb'] ?? 50),
                'max_lives_per_month' => (int) ($settings['caps_max_lives_per_month'] ?? 5),
            ],
            'features' => [
                'storefront' => (bool) ($settings['features_storefront'] ?? false),
                'lives' => (bool) ($settings['features_lives'] ?? false),
                'pay_per_view' => (bool) ($settings['features_pay_per_view'] ?? false),
            ],
            'rate_limits' => [],
            'branding' => [],
        ];

        return $this->signedPost('/api/v1/provisioning/tenants', $payload);
    }

    public function suspendServer(Service $service, $settings, $properties)
    {
        return $this->signedPost("/api/v1/provisioning/tenants/paymenter-svc-{$service->id}/suspend", []);
    }

    public function unsuspendServer(Service $service, $settings, $properties)
    {
        return $this->signedPost("/api/v1/provisioning/tenants/paymenter-svc-{$service->id}/unsuspend", []);
    }

    public function terminateServer(Service $service, $settings, $properties)
    {
        return $this->signedDelete("/api/v1/provisioning/tenants/paymenter-svc-{$service->id}");
    }

    public function upgradeServer(Service $service, $settings, $properties)
    {
        return $this->signedPatch("/api/v1/provisioning/tenants/paymenter-svc-{$service->id}", [
            'plan_code' => $settings['plan_code'] ?? null,
            'caps' => [
                'max_storage_gb' => (int) ($settings['caps_max_storage_gb'] ?? 0),
                'max_lives_per_month' => (int) ($settings['caps_max_lives_per_month'] ?? 0),
            ],
            'features' => [
                'storefront' => (bool) ($settings['features_storefront'] ?? false),
                'lives' => (bool) ($settings['features_lives'] ?? false),
                'pay_per_view' => (bool) ($settings['features_pay_per_view'] ?? false),
            ],
        ]);
    }

    public function getActions(Service $service)
    {
        $host = rtrim($this->config('host'), '/');

        return [
            [
                'label' => 'Open tenant admin',
                'url' => "{$host}/admin",
            ],
        ];
    }

    public function signature(int $timestamp, string $body): string
    {
        return hash_hmac('sha256', ((string) $timestamp) . $body, (string) $this->config('hmac_secret'));
    }

    private function signedPost(string $path, array $payload)
    {
        return $this->signed('post', $path, $payload);
    }

    private function signedPatch(string $path, array $payload)
    {
        return $this->signed('patch', $path, $payload);
    }

    private function signedDelete(string $path)
    {
        return $this->signed('delete', $path, []);
    }

    private function signed(string $method, string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = time();
        $signature = $this->signature($timestamp, $body);

        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('api_token'),
            'X-Creators-Sig' => $signature,
            'X-Creators-Timestamp' => (string) $timestamp,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->withBody($body, 'application/json');

        $response = $request->{$method}(rtrim($this->config('host'), '/') . $path);

        if (!$response->successful()) {
            throw new Exception("Creators {$method} {$path} returned {$response->status()}: " . $response->body());
        }

        return $response->json() ?? [];
    }
}
