<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource;

class ListReferralCodes extends ListRecords
{
    protected static string $resource = ReferralCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
