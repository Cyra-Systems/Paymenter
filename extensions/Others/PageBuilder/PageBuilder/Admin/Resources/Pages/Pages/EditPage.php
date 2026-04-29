<?php

namespace Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\PageResource;
use Filament\Notifications\Notification;
use Paymenter\Extensions\Others\PageBuilder\Models\Page;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label('Duplicate')
                ->color('gray')
                ->action(fn () => $this->duplicate()),
            DeleteAction::make(),
        ];
    }

    public function saveConfigScript(): void
    {
        $script = (string) ($this->form->getState()['config_script'] ?? '');
        $decoded = json_decode($script, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Notification::make()
                ->title('Invalid configuration script')
                ->body('Please provide valid JSON.')
                ->danger()
                ->send();
            return;
        }

        $record = $this->getRecord();
        $targetRoute = trim((string) ($decoded['route'] ?? $record->route));
        $target = Page::where('route', $targetRoute)->first();
        if (!$target) {
            $target = $record;
        }

        $attrs = [
            'title' => $decoded['title'] ?? $target->title,
            'route' => $targetRoute,
            'show_in_nav' => $decoded['show_in_nav'] ?? $target->show_in_nav,
            'nav_priority' => $decoded['nav_priority'] ?? $target->nav_priority,
            'meta_title' => $decoded['meta_title'] ?? $target->meta_title,
            'meta_description' => $decoded['meta_description'] ?? $target->meta_description,
            'meta_keywords' => $decoded['meta_keywords'] ?? $target->meta_keywords,
            'meta_og_title' => $decoded['meta_og_title'] ?? $target->meta_og_title,
            'meta_og_description' => $decoded['meta_og_description'] ?? $target->meta_og_description,
            'meta_og_image' => $decoded['meta_og_image'] ?? $target->meta_og_image,
            'meta_twitter_title' => $decoded['meta_twitter_title'] ?? $target->meta_twitter_title,
            'meta_twitter_description' => $decoded['meta_twitter_description'] ?? $target->meta_twitter_description,
            'meta_twitter_image' => $decoded['meta_twitter_image'] ?? $target->meta_twitter_image,
            'meta_twitter_card' => $decoded['meta_twitter_card'] ?? $target->meta_twitter_card,
            'canonical_url' => $decoded['canonical_url'] ?? $target->canonical_url,
            'sections' => $decoded['sections'] ?? $target->sections,
        ];

        $target->fill($attrs);
        $target->save();

        Notification::make()
            ->title('Configuration applied')
            ->success()
            ->send();

        if ($target->getKey() !== $record->getKey()) {
            $this->redirect(static::getResource()::getUrl('edit', ['record' => $target]));
            return;
        }
        $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
        return;
    }

    protected function duplicate(): void
    {
        $original = $this->getRecord();

        $baseRoute = trim((string) $original->route);
        if ($baseRoute === '') { $baseRoute = '/'; }
        if ($baseRoute !== '/' && str_ends_with($baseRoute, '/')) { $baseRoute = rtrim($baseRoute, '/'); }

        $candidate = $baseRoute === '/' ? '/home-copy' : $baseRoute . '-copy';
        $i = 2;
        while (Page::where('route', $candidate)->exists()) {
            $candidate = ($baseRoute === '/' ? '/home-copy' : $baseRoute . '-copy') . '-' . $i;
            $i++;
        }

        $copy = Page::create([
            'route' => $candidate,
            'show_in_nav' => $original->show_in_nav,
            'show_in_dropdown' => $original->show_in_dropdown ?? false,
            'dropdown_name' => $original->dropdown_name ?? null,
            'nav_priority' => $original->nav_priority,
            'title' => ($original->title ? ($original->title . ' (Copy)') : null),
            'meta_title' => $original->meta_title,
            'meta_description' => $original->meta_description,
            'meta_keywords' => $original->meta_keywords,
            'meta_og_title' => $original->meta_og_title,
            'meta_og_description' => $original->meta_og_description,
            'meta_og_image' => $original->meta_og_image,
            'meta_twitter_title' => $original->meta_twitter_title,
            'meta_twitter_description' => $original->meta_twitter_description,
            'meta_twitter_image' => $original->meta_twitter_image,
            'meta_twitter_card' => $original->meta_twitter_card,
            'canonical_url' => $original->canonical_url,
            'sections' => $original->sections,
        ]);

        Notification::make()->title('Page duplicated')->success()->send();
        $this->redirect(static::getResource()::getUrl('edit', ['record' => $copy]));
    }

    public function refreshConfigScript(): void
    {
        $state = $this->form->getState();
        $script = json_encode([
            'title' => $state['title'] ?? null,
            'route' => $state['route'] ?? null,
            'show_in_nav' => $state['show_in_nav'] ?? false,
            'nav_priority' => $state['nav_priority'] ?? null,
            'meta_title' => $state['meta_title'] ?? null,
            'meta_description' => $state['meta_description'] ?? null,
            'meta_keywords' => $state['meta_keywords'] ?? null,
            'meta_og_title' => $state['meta_og_title'] ?? null,
            'meta_og_description' => $state['meta_og_description'] ?? null,
            'meta_og_image' => $state['meta_og_image'] ?? null,
            'meta_twitter_title' => $state['meta_twitter_title'] ?? null,
            'meta_twitter_description' => $state['meta_twitter_description'] ?? null,
            'meta_twitter_image' => $state['meta_twitter_image'] ?? null,
            'meta_twitter_card' => $state['meta_twitter_card'] ?? 'summary_large_image',
            'canonical_url' => $state['canonical_url'] ?? null,
            'sections' => $state['sections'] ?? [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $data = $this->form->getState();
        $data['config_script'] = $script;
        $this->form->fill($data);

        Notification::make()
            ->title('Configuration refreshed')
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $script = json_encode([
            'title' => $data['title'] ?? null,
            'route' => $data['route'] ?? null,
            'show_in_nav' => $data['show_in_nav'] ?? false,
            'nav_priority' => $data['nav_priority'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'meta_og_title' => $data['meta_og_title'] ?? null,
            'meta_og_description' => $data['meta_og_description'] ?? null,
            'meta_og_image' => $data['meta_og_image'] ?? null,
            'meta_twitter_title' => $data['meta_twitter_title'] ?? null,
            'meta_twitter_description' => $data['meta_twitter_description'] ?? null,
            'meta_twitter_image' => $data['meta_twitter_image'] ?? null,
            'meta_twitter_card' => $data['meta_twitter_card'] ?? 'summary_large_image',
            'canonical_url' => $data['canonical_url'] ?? null,
            'sections' => $data['sections'] ?? [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $data['config_script'] = $script;
        return $data;
    }
}
