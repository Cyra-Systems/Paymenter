<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\UpsellResource;

class EditUpsell extends EditRecord
{
    protected static string $resource = UpsellResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

