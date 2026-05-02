<?php

namespace App\Domains\Services;

use App\Models\DomainServiceBinding;

class SubdomainAllocator
{
    private const RESERVED = [
        'www', 'mail', 'admin', 'api', 'ns', 'ns1', 'ns2', 'ns3', 'ns4',
        'smtp', 'pop', 'pop3', 'imap', 'webmail', 'ftp', 'sftp', 'ssh',
        'cpanel', 'webdisk', 'autodiscover', 'autoconfig', 'mx', 'dns',
        'localhost', 'static', 'cdn', 'app', 'apps', 'auth', 'login',
        'panel', 'dashboard', 'paymenter', 'billing', 'support',
    ];

    private const PREFIX_PATTERN = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/';

    public function base(): string
    {
        return strtolower(trim((string) config('settings.domains.subdomain_base', '')));
    }

    public function isConfigured(): bool
    {
        return $this->base() !== '';
    }

    public function isValidPrefix(string $prefix): bool
    {
        $prefix = strtolower(trim($prefix));

        if ($prefix === '' || in_array($prefix, self::RESERVED, true)) {
            return false;
        }

        return (bool) preg_match(self::PREFIX_PATTERN, $prefix);
    }

    public function build(string $prefix): string
    {
        return strtolower(trim($prefix)).'.'.$this->base();
    }

    public function isAvailable(string $prefix): bool
    {
        if (! $this->isValidPrefix($prefix) || ! $this->isConfigured()) {
            return false;
        }

        $hostname = $this->build($prefix);

        return ! DomainServiceBinding::query()
            ->where('hostname', $hostname)
            ->whereIn('status', [
                DomainServiceBinding::STATUS_PENDING,
                DomainServiceBinding::STATUS_PROVISIONING,
                DomainServiceBinding::STATUS_ACTIVE,
                DomainServiceBinding::STATUS_TRANSITIONING,
            ])
            ->exists();
    }
}
