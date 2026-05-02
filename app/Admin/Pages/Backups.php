<?php

namespace App\Admin\Pages;

use App\Jobs\Backup\CreateBackupJob;
use App\Jobs\Backup\RestoreDatabaseBackupJob;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Backups extends Page implements HasActions, HasTable
{
    use InteractsWithActions, InteractsWithTable;

    protected string $view = 'admin.pages.backups';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-archive-2-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-archive-2-fill';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?int $navigationSort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getBackupRows())
            ->columns([
                TextColumn::make('filename')->label('Filename')->searchable(),
                TextColumn::make('size_human')->label('Size'),
                TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('ri-download-line')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.backups.download'))
                    ->action(fn ($record) => $this->downloadBackup($record['path'])),
                Action::make('restore')
                    ->label('Restore Database')
                    ->icon('ri-restart-line')
                    ->color('warning')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.backups.restore'))
                    ->requiresConfirmation()
                    ->modalHeading('Restore database from backup?')
                    ->modalDescription('This OVERWRITES the current database with the contents of the backup. There is no undo. Make sure you have a recent backup of the current state before continuing.')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('confirm')
                            ->label('Type RESTORE to confirm')
                            ->required()
                            ->rule('in:RESTORE'),
                    ])
                    ->action(function ($record) {
                        try {
                            (new RestoreDatabaseBackupJob($record['path']))->handle();
                            Notification::make()->title('Database restored')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Restore failed')->body($e->getMessage())->danger()->persistent()->send();
                        }
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('ri-delete-bin-line')
                    ->color('danger')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.backups.delete'))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        Storage::disk('local')->delete($record['path']);
                        Notification::make()->title('Backup deleted')->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create Backup Now')
                    ->icon('ri-add-line')
                    ->visible(fn () => Auth::user()?->hasPermission('admin.backups.create'))
                    ->action(function () {
                        try {
                            (new CreateBackupJob)->handle();
                            Notification::make()->title('Backup completed')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Backup failed')->body($e->getMessage())->danger()->persistent()->send();
                        }
                    }),
            ]);
    }

    private function getBackupRows(): array
    {
        $disk = Storage::disk('local');
        $prefix = config('backup.backup.name', config('app.name', 'Paymenter'));

        if (!$disk->exists($prefix)) {
            return [];
        }

        $files = $disk->files($prefix);
        $rows = [];
        foreach ($files as $path) {
            if (!str_ends_with($path, '.zip')) {
                continue;
            }
            $size = $disk->size($path);
            $rows[] = [
                'id' => md5($path),
                'path' => $path,
                'filename' => basename($path),
                'size_human' => $this->humanBytes($size),
                'created_at' => $disk->lastModified($path) ? \Carbon\Carbon::createFromTimestamp($disk->lastModified($path)) : null,
            ];
        }

        return $rows;
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function downloadBackup(string $relativePath): StreamedResponse
    {
        return Storage::disk('local')->download($relativePath);
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasPermission('admin.backups.viewAny');
    }
}
