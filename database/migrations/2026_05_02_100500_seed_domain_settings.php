<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['key' => 'domains.enom_username', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.enom_password', 'value' => '', 'type' => 'string', 'encrypted' => true],
            ['key' => 'domains.enom_sandbox', 'value' => '1', 'type' => 'boolean', 'encrypted' => false],
            ['key' => 'domains.default_nameservers', 'value' => json_encode([]), 'type' => 'json', 'encrypted' => false],
            ['key' => 'domains.subdomain_base', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.npm_url', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.npm_email', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.npm_password', 'value' => '', 'type' => 'string', 'encrypted' => true],
            ['key' => 'domains.npm_default_certificate_id', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.npm_proxy_target_host', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.npm_letsencrypt_email', 'value' => '', 'type' => 'string', 'encrypted' => false],
            ['key' => 'domains.npm_dns_provider', 'value' => json_encode([]), 'type' => 'json', 'encrypted' => false],
            ['key' => 'domains.global_margin_percent', 'value' => '0', 'type' => 'string', 'encrypted' => false],
        ];

        foreach ($defaults as $row) {
            Setting::query()->updateOrCreate(
                ['key' => $row['key'], 'settingable_type' => null, 'settingable_id' => null],
                $row,
            );
        }
    }

    public function down(): void
    {
        Setting::query()
            ->whereNull('settingable_type')
            ->where('key', 'like', 'domains.%')
            ->delete();
    }
};
