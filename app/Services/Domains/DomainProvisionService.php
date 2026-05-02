<?php

namespace App\Services\Domains;

use App\Events\Domain as DomainEvent;
use App\Models\Domain;
use App\Models\DomainBindingHistory;
use App\Models\Service;
use App\Models\User;
use App\Services\Domains\Contracts\HasHostname;
use App\Services\Domains\Exceptions\EnomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates everything domain-related on the Paymenter side.
 *
 * The Filament resource, the cart/checkout listener and the admin
 * "switch service" action all call into this service — never into
 * EnomClient directly — so business rules (audit trail, status
 * transitions, hostname pushdown) stay in one place.
 */
class DomainProvisionService
{
    public function __construct(protected ?EnomClient $enom = null) {}

    protected function enom(): EnomClient
    {
        return $this->enom ??= DomainSettings::makeEnomClient();
    }

    public function provision(Domain $domain): Domain
    {
        try {
            switch ($domain->type) {
                case Domain::TYPE_REGISTER:
                    $this->register($domain);
                    break;
                case Domain::TYPE_TRANSFER:
                    $this->transfer($domain);
                    break;
                case Domain::TYPE_FORWARD:
                    $this->setupForward($domain);
                    break;
                case Domain::TYPE_SUBDOMAIN:
                    $this->setupSubdomain($domain);
                    break;
                case Domain::TYPE_EXTERNAL:
                    $domain->status = Domain::STATUS_ACTIVE;
                    $domain->save();
                    break;
            }
        } catch (EnomException $e) {
            Log::error('Domain provisioning failed', [
                'domain' => $domain->domain,
                'type' => $domain->type,
                'error' => $e->getMessage(),
                'payload' => $e->payload,
            ]);
            $domain->status = Domain::STATUS_FAILED;
            $domain->provider_meta = array_merge($domain->provider_meta ?? [], [
                'last_error' => $e->getMessage(),
                'last_error_at' => now()->toIso8601String(),
            ]);
            $domain->save();
            throw $e;
        }

        return $domain->fresh();
    }

    protected function register(Domain $domain): void
    {
        $contacts = $this->buildContacts($domain);
        $response = $this->enom()->purchase(
            sld: $domain->sld,
            tld: $domain->tld,
            numYears: $domain->period ?: 1,
            contacts: $contacts,
            extra: ['IgnoreNSFail' => 'Yes'],
        );

        $domain->status = Domain::STATUS_ACTIVE;
        $domain->registered_at = now();
        $domain->expires_at = now()->addYears($domain->period ?: 1);
        $domain->provider_meta = array_merge($domain->provider_meta ?? [], ['purchase' => $response]);
        $domain->save();

        if ($domain->id_protect) {
            $this->enom()->purchaseIdProtect($domain->sld, $domain->tld, true);
        }
        if ($domain->auto_renew) {
            $this->enom()->setAutoRenew($domain->sld, $domain->tld, true);
        }
        if (!empty($domain->nameservers)) {
            $this->enom()->modifyNs($domain->sld, $domain->tld, $domain->nameservers);
        }
    }

    protected function transfer(Domain $domain): void
    {
        $response = $this->enom()->transfer(
            sld: $domain->sld,
            tld: $domain->tld,
            authInfo: (string) $domain->auth_code,
            contacts: $this->buildContacts($domain),
        );

        $domain->status = Domain::STATUS_TRANSFERRING;
        $domain->provider_meta = array_merge($domain->provider_meta ?? [], ['transfer' => $response]);
        $domain->save();
    }

    protected function setupForward(Domain $domain): void
    {
        if (!$domain->forward_url) {
            throw new EnomException('Forward URL is required for domain forwarding');
        }
        $this->enom()->setDomainForwarding(
            sld: $domain->sld,
            tld: $domain->tld,
            forwardTo: $domain->forward_url,
            type: $domain->forward_type ?? '301',
        );
        $domain->status = Domain::STATUS_ACTIVE;
        $domain->save();
    }

    protected function setupSubdomain(Domain $domain): void
    {
        if (!$domain->parent) {
            throw new EnomException('Subdomain has no parent domain');
        }
        $parent = $domain->parent;
        $existing = $this->enom()->getHosts($parent->sld, $parent->tld);
        $records = data_get($existing, 'host-records.host', []);
        $records = array_map(fn ($r) => [
            'hostname' => $r['name'] ?? '@',
            'type' => $r['type'] ?? 'A',
            'address' => $r['address'] ?? '',
            'mxpref' => $r['mxpref'] ?? 10,
            'ttl' => $r['ttl'] ?? 3600,
        ], is_array($records) ? $records : []);

        $records[] = [
            'hostname' => $domain->sld,
            'type' => 'CNAME',
            'address' => 'parking.example.com',
            'ttl' => 3600,
        ];

        $this->enom()->setHosts($parent->sld, $parent->tld, $records);
        $domain->status = Domain::STATUS_ACTIVE;
        $domain->save();
    }

    public function bind(Domain $domain, ?Service $service, ?User $actor = null, ?string $reason = null): Domain
    {
        $previous = $domain->bindable;
        $previousHostname = $previous instanceof Service ? $domain->domain : null;

        DB::transaction(function () use ($domain, $service, $actor, $reason, $previous, $previousHostname) {
            DomainBindingHistory::create([
                'domain_id' => $domain->id,
                'previous_bindable_id' => $previous?->id,
                'previous_bindable_type' => $previous ? $previous::class : null,
                'new_bindable_id' => $service?->id,
                'new_bindable_type' => $service ? Service::class : null,
                'old_hostname' => $previousHostname,
                'new_hostname' => $service ? $domain->domain : null,
                'user_id' => $actor?->id,
                'reason' => $reason,
            ]);

            $domain->bindable_id = $service?->id;
            $domain->bindable_type = $service ? Service::class : null;
            $domain->save();
        });

        if ($previous instanceof Service) {
            $this->dispatchHostnameRemoval($previous, $previousHostname);
        }
        if ($service) {
            $this->dispatchHostnameApply($service, $domain, $previousHostname);
        }

        if ($previous && $service) {
            event(new DomainEvent\Switched($domain, $previous, $service));
        } elseif ($service) {
            event(new DomainEvent\Bound($domain, $service));
        } else {
            event(new DomainEvent\Unbound($domain, $previous));
        }

        return $domain->fresh();
    }

    public function unbind(Domain $domain, ?User $actor = null, ?string $reason = null): Domain
    {
        return $this->bind($domain, null, $actor, $reason);
    }

    public function renew(Domain $domain, int $years = 1): Domain
    {
        $response = $this->enom()->renew($domain->sld, $domain->tld, $years);
        $domain->expires_at = ($domain->expires_at ?? now())->addYears($years);
        $domain->provider_meta = array_merge($domain->provider_meta ?? [], ['renew' => $response]);
        $domain->save();
        return $domain;
    }

    public function syncFromProvider(Domain $domain): Domain
    {
        $info = $this->enom()->getDomainInfo($domain->sld, $domain->tld);
        $expires = data_get($info, 'GetDomainInfo.status.expiration');
        $domain->expires_at = $expires ? \Carbon\Carbon::parse($expires) : $domain->expires_at;
        $domain->last_synced_at = now();
        $domain->provider_meta = array_merge($domain->provider_meta ?? [], ['info' => $info]);
        $domain->save();
        return $domain;
    }

    public function setNameservers(Domain $domain, array $nameservers): Domain
    {
        $this->enom()->modifyNs($domain->sld, $domain->tld, $nameservers);
        $domain->nameservers = array_values(array_filter($nameservers));
        $domain->save();
        return $domain;
    }

    public function setAutoRenew(Domain $domain, bool $enable): Domain
    {
        $this->enom()->setAutoRenew($domain->sld, $domain->tld, $enable);
        $domain->auto_renew = $enable;
        $domain->save();
        return $domain;
    }

    public function setLock(Domain $domain, bool $locked): Domain
    {
        $this->enom()->setRegLock($domain->sld, $domain->tld, $locked);
        $domain->locked = $locked;
        $domain->save();
        return $domain;
    }

    public function getAuthCode(Domain $domain): string
    {
        $response = $this->enom()->getAuthInfo($domain->sld, $domain->tld);
        $code = (string) data_get($response, 'AuthInfo', '');
        $domain->auth_code = $code;
        $domain->save();
        return $code;
    }

    protected function dispatchHostnameApply(Service $service, Domain $domain, ?string $previous): void
    {
        foreach ($this->resolveHostnameAwareExtensions($service) as $ext) {
            try {
                $ext->applyHostname($service, $domain, $previous);
            } catch (\Throwable $e) {
                Log::error('Failed to apply hostname to service extension', [
                    'service_id' => $service->id,
                    'extension' => $ext::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function dispatchHostnameRemoval(Service $service, ?string $hostname): void
    {
        foreach ($this->resolveHostnameAwareExtensions($service) as $ext) {
            try {
                $ext->removeHostname($service, $hostname);
            } catch (\Throwable $e) {
                Log::error('Failed to remove hostname from service extension', [
                    'service_id' => $service->id,
                    'extension' => $ext::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return iterable<HasHostname> */
    protected function resolveHostnameAwareExtensions(Service $service): iterable
    {
        $product = $service->product;
        if (!$product || !$product->server) {
            return [];
        }
        $className = '\\Paymenter\\Extensions\\Servers\\' . $product->server->extension . '\\' . $product->server->extension;
        if (!class_exists($className)) {
            return [];
        }
        $instance = app($className);
        return $instance instanceof HasHostname ? [$instance] : [];
    }

    /**
     * Build the contact array for Enom's Purchase command from the domain
     * owner's user profile. Enom requires Registrant/Admin/Tech/AuxBilling.
     */
    protected function buildContacts(Domain $domain): array
    {
        $user = $domain->user;
        if (!$user) {
            return [];
        }

        $base = [
            'FirstName' => $user->first_name ?: 'Domain',
            'LastName' => $user->last_name ?: 'Owner',
            'Address1' => $user->properties()->where('key', 'address')->value('value') ?: 'N/A',
            'City' => $user->properties()->where('key', 'city')->value('value') ?: 'N/A',
            'StateProvince' => $user->properties()->where('key', 'state')->value('value') ?: 'N/A',
            'PostalCode' => $user->properties()->where('key', 'postal_code')->value('value') ?: 'N/A',
            'Country' => $user->properties()->where('key', 'country')->value('value') ?: 'US',
            'EmailAddress' => $user->email,
            'Phone' => $user->properties()->where('key', 'phone')->value('value') ?: '+1.5555555555',
        ];

        $contacts = [];
        foreach (['Registrant', 'Admin', 'Tech', 'AuxBilling'] as $role) {
            foreach ($base as $k => $v) {
                $contacts[$role . $k] = $v;
            }
        }
        return $contacts;
    }
}
