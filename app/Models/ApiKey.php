<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;

class ApiKey extends Model implements Auditable
{
    use Traits\Auditable;

    protected $fillable = [
        'name',
        'permissions',
        'token',
        'user_id',
        'type',
        'ip_addresses',
        'last_used_at',
        'enabled',
        'rate_limit',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_addresses' => 'array',
        'last_used_at' => 'datetime',
        'rate_limit' => 'integer',
    ];

    protected $auditExclude = [
        'last_used_at',
    ];
}
