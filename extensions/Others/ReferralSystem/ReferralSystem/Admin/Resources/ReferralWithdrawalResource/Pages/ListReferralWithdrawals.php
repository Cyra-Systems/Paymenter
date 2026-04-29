<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralWithdrawalResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralWithdrawalResource;

class ListReferralWithdrawals extends ListRecords
{
    protected static string $resource = ReferralWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
