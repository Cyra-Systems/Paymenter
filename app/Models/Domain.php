<?php

namespace App\Models;

use App\Observers\DomainObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([DomainObserver::class])]
class Domain extends Model implements Auditable
{
    use HasFactory, SoftDeletes, Traits\Auditable;

    public const TYPE_REGISTER = 'register';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_FORWARD = 'forward';
    public const TYPE_SUBDOMAIN = 'subdomain';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TRANSFERRING = 'transferring';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'domain', 'sld', 'tld', 'type', 'provider', 'status', 'user_id', 'order_id',
        'bindable_id', 'bindable_type', 'parent_domain_id', 'forward_url', 'forward_type',
        'period', 'auto_renew', 'id_protect', 'locked', 'auth_code', 'nameservers',
        'contacts', 'provider_meta', 'registered_at', 'expires_at', 'last_synced_at',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'id_protect' => 'boolean',
        'locked' => 'boolean',
        'nameservers' => 'array',
        'contacts' => 'array',
        'provider_meta' => 'array',
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Domain $domain) {
            if ($domain->isDirty('domain') && $domain->domain) {
                $parts = explode('.', strtolower(trim($domain->domain, '. ')), 2);
                $domain->sld = $parts[0] ?? null;
                $domain->tld = $parts[1] ?? null;
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function bindable(): MorphTo { return $this->morphTo(); }
    public function parent(): BelongsTo { return $this->belongsTo(Domain::class, 'parent_domain_id'); }
    public function subdomains(): HasMany { return $this->hasMany(Domain::class, 'parent_domain_id'); }
    public function bindingHistory(): HasMany { return $this->hasMany(DomainBindingHistory::class); }

    public function bound(): Attribute
    {
        return Attribute::make(get: fn () => $this->bindable_id !== null);
    }

    public function service(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bindable_type === Service::class ? $this->bindable : null,
        );
    }
}
