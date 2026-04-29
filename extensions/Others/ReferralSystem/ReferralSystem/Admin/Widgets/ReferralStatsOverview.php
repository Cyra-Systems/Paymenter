<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;

class ReferralStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        [$start, $end, $per] = $this->getDateRange();
        $codeId = $this->filters['referral_code_id'] ?? null;

        return [
            $this->buildClicksStat($start, $end, $per, $codeId),
            $this->buildPurchasesStat($start, $end, $per, $codeId),
            $this->buildCommissionsStat($start, $end, $per, $codeId),
            $this->buildActiveCodesStat($codeId),
        ];
    }

    protected function buildClicksStat(Carbon $start, Carbon $end, string $per, ?int $codeId): Stat
    {
        $query = ReferralCode::query();
        if ($codeId) {
            $query->where('id', $codeId);
        }

        $totalClicks = (clone $query)->sum('clicks_count');

        // For trend, we need to track click history - using orders as proxy
        $trendQuery = ReferralOrder::query();
        if ($codeId) {
            $trendQuery->where('referral_code_id', $codeId);
        }

        $trend = Trend::query($trendQuery)
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->count();

        $chartData = $trend->map(fn (TrendValue $v) => $v->aggregate)->toArray();

        return Stat::make('Total Clicks', number_format($totalClicks))
            ->description('Referral link clicks')
            ->descriptionIcon('heroicon-m-cursor-arrow-rays')
            ->chart($chartData)
            ->color('primary');
    }

    protected function buildPurchasesStat(Carbon $start, Carbon $end, string $per, ?int $codeId): Stat
    {
        $query = ReferralCode::query();
        if ($codeId) {
            $query->where('id', $codeId);
        }

        $totalPurchases = (clone $query)->sum('purchases_count');

        // Trend from referral orders
        $trendQuery = ReferralOrder::query();
        if ($codeId) {
            $trendQuery->where('referral_code_id', $codeId);
        }

        $trend = Trend::query($trendQuery)
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->count();

        $chartData = $trend->map(fn (TrendValue $v) => $v->aggregate)->toArray();

        // Period comparison
        $periodOrders = ReferralOrder::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $previousStart = $start->copy()->sub($start->diff($end));
        $previousOrders = ReferralOrder::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->whereBetween('created_at', [$previousStart, $start])
            ->count();

        $change = $previousOrders > 0
            ? round((($periodOrders - $previousOrders) / $previousOrders) * 100, 1)
            : ($periodOrders > 0 ? 100 : 0);

        $description = $change >= 0
            ? "↑ {$change}% vs previous period"
            : "↓ " . abs($change) . "% vs previous period";

        return Stat::make('Referral Purchases', number_format($totalPurchases))
            ->description($description)
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($chartData)
            ->color($change >= 0 ? 'success' : 'danger');
    }

    protected function buildCommissionsStat(Carbon $start, Carbon $end, string $per, ?int $codeId): Stat
    {
        $currency = $this->resolveCurrencyFilter();

        $query = ReferralCommission::query();
        if ($codeId) {
            $query->where('referral_code_id', $codeId);
        }
        $query->where('currency_code', $currency);

        $totalCommissions = (clone $query)->sum('amount');

        // Period commissions
        $periodCommissions = ReferralCommission::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->where('currency_code', $currency)
            ->whereBetween('awarded_at', [$start, $end])
            ->sum('amount');

        $trend = Trend::query(
            ReferralCommission::query()
                ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
                ->where('currency_code', $currency)
        )
            ->dateColumn('awarded_at')
            ->between($start, $end)
            ->{'per' . ucfirst($per)}()
            ->sum('amount');

        $chartData = $trend->map(fn (TrendValue $v) => (float) $v->aggregate)->toArray();

        // Previous period
        $previousStart = $start->copy()->sub($start->diff($end));
        $previousCommissions = ReferralCommission::query()
            ->when($codeId, fn ($q) => $q->where('referral_code_id', $codeId))
            ->where('currency_code', $currency)
            ->whereBetween('awarded_at', [$previousStart, $start])
            ->sum('amount');

        $change = $previousCommissions > 0
            ? round((($periodCommissions - $previousCommissions) / $previousCommissions) * 100, 1)
            : ($periodCommissions > 0 ? 100 : 0);

        $description = $change >= 0
            ? "↑ {$change}% vs previous period"
            : "↓ " . abs($change) . "% vs previous period";

        return Stat::make('Total Commissions', $currency . ' ' . number_format($totalCommissions, 2))
            ->description($description)
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($chartData)
            ->color($change >= 0 ? 'success' : 'warning');
    }

    protected function buildActiveCodesStat(?int $codeId): Stat
    {
        if ($codeId) {
            $code = ReferralCode::find($codeId);
            $status = $code?->status ?? 'unknown';
            return Stat::make('Code Status', ucfirst($status))
                ->description($code?->code ?? 'N/A')
                ->descriptionIcon('heroicon-m-identification')
                ->color($status === 'active' ? 'success' : 'danger');
        }

        $activeCount = ReferralCode::where('status', ReferralCode::STATUS_ACTIVE)->count();
        $suspendedCount = ReferralCode::where('status', ReferralCode::STATUS_SUSPENDED)->count();
        $totalCount = $activeCount + $suspendedCount;

        return Stat::make('Active Codes', $activeCount . ' / ' . $totalCount)
            ->description($suspendedCount . ' suspended')
            ->descriptionIcon('heroicon-m-code-bracket')
            ->color($suspendedCount > 0 ? 'warning' : 'success');
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
            'all' => now()->subYears(10)->startOfDay(),
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
