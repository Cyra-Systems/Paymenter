<?php

namespace App\Admin\Resources\DomainTldResource\Pages;

use App\Admin\Resources\DomainTldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainTld extends CreateRecord
{
    protected static string $resource = DomainTldResource::class;
}
