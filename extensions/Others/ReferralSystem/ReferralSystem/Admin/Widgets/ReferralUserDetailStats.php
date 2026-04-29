<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

class ReferralUserDetailStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public ?int $analyticsUserId = null;

    public ?string $analyticsUserEmail = null;

    protected function getStats(): array
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);
        $codeIds = $this->userCodeIds();

        $codesCount = ReferralCode::query()
            ->where('user_id', $this->analyticsUserId)
            ->count();
        $clicks = ReferralCode::query()
            ->where('user_id', $this->analyticsUserId)
            ->sum('clicks_count');
        $purchases = ReferralCode::query()
            ->where('user_id', $this->analyticsUserId)
            ->sum('purchases_count');
        $orders = $codeIds->isEmpty()
            ? 0
            : ReferralOrder::query()
                ->whereIn('referral_code_id', $codeIds)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        $commission = $codeIds->isEmpty()
            ? 0
            : ReferralCommission::query()
                ->whereIn('referral_code_id', $codeIds)
                ->where('currency_code', $currency)
                ->whereBetween('awarded_at', [$start, $end])
                ->sum('amount');
        $available = $codeIds->isEmpty()
            ? 0
            : ReferralCommission::query()
                ->whereIn('referral_code_id', $codeIds)
                ->where('currency_code', $currency)
                ->where('status', ReferralCommission::STATUS_AVAILABLE)
                ->sum('amount');
        $pendingWithdrawals = ReferralWithdrawal::query()
            ->where('user_id', $this->analyticsUserId)
            ->where('currency_code', $currency)
            ->where('status', ReferralWithdrawal::STATUS_PENDING)
            ->count();

        return [
            Stat::make('Codes', number_format($codesCount))
                ->description($this->analyticsUserEmail ?: 'Unknown user')
                ->descriptionIcon('heroicon-m-identification')
                ->color('primary'),
            Stat::make('Clicks', number_format((float) $clicks))
                ->description('All tracked clicks')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('info'),
            Stat::make('Purchases', number_format((float) $purchases))
                ->description('All referral purchases')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
            Stat::make('Sales (Period)', number_format((float) $orders))
                ->description('Referral sales in selected period')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
            Stat::make('Commission (' . $currency . ')', number_format((float) $commission, 2))
                ->description('Generated in selected period')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Available (' . $currency . ')', number_format((float) $available, 2))
                ->description($pendingWithdrawals . ' pending withdrawals')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }

    private function userCodeIds()
    {
        return ReferralCode::query()
            ->where('user_id', $this->analyticsUserId)
            ->pluck('id');
    }
}

