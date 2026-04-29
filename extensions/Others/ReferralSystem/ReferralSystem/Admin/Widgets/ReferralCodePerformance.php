<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralCodePerformance extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Referral Code Details';

    protected function getTableQuery(): Builder | Relation | null
    {
        $filters = $this->resolvePageFilters();
        $codeId = $filters['referral_code_id'] ?? null;
        $currency = $this->resolveCurrencyFilter();

        $query = ReferralCode::query()
            ->with(['user', 'coupon'])
            ->withSum([
                'commissions as commissions_sum_amount' => fn ($q) => $q->where('currency_code', $currency),
            ], 'amount')
            ->withSum([
                'commissions as available_balance_sum_amount' => fn ($q) => $q
                    ->where('currency_code', $currency)
                    ->where('status', ReferralCommission::STATUS_AVAILABLE),
            ], 'amount')
            ->withSum([
                'commissions as reserved_balance_sum_amount' => fn ($q) => $q
                    ->where('currency_code', $currency)
                    ->where('status', ReferralCommission::STATUS_RESERVED),
            ], 'amount')
            ->withSum([
                'commissions as paid_balance_sum_amount' => fn ($q) => $q
                    ->where('currency_code', $currency)
                    ->where('status', ReferralCommission::STATUS_PAID),
            ], 'amount')
            ->withCount([
                'commissions as commissions_count' => fn ($q) => $q->where('currency_code', $currency),
                'withdrawals as withdrawals_count' => fn ($q) => $q->where('currency_code', $currency),
            ]);

        if ($codeId) {
            $query->where('id', $codeId);
        }

        return $query->orderByDesc('commissions_sum_amount');
    }

    public function table(Table $table): Table
    {
        $currency = $this->resolveCurrencyFilter();
        $filters = $this->resolvePageFilters();
        $codeId = $filters['referral_code_id'] ?? null;

        return $table
            ->query($this->getTableQuery())
            ->paginated($codeId ? false : [5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('code')
                    ->label('Referral Code')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->description(fn (ReferralCode $record) => $record->user?->email ?? 'Unknown owner'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                    ]),
                TextColumn::make('default_revenue_share')
                    ->label('Rev. Share')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->alignCenter(),
                TextColumn::make('coupon.code')
                    ->label('Coupon')
                    ->placeholder('—')
                    ->description(fn (ReferralCode $record) => $record->coupon ? $record->discountLabel() : null),
                TextColumn::make('clicks_count')
                    ->label('Clicks')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('purchases_count')
                    ->label('Purchases')
                    ->numeric()
                    ->alignCenter()
                    ->description(fn (ReferralCode $record) => $record->purchase_limit
                        ? 'Limit: ' . $record->purchase_limit
                        : 'Unlimited'
                    ),
                TextColumn::make('commissions_sum_amount')
                    ->label('Total Earned')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) ($state ?? 0), 2))
                    ->weight('bold')
                    ->alignEnd(),
                TextColumn::make('available_balance_sum_amount')
                    ->label('Available')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) ($state ?? 0), 2))
                    ->color('success')
                    ->alignEnd(),
                TextColumn::make('reserved_balance_sum_amount')
                    ->label('Reserved')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) ($state ?? 0), 2))
                    ->color('warning')
                    ->alignEnd(),
                TextColumn::make('paid_balance_sum_amount')
                    ->label('Paid Out')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) ($state ?? 0), 2))
                    ->color('gray')
                    ->alignEnd(),
                TextColumn::make('commissions_count')
                    ->label('# Commissions')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('withdrawals_count')
                    ->label('# Withdrawals')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->striped();
    }

    public function getTableHeading(): ?string
    {
        $filters = $this->resolvePageFilters();
        $codeId = $filters['referral_code_id'] ?? null;

        if ($codeId) {
            $code = ReferralCode::find($codeId);
            return $code ? 'Details for: ' . $code->code : 'Referral Code Details';
        }

        return 'All Referral Codes Performance (' . $this->resolveCurrencyFilter() . ')';
    }

    protected function resolveCurrencyFilter(): string
    {
        $filters = $this->resolvePageFilters();
        $currency = strtoupper((string) ($filters['currency_code'] ?? ''));

        if ($currency !== '') {
            return $currency;
        }

        return strtoupper((string) config('settings.default_currency', 'USD'));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePageFilters(): array
    {
        return $this->filters ?? [];
    }
}
