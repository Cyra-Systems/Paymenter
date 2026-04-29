<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\Concerns\HasReadableYAxisTicks;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralRevenueChart extends ChartWidget
{
    use HasReadableYAxisTicks;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Commission Revenue';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '420px';

    protected function getData(): array
    {
        [$start, $end, $per] = $this->getDateRange();
        $codeId = $this->filters['referral_code_id'] ?? null;
        $currency = $this->resolveCurrencyFilter();

        $query = ReferralCommission::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->where('currency_code', $currency);

        $commissions = Trend::query($query)
            ->dateColumn('awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->sum('amount');

        // Also get count for secondary metric
        $counts = Trend::query(
            ReferralCommission::query()
                ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
                ->where('currency_code', $currency)
        )
            ->dateColumn('awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Commission Amount',
                    'data' => $commissions->map(fn (TrendValue $v) => round((float) $v->aggregate, 2))->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Commission Count',
                    'data' => $counts->map(fn (TrendValue $v) => $v->aggregate)->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => false,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $commissions->map(fn (TrendValue $v) => $this->formatLabel($v->date, $per))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array|RawJs|null
    {
        $selectedCurrency = $this->resolveCurrencyFilter();
        $data = $this->getData();

        return $this->buildDualAxisOptions(
            [$data['datasets'][0] ?? ['data' => []]],
            [$data['datasets'][1] ?? ['data' => []]],
            'Amount (' . $selectedCurrency . ')',
            'Count',
        );
    }

    protected function formatLabel(string $date, string $per): string
    {
        return match ($per) {
            'hour' => Carbon::parse($date)->format('H:i'),
            'day' => Carbon::parse($date)->format('M d'),
            'week' => 'W' . Carbon::parse($date)->format('W'),
            'month' => Carbon::parse($date)->format('M Y'),
            default => Carbon::parse($date)->format('M d'),
        };
    }

    protected function getDateRange(): array
    {
        $period = $this->filters['period'] ?? 'month';

        $end = now();
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7)->startOfDay(),
            'month' => now()->subDays(30)->startOfDay(),
            'quarter' => now()->subDays(90)->startOfDay(),
            'year' => now()->subDays(365)->startOfDay(),
            'all' => now()->subYears(5)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        $per = match ($period) {
            'today' => 'hour',
            'week' => 'day',
            'month' => 'day',
            'quarter' => 'week',
            'year' => 'month',
            'all' => 'month',
            default => 'day',
        };

        return [$start, $end, $per];
    }

    protected function resolveCurrencyFilter(): string
    {
        $currency = strtoupper((string) ($this->filters['currency_code'] ?? ''));

        if ($currency !== '') {
            return $currency;
        }

        return strtoupper((string) config('settings.default_currency', 'USD'));
    }
}
