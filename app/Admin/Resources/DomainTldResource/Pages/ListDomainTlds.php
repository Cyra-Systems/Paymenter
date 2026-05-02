<?php

namespace App\Admin\Resources\DomainTldResource\Pages;

use App\Admin\Resources\DomainTldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDomainTlds extends ListRecords
{
    protected static string $resource = DomainTldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
