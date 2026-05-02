<?php

namespace App\Services\Domains;

use App\Models\DomainSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Tiny key/value store for the Enom integration.
 *
 * Backed by the domain_settings table. Encrypted values are transparently
 * decrypted on read and re-encrypted on write. Reads are cached for the
 * lifetime of the request.
 */
class DomainSettings
{
    /** @var array<string, string|null> */
    protected static array $cache = [];

    /** Hard-coded list of which keys should be stored encrypted. */
    protected const ENCRYPTED = [
        'enom_password',
        'enom_api_key',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key] ?? $default;
        }

        $row = DomainSetting::where('key', $key)->first();
        if (!$row) {
            return self::$cache[$key] = $default;
        }

        $value = $row->encrypted ? Crypt::decryptString($row->value) : $row->value;
        return self::$cache[$key] = $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $encrypted = in_array($key, self::ENCRYPTED, true);
        $stored = $encrypted && $value !== null ? Crypt::encryptString((string) $value) : $value;

        DomainSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'encrypted' => $encrypted]
        );

        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        return DomainSetting::all()
            ->mapWithKeys(fn ($s) => [$s->key => $s->encrypted ? Crypt::decryptString($s->value) : $s->value])
            ->all();
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    public static function makeEnomClient(): EnomClient
    {
        return new EnomClient(
            username: (string) self::get('enom_username', ''),
            password: (string) self::get('enom_password', ''),
            environment: self::get('enom_environment', EnomClient::ENV_TEST),
            proxyUrl: self::get('enom_proxy_url'),
            timeout: (int) self::get('enom_timeout', 30),
        );
    }
}
