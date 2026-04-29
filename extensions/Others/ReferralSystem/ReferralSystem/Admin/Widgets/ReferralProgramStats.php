<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

class ReferralProgramStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $codeId = $this->filters['referral_code_id'] ?? null;
        $currency = $this->resolveCurrencyFilter();

        return [
            $this->buildPendingApplicationsStat(),
            $this->buildPendingWithdrawalsStat($codeId, $currency),
            $this->buildReferredCustomersStat($codeId),
            $this->buildConversionRateStat($codeId),
            $this->buildAvailableBalanceStat($codeId, $currency),
            $this->buildPaidOutStat($codeId, $currency),
        ];
    }

    protected function buildPendingApplicationsStat(): Stat
    {
        $pending = ReferralApplication::where('status', ReferralApplication::STATUS_PENDING)->count();
        $total = ReferralApplication::count();

        return Stat::make('Pending Applications', $pending)
            ->description($total . ' total applications')
            ->descriptionIcon('heroicon-m-document-text')
            ->color($pending > 0 ? 'warning' : 'success');
    }

    protected function buildPendingWithdrawalsStat(?int $codeId, string $currency): Stat
    {
        $query = ReferralWithdrawal::where('status', ReferralWithdrawal::STATUS_PENDING);
        if ($codeId) {
            $query->where('referral_code_id', $codeId);
        }
        $query->where('currency_code', $currency);

        $pending = $query->count();
        $pendingAmount = (clone $query)->sum('amount');

        return Stat::make('Pending Withdrawals', $pending)
            ->description($currency . ' ' . number_format($pendingAmount, 2) . ' requested')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color($pending > 0 ? 'warning' : 'success');
    }

    protected function buildReferredCustomersStat(?int $codeId): Stat
    {
        $query = ReferralOrder::query()
            ->join('orders', 'ext_referral_orders.order_id', '=', 'orders.id');

        if ($codeId) {
            $query->where('ext_referral_orders.referral_code_id', $codeId);
        }

        $uniqueCustomers = $query->distinct('orders.user_id')->count('orders.user_id');
        $totalOrders = ReferralOrder::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->count();

        return Stat::make('Referred Customers', number_format($uniqueCustomers))
            ->description($totalOrders . ' referral orders')
            ->descriptionIcon('heroicon-m-users')
            ->color('info');
    }

    protected function buildConversionRateStat(?int $codeId): Stat
    {
        $query = ReferralCode::query();
        if ($codeId) {
            $query->where('id', $codeId);
        }

        $totalClicks = $query->sum('clicks_count');
        $totalPurchases = $query->sum('purchases_count');

        $conversionRate = $totalClicks > 0
            ? round(($totalPurchases / $totalClicks) * 100, 2)
            : 0;

        $color = match (true) {
            $conversionRate >= 10 => 'success',
            $conversionRate >= 5 => 'info',
            $conversionRate >= 1 => 'warning',
            default => 'gray',
        };

        return Stat::make('Conversion Rate', $conversionRate . '%')
            ->description($totalPurchases . ' purchases / ' . $totalClicks . ' clicks')
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color($color);
    }

    protected function buildAvailableBalanceStat(?int $codeId, string $currency): Stat
    {
        $query = ReferralCommission::where('status', ReferralCommission::STATUS_AVAILABLE);
        if ($codeId) {
            $query->where('referral_code_id', $codeId);
        }
        $query->where('currency_code', $currency);

        $available = $query->sum('amount');

        $reservedQuery = ReferralCommission::where('status', ReferralCommission::STATUS_RESERVED);
        if ($codeId) {
            $reservedQuery->where('referral_code_id', $codeId);
        }
        $reservedQuery->where('currency_code', $currency);
        $reserved = $reservedQuery->sum('amount');

        return Stat::make('Available Balance', $currency . ' ' . number_format($available, 2))
            ->description($currency . ' ' . number_format($reserved, 2) . ' reserved')
            ->descriptionIcon('heroicon-m-wallet')
            ->color('success');
    }

    protected function buildPaidOutStat(?int $codeId, string $currency): Stat
    {
        $query = ReferralCommission::where('status', ReferralCommission::STATUS_PAID);
        if ($codeId) {
            $query->where('referral_code_id', $codeId);
        }
        $query->where('currency_code', $currency);

        $paid = $query->sum('amount');

        $approvedWithdrawals = ReferralWithdrawal::where('status', ReferralWithdrawal::STATUS_APPROVED)
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->where('currency_code', $currency)
            ->count();

        return Stat::make('Total Paid Out', $currency . ' ' . number_format($paid, 2))
            ->description($approvedWithdrawals . ' withdrawals completed')
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('primary');
    }

    protected function resolveCurrencyFilter(): string
    {
        $currency = strtoupper((string) ($this->filters['currency_code'] ?? ''));

        if ($currency !== '') {
            return $currency;
        }

        return strtoupper((string) config('settings.default_currency', 'USD'));
    }
}
