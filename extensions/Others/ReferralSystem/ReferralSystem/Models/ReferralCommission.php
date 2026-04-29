<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Model;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class ReferralCommission extends Model
{
    use HasFactory;

    protected ?Collection $groupedRowsCache = null;

    protected ?array $groupedTotalsCache = null;

    protected ?string $groupedStatusSummaryCache = null;

    protected ?string $groupedStatusColorCache = null;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const SOURCE_INVOICE = 'invoice';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_RECURRING = 'recurring';

    protected $table = 'ext_referral_commissions';

    protected $fillable = [
        'referral_code_id',
        'withdrawal_id',
        'invoice_id',
        'invoice_item_id',
        'service_id',
        'user_id',
        'award_signature',
        'source_type',
        'source_label',
        'manual_schedule_id',
        'created_by',
        'currency_code',
        'amount',
        'status',
        'meta',
        'awarded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'awarded_at' => 'datetime',
    ];

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function withdrawal()
    {
        return $this->belongsTo(ReferralWithdrawal::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manualSchedule()
    {
        return $this->belongsTo(ReferralManualCommissionSchedule::class, 'manual_schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function reserveForWithdrawal(int $withdrawalId): void
    {
        $this->forceFill([
            'withdrawal_id' => $withdrawalId,
            'status' => self::STATUS_RESERVED,
        ])->save();
    }

    public function markPaid(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PAID,
        ])->save();
    }

    public function release(): void
    {
        $this->forceFill([
            'withdrawal_id' => null,
            'status' => self::STATUS_AVAILABLE,
        ])->save();
    }

    public function rootCommissionId(): int
    {
        return (int) (($this->meta['split_from'] ?? null) ?: $this->id);
    }

    public function isManual(): bool
    {
        return in_array($this->source_type, [self::SOURCE_MANUAL, self::SOURCE_RECURRING], true);
    }

    public function sourceLabel(): string
    {
        if ($this->source_label) {
            return $this->source_label;
        }

        return match ($this->source_type) {
            self::SOURCE_MANUAL => 'Manual admin commission',
            self::SOURCE_RECURRING => 'Recurring manual commission',
            default => 'Invoice commission',
        };
    }

    public function isSplitChild(): bool
    {
        return isset($this->meta['split_from']) && (int) $this->meta['split_from'] > 0;
    }

    public function groupedRows()
    {
        if ($this->groupedRowsCache instanceof Collection) {
            return $this->groupedRowsCache;
        }

        $rootId = $this->rootCommissionId();

        return $this->groupedRowsCache = static::query()
            ->whereKey($rootId)
            ->orWhere('meta->split_from', $rootId)
            ->orderBy('id')
            ->get([
                'id',
                'amount',
                'status',
                'withdrawal_id',
                'award_signature',
                'meta',
                'created_at',
                'updated_at',
            ]);
    }

    public function groupedTotals(): array
    {
        if (is_array($this->groupedTotalsCache)) {
            return $this->groupedTotalsCache;
        }

        $rows = $this->groupedRows();

        $totals = [
            'total' => 0.0,
            'available' => 0.0,
            'reserved' => 0.0,
            'paid' => 0.0,
            'void' => 0.0,
        ];

        foreach ($rows as $row) {
            $amount = round((float) $row->amount, 2);
            $totals['total'] += $amount;

            if (array_key_exists($row->status, $totals)) {
                $totals[$row->status] += $amount;
            }
        }

        return $this->groupedTotalsCache = array_map(
            fn (float $amount): float => round($amount, 2),
            $totals
        );
    }

    public function groupedBreakdown(): array
    {
        return $this->groupedRows()
            ->map(function (self $row): array {
                return [
                    'id' => $row->id,
                    'amount' => round((float) $row->amount, 2),
                    'status' => $row->status,
                    'withdrawal_id' => $row->withdrawal_id,
                    'award_signature' => $row->award_signature,
                    'is_split_child' => $row->isSplitChild(),
                    'created_at' => optional($row->created_at)?->toDateTimeString(),
                    'updated_at' => optional($row->updated_at)?->toDateTimeString(),
                ];
            })
            ->all();
    }

    public function groupedStatusSummary(): string
    {
        if ($this->groupedStatusSummaryCache !== null) {
            return $this->groupedStatusSummaryCache;
        }

        $statuses = collect($this->groupedRows()->pluck('status'))
            ->filter()
            ->unique()
            ->values();

        if ($statuses->isEmpty()) {
            return $this->groupedStatusSummaryCache = ucfirst((string) $this->status);
        }

        return $this->groupedStatusSummaryCache = $statuses
            ->map(fn (string $status) => ucfirst($status))
            ->implode(' + ');
    }

    public function groupedStatusColor(): string
    {
        if ($this->groupedStatusColorCache !== null) {
            return $this->groupedStatusColorCache;
        }

        $statuses = collect($this->groupedRows()->pluck('status'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($statuses) > 1) {
            return $this->groupedStatusColorCache = 'info';
        }

        return $this->groupedStatusColorCache = match ($statuses[0] ?? $this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_RESERVED => 'warning',
            self::STATUS_VOID => 'danger',
            default => 'primary',
        };
    }
}
