<?php

namespace App\Listeners\Domain;

use App\Domains\ProxyManager\NginxProxyManagerClient;
use App\Events\Domain\UnboundFromService;
use App\Models\DomainServiceBinding;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RemoveProxyHostListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domains';

    public int $tries = 3;

    public function __construct(private NginxProxyManagerClient $client) {}

    public function handle(UnboundFromService $event): void
    {
        if (! $this->client->isConfigured()) {
            return;
        }

        $binding = DomainServiceBinding::query()
            ->where('domain_id', $event->domain->id)
            ->where('service_id', $event->service->id)
            ->where('hostname', $event->hostname)
            ->latest('id')
            ->first();

        if (! $binding) {
            return;
        }

        if ($binding->npm_proxy_host_id) {
            $this->client->deleteProxyHost($binding->npm_proxy_host_id);
        }

        if ($binding->npm_redirection_host_id) {
            $this->client->deleteRedirectionHost($binding->npm_redirection_host_id);
        }

        $binding->update([
            'npm_proxy_host_id' => null,
            'npm_redirection_host_id' => null,
        ]);
    }
}
