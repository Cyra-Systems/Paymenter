<?php

namespace App\Observers;

use App\Events\Domain as DomainEvent;
use App\Models\Domain;

class DomainObserver
{
    public function created(Domain $domain): void
    {
        event(new DomainEvent\Created($domain));
    }

    public function updated(Domain $domain): void
    {
        event(new DomainEvent\Updated($domain));
    }

    public function deleted(Domain $domain): void
    {
        event(new DomainEvent\Deleted($domain));
    }
}
