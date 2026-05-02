<?php

namespace App\Listeners;

use App\Events\Service\Created as ServiceCreated;
use App\Models\Domain;
use App\Services\Domains\DomainProvisionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * When a Service is created, look at its properties for the magic
 * `_domain_*` keys (set by the cart) and, if found, create the Domain
 * row + dispatch provisioning to Enom.
 *
 * Property keys consumed (set during cart -> checkout):
 *   _domain_action     register|transfer|forward|subdomain|external
 *   _domain            full FQDN, e.g. example.com or shop.example.com
 *   _domain_period     years (defaults to 1)
 *   _domain_auth_code  EPP code, only for transfer
 *   _domain_forward_url for forward
 *   _domain_parent_id  parent domain ID for subdomain (or product-pinned)
 *   _domain_id_protect bool
 *   _domain_auto_renew bool
 *   _domain_nameservers  comma-separated NS list
 */
class ProvisionDomainListener implements ShouldQueue
{
    public function __construct(protected DomainProvisionService $provisioner) {}

    public function handle(ServiceCreated $event): void
    {
        $service = $event->service;

        if (!$service->product || !$service->product->domain_enabled) {
            return;
        }

        $action = $service->properties()->where('key', '_domain_action')->value('value');
        $hostname = $service->properties()->where('key', '_domain')->value('value');

        if (!$action || !$hostname) {
            return;
        }

        if (Domain::where('bindable_id', $service->id)
            ->where('bindable_type', \App\Models\Service::class)
            ->exists()) {
            return;
        }

        $domain = Domain::create([
            'domain' => strtolower($hostname),
            'type' => $this->mapAction($action),
            'status' => Domain::STATUS_PENDING,
            'user_id' => $service->user_id,
            'order_id' => $service->order_id,
            'bindable_id' => $service->id,
            'bindable_type' => \App\Models\Service::class,
            'parent_domain_id' => $service->properties()->where('key', '_domain_parent_id')->value('value')
                ?: $service->product->domain_parent_id,
            'forward_url' => $service->properties()->where('key', '_domain_forward_url')->value('value'),
            'period' => (int) ($service->properties()->where('key', '_domain_period')->value('value') ?: 1),
            'auth_code' => $service->properties()->where('key', '_domain_auth_code')->value('value'),
            'id_protect' => (bool) $service->properties()->where('key', '_domain_id_protect')->value('value'),
            'auto_renew' => (bool) $service->properties()->where('key', '_domain_auto_renew')->value('value'),
            'nameservers' => $this->parseNs($service->properties()->where('key', '_domain_nameservers')->value('value')),
        ]);

        try {
            $this->provisioner->provision($domain);
        } catch (\Throwable $e) {
            Log::error('Auto-provision domain failed for service ' . $service->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function mapAction(string $action): string
    {
        return match ($action) {
            'register', 'custom_register', 'new' => Domain::TYPE_REGISTER,
            'transfer' => Domain::TYPE_TRANSFER,
            'forward' => Domain::TYPE_FORWARD,
            'subdomain' => Domain::TYPE_SUBDOMAIN,
            'external', 'custom', 'existing' => Domain::TYPE_EXTERNAL,
            default => Domain::TYPE_EXTERNAL,
        };
    }

    protected function parseNs(?string $raw): ?array
    {
        if (!$raw) return null;
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
