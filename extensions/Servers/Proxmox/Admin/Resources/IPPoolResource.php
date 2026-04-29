<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\Pages\CreateIPPool;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\Pages\EditIPPool;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\Pages\ListIPPools;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\RelationManagers\IPAddressRelationManager;
use Paymenter\Extensions\Servers\Proxmox\Models\IPPool;

class IPPoolResource extends Resource
{
    protected static ?string $model = IPPool::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-global-line';

    // Label to IP Pool
    protected static ?string $label = 'IP Pool';

    protected static string|\UnitEnum|null $navigationGroup = 'Proxmox';

    protected static ?string $slug = 'proxmox-ip-pools';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true),

                TextInput::make('network_interface')
                    ->label('Network Interface')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The network interface to use for this IP pool, e.g., vmbr0'),

                TextInput::make('mtu')
                    ->label('MTU')
                    ->numeric()
                    ->default(1500)
                    ->required()
                    ->helperText('The MTU (Maximum Transmission Unit) for the network interface, default is 1500'),

                Select::make('nodes')
                    ->label('Nodes')
                    ->multiple()
                    ->preload()
                    ->relationship('nodes', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->location->name})"),

                TextInput::make('vlan')
                    ->label('VLAN')
                    ->numeric()
                    ->nullable(),

                Toggle::make('firewall')
                    ->label('Enable Firewall')
                    ->default(false)
                    ->helperText('Enable or disable the firewall for this IP pool'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('nodes_count')
                    ->label('Nodes')
                    ->counts('nodes'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            IPAddressRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIPPools::route('/'),
            'create' => CreateIPPool::route('/create'),
            'edit' => EditIPPool::route('/{record}/edit'),
        ];
    }
}
