<?php

namespace App\Domains\ProxyManager;

use App\Models\DomainServiceBinding;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NginxProxyManagerClient
{
    private const TOKEN_CACHE_KEY = 'domains.npm.token';

    private string $baseUrl;

    private string $email;

    private string $password;

    public function __construct(?array $config = null)
    {
        $config = $config ?? [
            'url' => config('settings.domains.npm_url'),
            'email' => config('settings.domains.npm_email'),
            'password' => config('settings.domains.npm_password'),
        ];

        $this->baseUrl = rtrim((string) ($config['url'] ?? ''), '/');
        $this->email = (string) ($config['email'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->email !== '' && $this->password !== '';
    }

    public function token(bool $refresh = false): string
    {
        if (! $this->isConfigured()) {
            throw new Exception('NPM is not configured. Set the URL, email, and password in Domain Settings.');
        }

        if ($refresh) {
            Cache::forget(self::TOKEN_CACHE_KEY);
        }

        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addHours(23), function () {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl.'/api/tokens', [
                    'identity' => $this->email,
                    'secret' => $this->password,
                ]);

            if (! $response->successful() || empty($response->json('token'))) {
                throw new Exception('Unable to obtain NPM token: HTTP '.$response->status());
            }

            return (string) $response->json('token');
        });
    }

    private function http(bool $forceRefresh = false): PendingRequest
    {
        return Http::timeout(60)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->token($forceRefresh),
            ]);
    }

    private function send(string $method, string $path, array $payload = []): array
    {
        foreach ([false, true] as $refresh) {
            $request = $this->http($refresh);
            $url = $this->baseUrl.$path;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $payload),
                'POST' => $request->post($url, $payload),
                'PUT' => $request->put($url, $payload),
                'DELETE' => $request->delete($url, $payload),
                default => throw new Exception('Unsupported HTTP method: '.$method),
            };

            if ($response->status() === 401 && ! $refresh) {
                continue;
            }

            if (! $response->successful()) {
                throw new Exception('NPM request failed ('.$method.' '.$path.'): HTTP '.$response->status().' '.$response->body());
            }

            $body = $response->json();

            return is_array($body) ? $body : [];
        }

        throw new Exception('NPM authentication failed after retry.');
    }

    public function snapshot(int $proxyHostId): ?array
    {
        try {
            return $this->send('GET', '/api/nginx/proxy-hosts/'.$proxyHostId);
        } catch (Exception) {
            return null;
        }
    }

    public function rollback(?array $snapshot): void
    {
        if (! $snapshot || empty($snapshot['id'])) {
            return;
        }

        $this->send('PUT', '/api/nginx/proxy-hosts/'.$snapshot['id'], $this->extractProxyPayload($snapshot));
    }

    public function upsertProxyHost(DomainServiceBinding $binding, array $target, array $options = []): array
    {
        $payload = [
            'domain_names' => [$binding->hostname],
            'forward_scheme' => $target['scheme'] ?? 'http',
            'forward_host' => (string) ($target['host'] ?? ''),
            'forward_port' => (int) ($target['port'] ?? 80),
            'certificate_id' => (int) ($options['certificate_id'] ?? $binding->npm_certificate_id ?? 0),
            'ssl_forced' => (bool) ($options['ssl_forced'] ?? false),
            'hsts_enabled' => (bool) ($options['hsts_enabled'] ?? false),
            'hsts_subdomains' => (bool) ($options['hsts_subdomains'] ?? false),
            'http2_support' => (bool) ($options['http2_support'] ?? true),
            'block_exploits' => (bool) ($options['block_exploits'] ?? true),
            'caching_enabled' => (bool) ($options['caching_enabled'] ?? false),
            'allow_websocket_upgrade' => (bool) ($options['allow_websocket_upgrade'] ?? true),
            'enabled' => true,
            'advanced_config' => (string) ($options['advanced_config'] ?? ''),
            'locations' => [],
        ];

        if ($binding->npm_proxy_host_id) {
            return $this->send('PUT', '/api/nginx/proxy-hosts/'.$binding->npm_proxy_host_id, $payload);
        }

        return $this->send('POST', '/api/nginx/proxy-hosts', $payload);
    }

    public function deleteProxyHost(int $proxyHostId): void
    {
        $this->send('DELETE', '/api/nginx/proxy-hosts/'.$proxyHostId);
    }

    public function upsertRedirectionHost(DomainServiceBinding $binding): array
    {
        $payload = [
            'domain_names' => [$binding->hostname],
            'forward_domain_name' => (string) ($binding->forward_target ?? ''),
            'forward_scheme' => 'auto',
            'forward_http_code' => 301,
            'preserve_path' => true,
            'certificate_id' => (int) ($binding->npm_certificate_id ?? 0),
            'ssl_forced' => false,
            'block_exploits' => true,
            'http2_support' => true,
        ];

        if ($binding->npm_redirection_host_id) {
            return $this->send('PUT', '/api/nginx/redirection-hosts/'.$binding->npm_redirection_host_id, $payload);
        }

        return $this->send('POST', '/api/nginx/redirection-hosts', $payload);
    }

    public function deleteRedirectionHost(int $redirectionHostId): void
    {
        $this->send('DELETE', '/api/nginx/redirection-hosts/'.$redirectionHostId);
    }

    public function upsertCertificate(string $domain, array $options = []): array
    {
        $payload = [
            'provider' => 'letsencrypt',
            'domain_names' => [$domain],
            'meta' => [
                'letsencrypt_email' => (string) (config('settings.domains.npm_letsencrypt_email') ?: $this->email),
                'letsencrypt_agree' => true,
                'dns_challenge' => (bool) ($options['dns_challenge'] ?? false),
            ],
        ];

        if (! empty($options['dns_provider'])) {
            $payload['meta']['dns_provider'] = $options['dns_provider'];
            $payload['meta']['dns_provider_credentials'] = $options['dns_provider_credentials'] ?? '';
            $payload['meta']['propagation_seconds'] = (int) ($options['propagation_seconds'] ?? 60);
        }

        return $this->send('POST', '/api/nginx/certificates', $payload);
    }

    public function deleteCertificate(int $certificateId): void
    {
        $this->send('DELETE', '/api/nginx/certificates/'.$certificateId);
    }

    public function testConnection(): bool|string
    {
        try {
            $this->token(true);

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function extractProxyPayload(array $snapshot): array
    {
        return [
            'domain_names' => $snapshot['domain_names'] ?? [],
            'forward_scheme' => $snapshot['forward_scheme'] ?? 'http',
            'forward_host' => $snapshot['forward_host'] ?? '',
            'forward_port' => (int) ($snapshot['forward_port'] ?? 80),
            'certificate_id' => (int) ($snapshot['certificate_id'] ?? 0),
            'ssl_forced' => (bool) ($snapshot['ssl_forced'] ?? false),
            'hsts_enabled' => (bool) ($snapshot['hsts_enabled'] ?? false),
            'hsts_subdomains' => (bool) ($snapshot['hsts_subdomains'] ?? false),
            'http2_support' => (bool) ($snapshot['http2_support'] ?? false),
            'block_exploits' => (bool) ($snapshot['block_exploits'] ?? false),
            'caching_enabled' => (bool) ($snapshot['caching_enabled'] ?? false),
            'allow_websocket_upgrade' => (bool) ($snapshot['allow_websocket_upgrade'] ?? false),
            'enabled' => (bool) ($snapshot['enabled'] ?? true),
            'advanced_config' => (string) ($snapshot['advanced_config'] ?? ''),
            'locations' => $snapshot['locations'] ?? [],
        ];
    }
}
