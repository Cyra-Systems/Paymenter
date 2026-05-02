<?php

namespace App\Admin\Resources;

use App\Admin\Clusters\Domains;
use App\Admin\Components\UserComponent;
use App\Admin\Resources\DomainResource\Pages\CreateDomain;
use App\Admin\Resources\DomainResource\Pages\EditDomain;
use App\Admin\Resources\DomainResource\Pages\ListDomain;
use App\Models\Domain;
use App\Models\Service;
use App\Services\Domains\DomainProvisionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-global-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-global-fill';

    protected static ?string $cluster = Domains::class;

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        return Domain::where('status', Domain::STATUS_PENDING)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Domain')
                ->columns(2)
                ->schema([
                    TextInput::make('domain')
                        ->label('Domain (FQDN)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('example.com'),
                    Select::make('type')
                        ->label('Type')
                        ->required()
                        ->options([
                            Domain::TYPE_REGISTER => 'New Registration',
                            Domain::TYPE_TRANSFER => 'Transfer-in',
                            Domain::TYPE_EXTERNAL => 'External (customer-owned)',
                            Domain::TYPE_FORWARD => 'URL Forward',
                            Domain::TYPE_SUBDOMAIN => 'Subdomain',
                        ])
                        ->live()
                        ->default(Domain::TYPE_REGISTER),
                    Select::make('status')
                        ->options([
                            Domain::STATUS_PENDING => 'Pending',
                            Domain::STATUS_ACTIVE => 'Active',
                            Domain::STATUS_TRANSFERRING => 'Transferring',
                            Domain::STATUS_EXPIRED => 'Expired',
                            Domain::STATUS_FAILED => 'Failed',
                            Domain::STATUS_CANCELLED => 'Cancelled',
                        ])
                        ->required()
                        ->default(Domain::STATUS_PENDING),
                    UserComponent::make('user_id'),
                    TextInput::make('period')
                        ->label('Years')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(10),
                    DatePicker::make('expires_at')
                        ->label('Expires At'),
                    TextInput::make('forward_url')
                        ->label('Forward URL')
                        ->url()
                        ->visible(fn (Get $get) => $get('type') === Domain::TYPE_FORWARD),
                    Select::make('forward_type')
                        ->label('Forward Type')
                        ->options([
                            '301' => '301 (permanent)',
                            '302' => '302 (temporary)',
                            'frame' => 'Frame',
                        ])
                        ->visible(fn (Get $get) => $get('type') === Domain::TYPE_FORWARD),
                    Select::make('parent_domain_id')
                        ->label('Parent Domain')
                        ->relationship('parent', 'domain', fn (Builder $q) => $q->where('type', '!=', Domain::TYPE_SUBDOMAIN))
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('type') === Domain::TYPE_SUBDOMAIN),
                    TextInput::make('auth_code')
                        ->label('EPP Auth Code')
                        ->visible(fn (Get $get) => in_array($get('type'), [Domain::TYPE_TRANSFER])),
                ]),

            Section::make('Binding')
                ->description('Which service is this domain currently attached to?')
                ->schema([
                    Select::make('bindable_id')
                        ->label('Bound to Service')
                        ->options(fn () => Service::query()
                            ->with('product', 'user')
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn (Service $s) => [$s->id => "#{$s->id} — {$s->product?->name} ({$s->user?->email})"]))
                        ->searchable()
                        ->dehydrated(false),
                ]),

            Section::make('Settings')
                ->columns(3)
                ->schema([
                    Toggle::make('auto_renew')->label('Auto Renew'),
                    Toggle::make('id_protect')->label('Whois Privacy'),
                    Toggle::make('locked')->label('Registrar Lock'),
                    TagsInput::make('nameservers')
                        ->label('Nameservers')
                        ->placeholder('ns1.example.com')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('domain')->label('Domain')->searchable()->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Domain::TYPE_REGISTER => 'success',
                        Domain::TYPE_TRANSFER => 'info',
                        Domain::TYPE_FORWARD => 'warning',
                        Domain::TYPE_SUBDOMAIN => 'primary',
                        Domain::TYPE_EXTERNAL => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Domain::STATUS_ACTIVE => 'success',
                        Domain::STATUS_PENDING => 'gray',
                        Domain::STATUS_TRANSFERRING => 'warning',
                        Domain::STATUS_FAILED => 'danger',
                        Domain::STATUS_EXPIRED => 'danger',
                        Domain::STATUS_CANCELLED => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('user.email')->label('Owner')->searchable()->toggleable(),
                TextColumn::make('bindable_id')
                    ->label('Bound to')
                    ->formatStateUsing(function (Domain $record) {
                        if (!$record->bindable) return '—';
                        if ($record->bindable instanceof Service) {
                            return "Service #{$record->bindable->id} ({$record->bindable->product?->name})";
                        }
                        return class_basename($record->bindable_type) . ' #' . $record->bindable_id;
                    }),
                IconColumn::make('auto_renew')->boolean()->toggleable(),
                IconColumn::make('id_protect')->boolean()->toggleable()->label('Privacy'),
                TextColumn::make('expires_at')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    Domain::TYPE_REGISTER => 'New Registration',
                    Domain::TYPE_TRANSFER => 'Transfer',
                    Domain::TYPE_EXTERNAL => 'External',
                    Domain::TYPE_FORWARD => 'Forward',
                    Domain::TYPE_SUBDOMAIN => 'Subdomain',
                ]),
                SelectFilter::make('status')->options([
                    Domain::STATUS_PENDING => 'Pending',
                    Domain::STATUS_ACTIVE => 'Active',
                    Domain::STATUS_FAILED => 'Failed',
                    Domain::STATUS_EXPIRED => 'Expired',
                    Domain::STATUS_TRANSFERRING => 'Transferring',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('switchService')
                    ->label('Switch Service')
                    ->icon('ri-arrow-left-right-line')
                    ->modalHeading(fn (Domain $record) => "Switch service for {$record->domain}")
                    ->schema([
                        Select::make('service_id')
                            ->label('Bind to Service')
                            ->options(fn (Domain $record) => Service::query()
                                ->with('product', 'user')
                                ->where('user_id', $record->user_id)
                                ->orWhereNull('user_id')
                                ->limit(500)
                                ->get()
                                ->mapWithKeys(fn (Service $s) => [
                                    $s->id => "#{$s->id} — {$s->product?->name} ({$s->user?->email})",
                                ]))
                            ->searchable()
                            ->required(),
                        TextInput::make('reason')
                            ->label('Reason / note (optional)'),
                    ])
                    ->action(function (Domain $record, array $data, DomainProvisionService $provisioner) {
                        $service = Service::find($data['service_id']);
                        if (!$service) {
                            Notification::make()->title('Service not found')->danger()->send();
                            return;
                        }
                        $provisioner->bind($record, $service, auth()->user(), $data['reason'] ?? null);
                        Notification::make()
                            ->title("Domain {$record->domain} bound to service #{$service->id}")
                            ->success()->send();
                    }),
                Action::make('unbind')
                    ->label('Unbind')
                    ->icon('ri-link-unlink')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Domain $record) => $record->bindable_id !== null)
                    ->action(function (Domain $record, DomainProvisionService $provisioner) {
                        $provisioner->unbind($record, auth()->user());
                        Notification::make()->title('Domain unbound')->success()->send();
                    }),
                Action::make('renew')
                    ->label('Renew (1y)')
                    ->icon('ri-refresh-line')
                    ->requiresConfirmation()
                    ->visible(fn (Domain $record) => in_array($record->type, [Domain::TYPE_REGISTER, Domain::TYPE_TRANSFER]))
                    ->action(function (Domain $record, DomainProvisionService $provisioner) {
                        $provisioner->renew($record, 1);
                        Notification::make()->title('Domain renewed for 1 year')->success()->send();
                    }),
                Action::make('sync')
                    ->label('Sync from registrar')
                    ->icon('ri-cloud-line')
                    ->visible(fn (Domain $record) => in_array($record->type, [Domain::TYPE_REGISTER, Domain::TYPE_TRANSFER]))
                    ->action(function (Domain $record, DomainProvisionService $provisioner) {
                        $provisioner->syncFromProvider($record);
                        Notification::make()->title('Synced')->success()->send();
                    }),
                Action::make('authCode')
                    ->label('Reveal EPP code')
                    ->icon('ri-key-2-line')
                    ->visible(fn (Domain $record) => $record->type === Domain::TYPE_REGISTER)
                    ->modalContent(fn (Domain $record, DomainProvisionService $provisioner) => view('domains.auth-code', [
                        'code' => $provisioner->getAuthCode($record),
                    ])),
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
            'index' => ListDomain::route('/'),
            'create' => CreateDomain::route('/create'),
            'edit' => EditDomain::route('/{record}/edit'),
        ];
    }
}
