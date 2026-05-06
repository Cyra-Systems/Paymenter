<?php

namespace Tests\Unit;

use Paymenter\Extensions\Servers\Creators\Creators;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-level coverage for the Creators server extension. Excercises the
 * config schema, HMAC signing, and product-config map without hitting
 * the network. Network round-trip lives in the Creators-side test suite.
 *
 * Extends PHPUnit's TestCase directly (not Tests\TestCase) so it doesn't
 * require a database connection — the extension class is pure PHP.
 */
class CreatorsServerTest extends TestCase
{
    private function driver(array $config = []): Creators
    {
        $defaults = [
            'host' => 'http://creators.local',
            'api_token' => 'shared-token',
            'hmac_secret' => 'shared-secret',
            'default_plan_code' => 'creator-basic',
        ];

        return new Creators(array_merge($defaults, $config));
    }

    #[Test]
    public function config_schema_declares_required_provisioning_fields(): void
    {
        $fields = collect($this->driver()->getConfig())->pluck('name')->all();

        $this->assertContains('host', $fields);
        $this->assertContains('api_token', $fields);
        $this->assertContains('hmac_secret', $fields);
        $this->assertContains('default_plan_code', $fields);
    }

    #[Test]
    public function product_config_exposes_plan_code_caps_and_features(): void
    {
        $fields = collect($this->driver()->getProductConfig())->pluck('name')->all();

        $this->assertContains('plan_code', $fields);
        $this->assertContains('features_storefront', $fields);
        $this->assertContains('caps_max_storage_gb', $fields);
        $this->assertContains('processing_fee_bps', $fields);
    }

    #[Test]
    public function hmac_signature_matches_creators_side_format(): void
    {
        $driver = $this->driver(['hmac_secret' => 'shared-secret']);

        $body = '{"hello":"world"}';
        $timestamp = 1_700_000_000;
        $expected = hash_hmac('sha256', $timestamp . $body, 'shared-secret');

        $this->assertSame($expected, $driver->signature($timestamp, $body));
    }
}
