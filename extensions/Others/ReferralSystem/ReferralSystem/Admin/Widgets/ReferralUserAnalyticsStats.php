<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;

class ReferralUserAnalyticsStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        $referrerCount = ReferralCode::query()->distinct('user_id')->count('user_id');
        $activeReferrerCount = ReferralCode::query()
            ->where('status', ReferralCode::STATUS_ACTIVE)
            ->distinct('user_id')
            ->count('user_id');
        $clicks = ReferralCode::query()->sum('clicks_count');
        $orders = ReferralOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $commission = ReferralCommission::query()
            ->where('currency_code', $currency)
            ->whereBetween('awarded_at', [$start, $end])
            ->sum('amount');
        $available = ReferralCommission::query()
            ->where('currency_code', $currency)
            ->where('status', ReferralCommission::STATUS_AVAILABLE)
            ->whereBetween('awarded_at', [$start, $end])
            ->sum('amount');
        $paid = ReferralCommission::query()
            ->where('currency_code', $currency)
            ->where('status', ReferralCommission::STATUS_PAID)
            ->whereBetween('awarded_at', [$start, $end])
            ->sum('amount');

        return [
            Stat::make('Referral Users', number_format($referrerCount))
                ->description(number_format($activeReferrerCount) . ' active')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Tracked Clicks', number_format($clicks))
                ->description('All referral code clicks')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('info'),
            Stat::make('Referral Sales', number_format($orders))
                ->description('Sales in selected period')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
            Stat::make('Commission (' . $currency . ')', number_format((float) $commission, 2))
                ->description('Generated in selected period')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Available (' . $currency . ')', number_format((float) $available, 2))
                ->description('Commissions available to withdraw')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
            Stat::make('Paid Out (' . $currency . ')', number_format((float) $paid, 2))
                ->description('Commissions marked paid')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('gray'),
        ];
    }
}
