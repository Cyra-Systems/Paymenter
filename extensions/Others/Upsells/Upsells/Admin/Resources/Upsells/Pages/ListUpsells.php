<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\UpsellResource;

class ListUpsells extends ListRecords
{
    protected static string $resource = UpsellResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('design')
                ->label('Design Settings')
                ->icon(Heroicon::OutlinedPaintBrush)
                ->url('/admin/upsell-design'),
            CreateAction::make(),
        ];
    }
}

