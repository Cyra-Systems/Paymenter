<?php

namespace App\Events\Domain;

use App\Models\Domain;
use App\Models\Service;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoundToService implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Domain $domain,
        public Service $service,
        public ?Service $previousService,
        public string $bindingType,
        public string $hostname,
        public ?string $previousHostname,
    ) {}
}
