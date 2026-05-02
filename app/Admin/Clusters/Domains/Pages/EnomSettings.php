<?php

namespace App\Admin\Clusters\Domains\Pages;

use App\Admin\Clusters\Domains;
use App\Services\Domains\DomainSettings;
use App\Services\Domains\EnomClient;
use App\Services\Domains\Exceptions\EnomException;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnomSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Domains::class;

    protected static string|BackedEnum|null $navigationIcon = 'ri-settings-3-line';

    protected static ?string $title = 'Enom Settings';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.clusters.domains.pages.enom-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'enom_username' => DomainSettings::get('enom_username'),
            'enom_password' => DomainSettings::get('enom_password'),
            'enom_environment' => DomainSettings::get('enom_environment', EnomClient::ENV_TEST),
            'enom_proxy_url' => DomainSettings::get('enom_proxy_url'),
            'enom_timeout' => DomainSettings::get('enom_timeout', 30),
            'default_nameservers' => DomainSettings::get('default_nameservers', []),
            'default_id_protect' => (bool) DomainSettings::get('default_id_protect', false),
            'default_auto_renew' => (bool) DomainSettings::get('default_auto_renew', true),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Enom API Credentials')
                    ->description('Used for all domain calls. Test against Enom\'s sandbox first.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('enom_username')
                            ->label('Reseller Username')
                            ->required(),
                        TextInput::make('enom_password')
                            ->label('Reseller Password / API Token')
                            ->password()
                            ->revealable()
                            ->required(),
                        Select::make('enom_environment')
                            ->label('Environment')
                            ->options([
                                EnomClient::ENV_TEST => 'Sandbox (resellertest.enom.com)',
                                EnomClient::ENV_LIVE => 'Production (reseller.enom.com)',
                            ])
                            ->required(),
                        TextInput::make('enom_proxy_url')
                            ->label('Custom endpoint (optional)')
                            ->url()
                            ->placeholder('https://your-proxy/interface.asp'),
                        TextInput::make('enom_timeout')
                            ->label('Timeout (seconds)')
                            ->numeric()
                            ->default(30),
                    ]),
                Section::make('Defaults')
                    ->description('Applied to every newly created domain unless overridden at checkout.')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('default_nameservers')
                            ->label('Default Nameservers')
                            ->placeholder('ns1.example.com')
                            ->columnSpanFull(),
                        Toggle::make('default_id_protect')
                            ->label('Enable Whois Privacy by default'),
                        Toggle::make('default_auto_renew')
                            ->label('Enable Auto-Renew by default'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Test Connection')
                ->icon('ri-plug-line')
                ->action(function () {
                    $data = $this->form->getState();
                    DomainSettings::set('enom_username', $data['enom_username']);
                    DomainSettings::set('enom_password', $data['enom_password']);
                    DomainSettings::set('enom_environment', $data['enom_environment']);
                    DomainSettings::set('enom_proxy_url', $data['enom_proxy_url']);
                    DomainSettings::set('enom_timeout', $data['enom_timeout']);

                    try {
                        $balance = DomainSettings::makeEnomClient()->getBalance();
                        Notification::make()
                            ->title('Enom connection OK')
                            ->body('Account balance: ' . ($balance['Balance'] ?? 'unknown'))
                            ->success()->send();
                    } catch (EnomException $e) {
                        Notification::make()
                            ->title('Enom connection failed')
                            ->body($e->getMessage())
                            ->danger()->send();
                    }
                }),
            Action::make('save')
                ->label('Save')
                ->icon('ri-save-line')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            DomainSettings::set($key, $value);
        }

        Notification::make()
            ->title('Enom settings saved')
            ->success()->send();
    }
}
