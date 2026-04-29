<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralUserDetailResourceTable;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralUserDetailStats;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Widgets\ReferralUserDetailTrendChart;

class ReferralUserDetailAnalytics extends BaseReferralAnalyticsDashboard
{
    protected static ?string $title = 'User Referral Analytics';

    protected static ?string $navigationLabel = 'User Referral Analytics';

    protected static ?string $slug = 'referral-analitics/user/{user}';

    protected static string $routePath = 'referral-analitics/user/{user}';

    protected static bool $shouldRegisterNavigation = false;

    public ?User $analyticsUser = null;

    public function mount(int|string $user): void
    {
        $this->analyticsUser = User::query()->findOrFail((int) $user);
    }

    public function getWidgets(): array
    {
        return [
            ReferralUserDetailStats::class,
            ReferralUserDetailTrendChart::class,
            ReferralUserDetailResourceTable::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'analyticsUserId' => $this->analyticsUser?->id,
            'analyticsUserEmail' => $this->analyticsUser?->email,
        ];
    }

    public function getHeading(): string
    {
        $email = $this->analyticsUser?->email ?? 'Unknown user';

        return "Referral analytics for {$email}";
    }

    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            Action::make('back')
                ->label('Back to user analytics')
                ->icon('heroicon-o-arrow-left')
                ->url(ReferralUserAnalytics::getUrl()),
        ];
    }
}

