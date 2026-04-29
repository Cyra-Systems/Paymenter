<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Pages;

use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralResourceAnalyticsStats;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralResourceAnalyticsTable;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralResourceAnalyticsTrendChart;

class ReferralResourceAnalytics extends BaseReferralAnalyticsDashboard
{
    protected static ?string $title = 'Resource Analytics';

    protected static ?string $navigationLabel = 'Resource Analytics';

    protected static ?string $slug = 'referral-analitics/resources';

    protected static string $routePath = 'referral-analitics/resources';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-box-3-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-box-3-fill';

    protected static ?int $navigationSort = 6;

    public function getWidgets(): array
    {
        return [
            ReferralResourceAnalyticsStats::class,
            ReferralResourceAnalyticsTrendChart::class,
            ReferralResourceAnalyticsTable::class,
        ];
    }
}

