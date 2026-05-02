<?php

namespace App\Listeners;

use App\Events\Domain\Unbound;
use App\Models\Service;
use App\Services\Domains\Contracts\HasHostname;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * When a domain is unbound, ask the previously-bound service's extension
 * to drop the hostname. No-op if the bindable wasn't a Service.
 */
class SyncDomainHostnameOnUnboundListener implements ShouldQueue
{
    public function handle(Unbound $event): void
    {
        if (!$event->previousBindable instanceof Service) {
            return;
        }

        foreach ($this->extensionsFor($event->previousBindable) as $ext) {
            try {
                $ext->removeHostname($event->previousBindable, $event->domain->domain);
            } catch (\Throwable $e) {
                Log::error('Remove hostname failed', [
                    'service_id' => $event->previousBindable->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return iterable<HasHostname> */
    protected function extensionsFor(Service $service): iterable
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
}
