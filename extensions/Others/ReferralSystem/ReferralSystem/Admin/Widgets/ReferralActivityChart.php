<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\Concerns\HasReadableYAxisTicks;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralActivityChart extends ChartWidget
{
    use HasReadableYAxisTicks;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Referral Activity';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '420px';

    protected function getData(): array
    {
        [$start, $end, $per] = $this->getDateRange();
        $codeId = $this->filters['referral_code_id'] ?? null;
        $currency = $this->resolveCurrencyFilter();

        // Referral orders (purchases via referral links)
        $ordersQuery = ReferralOrder::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId));

        $orders = Trend::query($ordersQuery)
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->count();

        // Unique customers from referral orders (single query, grouped in-memory by period bucket).
        $customerRows = ReferralOrder::query()
            ->join('orders', 'ext_referral_orders.order_id', '=', 'orders.id')
            ->when($codeId, fn ($q) => $q->where('ext_referral_orders.referral_code_id', $codeId))
            ->whereBetween('ext_referral_orders.created_at', [$start, $end])
            ->get(['ext_referral_orders.created_at', 'orders.user_id']);

        $customersByBucket = $customerRows
            ->groupBy(fn ($row) => $this->bucketKey(Carbon::parse($row->created_at), $per))
            ->map(fn ($group) => $group->pluck('user_id')->unique()->count());

        // Commissions generated
        $commissionsQuery = ReferralCommission::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->where('currency_code', $currency);

        $commissions = Trend::query($commissionsQuery)
            ->dateColumn('awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Referral Orders',
                    'data' => $orders->map(fn (TrendValue $v) => $v->aggregate)->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'New Customers',
                    'data' => $orders
                        ->map(fn (TrendValue $v) => $customersByBucket[$this->bucketKey(Carbon::parse($v->date), $per)] ?? 0)
                        ->toArray(),
                    'backgroundColor' => 'rgba(168, 85, 247, 0.7)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Commissions',
                    'data' => $commissions->map(fn (TrendValue $v) => $v->aggregate)->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $orders->map(fn (TrendValue $v) => $this->formatLabel($v->date, $per))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|RawJs|null
    {
        return $this->buildSingleAxisOptions($this->getData()['datasets'], true);
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

    protected function bucketKey(Carbon $date, string $per): string
    {
        return match ($per) {
            'hour' => $date->copy()->startOfHour()->format('Y-m-d H:00'),
            'week' => $date->copy()->startOfWeek()->format('o-\WW'),
            'month' => $date->copy()->startOfMonth()->format('Y-m'),
            default => $date->copy()->startOfDay()->format('Y-m-d'),
        };
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
