<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralApplicationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralApplicationResource;

class ListReferralApplications extends ListRecords
{
    protected static string $resource = ReferralApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
