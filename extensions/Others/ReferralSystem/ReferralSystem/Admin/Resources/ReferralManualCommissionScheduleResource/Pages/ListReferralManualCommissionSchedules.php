<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralManualCommissionScheduleResource;

class ListReferralManualCommissionSchedules extends ListRecords
{
    protected static string $resource = ReferralManualCommissionScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Schedule')
                ->icon('heroicon-o-plus')
                ->url(ReferralManualCommissionScheduleResource::getUrl('create')),
        ];
    }
}
