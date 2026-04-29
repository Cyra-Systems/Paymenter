<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Coupon;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReferralCode extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $table = 'ext_referral_codes';

    protected $fillable = [
        'user_id',
        'coupon_id',
        'code',
        'status',
        'default_revenue_share',
        'purchase_limit',
        'purchases_count',
        'clicks_count',
        'notes',
        'suspended_at',
    ];

    protected $casts = [
        'default_revenue_share' => 'decimal:2',
        'purchase_limit' => 'integer',
        'purchases_count' => 'integer',
        'clicks_count' => 'integer',
        'suspended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function discountLabel(): ?string
    {
        if (!$this->relationLoaded('coupon')) {
            $this->load('coupon');
        }

        if (!$this->coupon) {
            return null;
        }

        $label = match ($this->coupon->type) {
            'percentage' => number_format((float) $this->coupon->value, 2) . '%',
            'fixed' => number_format((float) $this->coupon->value, 2) . ' ' . (config('settings.default_currency') ?? ''),
            'free_setup' => 'Free setup fee',
            default => null,
        };

        if ($label && (int) ($this->coupon->recurring ?? 0) === 1) {
            $label .= ' (' . __('referrals::referrals.customer_discount_first') . ')';
        }

        return $label;
    }

    public function applications()
    {
        return $this->hasMany(ReferralApplication::class);
    }

    public function packageOverrides()
    {
        return $this->hasMany(ReferralCodePackage::class);
    }

    public function commissions()
    {
        return $this->hasMany(ReferralCommission::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(ReferralWithdrawal::class);
    }

    public function manualCommissionSchedules()
    {
        return $this->hasMany(ReferralManualCommissionSchedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function hasReachedPurchaseLimit(): bool
    {
        if (empty($this->purchase_limit)) {
            return false;
        }

        return $this->purchases_count >= $this->purchase_limit;
    }

    public function revenueShareForProduct(?int $productId): float
    {
        if (!$productId) {
            return (float) $this->default_revenue_share;
        }

        $override = $this->packageOverrides
            ->firstWhere('product_id', $productId);

        if (!$override) {
            return (float) $this->default_revenue_share;
        }

        return (float) $override->revenue_share;
    }

    public function remainingPurchases(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->remainingPurchasesForProduct(null)
        );
    }

    public function getPurchaseLimitForProduct(?int $productId): ?int
    {
        if (!$productId) {
            return $this->purchase_limit;
        }

        return $this->packageOverrides
            ->firstWhere('product_id', $productId)
            ?->purchase_limit ?? $this->purchase_limit;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getPurchasesCountForProduct(?int $productId): int
    {
        if (!$productId) {
            return (int) $this->purchases_count;
        }

        return (int) $this->packageOverrides
            ->firstWhere('product_id', $productId)
            ?->purchases_count ?? (int) $this->purchases_count;
    }

    public function remainingPurchasesForProduct(?int $productId): ?int
    {
        $limit = $this->getPurchaseLimitForProduct($productId);

        if (!$limit) {
            return null;
        }

        $consumed = $this->packageOverrides
            ->firstWhere('product_id', $productId)
            ?->purchases_count ?? $this->purchases_count;

        return max(0, $limit - $consumed);
    }

    public function canRewardPurchase(?int $productId, bool $isFirstCommissionForService): bool
    {
        if (!$isFirstCommissionForService) {
            return true;
        }

        $remaining = $this->remainingPurchasesForProduct($productId);

        return $remaining === null || $remaining > 0;
    }

    public function incrementPurchaseCounters(?int $productId, bool $isFirstCommissionForService): void
    {
        if (!$isFirstCommissionForService) {
            return;
        }

        $this->increment('purchases_count');

        if (!$productId) {
            return;
        }

        $override = $this->packageOverrides()
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($override) {
            $override->increment('purchases_count');
        }
    }

    public function markSuspended(?string $reason = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'notes' => $reason ? ($this->notes ? $this->notes . "\n\n" . $reason : $reason) : $this->notes,
        ])->save();
    }

    public function markActive(): void
    {
        $this->forceFill([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
        ])->save();
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }
}
