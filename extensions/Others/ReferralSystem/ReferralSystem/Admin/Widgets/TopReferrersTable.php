<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;

class TopReferrersTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top Performing Referrers';

    protected function getTableQuery(): Builder | Relation | null
    {
        $filters = $this->resolvePageFilters();
        $codeId = $filters['referral_code_id'] ?? null;
        $currency = $this->resolveCurrencyFilter();

        return ReferralCode::query()
            ->with('user')
            ->when($codeId, fn ($q) => $q->where('id', $codeId))
            ->withSum([
                'commissions as commissions_sum_amount' => fn ($q) => $q->where('currency_code', $currency),
            ], 'amount')
            ->orderByDesc('commissions_sum_amount');
    }

    public function table(Table $table): Table
    {
        $currency = $this->resolveCurrencyFilter();

        return $table
            ->query($this->getTableQuery())
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->width('40px'),
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('user.email')
                    ->label('Owner')
                    ->searchable()
                    ->limit(25),
                TextColumn::make('clicks_count')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('purchases_count')
                    ->label('Purchases')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('conversion')
                    ->label('Conv.')
                    ->getStateUsing(function (ReferralCode $record): string {
                        if ($record->clicks_count <= 0) {
                            return '0%';
                        }
                        return round(($record->purchases_count / $record->clicks_count) * 100, 1) . '%';
                    })
                    ->alignCenter()
                    ->color(fn (ReferralCode $record) => match (true) {
                        $record->clicks_count > 0 && ($record->purchases_count / $record->clicks_count) >= 0.1 => 'success',
                        $record->clicks_count > 0 && ($record->purchases_count / $record->clicks_count) >= 0.05 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('commissions_sum_amount')
                    ->label('Earnings')
                    ->formatStateUsing(fn ($state) => $currency . ' ' . number_format((float) $state, 2))
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                    ]),
            ])
            ->striped();
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
