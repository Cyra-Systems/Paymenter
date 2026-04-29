<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReferralApplication extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'ext_referral_applications';

    protected $fillable = [
        'user_id',
        'status',
        'requested_code',
        'message',
        'admin_notes',
        'referral_code_id',
        'desired_revenue_share',
        'decision_at',
    ];

    protected $casts = [
        'desired_revenue_share' => 'decimal:2',
        'decision_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function approve(ReferralCode $code, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'decision_at' => now(),
            'referral_code_id' => $code->id,
            'admin_notes' => $notes ?? $this->admin_notes,
        ])->save();
    }

    public function reject(?string $notes = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'decision_at' => now(),
            'admin_notes' => $notes ?? $this->admin_notes,
        ])->save();
    }
}
