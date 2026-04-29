<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use App\Admin\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Pages\ReferralUserDetailAnalytics;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;

class ReferralUserAnalyticsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Referral Users';

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder | Relation | null
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        $commissionWindow = ReferralCommission::query()
            ->join('ext_referral_codes as code_map', 'code_map.id', '=', 'ext_referral_commissions.referral_code_id')
            ->where('ext_referral_commissions.currency_code', $currency)
            ->whereBetween('ext_referral_commissions.awarded_at', [$start, $end])
            ->groupBy('code_map.user_id')
            ->selectRaw('code_map.user_id as owner_user_id')
            ->selectRaw('SUM(ext_referral_commissions.amount) as commission_amount')
            ->selectRaw(
                'SUM(CASE WHEN ext_referral_commissions.status = ? THEN ext_referral_commissions.amount ELSE 0 END) as available_amount',
                [ReferralCommission::STATUS_AVAILABLE]
            )
            ->selectRaw(
                'SUM(CASE WHEN ext_referral_commissions.status = ? THEN ext_referral_commissions.amount ELSE 0 END) as reserved_amount',
                [ReferralCommission::STATUS_RESERVED]
            )
            ->selectRaw(
                'SUM(CASE WHEN ext_referral_commissions.status = ? THEN ext_referral_commissions.amount ELSE 0 END) as paid_amount',
                [ReferralCommission::STATUS_PAID]
            );

        $ordersWindow = ReferralOrder::query()
            ->join('ext_referral_codes as code_map', 'code_map.id', '=', 'ext_referral_orders.referral_code_id')
            ->join('orders', 'orders.id', '=', 'ext_referral_orders.order_id')
            ->whereBetween('ext_referral_orders.created_at', [$start, $end])
            ->groupBy('code_map.user_id')
            ->selectRaw('code_map.user_id as owner_user_id')
            ->selectRaw('COUNT(*) as referral_orders_count')
            ->selectRaw('COUNT(DISTINCT orders.user_id) as referred_customers_count');

        return ReferralCode::query()
            ->join('users', 'users.id', '=', 'ext_referral_codes.user_id')
            ->leftJoinSub($commissionWindow, 'commission_window', fn ($join) => $join
                ->on('commission_window.owner_user_id', '=', 'users.id'))
            ->leftJoinSub($ordersWindow, 'orders_window', fn ($join) => $join
                ->on('orders_window.owner_user_id', '=', 'users.id'))
            ->groupBy('users.id', 'users.email')
            ->selectRaw('users.id as id')
            ->selectRaw('users.id as user_id')
            ->selectRaw('users.email as user_email')
            ->selectRaw("MAX(TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')))) as user_name")
            ->selectRaw('COUNT(DISTINCT ext_referral_codes.id) as referral_codes_count')
            ->selectRaw('SUM(ext_referral_codes.clicks_count) as clicks_total')
            ->selectRaw('SUM(ext_referral_codes.purchases_count) as purchases_total')
            ->selectRaw('MAX(COALESCE(orders_window.referral_orders_count, 0)) as referral_orders_count')
            ->selectRaw('MAX(COALESCE(orders_window.referred_customers_count, 0)) as referred_customers_count')
            ->selectRaw('MAX(COALESCE(commission_window.commission_amount, 0)) as commission_amount')
            ->selectRaw('MAX(COALESCE(commission_window.available_amount, 0)) as available_amount')
            ->selectRaw('MAX(COALESCE(commission_window.reserved_amount, 0)) as reserved_amount')
            ->selectRaw('MAX(COALESCE(commission_window.paid_amount, 0)) as paid_amount')
            ->orderByDesc('commission_amount')
            ->orderByDesc('clicks_total');
    }

    public function table(Table $table): Table
    {
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable(),
                TextColumn::make('user_email')
                    ->label('User')
                    ->description(function ($record): string {
                        $name = trim((string) ($record->user_name ?? ''));

                        return $name !== '' ? $name : '—';
                    })
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where(fn (Builder $searchQuery) => $searchQuery
                            ->where('users.email', 'like', "%{$search}%")
                            ->orWhere('users.first_name', 'like', "%{$search}%")
                            ->orWhere('users.last_name', 'like', "%{$search}%")
                        )
                    )
                    ->sortable(),
                TextColumn::make('referral_codes_count')
                    ->label('Codes')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('clicks_total')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('purchases_total')
                    ->label('Purchases')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('conversion_rate')
                    ->label('Conv.')
                    ->getStateUsing(function ($record): string {
                        $clicks = (float) ($record->clicks_total ?? 0);
                        $purchases = (float) ($record->purchases_total ?? 0);

                        if ($clicks <= 0) {
                            return '0%';
                        }

                        return number_format(($purchases / $clicks) * 100, 2) . '%';
                    })
                    ->alignCenter(),
                TextColumn::make('referral_orders_count')
                    ->label('Sales')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('referred_customers_count')
                    ->label('Customers')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) $state, 2))
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->alignEnd(),
                TextColumn::make('available_amount')
                    ->label('Available')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) $state, 2))
                    ->color('primary')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reserved_amount')
                    ->label('Reserved')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) $state, 2))
                    ->color('warning')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) $state, 2))
                    ->color('gray')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('viewMore')
                    ->label('View More')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record): string => ReferralUserDetailAnalytics::getUrl([
                        'user' => (int) $record->user_id,
                    ])),
                Action::make('openUser')
                    ->label('Open User')
                    ->icon('ri-external-link-line')
                    ->url(fn ($record): string => UserResource::getUrl('edit', ['record' => (int) $record->user_id]))
                    ->openUrlInNewTab(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->striped();
    }
}
