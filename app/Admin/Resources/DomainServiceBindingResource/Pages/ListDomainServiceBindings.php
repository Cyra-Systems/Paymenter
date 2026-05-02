<?php

namespace App\Admin\Resources\DomainServiceBindingResource\Pages;

use App\Admin\Resources\DomainServiceBindingResource;
use Filament\Resources\Pages\ListRecords;

class ListDomainServiceBindings extends ListRecords
{
    protected static string $resource = DomainServiceBindingResource::class;
}
