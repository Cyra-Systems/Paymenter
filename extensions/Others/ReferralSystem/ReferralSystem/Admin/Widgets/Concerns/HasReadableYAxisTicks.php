<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\Concerns;

trait HasReadableYAxisTicks
{
    protected function buildSingleAxisOptions(array $datasets, bool $wholeNumbers = false): array
    {
        return [
            'scales' => [
                'y' => $this->buildAxisConfig($wholeNumbers),
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }

    protected function buildDualAxisOptions(array $leftDatasets, array $rightDatasets, ?string $leftTitle = null, ?string $rightTitle = null): array
    {
        $leftAxis = $this->buildAxisConfig();
        $rightAxis = $this->buildAxisConfig(true);

        $leftAxis['type'] = 'linear';
        $leftAxis['display'] = true;
        $leftAxis['position'] = 'left';

        $rightAxis['type'] = 'linear';
        $rightAxis['display'] = true;
        $rightAxis['position'] = 'right';
        $rightAxis['grid'] = [
            'drawOnChartArea' => false,
        ];

        if ($leftTitle) {
            $leftAxis['title'] = [
                'display' => true,
                'text' => $leftTitle,
            ];
        }

        if ($rightTitle) {
            $rightAxis['title'] = [
                'display' => true,
                'text' => $rightTitle,
            ];
        }

        return [
            'scales' => [
                'y' => $leftAxis,
                'y1' => $rightAxis,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }

    protected function buildAxisConfig(bool $wholeNumbers = false): array
    {
        return [
            'beginAtZero' => true,
            'grace' => $wholeNumbers ? '12%' : '8%',
            'ticks' => array_filter([
                'precision' => $wholeNumbers ? 0 : 2,
                'maxTicksLimit' => 5,
                'padding' => 8,
            ], fn ($value) => $value !== null),
        ];
    }
}
