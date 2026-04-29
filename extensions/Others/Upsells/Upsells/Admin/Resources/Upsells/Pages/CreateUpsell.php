<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Pages;

use Filament\Resources\Pages\CreateRecord;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\UpsellResource;

class CreateUpsell extends CreateRecord
{
    protected static string $resource = UpsellResource::class;
}

