<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use App\Admin\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Pages\ReferralResourceDetailAnalytics;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralResourceAnalyticsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Resource Performance';

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder | Relation | null
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        return ReferralCommission::query()
            ->join('services', 'services.id', '=', 'ext_referral_commissions.service_id')
            ->join('products', 'products.id', '=', 'services.product_id')
            ->join('ext_referral_codes as code_map', 'code_map.id', '=', 'ext_referral_commissions.referral_code_id')
            ->where('ext_referral_commissions.currency_code', $currency)
            ->whereBetween('ext_referral_commissions.awarded_at', [$start, $end])
            ->groupBy('products.id', 'products.name')
            ->selectRaw('MIN(ext_referral_commissions.id) as id')
            ->selectRaw('products.id as product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COUNT(*) as commission_events_count')
            ->selectRaw('COUNT(DISTINCT ext_referral_commissions.invoice_id) as referral_orders_count')
            ->selectRaw('COUNT(DISTINCT services.user_id) as referred_customers_count')
            ->selectRaw('COUNT(DISTINCT ext_referral_commissions.referral_code_id) as referral_codes_count')
            ->selectRaw('COUNT(DISTINCT code_map.user_id) as referrers_count')
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
            )
            ->selectRaw('MAX(ext_referral_commissions.awarded_at) as last_awarded_at')
            ->orderByDesc('commission_amount');
    }

    public function table(Table $table): Table
    {
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('product_id')
                    ->label('Product ID')
                    ->sortable(),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('products.name', 'like', "%{$search}%")
                    )
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('referrers_count')
                    ->label('Referrers')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('referral_codes_count')
                    ->label('Codes')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('referral_orders_count')
                    ->label('Sales')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('referred_customers_count')
                    ->label('Customers')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('commission_events_count')
                    ->label('Commissions')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) $state, 2))
                    ->color('success')
                    ->weight('bold')
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
                TextColumn::make('last_awarded_at')
                    ->label('Last Commission')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('viewMore')
                    ->label('View More')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record): string => ReferralResourceDetailAnalytics::getUrl([
                        'product' => (int) $record->product_id,
                    ])),
                Action::make('openProduct')
                    ->label('Open Product')
                    ->icon('ri-external-link-line')
                    ->url(fn ($record): string => ProductResource::getUrl('edit', ['record' => (int) $record->product_id]))
                    ->openUrlInNewTab(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->striped();
    }
}
