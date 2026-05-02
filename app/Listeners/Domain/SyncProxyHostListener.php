<?php

namespace App\Listeners\Domain;

use App\Domains\ProxyManager\NginxProxyManagerClient;
use App\Domains\Services\DomainBindingService;
use App\Domains\Services\ProxyTargetResolver;
use App\Events\Domain\BoundToService;
use App\Events\Domain\HostnameChanged;
use App\Models\DomainServiceBinding;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncProxyHostListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domains';

    public int $tries = 5;

    public function __construct(
        private NginxProxyManagerClient $client,
        private ProxyTargetResolver $resolver,
        private DomainBindingService $bindings,
    ) {}

    public function handle(BoundToService|HostnameChanged $event): void
    {
        $service = $event->service;
        $hostname = $event instanceof BoundToService ? $event->hostname : $event->newHostname;

        $binding = DomainServiceBinding::query()
            ->where('domain_id', $event->domain->id)
            ->where('service_id', $service->id)
            ->where('hostname', $hostname)
            ->latest('id')
            ->first();

        if (! $binding) {
            return;
        }

        if (! $this->client->isConfigured()) {
            $this->bindings->finalize($binding);

            return;
        }

        try {
            if ($binding->type === 'forward') {
                $response = $this->client->upsertRedirectionHost($binding);
                $binding->update(['npm_redirection_host_id' => (int) ($response['id'] ?? $binding->npm_redirection_host_id)]);
            } else {
                $target = $this->resolver->resolve($service);
                if ($target['host'] === '') {
                    throw new Exception('No proxy target host resolved for service '.$service->id);
                }

                $response = $this->client->upsertProxyHost($binding, $target);
                $binding->update([
                    'npm_proxy_host_id' => (int) ($response['id'] ?? $binding->npm_proxy_host_id),
                    'npm_certificate_id' => (int) ($response['certificate_id'] ?? $binding->npm_certificate_id),
                ]);
            }

            $previous = null;
            if ($event instanceof BoundToService && $event->previousService) {
                $previous = DomainServiceBinding::query()
                    ->where('domain_id', $event->domain->id)
                    ->where('service_id', $event->previousService->id)
                    ->where('status', DomainServiceBinding::STATUS_TRANSITIONING)
                    ->first();
            }

            $this->bindings->finalize($binding, $previous);
        } catch (Exception $e) {
            $this->bindings->markFailed($binding, $e->getMessage());

            throw $e;
        }
    }

    public function backoff(): array
    {
        return [10, 30, 120, 300, 600];
    }
}
