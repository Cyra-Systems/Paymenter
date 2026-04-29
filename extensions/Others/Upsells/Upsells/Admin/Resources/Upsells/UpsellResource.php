<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Pages\CreateUpsell;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Pages\EditUpsell;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Pages\ListUpsells;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Schemas\UpsellForm;
use Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Tables\UpsellsTable;
use Paymenter\Extensions\Others\Upsells\Models\Upsell;
use UnitEnum;

class UpsellResource extends Resource
{
    protected static ?string $model = Upsell::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static string|UnitEnum|null $navigationGroup = 'Extensions';

    protected static ?string $navigationLabel = 'Upsells';

    protected static ?string $recordTitleAttribute = 'Upsell';

    public static function form(Schema $schema): Schema
    {
        return UpsellForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UpsellsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUpsells::route('/'),
            'create' => CreateUpsell::route('/create'),
            'edit' => EditUpsell::route('/{record}/edit'),
        ];
    }
}

