<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Model;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class ReferralManualCommissionSchedule extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'ext_referral_manual_commission_schedules';

    protected $fillable = [
        'referral_code_id',
        'service_id',
        'user_id',
        'created_by',
        'title',
        'notes',
        'currency_code',
        'amount',
        'status',
        'frequency_unit',
        'frequency_interval',
        'starts_at',
        'next_run_at',
        'last_run_at',
        'ends_at',
        'max_cycles',
        'cycles_generated',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'frequency_interval' => 'integer',
        'starts_at' => 'datetime',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_cycles' => 'integer',
        'cycles_generated' => 'integer',
        'meta' => 'array',
    ];

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function commissions()
    {
        return $this->hasMany(ReferralCommission::class, 'manual_schedule_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function shouldRun(?Carbon $now = null): bool
    {
        $now ??= now();

        if (!$this->isActive() || !$this->next_run_at) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        if ($this->max_cycles !== null && $this->cycles_generated >= $this->max_cycles) {
            return false;
        }

        return $this->next_run_at->lte($now);
    }

    public function cadenceLabel(): string
    {
        $interval = max(1, (int) $this->frequency_interval);
        $unit = (string) $this->frequency_unit;

        if ($interval === 1) {
            return match ($unit) {
                'day' => 'Daily',
                'week' => 'Weekly',
                'month' => 'Monthly',
                'quarter' => 'Quarterly',
                'year' => 'Yearly',
                default => ucfirst($unit),
            };
        }

        return 'Every ' . $interval . ' ' . $unit . 's';
    }
}
