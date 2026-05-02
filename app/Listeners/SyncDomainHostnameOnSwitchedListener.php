<?php

namespace App\Listeners;

use App\Events\Domain\Switched;
use App\Models\Service;
use App\Services\Domains\Contracts\HasHostname;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * When a domain moves from one service to another, remove the hostname
 * from the old service and apply it to the new one.
 */
class SyncDomainHostnameOnSwitchedListener implements ShouldQueue
{
    public function handle(Switched $event): void
    {
        if ($event->from instanceof Service) {
            foreach ($this->extensionsFor($event->from) as $ext) {
                try {
                    $ext->removeHostname($event->from, $event->domain->domain);
                } catch (\Throwable $e) {
                    Log::error('Remove hostname failed', [
                        'service_id' => $event->from->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($event->to instanceof Service) {
            foreach ($this->extensionsFor($event->to) as $ext) {
                try {
                    $ext->applyHostname($event->to, $event->domain, $event->domain->domain);
                } catch (\Throwable $e) {
                    Log::error('Apply hostname failed', [
                        'service_id' => $event->to->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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
