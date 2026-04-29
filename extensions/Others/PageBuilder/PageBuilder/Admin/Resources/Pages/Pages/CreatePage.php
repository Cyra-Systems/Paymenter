<?php

namespace Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;
}
