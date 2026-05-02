<?php

namespace App\Events\Domain;

use App\Models\Domain;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Expired
{
    use Dispatchable, SerializesModels;

    public function __construct(public Domain $domain) {}
}
