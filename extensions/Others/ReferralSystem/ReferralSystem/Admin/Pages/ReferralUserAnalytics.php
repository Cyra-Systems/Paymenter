<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Pages;

use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralUserAnalyticsStats;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralUserAnalyticsTable;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralUserAnalyticsTrendChart;

class ReferralUserAnalytics extends BaseReferralAnalyticsDashboard
{
    protected static ?string $title = 'User Analytics';

    protected static ?string $navigationLabel = 'User Analytics';

    protected static ?string $slug = 'referral-analitics/users';

    protected static string $routePath = 'referral-analitics/users';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-user-search-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-user-search-fill';

    protected static ?int $navigationSort = 5;

    public function getWidgets(): array
    {
        return [
            ReferralUserAnalyticsStats::class,
            ReferralUserAnalyticsTrendChart::class,
            ReferralUserAnalyticsTable::class,
        ];
    }
}

