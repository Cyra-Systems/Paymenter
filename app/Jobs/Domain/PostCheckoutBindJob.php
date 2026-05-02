<?php

namespace App\Jobs\Domain;

use App\Domains\Services\DomainBindingService;
use App\Domains\Services\DomainProvisioningService;
use App\Domains\Services\SubdomainAllocator;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Models\Service;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostCheckoutBindJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'domains';

    public int $tries = 3;

    public function __construct(public Service $service, public array $checkoutConfig) {}

    public function handle(): void
    {
        $path = $this->checkoutConfig['domain_path'] ?? null;
        $fqdn = strtolower(trim((string) ($this->checkoutConfig['domain_fqdn'] ?? '')));

        if (! $path || $fqdn === '') {
            return;
        }

        $existing = Domain::query()
            ->where('user_id', $this->service->user_id)
            ->where('fqdn', $fqdn)
            ->first();

        $domain = match ($path) {
            Domain::TYPE_PRIMARY, Domain::TYPE_CUSTOM => $existing
                ?? $this->registerOrAdopt($fqdn),
            Domain::TYPE_FORWARD => $existing ?? $this->createExternalShell($fqdn),
            Domain::TYPE_SUBDOMAIN => $existing ?? $this->createSubdomainShell($fqdn),
            default => null,
        };

        if (! $domain) {
            return;
        }

        $type = match ($path) {
            Domain::TYPE_FORWARD => Domain::TYPE_FORWARD,
            Domain::TYPE_SUBDOMAIN => Domain::TYPE_SUBDOMAIN,
            default => Domain::TYPE_PRIMARY,
        };

        $binding = app(DomainBindingService::class)->bind($domain, $this->service, $type, $fqdn);

        if ($path === Domain::TYPE_FORWARD && ! empty($this->checkoutConfig['domain_forward_target'])) {
            $binding->update(['forward_target' => (string) $this->checkoutConfig['domain_forward_target']]);
        }

        if ($path === Domain::TYPE_SUBDOMAIN) {
            $allocator = app(SubdomainAllocator::class);
            $prefix = explode('.', $fqdn, 2)[0] ?? '';
            if (! $allocator->isValidPrefix($prefix)) {
                throw new Exception('Invalid subdomain prefix: '.$prefix);
            }
        }
    }

    private function registerOrAdopt(string $fqdn): Domain
    {
        $parts = explode('.', $fqdn, 2);
        if (count($parts) !== 2) {
            return $this->createExternalShell($fqdn);
        }
        [$sld, $tld] = $parts;

        $tldRecord = DomainTld::query()->where('tld', $tld)->where('enabled', true)->first();
        if (! $tldRecord) {
            return $this->createExternalShell($fqdn);
        }

        return app(DomainProvisioningService::class)->register(
            $this->service->user,
            $sld,
            $tld,
            (int) ($this->checkoutConfig['domain_years'] ?? 1),
            ['service_id' => $this->service->id],
        );
    }

    private function createExternalShell(string $fqdn): Domain
    {
        $parts = explode('.', $fqdn, 2);
        $sld = $parts[0] ?? $fqdn;
        $tld = $parts[1] ?? '';

        return Domain::create([
            'user_id' => $this->service->user_id,
            'sld' => $sld,
            'tld' => $tld,
            'fqdn' => $fqdn,
            'registrar' => 'external',
            'status' => Domain::STATUS_ACTIVE,
            'registered_via_service_id' => $this->service->id,
        ]);
    }

    private function createSubdomainShell(string $fqdn): Domain
    {
        return Domain::create([
            'user_id' => $this->service->user_id,
            'sld' => explode('.', $fqdn, 2)[0] ?? $fqdn,
            'tld' => 'subdomain',
            'fqdn' => $fqdn,
            'registrar' => 'subdomain',
            'status' => Domain::STATUS_ACTIVE,
            'registered_via_service_id' => $this->service->id,
        ]);
    }
}
