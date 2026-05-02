<?php

namespace App\Events\Domain;

use App\Models\Domain;
use App\Models\Service;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HostnameChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Domain $domain,
        public Service $service,
        public string $newHostname,
        public string $previousHostname,
    ) {}
}
