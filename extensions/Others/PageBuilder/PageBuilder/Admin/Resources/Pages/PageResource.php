<?php

namespace Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Pages\CreatePage;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Pages\EditPage;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Pages\ListPages;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Schemas\PageForm;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Tables\PagesTable;
use Paymenter\Extensions\Others\PageBuilder\Models\Page;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Extensions';

    protected static ?string $navigationLabel = 'PageBuilder';

    protected static ?string $recordTitleAttribute = 'Page';

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
