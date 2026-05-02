<?php

namespace App\Admin\Concerns;

use App\Helpers\ExtensionHelper;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasMigrateExtensionAction
{
    protected function migrateExtensionAction(): Action
    {
        return Action::make('migrate')
            ->label('Run Database Migrations')
            ->icon('ri-database-2-line')
            ->color('warning')
            ->visible(fn () => Auth::user()?->hasPermission('admin.extensions.migrate'))
            ->requiresConfirmation()
            ->modalDescription('This will run any pending migrations shipped with this extension. Database schema changes are not reversible from this dialog.')
            ->action(function (Model $record) {
                $path = 'extensions/' . ucfirst($record->type) . 's/' . ucfirst($record->extension) . '/database/migrations';

                if (!is_dir(base_path($path))) {
                    Notification::make()
                        ->title('No migrations directory')
                        ->body('This extension does not ship a database/migrations directory at ' . $path . '.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = ExtensionHelper::runMigrationsWithResult($path);

                if ($result['ok']) {
                    $count = count($result['ran']);
                    Notification::make()
                        ->title('Migrations complete')
                        ->body($count > 0 ? "Ran {$count} migration(s)." : 'No new migrations to run.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Migration failed')
                        ->body($result['error'])
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
