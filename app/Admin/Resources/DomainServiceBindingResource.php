<?php

namespace App\Admin\Resources;

use App\Admin\Clusters\Domains;
use App\Admin\Resources\DomainServiceBindingResource\Pages\ListDomainServiceBindings;
use App\Domains\Services\DomainBindingService;
use App\Models\DomainServiceBinding;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DomainServiceBindingResource extends Resource
{
    protected static ?string $model = DomainServiceBinding::class;

    protected static ?string $cluster = Domains::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-link';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Bindings';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain.fqdn')->label('Domain')->searchable()->sortable(),
                TextColumn::make('service.id')->label('Service #')->sortable(),
                TextColumn::make('service.user.email')->label('Owner')->searchable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('hostname')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('npm_proxy_host_id')->label('NPM proxy id')->toggleable(),
                TextColumn::make('npm_redirection_host_id')->label('NPM redirect id')->toggleable(),
                TextColumn::make('bound_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    DomainServiceBinding::STATUS_PENDING => 'Pending',
                    DomainServiceBinding::STATUS_PROVISIONING => 'Provisioning',
                    DomainServiceBinding::STATUS_ACTIVE => 'Active',
                    DomainServiceBinding::STATUS_TRANSITIONING => 'Transitioning',
                    DomainServiceBinding::STATUS_FAILED => 'Failed',
                    DomainServiceBinding::STATUS_RELEASED => 'Released',
                ]),
                SelectFilter::make('type')->options([
                    'primary' => 'Primary',
                    'forward' => 'Forward',
                    'subdomain' => 'Subdomain',
                    'custom' => 'Custom',
                ]),
            ])
            ->recordActions([
                Action::make('release')
                    ->label('Force release')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (DomainServiceBinding $record) => $record->isLive())
                    ->action(function (DomainServiceBinding $record) {
                        try {
                            app(DomainBindingService::class)->unbind($record);
                            Notification::make()->title('Binding released.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Release failed: '.$e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainServiceBindings::route('/'),
        ];
    }
}
