<?php

namespace App\Admin\Pages;

use App\Admin\Clusters\Extensions;
use App\Admin\Resources\ExtensionResource;
use App\Admin\Resources\GatewayResource;
use App\Admin\Resources\ServerResource;
use App\Helpers\ExtensionHelper;
use App\Jobs\Marketplace\SyncMarketplaceJob;
use App\Models\MarketplaceListing;
use App\Services\Extensions\UploadExtensionService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Url;

class Extension extends Page implements HasActions, HasTable
{
    use InteractsWithActions, InteractsWithTable;

    protected string $view = 'admin.pages.extension';

    protected static ?string $cluster = Extensions::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-download-2-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-download-2-fill';

    protected static ?string $navigationLabel = 'Available Extensions';

    #[Url(except: 'marketplace', as: 'tab')]
    public string $activeTab = 'marketplace';

    private const PER_PAGE = 12;

    #[Url(except: '', as: 'q')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $filter = 'all';

    public int $loadedItems = self::PER_PAGE;

    public ?string $error = null;

    public function mount(): void
    {
        if (!config('settings.marketplace_url')) {
            $this->error = 'No marketplace is configured. Set the Marketplace Manifest URL in Settings to start syncing extensions.';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetLoadedItems();
    }

    public function updatedFilter(): void
    {
        $this->resetLoadedItems();
    }

    public function loadMore(): void
    {
        $this->loadedItems += self::PER_PAGE;
    }

    private function resetLoadedItems(): void
    {
        $this->loadedItems = self::PER_PAGE;
    }

    public function getAllExtensionsProperty(): Collection
    {
        return MarketplaceListing::query()
            ->whereIn('type', ['gateway', 'server', 'other'])
            ->orderBy('name')
            ->get()
            ->map(function (MarketplaceListing $row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                    'meta' => [
                        'name' => $row->raw_meta['meta']['name'] ?? $row->name,
                        'author' => $row->author,
                        'description' => $row->description,
                        'version' => $row->version,
                        'icon' => $row->icon,
                    ],
                    'download_url' => $row->download_url,
                    'sha256' => $row->sha256,
                    'signature' => $row->signature,
                    'has_migrations' => $row->has_migrations,
                ];
            });
    }

    public function getFilteredExtensionsProperty(): Collection
    {
        return $this->allExtensions
            ->when($this->search, fn (Collection $c) => $c->filter(fn ($i) => stripos($i['meta']['name'] ?? $i['name'], $this->search) !== false))
            ->when($this->filter !== 'all', fn (Collection $c) => $c->where('type', $this->filter))
            ->values();
    }

    public function getCanLoadMoreProperty(): bool
    {
        return $this->filteredExtensions->count() > $this->loadedItems;
    }

    public function getExtensionsProperty(): Collection
    {
        return $this->filteredExtensions->take($this->loadedItems);
    }

    public function downloadAndInstall(int $listingId): void
    {
        $listing = MarketplaceListing::find($listingId);
        if (!$listing) {
            Notification::make()->title('Listing not found')->danger()->send();

            return;
        }

        try {
            $zipPath = $this->downloadVerified($listing);

            $service = app(UploadExtensionService::class);
            $type = $service->handle($zipPath, $listing->sha256, $listing->signature, $listing->download_url);

            Notification::make()
                ->title('Extension installed')
                ->body($this->postInstallMessage($type))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to install extension')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function downloadVerified(MarketplaceListing $listing): string
    {
        if (config('settings.marketplace_require_signature', true)) {
            if (empty($listing->signature)) {
                throw new \Exception('This package is unsigned and the marketplace requires signed packages.');
            }
            $key = config('settings.marketplace_signing_key');
            if (empty($key)) {
                throw new \Exception('No marketplace_signing_key is configured.');
            }
            if (!hash_equals($listing->signature, hash_hmac('sha256', $listing->sha256, $key))) {
                throw new \Exception('Signature verification failed for this package.');
            }
        }

        $cacheDir = storage_path('app/marketplace-cache');
        if (!is_dir($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }
        $zipPath = $cacheDir . '/' . $listing->sha256 . '.zip';

        if (!file_exists($zipPath) || hash_file('sha256', $zipPath) !== $listing->sha256) {
            $response = Http::timeout(60)
                ->withUserAgent('Paymenter/' . config('app.version') . ' (marketplace-download)')
                ->get($listing->download_url);

            if (!$response->successful()) {
                throw new \Exception('Failed to download package (HTTP ' . $response->status() . ').');
            }

            file_put_contents($zipPath, $response->body());
        }

        if (hash_file('sha256', $zipPath) !== $listing->sha256) {
            File::delete($zipPath);
            throw new \Exception('Downloaded package hash does not match the manifest.');
        }

        // Move into a unique path so UploadExtensionService can delete it without
        // racing other concurrent installs.
        $stagedPath = $cacheDir . '/staged-' . uniqid() . '.zip';
        File::copy($zipPath, $stagedPath);

        return $stagedPath;
    }

    private function postInstallMessage(string $type): string
    {
        return match ($type) {
            'server' => 'Server extension installed. Visit the Servers page to configure it.',
            'gateway' => 'Gateway extension installed. Visit the Gateways page to configure it.',
            default => 'Extension installed. It is now available on the Installed tab.',
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => collect(ExtensionHelper::getInstallableExtensions()))
            ->description('List of available extensions (not gateway or server extensions) that can be installed.')
            ->columns([
                ImageColumn::make('meta.icon')
                    ->label('Icon')
                    ->state(fn ($record) => $record['meta']?->icon ? $record['meta']->icon : 'ri-puzzle-fill'),
                TextColumn::make('meta.name')
                    ->label('Extension Name')
                    ->searchable()
                    ->sortable()
                    ->state(fn ($record) => $record['meta'] ? $record['meta']->name . ' (' . $record['meta']->author . ')' : $record['name']),
                TextColumn::make('meta.description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('install')
                    ->label('Install')
                    ->action(function ($record) {
                        $extension = \App\Models\Extension::create([
                            'name' => $record['name'],
                            'type' => $record['type'],
                            'extension' => $record['name'],
                        ]);
                        ExtensionHelper::call($extension, 'installed', mayFail: true);

                        Notification::make()
                            ->title('Extension Installed')
                            ->body('The extension has been successfully installed.')
                            ->success()
                            ->send();

                        $this->redirect(ExtensionResource::getUrl('edit', ['record' => $extension->id]), true);
                    })
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload Extension')
                    ->icon('ri-upload-2-line')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.extensions.upload'))
                    ->form([
                        FileUpload::make('file')
                            ->label('Extension File')
                            ->required()
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->directory('extensions/uploaded')
                            ->preserveFilenames()
                            ->maxSize(10240),
                        TextInput::make('expected_sha256')
                            ->label('Expected SHA-256 (optional)')
                            ->helperText('If provided, the upload will be rejected unless its hash matches.')
                            ->regex('/^[a-f0-9]{64}$/i'),
                        TextInput::make('expected_signature')
                            ->label('Expected Signature (optional)')
                            ->helperText('HMAC-SHA256 over the SHA-256, base64 or hex. Only used when a marketplace signing key is configured.'),
                    ])
                    ->action(function (array $data, UploadExtensionService $service) {
                        try {
                            $type = $service->handle(
                                storage_path('app/' . $data['file']),
                                $data['expected_sha256'] ?: null,
                                $data['expected_signature'] ?: null,
                            );
                            switch ($type) {
                                case 'server':
                                    Notification::make()
                                        ->title('Extension uploaded successfully')
                                        ->body('Server uploaded successfully. Please go to the <a class="text-primary-600" wire:navigate href="' . ServerResource::getUrl() . '">Servers</a> page to install the new server extension.')
                                        ->success()
                                        ->send();
                                    break;
                                case 'gateway':
                                    Notification::make()
                                        ->title('Extension uploaded successfully')
                                        ->body('Gateway uploaded successfully. Please go to the <a class="text-primary-600" wire:navigate href="' . GatewayResource::getUrl() . '">Gateways</a> page to install the new gateway extension.')
                                        ->success()
                                        ->send();
                                    break;
                                default:
                                    Notification::make()
                                        ->title('Extension uploaded successfully')
                                        ->body('It should now be available on the "Ready to Install" tab.')
                                        ->success()
                                        ->send();
                                    break;
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to upload extension')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('resync')
                    ->label('Resync Marketplace')
                    ->icon('ri-refresh-line')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.marketplace.sync'))
                    ->action(function () {
                        try {
                            (new SyncMarketplaceJob)->handle();
                            Notification::make()->title('Marketplace synced')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Sync failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasPermission('admin.extensions.viewAny') && Auth::user()->hasPermission('admin.extensions.install');
    }
}
