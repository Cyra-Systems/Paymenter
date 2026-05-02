<?php

namespace App\Listeners\Domain;

use App\Events\Domain\BoundToService;
use App\Events\Domain\HostnameChanged;
use App\Helpers\ExtensionHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyServerExtensionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domains';

    public int $tries = 3;

    public function handle(BoundToService|HostnameChanged $event): void
    {
        if ($event instanceof BoundToService) {
            $newHostname = $event->hostname;
            $previousHostname = $event->previousHostname;
        } else {
            $newHostname = $event->newHostname;
            $previousHostname = $event->previousHostname;
        }

        if ($previousHostname !== null && $previousHostname === $newHostname) {
            return;
        }

        ExtensionHelper::updateServerHostname($event->service, $newHostname, $previousHostname);
    }
}
