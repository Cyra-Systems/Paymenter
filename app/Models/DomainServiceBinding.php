<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class DomainServiceBinding extends Model implements Auditable
{
    use HasFactory, Traits\Auditable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRANSITIONING = 'transitioning';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'domain_id',
        'service_id',
        'type',
        'hostname',
        'npm_proxy_host_id',
        'npm_certificate_id',
        'npm_redirection_host_id',
        'status',
        'transitioning',
        'forward_target',
        'bound_at',
        'released_at',
        'last_error',
    ];

    protected $casts = [
        'transitioning' => 'boolean',
        'bound_at' => 'datetime',
        'released_at' => 'datetime',
        'npm_proxy_host_id' => 'integer',
        'npm_certificate_id' => 'integer',
        'npm_redirection_host_id' => 'integer',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function isLive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRANSITIONING], true);
    }
}
