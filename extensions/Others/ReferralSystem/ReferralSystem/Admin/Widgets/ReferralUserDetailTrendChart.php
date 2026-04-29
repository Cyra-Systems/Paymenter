<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Support\ReferralAnalyticsFilters;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\Concerns\HasReadableYAxisTicks;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralUserDetailTrendChart extends ChartWidget
{
    use HasReadableYAxisTicks;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'User Referral Revenue';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '420px';

    public ?int $analyticsUserId = null;

    protected function getData(): array
    {
        [$start, $end, $bucket] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        $codeIds = ReferralCode::query()
            ->where('user_id', $this->analyticsUserId)
            ->pluck('id');

        if ($codeIds->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Generated (' . $currency . ')',
                        'data' => [],
                        'backgroundColor' => 'rgba(37, 99, 235, 0.25)',
                        'borderColor' => '#2563EB',
                        'tension' => 0.3,
                    ],
                    [
                        'label' => 'Paid (' . $currency . ')',
                        'data' => [],
                        'backgroundColor' => 'rgba(22, 163, 74, 0.25)',
                        'borderColor' => '#16A34A',
                        'borderDash' => [6, 4],
                        'tension' => 0.3,
                    ],
                ],
                'labels' => [],
            ];
        }

        $generatedCommissions = Trend::query(
            ReferralCommission::query()
                ->whereIn('referral_code_id', $codeIds)
                ->where('currency_code', $currency)
                ->whereBetween('awarded_at', [$start, $end])
        )
            ->dateColumn('awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($bucket)}()
            ->sum('amount');

        $paidCommissions = Trend::query(
            ReferralCommission::query()
                ->whereIn('referral_code_id', $codeIds)
                ->where('currency_code', $currency)
                ->where('status', ReferralCommission::STATUS_PAID)
                ->whereBetween('awarded_at', [$start, $end])
        )
            ->dateColumn('awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($bucket)}()
            ->sum('amount');

        $labels = $generatedCommissions->isNotEmpty()
            ? $generatedCommissions->map(fn (TrendValue $value) => $this->formatLabel($value->date, $bucket))->toArray()
            : $paidCommissions->map(fn (TrendValue $value) => $this->formatLabel($value->date, $bucket))->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Generated (' . $currency . ')',
                    'data' => $generatedCommissions->map(fn (TrendValue $value) => round((float) $value->aggregate, 2))->toArray(),
                    'backgroundColor' => 'rgba(37, 99, 235, 0.25)',
                    'borderColor' => '#2563EB',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Paid (' . $currency . ')',
                    'data' => $paidCommissions->map(fn (TrendValue $value) => round((float) $value->aggregate, 2))->toArray(),
                    'backgroundColor' => 'rgba(22, 163, 74, 0.25)',
                    'borderColor' => '#16A34A',
                    'borderDash' => [6, 4],
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array|RawJs|null
    {
        return $this->buildSingleAxisOptions($this->getData()['datasets']);
    }

    private function formatLabel(string $date, string $bucket): string
    {
        return match ($bucket) {
            'hour' => Carbon::parse($date)->format('H:i'),
            'day' => Carbon::parse($date)->format('M d'),
            'week' => 'W' . Carbon::parse($date)->format('W'),
            'month' => Carbon::parse($date)->format('M Y'),
            default => Carbon::parse($date)->format('M d'),
        };
    }
}
