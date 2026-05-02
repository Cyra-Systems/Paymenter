<?php

namespace App\Providers;

use App\Events\Domain\BoundToService;
use App\Events\Domain\Expired;
use App\Events\Domain\HostnameChanged;
use App\Events\Domain\Registered;
use App\Events\Domain\UnboundFromService;
use App\Listeners\Domain\MarkDomainExpiredListener;
use App\Listeners\Domain\NotifyServerExtensionListener;
use App\Listeners\Domain\RegisterDomainPropertiesListener;
use App\Listeners\Domain\RemoveProxyHostListener;
use App\Listeners\Domain\SyncProxyHostListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class DomainEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BoundToService::class => [
            SyncProxyHostListener::class,
            NotifyServerExtensionListener::class,
        ],
        HostnameChanged::class => [
            SyncProxyHostListener::class,
            NotifyServerExtensionListener::class,
        ],
        UnboundFromService::class => [
            RemoveProxyHostListener::class,
        ],
        Registered::class => [
            RegisterDomainPropertiesListener::class,
        ],
        Expired::class => [
            MarkDomainExpiredListener::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
