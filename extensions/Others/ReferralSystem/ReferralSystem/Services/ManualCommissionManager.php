<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralManualCommissionSchedule;
use Paymenter\Extensions\Others\ReferralSystem\Services\ReferralNotifier;

class ManualCommissionManager
{
    public static function previewCalculatedCommission(array $data): ?array
    {
        $code = ReferralCode::query()
            ->with('packageOverrides')
            ->find($data['referral_code_id'] ?? null);

        if (!$code) {
            return null;
        }

        $service = !empty($data['service_id'])
            ? Service::query()->find($data['service_id'])
            : null;

        $invoiceItem = !empty($data['invoice_item_id'])
            ? InvoiceItem::query()->with(['invoice', 'reference'])->find($data['invoice_item_id'])
            : null;

        $invoice = !empty($data['invoice_id'])
            ? Invoice::query()->with('items.reference')->find($data['invoice_id'])
            : null;

        return self::calculateCommissionFromContext($code, $service, $invoiceItem, $invoice);
    }

    public static function createManualCommission(array $data, ?int $adminId = null): ReferralCommission
    {
        return DB::transaction(function () use ($data, $adminId): ReferralCommission {
            $code = ReferralCode::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail((int) $data['referral_code_id']);

            $service = null;
            if (!empty($data['service_id'])) {
                $service = Service::query()->find($data['service_id']);
            }

            $invoiceItem = !empty($data['invoice_item_id'])
                ? InvoiceItem::query()->with(['invoice', 'reference'])->find($data['invoice_item_id'])
                : null;

            $invoice = !empty($data['invoice_id'])
                ? Invoice::query()->with('items.reference')->find($data['invoice_id'])
                : null;

            self::ensureManualCommissionTargetIsAvailable($invoiceItem, $invoice);

            $referredUserId = $data['user_id'] ?? null;
            if (!$referredUserId && $service?->user_id) {
                $referredUserId = $service->user_id;
            }

            $calculated = !empty($data['auto_calculate_amount'])
                ? self::calculateCommissionFromContext($code, $service, $invoiceItem, $invoice)
                : null;

            $awardedAt = !empty($data['awarded_at'])
                ? Carbon::parse($data['awarded_at'])
                : now();

            $meta = array_filter([
                'reason' => trim((string) ($data['reason'] ?? '')),
                'manual_reference' => trim((string) ($data['manual_reference'] ?? '')),
                'linked_service_id' => $service?->id,
                'created_by_admin_id' => $adminId,
                'is_manual' => true,
                'auto_calculated_amount' => !empty($data['auto_calculate_amount']),
                'calculation_basis' => $calculated['basis'] ?? null,
                'share' => $calculated['share'] ?? null,
                'base_amount' => $calculated['base_amount'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            return ReferralCommission::create([
                'referral_code_id' => $code->id,
                'invoice_id' => $invoice?->id ?? ($data['invoice_id'] ?? null),
                'invoice_item_id' => $invoiceItem?->id ?? ($data['invoice_item_id'] ?? null),
                'service_id' => $service?->id,
                'user_id' => $referredUserId,
                'withdrawal_id' => null,
                'award_signature' => 'manual:' . Str::lower((string) Str::ulid()),
                'source_type' => ReferralCommission::SOURCE_MANUAL,
                'source_label' => $data['source_label'] ?? 'Manual admin commission',
                'manual_schedule_id' => $data['manual_schedule_id'] ?? null,
                'created_by' => $adminId,
                'currency_code' => strtoupper((string) ($calculated['currency_code'] ?? $data['currency_code'])),
                'amount' => round((float) ($calculated['amount'] ?? $data['amount']), 2),
                'status' => $data['status'] ?? ReferralCommission::STATUS_AVAILABLE,
                'meta' => !empty($meta) ? $meta : null,
                'awarded_at' => $awardedAt,
            ]);
        });
    }

    public static function createSchedule(array $data, ?int $adminId = null): ReferralManualCommissionSchedule
    {
        return DB::transaction(function () use ($data, $adminId): ReferralManualCommissionSchedule {
            $service = null;
            if (!empty($data['service_id'])) {
                $service = Service::query()->find($data['service_id']);
            }

            $referredUserId = $data['user_id'] ?? null;
            if (!$referredUserId && $service?->user_id) {
                $referredUserId = $service->user_id;
            }

            $startsAt = !empty($data['starts_at'])
                ? Carbon::parse($data['starts_at'])
                : now();

            $code = ReferralCode::query()
                ->with('packageOverrides')
                ->findOrFail((int) $data['referral_code_id']);

            $calculated = !empty($data['auto_calculate_amount'])
                ? self::calculateCommissionFromContext($code, $service, null, null)
                : null;

            $schedule = ReferralManualCommissionSchedule::create([
                'referral_code_id' => $code->id,
                'service_id' => $service?->id,
                'user_id' => $referredUserId,
                'created_by' => $adminId,
                'title' => trim((string) $data['title']),
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
                'currency_code' => strtoupper((string) ($calculated['currency_code'] ?? $data['currency_code'])),
                'amount' => round((float) ($calculated['amount'] ?? $data['amount']), 2),
                'status' => ReferralManualCommissionSchedule::STATUS_ACTIVE,
                'frequency_unit' => (string) $data['frequency_unit'],
                'frequency_interval' => max(1, (int) $data['frequency_interval']),
                'starts_at' => $startsAt,
                'next_run_at' => $startsAt,
                'last_run_at' => null,
                'ends_at' => !empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null,
                'max_cycles' => filled($data['max_cycles'] ?? null) ? (int) $data['max_cycles'] : null,
                'cycles_generated' => 0,
                'meta' => [
                    'manual_reference' => trim((string) ($data['manual_reference'] ?? '')) ?: null,
                    'auto_calculate_amount' => !empty($data['auto_calculate_amount']),
                    'share' => $calculated['share'] ?? null,
                    'base_amount' => $calculated['base_amount'] ?? null,
                    'calculation_basis' => $calculated['basis'] ?? null,
                ],
            ]);

            if (!empty($data['issue_immediately'])) {
                self::issueImmediateScheduleCommission($schedule);
            }

            return $schedule->fresh();
        });
    }

    public static function runSchedule(ReferralManualCommissionSchedule $schedule, bool $force = false): ?ReferralCommission
    {
        $commission = DB::transaction(function () use ($schedule, $force): ?ReferralCommission {
            $schedule = ReferralManualCommissionSchedule::query()
                ->with('referralCode')
                ->lockForUpdate()
                ->find($schedule->id);

            if (!$schedule) {
                return null;
            }

            if (!$force && !$schedule->shouldRun()) {
                return null;
            }

            if (!$schedule->referralCode || !$schedule->referralCode->isActive()) {
                return null;
            }

            $cycle = (int) $schedule->cycles_generated + 1;
            $calculated = !empty($schedule->meta['auto_calculate_amount'])
                ? self::calculateCommissionFromContext($schedule->referralCode, $schedule->service, null, null)
                : null;

            $commission = self::createRecurringCommission(
                $schedule,
                $cycle,
                $calculated,
                $schedule->next_run_at ?? now(),
            );

            $nextRunAt = self::nextRunAt(
                $schedule->next_run_at ?? $schedule->starts_at ?? now(),
                $schedule->frequency_unit,
                $schedule->frequency_interval,
            );

            $schedule->forceFill([
                'cycles_generated' => $cycle,
                'last_run_at' => now(),
                'next_run_at' => $nextRunAt,
            ])->save();

            if (
                ($schedule->max_cycles !== null && $cycle >= $schedule->max_cycles)
                || ($schedule->ends_at && $nextRunAt->gt($schedule->ends_at))
            ) {
                $schedule->forceFill([
                    'status' => ReferralManualCommissionSchedule::STATUS_COMPLETED,
                    'next_run_at' => null,
                ])->save();
            }

            return $commission;
        });

        self::notifyCommissionEarned($commission);

        return $commission;
    }

    public static function processDueSchedules(?Carbon $now = null): int
    {
        if (!Schema::hasTable('ext_referral_manual_commission_schedules')) {
            return 0;
        }

        $now ??= now();
        $processed = 0;

        ReferralManualCommissionSchedule::query()
            ->where('status', ReferralManualCommissionSchedule::STATUS_ACTIVE)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->chunkById(100, function ($schedules) use (&$processed) {
                foreach ($schedules as $schedule) {
                    if (self::runSchedule($schedule) instanceof ReferralCommission) {
                        $processed++;
                    }
                }
            });

        return $processed;
    }

    protected static function calculateCommissionFromContext(
        ReferralCode $code,
        ?Service $service = null,
        ?InvoiceItem $invoiceItem = null,
        ?Invoice $invoice = null,
    ): ?array {
        if ($invoiceItem instanceof InvoiceItem) {
            $service = $service ?: ($invoiceItem->reference instanceof Service ? $invoiceItem->reference : null);
            $productId = $service?->product_id;
            $share = $code->revenueShareForProduct($productId);
            $baseAmount = round((float) $invoiceItem->total(), 2);

            return [
                'amount' => round($baseAmount * ($share / 100), 2),
                'share' => $share,
                'base_amount' => $baseAmount,
                'basis' => 'invoice_item',
                'currency_code' => $invoiceItem->invoice?->currency_code ?? $service?->currency_code ?? null,
                'product_id' => $productId,
            ];
        }

        if ($service instanceof Service) {
            $share = $code->revenueShareForProduct($service->product_id);
            $baseAmount = round((float) $service->calculatePrice(), 2);

            return [
                'amount' => round($baseAmount * ($share / 100), 2),
                'share' => $share,
                'base_amount' => $baseAmount,
                'basis' => 'service_price',
                'currency_code' => $service->currency_code,
                'product_id' => $service->product_id,
            ];
        }

        if ($invoice instanceof Invoice) {
            $serviceItem = $invoice->items
                ->first(fn (InvoiceItem $item) => $item->reference instanceof Service);

            if ($serviceItem instanceof InvoiceItem) {
                return self::calculateCommissionFromContext(
                    $code,
                    $serviceItem->reference instanceof Service ? $serviceItem->reference : null,
                    $serviceItem,
                    $invoice,
                );
            }

            $share = (float) $code->default_revenue_share;
            $baseAmount = round((float) $invoice->total, 2);

            return [
                'amount' => round($baseAmount * ($share / 100), 2),
                'share' => $share,
                'base_amount' => $baseAmount,
                'basis' => 'invoice_total',
                'currency_code' => $invoice->currency_code,
                'product_id' => null,
            ];
        }

        return null;
    }

    protected static function issueImmediateScheduleCommission(ReferralManualCommissionSchedule $schedule): ?ReferralCommission
    {
        $commission = DB::transaction(function () use ($schedule): ?ReferralCommission {
            $schedule = ReferralManualCommissionSchedule::query()
                ->with(['referralCode', 'service'])
                ->lockForUpdate()
                ->find($schedule->id);

            if (!$schedule || !$schedule->referralCode || !$schedule->referralCode->isActive()) {
                return null;
            }

            $cycle = (int) $schedule->cycles_generated + 1;
            $calculated = !empty($schedule->meta['auto_calculate_amount'])
                ? self::calculateCommissionFromContext($schedule->referralCode, $schedule->service, null, null)
                : null;

            $commission = self::createRecurringCommission(
                $schedule,
                $cycle,
                $calculated,
                now(),
            );

            $now = now();
            $nextRunAt = $schedule->starts_at && $schedule->starts_at->isFuture()
                ? $schedule->starts_at
                : self::nextRunAtAfter(
                    $schedule->starts_at ?? $now,
                    $schedule->frequency_unit,
                    $schedule->frequency_interval,
                    $now,
                );

            $updates = [
                'cycles_generated' => $cycle,
                'last_run_at' => $now,
                'next_run_at' => $nextRunAt,
            ];

            if (
                ($schedule->max_cycles !== null && $cycle >= $schedule->max_cycles)
                || ($schedule->ends_at && $nextRunAt && $nextRunAt->gt($schedule->ends_at))
            ) {
                $updates['status'] = ReferralManualCommissionSchedule::STATUS_COMPLETED;
                $updates['next_run_at'] = null;
            }

            $schedule->forceFill($updates)->save();

            return $commission;
        });

        self::notifyCommissionEarned($commission);

        return $commission;
    }

    protected static function createRecurringCommission(
        ReferralManualCommissionSchedule $schedule,
        int $cycle,
        ?array $calculated,
        Carbon $awardedAt,
    ): ReferralCommission {
        return ReferralCommission::create([
            'referral_code_id' => $schedule->referral_code_id,
            'invoice_id' => null,
            'invoice_item_id' => null,
            'service_id' => $schedule->service_id,
            'user_id' => $schedule->user_id,
            'withdrawal_id' => null,
            'award_signature' => sprintf('schedule:%d:%d', $schedule->id, $cycle),
            'source_type' => ReferralCommission::SOURCE_RECURRING,
            'source_label' => $schedule->title,
            'manual_schedule_id' => $schedule->id,
            'created_by' => $schedule->created_by,
            'currency_code' => strtoupper((string) ($calculated['currency_code'] ?? $schedule->currency_code)),
            'amount' => round((float) ($calculated['amount'] ?? $schedule->amount), 2),
            'status' => ReferralCommission::STATUS_AVAILABLE,
            'meta' => array_filter([
                'schedule_id' => $schedule->id,
                'schedule_title' => $schedule->title,
                'schedule_cycle' => $cycle,
                'schedule_notes' => $schedule->notes,
                'manual_reference' => $schedule->meta['manual_reference'] ?? null,
                'auto_calculated_amount' => !empty($schedule->meta['auto_calculate_amount']),
                'share' => $calculated['share'] ?? ($schedule->meta['share'] ?? null),
                'base_amount' => $calculated['base_amount'] ?? null,
                'calculation_basis' => $calculated['basis'] ?? ($schedule->meta['calculation_basis'] ?? null),
            ], fn ($value) => $value !== null && $value !== ''),
            'awarded_at' => $awardedAt,
        ]);
    }

    protected static function notifyCommissionEarned(?ReferralCommission $commission): void
    {
        if (!$commission) {
            return;
        }

        $commission->loadMissing('referralCode.user');

        if ($commission->referralCode) {
            ReferralNotifier::sendCommissionEarned($commission->referralCode, $commission);
        }
    }

    protected static function ensureManualCommissionTargetIsAvailable(
        ?InvoiceItem $invoiceItem,
        ?Invoice $invoice,
    ): void {
        if ($invoiceItem) {
            $hasExistingCommission = ReferralCommission::query()
                ->where('invoice_item_id', $invoiceItem->id)
                ->exists();

            if ($hasExistingCommission) {
                throw ValidationException::withMessages([
                    'invoice_item_id' => 'This invoice item already has a referral commission. The latest referral attribution has already been used.',
                ]);
            }

            return;
        }

        if (!$invoice) {
            return;
        }

        $hasExistingCommission = ReferralCommission::query()
            ->where('invoice_id', $invoice->id)
            ->exists();

        if ($hasExistingCommission) {
            throw ValidationException::withMessages([
                'invoice_id' => 'This invoice already has a referral commission. The latest referral attribution has already been used.',
            ]);
        }
    }

    public static function nextRunAt(Carbon $current, string $unit, int $interval): Carbon
    {
        $interval = max(1, $interval);

        return match ($unit) {
            'day' => $current->copy()->addDays($interval),
            'week' => $current->copy()->addWeeks($interval),
            'month' => $current->copy()->addMonthsNoOverflow($interval),
            'quarter' => $current->copy()->addMonthsNoOverflow($interval * 3),
            'year' => $current->copy()->addYearsNoOverflow($interval),
            default => $current->copy()->addMonthsNoOverflow($interval),
        };
    }

    protected static function nextRunAtAfter(Carbon $current, string $unit, int $interval, Carbon $after): Carbon
    {
        $nextRunAt = $current->copy();

        while ($nextRunAt->lte($after)) {
            $nextRunAt = self::nextRunAt($nextRunAt, $unit, $interval);
        }

        return $nextRunAt;
    }
}
