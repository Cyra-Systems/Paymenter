<?php

namespace App\Admin\Actions;

use App\Classes\Settings as ClassesSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ResetColorsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resetColors';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Reset Theme')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function () {
                Gate::authorize('has-permission', 'admin.settings.update');

                $resetSettings = [];
                foreach (ClassesSettings::settings() as $settings) {
                    foreach ($settings as $setting) {
                        $type = $setting['type'] ?? '';
                        $name = $setting['name'] ?? '';

                        $isAppearance = $type === 'color'
                            || Str::contains($name, ['radius-', '-radius', 'gradient-', 'glass-', 'glow-']);

                        if ($isAppearance) {
                            $resetSettings[$name] = $setting['default'] ?? '';
                        }
                    }
                }

                $livewire = $this->getLivewire();
                $currentData = $livewire->form->getState();
                foreach ($resetSettings as $key => $defaultValue) {
                    $currentData[$key] = $defaultValue;
                }
                $livewire->form->fill($currentData);

                Notification::make()
                    ->title('Theme appearance has been reset!')
                    ->success()
                    ->send();
            });
    }
}
