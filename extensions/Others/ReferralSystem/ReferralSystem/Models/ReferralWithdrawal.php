<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Paymenter\Extensions\Others\ReferralSystem\Services\WithdrawalConfiguration;

class ReferralWithdrawal extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'ext_referral_withdrawals';

    protected $fillable = [
        'referral_code_id',
        'user_id',
        'amount',
        'currency_code',
        'payment_method',
        'payment_method_info',
        'status',
        'notes',
        'admin_notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function commissions()
    {
        return $this->hasMany(ReferralCommission::class, 'withdrawal_id');
    }

    public function paymentMethodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => WithdrawalConfiguration::paymentMethodLabel($this->payment_method)
        );
    }

    public function approve(?string $notes = null, ?int $adminId = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'admin_notes' => $notes ?? $this->admin_notes,
            'processed_at' => now(),
            'processed_by' => $adminId,
        ])->save();
    }

    public function reject(?string $notes = null, ?int $adminId = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'admin_notes' => $notes ?? $this->admin_notes,
            'processed_at' => now(),
            'processed_by' => $adminId,
        ])->save();
    }
}
