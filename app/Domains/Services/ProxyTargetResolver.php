<?php

namespace App\Domains\Services;

use App\Helpers\ExtensionHelper;
use App\Models\Service;

class ProxyTargetResolver
{
    public function resolve(Service $service): array
    {
        $target = $this->fromExtension($service);

        if ($target) {
            return $this->normalize($target);
        }

        return $this->normalize([
            'scheme' => 'http',
            'host' => (string) config('settings.domains.npm_proxy_target_host', ''),
            'port' => 80,
        ]);
    }

    private function fromExtension(Service $service): ?array
    {
        if (! $service->product || ! $service->product->server) {
            return null;
        }

        $server = $service->product->server;

        if (! ExtensionHelper::hasFunction($server, 'getProxyTarget')) {
            return null;
        }

        $extension = ExtensionHelper::getExtension('server', $server->extension, $server->settings);
        $properties = ExtensionHelper::getServiceProperties($service);
        $settings = ExtensionHelper::settingsToArray($service->product->settings);

        try {
            $target = $extension->getProxyTarget($service, $settings, $properties);
        } catch (\Throwable) {
            return null;
        }

        return is_array($target) ? $target : null;
    }

    private function normalize(array $target): array
    {
        $host = trim((string) ($target['host'] ?? ''));
        $scheme = strtolower((string) ($target['scheme'] ?? 'http'));
        $port = (int) ($target['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (! in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'http';
        }

        if ($port < 1 || $port > 65535) {
            $port = $scheme === 'https' ? 443 : 80;
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
        ];
    }
}
