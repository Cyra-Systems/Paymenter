<?php

namespace App\Events\Domain;

use App\Models\Domain;
use App\Models\Service;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Bound
{
    use Dispatchable, SerializesModels;

    public function __construct(public Domain $domain, public Service $service) {}
}
