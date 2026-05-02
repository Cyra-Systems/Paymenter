<?php

namespace App\Admin\Resources;

use App\Admin\Clusters\Domains;
use App\Admin\Resources\DomainResource\Pages\EditDomain;
use App\Admin\Resources\DomainResource\Pages\ListDomains;
use App\Admin\Resources\DomainResource\Pages\ViewDomain;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $cluster = Domains::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-global-line';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'All Domains';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('fqdn')->disabled(),
            TextInput::make('sld')->disabled(),
            TextInput::make('tld')->disabled(),
            Select::make('user_id')
                ->relationship('user', 'email')
                ->searchable()
                ->required(),
            Select::make('status')
                ->options([
                    Domain::STATUS_PENDING => 'Pending',
                    Domain::STATUS_ACTIVE => 'Active',
                    Domain::STATUS_EXPIRED => 'Expired',
                    Domain::STATUS_TRANSFERRING => 'Transferring',
                    Domain::STATUS_TRANSFERRED_OUT => 'Transferred Out',
                    Domain::STATUS_CANCELLED => 'Cancelled',
                    Domain::STATUS_REDEMPTION => 'Redemption',
                ])
                ->required(),
            Select::make('registrar')
                ->options(['enom' => 'Enom', 'external' => 'External'])
                ->required(),
            DateTimePicker::make('registered_at'),
            DateTimePicker::make('expires_at'),
            DateTimePicker::make('last_synced_at'),
            Toggle::make('locked'),
            Toggle::make('auto_renew'),
            Toggle::make('id_protect'),
            TextInput::make('auth_code')->password()->revealable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fqdn')->searchable()->sortable(),
                TextColumn::make('user.email')->searchable()->sortable()->label('Owner'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('registrar')->sortable()->toggleable(),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                IconColumn::make('auto_renew')->boolean()->sortable()->toggleable(),
                IconColumn::make('locked')->boolean()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Domain::STATUS_PENDING => 'Pending',
                    Domain::STATUS_ACTIVE => 'Active',
                    Domain::STATUS_EXPIRED => 'Expired',
                    Domain::STATUS_TRANSFERRING => 'Transferring',
                    Domain::STATUS_CANCELLED => 'Cancelled',
                ]),
                SelectFilter::make('registrar')->options(['enom' => 'Enom', 'external' => 'External']),
                Filter::make('expiring_soon')
                    ->label('Expiring within 30 days')
                    ->query(fn ($query) => $query->expiringWithin(30)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('sync')
                    ->label('Sync from registrar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Domain $record) {
                        try {
                            $registrar = \App\Domains\Registrars\RegistrarFactory::for($record);
                            $info = $registrar->getInfo($record);
                            $record->update([
                                'expires_at' => $info['expires_at'] ?? $record->expires_at,
                                'auth_code' => $info['auth_code'] ?? $record->auth_code,
                                'locked' => $info['locked'] ?? $record->locked,
                                'last_synced_at' => now(),
                            ]);
                            Notification::make()->title('Synced')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Sync failed: '.$e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomains::route('/'),
            'view' => ViewDomain::route('/{record}'),
            'edit' => EditDomain::route('/{record}/edit'),
        ];
    }
}
