<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Pages;

use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;

abstract class BaseReferralAnalyticsDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static string|\UnitEnum|null $navigationGroup = 'Referral System';

    protected function getHeaderActions(): array
    {
        $defaultCurrency = strtoupper((string) config('settings.default_currency', 'USD'));

        return [
            FilterAction::make()
                ->slideOver(false)
                ->schema([
                    Select::make('period')
                        ->label('Time Period')
                        ->options(ReferralAnalyticsFilters::periodOptions())
                        ->default(ReferralAnalyticsFilters::defaultPeriod())
                        ->required(),
                    Select::make('currency_code')
                        ->label('Currency')
                        ->options(ReferralAnalyticsFilters::currencyOptions())
                        ->default($defaultCurrency)
                        ->searchable()
                        ->required(),
                ]),
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

