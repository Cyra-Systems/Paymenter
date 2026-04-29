<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Pages;

use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Forms\Components\Select;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralStatsOverview;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralProgramStats;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralRevenueChart;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralActivityChart;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\TopReferrersTable;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralCodePerformance;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

class ReferralOverview extends Dashboard
{
    use HasFiltersAction;

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected static ?string $title = 'Overview';

    protected static ?string $navigationLabel = 'Overview';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-dashboard-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-dashboard-fill';

    protected static ?int $navigationSort = 0;

    protected static string $routePath = 'referral-overview';

    protected function getHeaderActions(): array
    {
        $defaultCurrency = (string) config('settings.default_currency', 'USD');

        $codes = ReferralCode::with('user')
            ->get()
            ->mapWithKeys(fn ($code) => [
                $code->id => $code->code . ' (' . ($code->user?->email ?? 'Unknown') . ')'
            ])
            ->toArray();

        $currencies = ReferralCommission::query()
            ->select('currency_code')
            ->whereNotNull('currency_code')
            ->distinct()
            ->pluck('currency_code')
            ->merge(
                ReferralWithdrawal::query()
                    ->select('currency_code')
                    ->whereNotNull('currency_code')
                    ->distinct()
                    ->pluck('currency_code')
            )
            ->push($defaultCurrency)
            ->filter()
            ->map(fn (string $currency) => strtoupper($currency))
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn (string $currency) => [$currency => $currency])
            ->toArray();

        return [
            FilterAction::make()
                ->slideOver(false)
                ->schema([
                    Select::make('period')
                        ->label('Time Period')
                        ->options([
                            'today' => 'Today',
                            'week' => 'Last 7 Days',
                            'month' => 'Last 30 Days',
                            'quarter' => 'Last 90 Days',
                            'year' => 'Last 365 Days',
                            'all' => 'All Time',
                        ])
                        ->default('month'),
                    Select::make('referral_code_id')
                        ->label('Filter by Referral Code')
                        ->options($codes)
                        ->placeholder('All Codes')
                        ->searchable(),
                    Select::make('currency_code')
                        ->label('Currency')
                        ->options($currencies)
                        ->default($defaultCurrency)
                        ->searchable()
                        ->required(),
                ]),
        ];
    }

    public function getWidgets(): array
    {
        return [
            ReferralStatsOverview::class,
            ReferralProgramStats::class,
            ReferralRevenueChart::class,
            ReferralActivityChart::class,
            TopReferrersTable::class,
            ReferralCodePerformance::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasPermission('admin.referrals.analytics.view') ?? false;
    }
}
