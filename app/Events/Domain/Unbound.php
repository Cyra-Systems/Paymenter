<?php

namespace App\Events\Domain;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Unbound
{
    use Dispatchable, SerializesModels;

    public function __construct(public Domain $domain, public ?Model $previousBindable = null) {}
}
