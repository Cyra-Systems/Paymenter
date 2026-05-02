<?php

namespace App\Listeners\Domain;

use App\Domains\Services\DomainBindingService;
use App\Events\Domain\Expired;
use App\Models\Domain;
use App\Models\DomainServiceBinding;

class MarkDomainExpiredListener
{
    public function __construct(private DomainBindingService $bindings) {}

    public function handle(Expired $event): void
    {
        $event->domain->update(['status' => Domain::STATUS_EXPIRED]);

        $event->domain
            ->bindings()
            ->whereIn('status', [DomainServiceBinding::STATUS_ACTIVE, DomainServiceBinding::STATUS_TRANSITIONING])
            ->get()
            ->each(fn (DomainServiceBinding $binding) => $this->bindings->unbind($binding));
    }
}
