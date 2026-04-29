<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class OS extends Model
{
    protected $table = 'ext_proxmox_oses';

    protected $fillable = [
        // Shows to customer
        'name',
        'vm_id',
    ];
}
