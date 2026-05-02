<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class DomainTld extends Model implements Auditable
{
    use HasFactory, Traits\Auditable;

    protected $fillable = [
        'tld',
        'enabled',
        'register_price',
        'transfer_price',
        'renewal_price',
        'redemption_price',
        'currency_code',
        'margin_percent',
        'min_years',
        'max_years',
        'whois_privacy_supported',
        'transfer_supported',
        'epp_required',
        'display_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'register_price' => 'decimal:2',
        'transfer_price' => 'decimal:2',
        'renewal_price' => 'decimal:2',
        'redemption_price' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'min_years' => 'integer',
        'max_years' => 'integer',
        'whois_privacy_supported' => 'boolean',
        'transfer_supported' => 'boolean',
        'epp_required' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function priceWithMargin(string $kind = 'register_price'): float
    {
        $base = (float) ($this->{$kind} ?? 0);
        $globalMargin = (float) config('settings.domains.global_margin_percent', 0);
        $tldMargin = (float) ($this->margin_percent ?? 0);

        return round($base * (1 + ($globalMargin + $tldMargin) / 100), 2);
    }
}
