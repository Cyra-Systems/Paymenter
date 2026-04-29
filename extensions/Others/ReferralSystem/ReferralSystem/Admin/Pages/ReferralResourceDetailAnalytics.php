<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Pages;

use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralResourceDetailReferrerTable;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralResourceDetailStats;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralResourceDetailTrendChart;

class ReferralResourceDetailAnalytics extends BaseReferralAnalyticsDashboard
{
    protected static ?string $title = 'Resource Referral Analytics';

    protected static ?string $navigationLabel = 'Resource Referral Analytics';

    protected static ?string $slug = 'referral-analitics/resource/{product}';

    protected static string $routePath = 'referral-analitics/resource/{product}';

    protected static bool $shouldRegisterNavigation = false;

    public ?Product $analyticsProduct = null;

    public ?User $analyticsUserScope = null;

    public function mount(int|string $product): void
    {
        $this->analyticsProduct = Product::query()->findOrFail((int) $product);

        $scopeUserId = (int) request()->query('user', 0);
        if ($scopeUserId > 0) {
            $this->analyticsUserScope = User::query()->find($scopeUserId);
        }
    }

    public function getWidgets(): array
    {
        return [
            ReferralResourceDetailStats::class,
            ReferralResourceDetailTrendChart::class,
            ReferralResourceDetailReferrerTable::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'analyticsProductId' => $this->analyticsProduct?->id,
            'analyticsProductName' => $this->analyticsProduct?->name,
            'analyticsUserScopeId' => $this->analyticsUserScope?->id,
            'analyticsUserScopeEmail' => $this->analyticsUserScope?->email,
        ];
    }

    public function getHeading(): string
    {
        $name = $this->analyticsProduct?->name ?? 'Unknown resource';

        if ($this->analyticsUserScope) {
            return "Referral analytics for {$name} ({$this->analyticsUserScope->email})";
        }

        return "Referral analytics for {$name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            Action::make('back')
                ->label('Back to resource analytics')
                ->icon('heroicon-o-arrow-left')
                ->url(ReferralResourceAnalytics::getUrl()),
        ];
    }
}

