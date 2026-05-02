<?php

namespace App\Models;

use App\Models\Traits\HasProperties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Domain extends Model implements Auditable
{
    use HasFactory, HasProperties, SoftDeletes, Traits\Auditable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_TRANSFERRING = 'transferring';

    public const STATUS_TRANSFERRED_OUT = 'transferred_out';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REDEMPTION = 'redemption';

    public const TYPE_PRIMARY = 'primary';

    public const TYPE_FORWARD = 'forward';

    public const TYPE_SUBDOMAIN = 'subdomain';

    public const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'user_id',
        'sld',
        'tld',
        'fqdn',
        'registrar',
        'status',
        'auth_code',
        'locked',
        'auto_renew',
        'id_protect',
        'registered_at',
        'expires_at',
        'last_synced_at',
        'registered_via_service_id',
        'registrar_data',
        'currency_code',
        'price',
    ];

    protected $casts = [
        'locked' => 'boolean',
        'auto_renew' => 'boolean',
        'id_protect' => 'boolean',
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'registrar_data' => 'array',
        'auth_code' => 'encrypted',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bindings()
    {
        return $this->hasMany(DomainServiceBinding::class);
    }

    public function activeBinding()
    {
        return $this->hasOne(DomainServiceBinding::class)->whereIn('status', ['active', 'transitioning']);
    }

    public function dnsRecords()
    {
        return $this->hasMany(DomainDnsRecord::class);
    }

    public function tldRecord()
    {
        return $this->belongsTo(DomainTld::class, 'tld', 'tld');
    }

    public function originService()
    {
        return $this->belongsTo(Service::class, 'registered_via_service_id');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'code', 'currency_code');
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }
}
