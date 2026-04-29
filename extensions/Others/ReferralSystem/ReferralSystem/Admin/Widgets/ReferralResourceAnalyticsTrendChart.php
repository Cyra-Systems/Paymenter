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
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralResourceAnalyticsTrendChart extends ChartWidget
{
    use HasReadableYAxisTicks;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Resource Revenue';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '420px';

    protected function getData(): array
    {
        [$start, $end, $bucket] = ReferralAnalyticsFilters::resolveDateRange($this->filters);
        $currency = ReferralAnalyticsFilters::resolveCurrency($this->filters);

        $baseQuery = ReferralCommission::query()
            ->join('services', 'services.id', '=', 'ext_referral_commissions.service_id')
            ->where('ext_referral_commissions.currency_code', $currency)
            ->whereBetween('ext_referral_commissions.awarded_at', [$start, $end]);

        $generatedCommissions = Trend::query(clone $baseQuery)
            ->dateColumn('ext_referral_commissions.awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($bucket)}()
            ->sum('ext_referral_commissions.amount');

        $paidCommissions = Trend::query(
            (clone $baseQuery)->where('ext_referral_commissions.status', ReferralCommission::STATUS_PAID)
        )
            ->dateColumn('ext_referral_commissions.awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($bucket)}()
            ->sum('ext_referral_commissions.amount');

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
