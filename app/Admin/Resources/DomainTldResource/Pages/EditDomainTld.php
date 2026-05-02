<?php

namespace App\Admin\Resources\DomainTldResource\Pages;

use App\Admin\Resources\DomainTldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDomainTld extends EditRecord
{
    protected static string $resource = DomainTldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
