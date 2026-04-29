<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCommissionResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCommissionResource;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Services\ManualCommissionManager;
use Paymenter\Extensions\Others\ReferralSystem\Services\ReferralNotifier;

class ListReferralCommissions extends ListRecords
{
    protected static string $resource = ReferralCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_manual_commission')
                ->label('Add Manual Commission')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => Auth::user()?->can('create', ReferralCommissionResource::getModel()) ?? false)
                ->form(ReferralCommissionResource::manualCommissionFormComponents())
                ->action(function (array $data): void {
                    $commission = ManualCommissionManager::createManualCommission($data, Auth::id());
                    $code = ReferralCode::query()->with('user')->find($commission->referral_code_id);

                    if ($code) {
                        ReferralNotifier::sendCommissionEarned($code, $commission);
                    }

                    Notification::make()
                        ->title('Manual commission created')
                        ->success()
                        ->send();
                }),
            Action::make('manage_schedules')
                ->label('Recurring Schedules')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => Auth::user()?->can('viewAny', ReferralManualCommissionScheduleResource::getModel()) ?? false)
                ->url(ReferralManualCommissionScheduleResource::getUrl()),
        ];
    }
}
