<?php

namespace App\Admin\Pages;

use App\Admin\Clusters\Themes as ThemesCluster;
use App\Jobs\Marketplace\SyncMarketplaceJob;
use App\Models\MarketplaceListing;
use App\Models\Theme;
use App\Services\Themes\BuildThemeService;
use App\Services\Themes\UploadThemeService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Url;

class Themes extends Page implements HasActions, HasTable
{
    use InteractsWithActions, InteractsWithTable;

    protected string $view = 'admin.pages.themes';

    protected static ?string $cluster = ThemesCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-palette-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-palette-fill';

    protected static ?string $navigationLabel = 'Themes';

    #[Url(except: 'installed', as: 'tab')]
    public string $activeTab = 'installed';

    #[Url(except: '', as: 'q')]
    public string $search = '';

    public ?string $error = null;

    public function mount(): void
    {
        $this->ensureLocalThemesTracked();
    }

    private function ensureLocalThemesTracked(): void
    {
        $dirs = glob(base_path('themes/*'), GLOB_ONLYDIR);
        $activeName = config('settings.theme', 'default');

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if ($name === '__MACOSX') {
                continue;
            }

            Theme::firstOrCreate(
                ['name' => $name],
                [
                    'active' => $name === $activeName,
                    'version' => $this->readThemeVersion($dir),
                ]
            );
        }
    }

    private function readThemeVersion(string $dir): ?string
    {
        $themeFile = $dir . '/theme.php';
        if (!file_exists($themeFile)) {
            return null;
        }
        $meta = require $themeFile;

        return is_array($meta) ? ($meta['version'] ?? null) : null;
    }

    public function getMarketplaceThemesProperty(): Collection
    {
        return MarketplaceListing::query()
            ->where('type', 'theme')
            ->orderBy('name')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->get()
            ->map(fn (MarketplaceListing $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'type' => 'theme',
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
            ]);
    }

    public function downloadAndInstallTheme(int $listingId): void
    {
        $listing = MarketplaceListing::find($listingId);
        if (!$listing || $listing->type !== 'theme') {
            Notification::make()->title('Theme not found')->danger()->send();

            return;
        }

        try {
            if (config('settings.marketplace_require_signature', true)) {
                if (empty($listing->signature)) {
                    throw new \Exception('This theme is unsigned and the marketplace requires signed packages.');
                }
                $key = config('settings.marketplace_signing_key');
                if (empty($key) || !hash_equals($listing->signature, hash_hmac('sha256', $listing->sha256, $key))) {
                    throw new \Exception('Signature verification failed for this theme.');
                }
            }

            $cacheDir = storage_path('app/marketplace-cache');
            if (!is_dir($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }
            $zipPath = $cacheDir . '/theme-' . $listing->sha256 . '.zip';

            if (!file_exists($zipPath) || hash_file('sha256', $zipPath) !== $listing->sha256) {
                $response = Http::timeout(60)
                    ->withUserAgent('Paymenter/' . config('app.version') . ' (theme-download)')
                    ->get($listing->download_url);
                if (!$response->successful()) {
                    throw new \Exception('Failed to download theme (HTTP ' . $response->status() . ').');
                }
                file_put_contents($zipPath, $response->body());
            }

            if (hash_file('sha256', $zipPath) !== $listing->sha256) {
                File::delete($zipPath);
                throw new \Exception('Downloaded theme hash does not match the manifest.');
            }

            $stagedPath = $cacheDir . '/theme-staged-' . uniqid() . '.zip';
            File::copy($zipPath, $stagedPath);

            $service = app(UploadThemeService::class);
            $service->handle($stagedPath, $listing->sha256, $listing->signature, $listing->download_url);

            Notification::make()->title('Theme installed')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to install theme')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => Theme::query()->orderByDesc('active')->orderBy('name')->get())
            ->columns([
                IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('ri-check-line')
                    ->falseIcon(null),
                TextColumn::make('name')->label('Theme')->searchable()->sortable(),
                TextColumn::make('version')->label('Version'),
                TextColumn::make('author')->label('Author'),
                TextColumn::make('last_build_status')
                    ->label('Build')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'ok' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ?? 'Not built'),
                TextColumn::make('last_built_at')->label('Last Built')->since()->placeholder('Never'),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Set Active')
                    ->icon('ri-check-line')
                    ->color('success')
                    ->visible(fn (Theme $record) => !$record->active && Auth::user()?->hasPermission('admin.themes.install'))
                    ->action(function (Theme $record) {
                        $record->setActive();
                        Notification::make()->title("Activated {$record->name}")->success()->send();
                    }),
                Action::make('build')
                    ->label('Build')
                    ->icon('ri-hammer-line')
                    ->color('warning')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.themes.build'))
                    ->requiresConfirmation()
                    ->modalDescription('This runs `node vite.js <theme>` and may take up to 5 minutes. Output will be captured.')
                    ->action(function (Theme $record, BuildThemeService $service) {
                        $result = $service->build($record);

                        if ($result['ok']) {
                            Notification::make()
                                ->title("Built {$record->name} in " . round($result['duration_ms'] / 1000, 1) . 's')
                                ->success()
                                ->send();
                        } else {
                            $stderr = $result['stderr'] ?: $result['stdout'] ?: 'No output captured.';
                            $truncated = strlen($stderr) > 4000 ? '…' . substr($stderr, -4000) : $stderr;
                            Notification::make()
                                ->title('Theme build failed')
                                ->body('<pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded">' . e($truncated) . '</pre><p class="mt-2 text-xs">Full log: ' . e($result['log_path'] ?? '') . '</p>')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('viewLog')
                    ->label('View Log')
                    ->icon('ri-file-text-line')
                    ->visible(fn (Theme $record) => $record->last_build_log_path && file_exists($record->last_build_log_path))
                    ->modalContent(fn (Theme $record) => view('admin.pages.theme-log', [
                        'log' => file_get_contents($record->last_build_log_path),
                        'path' => $record->last_build_log_path,
                    ])),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('ri-delete-bin-line')
                    ->color('danger')
                    ->visible(fn (Theme $record) => !$record->active && Auth::user()?->hasPermission('admin.themes.delete'))
                    ->requiresConfirmation()
                    ->modalDescription('This permanently deletes the theme files and database row.')
                    ->action(function (Theme $record) {
                        if ($record->active) {
                            Notification::make()->title('Cannot delete the active theme')->danger()->send();

                            return;
                        }
                        File::deleteDirectory(base_path('themes/' . $record->name));
                        $record->delete();
                        Notification::make()->title("Deleted {$record->name}")->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload Theme')
                    ->icon('ri-upload-2-line')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.themes.upload'))
                    ->form([
                        FileUpload::make('file')
                            ->label('Theme ZIP')
                            ->required()
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->directory('themes/uploaded')
                            ->preserveFilenames()
                            ->maxSize(20480),
                        TextInput::make('expected_sha256')
                            ->label('Expected SHA-256 (optional)')
                            ->regex('/^[a-f0-9]{64}$/i'),
                        TextInput::make('expected_signature')
                            ->label('Expected Signature (optional)'),
                    ])
                    ->action(function (array $data, UploadThemeService $service) {
                        try {
                            $theme = $service->handle(
                                storage_path('app/' . $data['file']),
                                $data['expected_sha256'] ?: null,
                                $data['expected_signature'] ?: null,
                            );
                            Notification::make()
                                ->title("Theme {$theme->name} installed")
                                ->body('Run "Build" before activating to compile its assets.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Theme upload failed')->body($e->getMessage())->danger()->send();
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
        return Auth::user()->hasPermission('admin.themes.viewAny');
    }
}
