<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralResourceAnalyticsStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        $baseQuery = ReferralCommission::query()
            ->join('services', 'services.id', '=', 'ext_referral_commissions.service_id')
            ->join('ext_referral_codes as code_map', 'code_map.id', '=', 'ext_referral_commissions.referral_code_id')
            ->where('ext_referral_commissions.currency_code', $currency)
            ->whereBetween('ext_referral_commissions.awarded_at', [$start, $end]);

        $products = (clone $baseQuery)->distinct('services.product_id')->count('services.product_id');
        $sales = (clone $baseQuery)->distinct('ext_referral_commissions.invoice_id')->count('ext_referral_commissions.invoice_id');
        $customers = (clone $baseQuery)->distinct('services.user_id')->count('services.user_id');
        $referrers = (clone $baseQuery)->distinct('code_map.user_id')->count('code_map.user_id');
        $commission = (clone $baseQuery)->sum('ext_referral_commissions.amount');
        $available = (clone $baseQuery)
            ->where('ext_referral_commissions.status', ReferralCommission::STATUS_AVAILABLE)
            ->sum('ext_referral_commissions.amount');
        $paid = (clone $baseQuery)
            ->where('ext_referral_commissions.status', ReferralCommission::STATUS_PAID)
            ->sum('ext_referral_commissions.amount');

        return [
            Stat::make('Resources', number_format($products))
                ->description('Products with referral activity')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Referral Sales', number_format($sales))
                ->description(number_format($customers) . ' unique customers')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
            Stat::make('Active Referrers', number_format($referrers))
                ->description('Users who generated commissions')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            Stat::make('Commission (' . $currency . ')', number_format((float) $commission, 2))
                ->description('Generated in selected period')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Available (' . $currency . ')', number_format((float) $available, 2))
                ->description('Commissions still available')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
            Stat::make('Paid (' . $currency . ')', number_format((float) $paid, 2))
                ->description('Commissions marked paid')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('gray'),
        ];
    }
}
