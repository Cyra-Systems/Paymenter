<?php

namespace App\Admin\Resources;

use App\Admin\Clusters\Domains;
use App\Admin\Resources\DomainTldResource\Pages\CreateDomainTld;
use App\Admin\Resources\DomainTldResource\Pages\EditDomainTld;
use App\Admin\Resources\DomainTldResource\Pages\ListDomainTlds;
use App\Models\Currency;
use App\Models\DomainTld;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DomainTldResource extends Resource
{
    protected static ?string $model = DomainTld::class;

    protected static ?string $cluster = Domains::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-list-check-2';

    protected static ?string $navigationLabel = 'TLD Catalog';

    protected static ?string $modelLabel = 'TLD';

    protected static ?string $pluralModelLabel = 'TLDs';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tld')
                ->label('TLD')
                ->prefix('.')
                ->required()
                ->lowercase()
                ->maxLength(63)
                ->unique(static::getModel(), 'tld', ignoreRecord: true)
                ->placeholder('com'),
            Toggle::make('enabled')
                ->label('Enabled')
                ->default(true),
            TextInput::make('register_price')
                ->numeric()
                ->required()
                ->prefix('Wholesale')
                ->step('0.01'),
            TextInput::make('renewal_price')
                ->numeric()
                ->required()
                ->step('0.01'),
            TextInput::make('transfer_price')
                ->numeric()
                ->required()
                ->step('0.01'),
            TextInput::make('redemption_price')
                ->numeric()
                ->step('0.01'),
            Select::make('currency_code')
                ->label('Currency')
                ->required()
                ->options(fn () => Currency::query()->pluck('code', 'code')->toArray()),
            TextInput::make('margin_percent')
                ->label('Per-TLD margin %')
                ->numeric()
                ->step('0.01')
                ->default(0),
            TextInput::make('min_years')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1)
                ->maxValue(10),
            TextInput::make('max_years')
                ->numeric()
                ->required()
                ->default(10)
                ->minValue(1)
                ->maxValue(10),
            Toggle::make('whois_privacy_supported')->default(true),
            Toggle::make('transfer_supported')->default(true),
            Toggle::make('epp_required')->default(true),
            TextInput::make('display_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tld')->searchable()->sortable()->prefix('.'),
                IconColumn::make('enabled')->boolean()->sortable(),
                TextColumn::make('register_price')->money(fn ($record) => $record->currency_code)->sortable(),
                TextColumn::make('renewal_price')->money(fn ($record) => $record->currency_code)->sortable(),
                TextColumn::make('transfer_price')->money(fn ($record) => $record->currency_code)->sortable(),
                TextColumn::make('margin_percent')->suffix('%')->sortable(),
                TextColumn::make('display_order')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('enabled'),
                TernaryFilter::make('transfer_supported'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainTlds::route('/'),
            'create' => CreateDomainTld::route('/create'),
            'edit' => EditDomainTld::route('/{record}/edit'),
        ];
    }
}
