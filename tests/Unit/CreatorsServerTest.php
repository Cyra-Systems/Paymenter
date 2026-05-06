<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use Paymenter\Extensions\Servers\Creators\Creators;
use ReflectionClass;
use Tests\TestCase;

/**
 * Verifies the Cyra Creators Server extension produces the right HTTP shape
 * (HMAC headers + payload schema) when called by Paymenter's lifecycle hooks.
 *
 * Network is faked via `Http::fake()` so no real Creators instance is needed.
 */
class CreatorsServerTest extends TestCase
{
    protected bool $seed = false;

    public function test_get_config_lists_required_fields(): void
    {
        $extension = $this->makeExtension();

        $config = $extension->getConfig();
        $names = array_column($config, 'name');

        $this->assertContains('host', $names);
        $this->assertContains('api_key', $names);
        $this->assertContains('hmac_secret', $names);
        $this->assertContains('default_processing_fee_bps', $names);

        $apiKey = collect($config)->firstWhere('name', 'api_key');
        $hmac = collect($config)->firstWhere('name', 'hmac_secret');
        $this->assertTrue($apiKey['encrypted'] ?? false, 'api_key must be marked encrypted');
        $this->assertTrue($hmac['encrypted'] ?? false, 'hmac_secret must be marked encrypted');
    }

    public function test_get_product_config_covers_caps_features_and_storefront(): void
    {
        $extension = $this->makeExtension();

        $names = array_column($extension->getProductConfig(), 'name');

        // Caps
        foreach (['storage_gb', 'bandwidth_gb_month', 'max_moderators', 'max_groups',
            'max_subgroups_per_group', 'max_content_items', 'max_live_concurrent',
            'max_dms_per_day', 'max_products'] as $cap) {
            $this->assertContains($cap, $names, "missing cap field: {$cap}");
        }

        // Feature flags
        foreach (['feature_live_streaming', 'feature_pay_per_view', 'feature_subscriptions',
            'feature_groups', 'feature_user_to_user_dms', 'feature_affiliate_links',
            'feature_custom_domain', 'feature_rest_api', 'feature_storefront',
            'feature_storefront_physical', 'feature_storefront_digital',
            'feature_storefront_services', 'feature_storefront_subscriptions'] as $feature) {
            $this->assertContains($feature, $names, "missing feature field: {$feature}");
        }

        // Rate limits
        $this->assertContains('rate_uploads_per_minute', $names);
        $this->assertContains('rate_api_per_minute', $names);

        // Billing + domain
        $this->assertContains('processing_fee_bps', $names);
        $this->assertContains('domain_mode', $names);
        $this->assertContains('domain_value', $names);
    }

    public function test_test_config_returns_true_when_health_endpoint_responds(): void
    {
        Http::fake([
            'https://creators.local/_health' => Http::response(['status' => 'ok'], 200),
        ]);

        $extension = $this->makeExtension();
        $this->assertTrue($extension->testConfig());
    }

    public function test_test_config_returns_error_message_on_failure(): void
    {
        Http::fake([
            'https://creators.local/_health' => Http::response(['error' => 'down'], 500),
        ]);

        $extension = $this->makeExtension();
        $result = $extension->testConfig();

        $this->assertIsString($result);
        $this->assertStringContainsString('500', $result);
    }

    public function test_request_signs_with_hmac_and_required_headers(): void
    {
        Http::fake([
            'https://creators.local/api/v1/provisioning/tenants*' => Http::response(['external_id' => 'svc-1'], 200),
        ]);

        $extension = $this->makeExtension();

        // Invoke the protected request() via reflection so we don't need a full
        // Service factory just to exercise the HTTP-shape contract.
        $invoke = (new ReflectionClass($extension))->getMethod('request');
        $invoke->setAccessible(true);

        $invoke->invoke($extension, '/api/v1/provisioning/tenants', 'POST', ['external_id' => 'svc-1', 'plan' => ['code' => 'creator-pro']]);

        Http::assertSent(function ($request) {
            $sig = $request->header('X-Creators-Sig')[0] ?? null;
            $ts = $request->header('X-Creators-Timestamp')[0] ?? null;
            $auth = $request->header('Authorization')[0] ?? null;

            // Headers present
            if (!$sig || !$ts || !$auth) {
                return false;
            }

            // Bearer token
            if ($auth !== 'Bearer test-api-key') {
                return false;
            }

            // Signature is hex_hmac_sha256(timestamp + raw_body, secret)
            $expected = hash_hmac('sha256', $ts . $request->body(), 'test-hmac-secret');

            return hash_equals($expected, $sig);
        });
    }

    public function test_payload_includes_external_id_and_full_caps_features_schema(): void
    {
        Http::fake([
            'https://creators.local/api/v1/provisioning/tenants*' => Http::response(['external_id' => '99'], 200),
        ]);

        $extension = $this->makeExtension();

        $service = (object) [
            'id' => 99,
            'product' => (object) ['name' => 'Creator Pro'],
            'plan' => (object) ['billing_period' => 1, 'billing_unit' => 'month'],
            'user' => (object) ['email' => 'alice@example.com', 'first_name' => 'Alice', 'last_name' => 'Doe'],
        ];

        $settings = [
            ['name' => 'plan_code', 'value' => 'creator-pro'],
            ['name' => 'storage_gb', 'value' => 250],
            ['name' => 'feature_live_streaming', 'value' => true],
            ['name' => 'processing_fee_bps', 'value' => 750],
            ['name' => 'domain_mode', 'value' => 'subdomain'],
            ['name' => 'domain_value', 'value' => 'alice'],
        ];

        // Build payload via reflection (same path createServer takes).
        $build = (new ReflectionClass($extension))->getMethod('servicePayload');
        $build->setAccessible(true);
        $payload = $build->invoke($extension, $service, $settings, []);

        $this->assertSame('99', $payload['external_id']);
        $this->assertSame('alice@example.com', $payload['owner']['email']);
        $this->assertSame('creator-pro', $payload['plan']['code']);
        $this->assertSame('Creator Pro', $payload['plan']['label']);
        $this->assertSame(250, $payload['caps']['storage_gb']);
        $this->assertTrue($payload['features']['live_streaming']);
        $this->assertSame(750, $payload['processing_fee_bps']);
        $this->assertSame('subdomain', $payload['domain']['mode']);
        $this->assertSame('alice', $payload['domain']['value']);

        // Smoke-check the schema is complete enough to match docs/provisioning.md.
        foreach (['storage_gb', 'bandwidth_gb_month', 'max_moderators', 'max_groups',
            'max_subgroups_per_group', 'max_content_items', 'max_live_concurrent',
            'max_dms_per_day', 'max_products'] as $cap) {
            $this->assertArrayHasKey($cap, $payload['caps']);
        }
        foreach (['live_streaming', 'pay_per_view', 'subscriptions', 'tips', 'groups',
            'user_to_user_dms', 'affiliate_links', 'custom_domain', 'rest_api',
            'storefront', 'storefront_physical', 'storefront_digital',
            'storefront_services', 'storefront_subscriptions'] as $feature) {
            $this->assertArrayHasKey($feature, $payload['features']);
        }
    }

    /**
     * Build a Creators extension with stub config so we can call protected
     * methods directly without hitting the Paymenter extension settings table.
     */
    protected function makeExtension(): Creators
    {
        $extension = new class extends Creators
        {
            public function config($key)
            {
                return [
                    'host' => 'https://creators.local',
                    'api_key' => 'test-api-key',
                    'hmac_secret' => 'test-hmac-secret',
                    'default_processing_fee_bps' => 500,
                ][$key] ?? null;
            }
        };

        return $extension;
    }
}
