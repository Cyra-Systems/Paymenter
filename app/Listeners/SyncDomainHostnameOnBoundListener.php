<?php

namespace App\Listeners;

use App\Events\Domain\Bound;
use App\Models\Service;
use App\Services\Domains\Contracts\HasHostname;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Push the new hostname to the bound service's product extension when a
 * Domain is freshly bound (not via the synchronous bind() helper).
 */
class SyncDomainHostnameOnBoundListener implements ShouldQueue
{
    public function handle(Bound $event): void
    {
        foreach ($this->extensionsFor($event->service) as $ext) {
            try {
                $ext->applyHostname($event->service, $event->domain, null);
            } catch (\Throwable $e) {
                Log::error('Apply hostname failed', [
                    'service_id' => $event->service->id,
                    'hostname' => $event->domain->domain,
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
