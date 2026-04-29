<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralResourceDetailStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public ?int $analyticsProductId = null;

    public ?string $analyticsProductName = null;

    public ?int $analyticsUserScopeId = null;

    public ?string $analyticsUserScopeEmail = null;

    protected function getStats(): array
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        $baseQuery = ReferralCommission::query()
            ->join('services', 'services.id', '=', 'ext_referral_commissions.service_id')
            ->join('ext_referral_codes as code_map', 'code_map.id', '=', 'ext_referral_commissions.referral_code_id')
            ->where('services.product_id', $this->analyticsProductId)
            ->where('ext_referral_commissions.currency_code', $currency)
            ->whereBetween('ext_referral_commissions.awarded_at', [$start, $end])
            ->when($this->analyticsUserScopeId, fn ($query) => $query->where('code_map.user_id', $this->analyticsUserScopeId));

        $sales = (clone $baseQuery)->distinct('ext_referral_commissions.invoice_id')->count('ext_referral_commissions.invoice_id');
        $customers = (clone $baseQuery)->distinct('services.user_id')->count('services.user_id');
        $referrers = (clone $baseQuery)->distinct('code_map.user_id')->count('code_map.user_id');
        $codes = (clone $baseQuery)->distinct('ext_referral_commissions.referral_code_id')->count('ext_referral_commissions.referral_code_id');
        $commission = (clone $baseQuery)->sum('ext_referral_commissions.amount');
        $available = (clone $baseQuery)
            ->where('ext_referral_commissions.status', ReferralCommission::STATUS_AVAILABLE)
            ->sum('ext_referral_commissions.amount');
        $paid = (clone $baseQuery)
            ->where('ext_referral_commissions.status', ReferralCommission::STATUS_PAID)
            ->sum('ext_referral_commissions.amount');

        $scopeText = $this->analyticsUserScopeEmail
            ? 'Scoped to ' . $this->analyticsUserScopeEmail
            : 'All referrers';

        return [
            Stat::make('Resource', $this->analyticsProductName ?: ('#' . $this->analyticsProductId))
                ->description($scopeText)
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Referral Sales', number_format($sales))
                ->description(number_format($customers) . ' customers')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
            Stat::make('Referrers', number_format($referrers))
                ->description(number_format($codes) . ' referral codes')
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
