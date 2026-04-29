<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource;
use Paymenter\Extensions\Others\ReferralSystem\Services\ManualCommissionManager;

class CreateReferralManualCommissionSchedule extends CreateRecord
{
    protected static string $resource = ReferralManualCommissionScheduleResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $schedule = ManualCommissionManager::createSchedule($data, Auth::id());
        $issuedImmediately = !empty($data['issue_immediately']);

        Notification::make()
            ->title('Recurring commission schedule created')
            ->body($issuedImmediately
                ? 'The first commission was issued immediately and the schedule remains active for future runs.'
                : 'The schedule was saved and will create commissions on its configured cadence.')
            ->success()
            ->send();

        return $schedule;
    }
}
