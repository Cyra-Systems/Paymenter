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
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralResourceDetailReferrerTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Referrer Breakdown';

    protected int|string|array $columnSpan = 'full';

    public ?int $analyticsProductId = null;

    public ?int $analyticsUserScopeId = null;

    protected function getTableQuery(): Builder | Relation | null
    {
        [$start, $end] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        return ReferralCommission::query()
            ->join('services', 'services.id', '=', 'ext_referral_commissions.service_id')
            ->join('ext_referral_codes as codes', 'codes.id', '=', 'ext_referral_commissions.referral_code_id')
            ->join('users', 'users.id', '=', 'codes.user_id')
            ->where('services.product_id', $this->analyticsProductId)
            ->where('ext_referral_commissions.currency_code', $currency)
            ->whereBetween('ext_referral_commissions.awarded_at', [$start, $end])
            ->when($this->analyticsUserScopeId, fn ($query) => $query->where('codes.user_id', $this->analyticsUserScopeId))
            ->groupBy('codes.id', 'codes.code', 'codes.clicks_count', 'codes.purchases_count', 'users.id', 'users.email')
            ->selectRaw('MIN(ext_referral_commissions.id) as id')
            ->selectRaw('codes.id as referral_code_id')
            ->selectRaw('codes.code as referral_code')
            ->selectRaw('codes.clicks_count as clicks_total')
            ->selectRaw('codes.purchases_count as purchases_total')
            ->selectRaw('users.id as owner_user_id')
            ->selectRaw('users.email as owner_email')
            ->selectRaw("MAX(TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')))) as owner_name")
            ->selectRaw('COUNT(*) as commission_events_count')
            ->selectRaw('COUNT(DISTINCT ext_referral_commissions.invoice_id) as referral_orders_count')
            ->selectRaw('COUNT(DISTINCT services.user_id) as referred_customers_count')
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
                TextColumn::make('owner_user_id')
                    ->label('User ID')
                    ->sortable(),
                TextColumn::make('owner_email')
                    ->label('Referrer')
                    ->description(function ($record): string {
                        $name = trim((string) ($record->owner_name ?? ''));

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
                TextColumn::make('referral_code')
                    ->label('Code')
                    ->copyable()
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('codes.code', 'like', "%{$search}%")
                    )
                    ->weight('bold'),
                TextColumn::make('clicks_total')
                    ->label('Clicks')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchases_total')
                    ->label('Purchases')
                    ->numeric()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Action::make('viewUserAnalytics')
                    ->label('View User Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record): string => ReferralUserDetailAnalytics::getUrl([
                        'user' => (int) $record->owner_user_id,
                    ])),
                Action::make('openUser')
                    ->label('Open User')
                    ->icon('ri-external-link-line')
                    ->url(fn ($record): string => UserResource::getUrl('edit', ['record' => (int) $record->owner_user_id]))
                    ->openUrlInNewTab(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->striped();
    }
}
