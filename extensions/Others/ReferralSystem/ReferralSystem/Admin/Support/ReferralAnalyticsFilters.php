<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Support;

use Illuminate\Support\Carbon;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

class ReferralAnalyticsFilters
{
    public static function periodOptions(): array
    {
        return [
            'today' => 'Today',
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
            'quarter' => 'Last 90 Days',
            'year' => 'Last 365 Days',
            'all' => 'All Time',
        ];
    }

    public static function defaultPeriod(): string
    {
        return 'month';
    }

    public static function resolvePeriod(?array $filters = null): string
    {
        $filters = static::normalizeFilters($filters);
        $period = strtolower((string) ($filters['period'] ?? static::defaultPeriod()));

        return array_key_exists($period, static::periodOptions())
            ? $period
            : static::defaultPeriod();
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public static function resolveDateRange(?array $filters = null): array
    {
        $period = static::resolvePeriod($filters);

        $end = now();
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7)->startOfDay(),
            'month' => now()->subDays(30)->startOfDay(),
            'quarter' => now()->subDays(90)->startOfDay(),
            'year' => now()->subDays(365)->startOfDay(),
            'all' => now()->subYears(10)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        $bucket = match ($period) {
            'today' => 'hour',
            'week', 'month' => 'day',
            'quarter' => 'week',
            'year', 'all' => 'month',
            default => 'day',
        };

        return [$start, $end, $bucket];
    }

    public static function resolveCurrency(?array $filters = null): string
    {
        $filters = static::normalizeFilters($filters);
        $configured = strtoupper(trim((string) ($filters['currency_code'] ?? '')));
        if ($configured !== '') {
            return $configured;
        }

        return strtoupper((string) config('settings.default_currency', 'USD'));
    }

    public static function currencyOptions(): array
    {
        $defaultCurrency = strtoupper((string) config('settings.default_currency', 'USD'));

        return ReferralCommission::query()
            ->select('currency_code')
            ->whereNotNull('currency_code')
            ->distinct()
            ->pluck('currency_code')
            ->merge(
                ReferralWithdrawal::query()
                    ->select('currency_code')
                    ->whereNotNull('currency_code')
                    ->distinct()
                    ->pluck('currency_code')
            )
            ->push($defaultCurrency)
            ->filter(fn ($currency) => is_string($currency) && preg_match('/^[A-Za-z]{3}$/', $currency))
            ->map(fn (string $currency) => strtoupper(trim($currency)))
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn (string $currency) => [$currency => $currency])
            ->toArray();
    }

    private static function normalizeFilters(?array $filters): array
    {
        return $filters ?? [];
    }
}
