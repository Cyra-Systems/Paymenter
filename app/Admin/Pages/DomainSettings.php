<?php

namespace App\Admin\Pages;

use App\Admin\Clusters\Domains;
use App\Domains\ProxyManager\NginxProxyManagerClient;
use App\Domains\Registrars\EnomRegistrar;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class DomainSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Domains::class;

    protected static ?string $title = 'Domain Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-settings-3-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-settings-3-fill';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'settings';

    protected string $view = 'admin.pages.domain-settings';

    public ?array $data = [];

    private const KEYS = [
        'domains.enom_username',
        'domains.enom_password',
        'domains.enom_sandbox',
        'domains.default_nameservers',
        'domains.subdomain_base',
        'domains.npm_url',
        'domains.npm_email',
        'domains.npm_password',
        'domains.npm_default_certificate_id',
        'domains.npm_proxy_target_host',
        'domains.npm_letsencrypt_email',
        'domains.npm_dns_provider',
        'domains.global_margin_percent',
    ];

    public function mount(): void
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $value = config('settings.'.$key);
            if (in_array($key, ['domains.default_nameservers', 'domains.npm_dns_provider'], true)) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : [];
                }
                if (! is_array($value)) {
                    $value = [];
                }

                if ($key === 'domains.default_nameservers') {
                    $value = array_map(fn ($v) => ['hostname' => (string) $v], array_values($value));
                }
            }
            $values[$key] = $value;
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Tabs::make('DomainSettingsTabs')
                        ->tabs([
                            Tab::make('enom')
                                ->label('Enom Credentials')
                                ->schema([
                                    TextInput::make('domains.enom_username')->label('Enom Username'),
                                    TextInput::make('domains.enom_password')->label('Enom Password / Token')->password()->revealable(),
                                    Toggle::make('domains.enom_sandbox')->label('Use Sandbox'),
                                ]),
                            Tab::make('nameservers')
                                ->label('Default Nameservers')
                                ->schema([
                                    Repeater::make('domains.default_nameservers')
                                        ->label('Default nameservers (used when registering new domains)')
                                        ->schema([
                                            TextInput::make('hostname')->label('Hostname')->required(),
                                        ])
                                        ->reorderable()
                                        ->minItems(0)
                                        ->maxItems(6),
                                ]),
                            Tab::make('npm')
                                ->label('Reverse Proxy (NPM)')
                                ->schema([
                                    TextInput::make('domains.npm_url')->label('NPM Admin URL')->placeholder('http://npm:81'),
                                    TextInput::make('domains.npm_email')->label('NPM Admin Email')->email(),
                                    TextInput::make('domains.npm_password')->label('NPM Admin Password')->password()->revealable(),
                                    TextInput::make('domains.npm_proxy_target_host')->label('Default upstream host')->helperText('Used for forward redirects and as a fallback when an extension does not provide its own proxy target.'),
                                    TextInput::make('domains.npm_letsencrypt_email')->label('Let\'s Encrypt Email')->email(),
                                    TextInput::make('domains.npm_default_certificate_id')->label('Default certificate ID')->numeric(),
                                    KeyValue::make('domains.npm_dns_provider')->label('DNS provider config (JSON)'),
                                ]),
                            Tab::make('subdomain')
                                ->label('Subdomain Base')
                                ->schema([
                                    TextInput::make('domains.subdomain_base')
                                        ->label('Subdomain base')
                                        ->placeholder('apps.example.com')
                                        ->helperText('The wildcard hostname used for subdomain checkout (e.g. setting `apps.example.com` enables `*.apps.example.com`).'),
                                ]),
                            Tab::make('pricing')
                                ->label('Pricing')
                                ->schema([
                                    TextInput::make('domains.global_margin_percent')
                                        ->label('Global margin %')
                                        ->numeric()
                                        ->step('0.01')
                                        ->helperText('Applied on top of each TLD\'s wholesale price (in addition to per-TLD margin).'),
                                ]),
                        ])
                        ->persistTabInQueryString(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')->submit('save')->keyBindings(['mod+s']),
                            Action::make('test_enom')
                                ->label('Test Enom')
                                ->color('gray')
                                ->action(fn () => $this->testEnom()),
                            Action::make('test_npm')
                                ->label('Test NPM')
                                ->color('gray')
                                ->action(fn () => $this->testNpm()),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        Gate::authorize('has-permission', 'admin.settings.update');

        $data = $this->form->getState();

        foreach (self::KEYS as $key) {
            $value = $data[$key] ?? null;
            $type = 'string';
            $encrypted = in_array($key, ['domains.enom_password', 'domains.npm_password'], true);

            if ($key === 'domains.default_nameservers') {
                $value = json_encode(array_values(array_filter(array_map(fn ($row) => trim((string) ($row['hostname'] ?? '')), $value ?? []))));
                $type = 'json';
            } elseif ($key === 'domains.npm_dns_provider') {
                $value = json_encode($value ?? []);
                $type = 'json';
            } elseif ($key === 'domains.enom_sandbox') {
                $value = $value ? '1' : '0';
                $type = 'boolean';
            }

            Setting::updateOrCreate(
                ['key' => $key, 'settingable_type' => null, 'settingable_id' => null],
                ['value' => (string) $value, 'type' => $type, 'encrypted' => $encrypted],
            );
        }

        Notification::make()->title('Domain settings saved.')->success()->send();
    }

    public function testEnom(): void
    {
        $data = $this->form->getState();

        $registrar = new EnomRegistrar([
            'username' => $data['domains.enom_username'] ?? '',
            'password' => $data['domains.enom_password'] ?? '',
            'sandbox' => (bool) ($data['domains.enom_sandbox'] ?? true),
        ]);

        $result = $registrar->testConnection();

        if ($result === true) {
            Notification::make()->title('Enom connection OK.')->success()->send();
        } else {
            Notification::make()->title('Enom connection failed: '.$result)->danger()->send();
        }
    }

    public function testNpm(): void
    {
        $data = $this->form->getState();

        $client = new NginxProxyManagerClient([
            'url' => $data['domains.npm_url'] ?? '',
            'email' => $data['domains.npm_email'] ?? '',
            'password' => $data['domains.npm_password'] ?? '',
        ]);

        $result = $client->testConnection();

        if ($result === true) {
            Notification::make()->title('NPM connection OK.')->success()->send();
        } else {
            Notification::make()->title('NPM connection failed: '.$result)->danger()->send();
        }
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user && $user->hasPermission('admin.settings.view');
    }
}
