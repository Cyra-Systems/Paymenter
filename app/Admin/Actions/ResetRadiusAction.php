<?php

namespace App\Admin\Actions;

use App\Classes\Settings as ClassesSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ResetRadiusAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resetRadius';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Reset Radius')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function () {
                Gate::authorize('has-permission', 'admin.settings.update');

                $radiusSettings = [];
                foreach (ClassesSettings::settings() as $settings) {
                    foreach ($settings as $setting) {
                        $name = $setting['name'] ?? '';
                        // Match either "radius-*" or "theme_<theme>_radius-*"
                        if (Str::contains($name, 'radius-') || Str::endsWith($name, '-radius')) {
                            $radiusSettings[$name] = $setting['default'] ?? '';
                        }
                    }
                }

                $livewire = $this->getLivewire();
                $currentData = $livewire->form->getState();
                foreach ($radiusSettings as $key => $defaultValue) {
                    $currentData[$key] = $defaultValue;
                }
                $livewire->form->fill($currentData);

                Notification::make()
                    ->title('Radius has been reset!')
                    ->success()
                    ->send();
            });
    }
}
