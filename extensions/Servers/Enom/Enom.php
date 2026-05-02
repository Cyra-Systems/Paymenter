<?php

namespace Paymenter\Extensions\Servers\Enom;

use App\Attributes\ExtensionMeta;
use App\Classes\Extension\Server;
use App\Domains\Registrars\EnomRegistrar;
use App\Domains\Services\DomainBindingService;
use App\Domains\Services\DomainProvisioningService;
use App\Helpers\ExtensionHelper;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Models\Service;
use Exception;

#[ExtensionMeta(
    name: 'Enom Domain Registrar',
    description: 'Register and renew domains through the Enom XML API. This extension is now a thin facade over the core Domain Management pillar (App\\Domains).',
    version: '2.0.0',
    author: 'Paymenteer',
    url: '',
    icon: ''
)]
class Enom extends Server
{
    private function registrar(): EnomRegistrar
    {
        return new EnomRegistrar([
            'username' => $this->config('username'),
            'password' => $this->config('password'),
            'sandbox' => (bool) $this->config('sandbox'),
        ]);
    }

    private function companionStatusDescription(): string
    {
        try {
            ExtensionHelper::getExtension('other', 'EnomDomains');

            return 'Optional companion detected: Enom Search is installed and can use this server for client-area search and synced TLD catalog features.';
        } catch (\Throwable) {
            return 'Optional companion not installed: install Enom Search if you want client-area domain search and synced TLD catalog features.';
        }
    }

    public function getConfig($values = []): array
    {
        return [
            [
                'name' => 'username',
                'label' => 'Enom Username',
                'type' => 'text',
                'description' => 'Your Enom reseller username. '.$this->companionStatusDescription(),
                'required' => true,
            ],
            [
                'name' => 'password',
                'label' => 'Enom Password / Token',
                'type' => 'password',
                'description' => 'Use the live or test API credential issued by Enom.',
                'required' => true,
                'encrypted' => true,
            ],
            [
                'name' => 'sandbox',
                'label' => 'Sandbox Mode',
                'type' => 'checkbox',
                'description' => 'Use the Enom test endpoint instead of the live reseller endpoint.',
            ],
        ];
    }

    public function getProductConfig($values = []): array
    {
        return [
            [
                'name' => 'tld',
                'label' => 'TLD',
                'type' => 'text',
                'description' => 'The extension of this domain product, for example com, net, org',
                'placeholder' => 'com',
                'required' => true,
            ],
            [
                'name' => 'years',
                'label' => 'Registration Period',
                'type' => 'number',
                'description' => 'How many years to register or renew the domain for.',
                'required' => true,
                'default' => 1,
                'min_value' => 1,
                'max_value' => 10,
            ],
            [
                'name' => 'id_protect',
                'label' => 'WHOIS Privacy',
                'type' => 'checkbox',
                'description' => 'Request privacy protection during registration when the TLD supports it.',
            ],
            [
                'name' => 'auto_renew',
                'label' => 'Auto Renew',
                'type' => 'checkbox',
                'description' => 'Enable auto-renew on newly registered domains.',
            ],
            [
                'name' => 'lock',
                'label' => 'Registrar Lock',
                'type' => 'checkbox',
                'description' => 'Apply registrar lock after the domain is registered.',
            ],
        ];
    }

    public function getCheckoutConfig($product = null, $values = [], $settings = []): array
    {
        $availabilityValidation = [];
        $domain = strtolower(trim((string) ($values['domain'] ?? '')));
        $tld = strtolower(trim((string) ($settings['tld'] ?? '')));

        if ($domain !== '' && $tld !== '') {
            $availabilityValidation[] = function (string $attribute, mixed $value, \Closure $fail) use ($domain, $tld) {
                try {
                    if (! $this->registrar()->check($domain, $tld)) {
                        $fail('The selected domain is not available.');
                    }
                } catch (Exception) {
                    $fail('We could not verify domain availability right now. Please try again in a moment.');
                }
            };
        }

        return [
            [
                'name' => 'domain',
                'label' => 'Domain Label',
                'type' => 'text',
                'description' => 'Enter only the name before the TLD. Example: use "example" for example.com.',
                'placeholder' => 'example',
                'required' => true,
                'validation' => array_merge([
                    'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/',
                ], $availabilityValidation),
            ],
        ];
    }

    public function testConfig(): bool|string
    {
        return $this->registrar()->testConnection();
    }

    public function createServer(Service $service, $settings, $properties)
    {
        $registrar = $this->registrar();

        $tld = strtolower(ltrim(trim((string) ($settings['tld'] ?? '')), '.'));
        if ($tld !== '' && ! DomainTld::query()->where('tld', $tld)->where('enabled', true)->exists()) {
            DomainTld::firstOrCreate(
                ['tld' => $tld],
                [
                    'enabled' => true,
                    'register_price' => 0,
                    'transfer_price' => 0,
                    'renewal_price' => 0,
                    'redemption_price' => 0,
                    'currency_code' => $service->currency_code ?? 'USD',
                    'min_years' => 1,
                    'max_years' => 10,
                ]
            );
        }

        $domain = app(DomainProvisioningService::class)
            ->registerForService($service, $settings, $properties, $registrar);

        app(DomainBindingService::class)
            ->bind($domain, $service, Domain::TYPE_PRIMARY, $domain->fqdn);

        $service->properties()->updateOrCreate(
            ['key' => 'enom_domain'],
            ['name' => 'Enom domain', 'value' => $domain->fqdn],
        );

        return [
            'domain' => $domain->fqdn,
            'status' => 'active',
        ];
    }

    public function suspendServer(Service $service, $settings, $properties)
    {
        return true;
    }

    public function unsuspendServer(Service $service, $settings, $properties)
    {
        return true;
    }

    public function terminateServer(Service $service, $settings, $properties)
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if ($domain) {
            $registrar = $this->registrar();
            try {
                $registrar->request('SetRenew', [
                    'SLD' => $domain->sld,
                    'TLD' => $domain->tld,
                    'AutoRenew' => 0,
                ]);
            } catch (Exception) {
                // Swallow — termination is best-effort at the registrar layer.
            }

            $domain->update(['auto_renew' => false, 'status' => Domain::STATUS_CANCELLED]);

            $domain->bindings()->where('service_id', $service->id)->get()->each(function ($binding) {
                app(DomainBindingService::class)->unbind($binding);
            });
        }

        return true;
    }

    public function renewServer(Service $service, $settings, $properties)
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if (! $domain) {
            throw new Exception('Domain record not found for this service.');
        }

        app(DomainProvisioningService::class)->renew($domain, (int) ($settings['years'] ?? 1), $this->registrar());

        return true;
    }

    public function getDomainInfo(Service $service, $settings, $properties): array
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if (! $domain) {
            throw new Exception('Domain record not found for this service.');
        }

        $info = $this->registrar()->getInfo($domain);

        if ($info['expires_at']) {
            $domain->update(['expires_at' => $info['expires_at'], 'last_synced_at' => now()]);
            $service->forceFill(['expires_at' => $info['expires_at']])->save();
        }

        $info['domain'] = $domain->fqdn;

        return $info;
    }

    public function updateNameservers(Service $service, $settings, $properties, array $nameservers): array
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if (! $domain) {
            throw new Exception('Domain record not found for this service.');
        }

        $this->registrar()->setNameservers($domain, $nameservers);

        return $this->getDomainInfo($service, $settings, $properties);
    }

    public function setRegistrarLock(Service $service, $settings, $properties, bool $lock): array
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if (! $domain) {
            throw new Exception('Domain record not found for this service.');
        }

        $this->registrar()->setLock($domain, $lock);
        $domain->update(['locked' => $lock]);

        return $this->getDomainInfo($service, $settings, $properties);
    }

    public function getDNS(Service $service, $settings, $properties): array
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if (! $domain) {
            throw new Exception('Domain record not found for this service.');
        }

        return $this->registrar()->getDns($domain);
    }

    public function setDNS(Service $service, $settings, $properties, array $records): array
    {
        $domain = $this->resolveDomain($service, $settings, $properties);
        if (! $domain) {
            throw new Exception('Domain record not found for this service.');
        }

        return $this->registrar()->setDns($domain, $records);
    }

    public function getNameServers(Service $service, $settings, $properties): array
    {
        return $this->getDomainInfo($service, $settings, $properties)['nameservers'] ?? [];
    }

    public function setNameServers(Service $service, $settings, $properties, array $nameservers): bool
    {
        $this->updateNameservers($service, $settings, $properties, $nameservers);

        return true;
    }

    public function checkDomain(string $sld, string $tld): bool
    {
        return $this->registrar()->check($sld, $tld);
    }

    public function getActions(Service $service, $settings, $properties): array
    {
        $fqdn = $this->resolveDomain($service, $settings, $properties)?->fqdn
            ?? trim((string) ($properties['domain'] ?? '')).'.'.trim((string) ($settings['tld'] ?? ''));

        return [
            [
                'label' => 'Open Enom',
                'type' => 'button',
                'url' => 'https://www.enomcentral.com/',
            ],
            [
                'label' => 'WHOIS Lookup',
                'type' => 'button',
                'url' => 'https://www.whois.com/whois/'.rawurlencode($fqdn),
            ],
        ];
    }

    private function resolveDomain(Service $service, array $settings, array $properties): ?Domain
    {
        $binding = $service->primaryDomainBinding;
        if ($binding && $binding->domain) {
            return $binding->domain;
        }

        $sld = strtolower(trim((string) ($properties['domain'] ?? '')));
        $tld = strtolower(ltrim(trim((string) ($settings['tld'] ?? '')), '.'));
        if ($sld === '' || $tld === '') {
            return null;
        }

        return Domain::query()->where('fqdn', $sld.'.'.$tld)->where('user_id', $service->user_id)->first();
    }
}
