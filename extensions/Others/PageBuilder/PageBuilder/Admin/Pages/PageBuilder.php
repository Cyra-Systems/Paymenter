<?php

namespace Paymenter\Extensions\Others\PageBuilder\Admin\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Extension as ExtensionModel;
use Livewire\Attributes\On;
use App\Models\Category;
use UnitEnum;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;

class PageBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static string|\UnitEnum|null $navigationGroup = 'Extensions';
    protected static ?string $title = 'PageBuilder';
    protected static ?string $slug = 'page-builder';

    protected string $view = 'pagebuilder::page-builder';

    public ?array $data = [];
    public ?array $previewData = [];
    public string $activeTab = 'settings';

    public function mount(): void
    {
        $this->form->fill($this->getFormData());
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getViewData(): array
    {
        return [];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General Settings')
                ->schema([
                    Section::make('Light Theme Colors')
                        ->schema([
                            ColorPicker::make('primary')
                                ->label('Primary - Brand Color (Light)')
                                ->hsl()
                                ->default('hsl(229, 100%, 64%)'),
                            ColorPicker::make('secondary')
                                ->label('Secondary - Brand Color (Light)')
                                ->hsl()
                                ->default('hsl(237, 33%, 60%)'),
                            ColorPicker::make('neutral')
                                ->label('Borders, Accents... (Light)')
                                ->hsl()
                                ->default('hsl(220, 25%, 85%)'),
                            ColorPicker::make('base')
                                ->label('Base - Text Color (Light)')
                                ->hsl()
                                ->default('hsl(0, 0%, 0%)'),
                            ColorPicker::make('muted')
                                ->label('Muted - Text Color (Light)')
                                ->hsl()
                                ->default('hsl(220, 28%, 25%)'),
                            ColorPicker::make('inverted')
                                ->label('Inverted - Text Color (Light)')
                                ->hsl()
                                ->default('hsl(100, 100%, 100%)'),
                            ColorPicker::make('background')
                                ->label('Background - Color (Light)')
                                ->hsl()
                                ->default('hsl(100, 100%, 100%)'),
                        ])
                        ->columns(2),

                    Section::make('Dark Theme Colors')
                        ->schema([
                            ColorPicker::make('dark-primary')
                                ->label('Primary - Brand Color (Dark)')
                                ->hsl()
                                ->default('hsl(229, 100%, 64%)'),
                            ColorPicker::make('dark-secondary')
                                ->label('Secondary - Brand Color (Dark)')
                                ->hsl()
                                ->default('hsl(237, 33%, 60%)'),
                            ColorPicker::make('dark-neutral')
                                ->label('Borders, Accents... (Dark)')
                                ->hsl()
                                ->default('hsl(220, 25%, 29%)'),
                            ColorPicker::make('dark-base')
                                ->label('Base - Text Color (Dark)')
                                ->hsl()
                                ->default('hsl(100, 100%, 100%)'),
                            ColorPicker::make('dark-muted')
                                ->label('Muted - Text Color (Dark)')
                                ->hsl()
                                ->default('hsl(220, 28%, 25%)'),
                            ColorPicker::make('dark-inverted')
                                ->label('Inverted - Text Color (Dark)')
                                ->hsl()
                                ->default('hsl(220, 14%, 60%)'),
                            ColorPicker::make('dark-background')
                                ->label('Background - Color (Dark)')
                                ->hsl()
                                ->default('hsl(60, 3%, 7%)'),
                        ])
                        ->columns(2),

                    Section::make('General Theme Settings')
                        ->schema([
                            TextInput::make('card_radius')
                                ->label('Card Border Radius')
                                ->placeholder('12')
                                ->default('10'),
                            TextInput::make('card_shadow')
                                ->label('Card Shadow (CSS)')
                                ->placeholder('none')
                                ->default('none'),
                            TextInput::make('section_spacing')
                                ->label('Section Spacing (px)')
                                ->placeholder('60')
                                ->default('60'),
                            TextInput::make('container_size')
                                ->label('Container Size (px)')
                                ->placeholder('1200')
                                ->default('1200'),
                            Select::make('section_transition')
                                ->label('Section Transition')
                                ->options([
                                    'none' => 'None',
                                    'fade-in' => 'Fade in',
                                    'fade-right' => 'Slide from right',
                                    'fade-left' => 'Slide from left',
                                    'fade-top' => 'Slide from top',
                                    'fade-bottom' => 'Slide from bottom',
                                ])
                                ->default('none')
                                ->native(false),

                        ])
                        ->columns(2),
                    Section::make('Configuration Script')
                        ->schema([
                            Textarea::make('config_script')
                                ->label('Configuration Script')
                                ->rows(16)
                                ->helperText('Edit as JSON. Click Sync to apply to database, or Refresh to regenerate.'),
                            \Filament\Forms\Components\Placeholder::make('config_actions')
                                ->content(fn() => new \Illuminate\Support\HtmlString(view('pagebuilder::components.config-actions', ['wireId' => $this->getId()])->render())),
                        ])
                        ->columns(1),
                ]),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Configuration')
                ->submit('save')
                ->color('primary'),
        ];
    }

    private function generatePreviewHtml(): string
    {
        $data = $this->form->getState();
        $pages = (array) ($data['pages'] ?? []);
        $previewPage = null;
        foreach ($pages as $p) {
            $route = trim((string) ($p['route'] ?? ''));
            if ($route === '/') {
                $previewPage = $p;
                break;
            }
        }
        if ($previewPage === null && !empty($pages)) {
            $previewPage = $pages[0];
        }
        $sections = (array) ($previewPage['sections'] ?? []);

        $category = null;
        $products = collect();

        $cssVariables = $this->generateCssVariables($data);
        $fontAwesome = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">';
        $html = $fontAwesome . "<style>{$cssVariables} section{padding-top: var(--hpb-section-spacing)!important;padding-bottom: var(--hpb-section-spacing)!important;} .container, .max-w-\\[1320px\\], .max-w-\\[1200px\\], .max-w-\\[1280px\\]{max-width: var(--hpb-container-size)!important;}</style>";

        $fast = (bool) ($data['fast_preview'] ?? true);
        $count = 0;
        foreach ($sections as $section) {
            if ($fast && $count >= 6) {
                break;
            }
            $type = $section['type'] ?? '';
            $variation = $section['variation'] ?? '1';
            $content = $section['content'] ?? [];
            $contentForView = $content;
            if ($type === 'hero' && isset($content['hero_data']) && is_array($content['hero_data'])) {
                $contentForView = array_merge($contentForView, $content['hero_data']);
            }
            if ($type === 'features' && isset($content['features_data']) && is_array($content['features_data'])) {
                $contentForView = array_merge($contentForView, $content['features_data']);
            }
            if ($type === 'reviews' && isset($content['reviews_data']) && is_array($content['reviews_data'])) {
                $contentForView = array_merge($contentForView, $content['reviews_data']);
                if (isset($content['reviews_data']['reviews']) && is_array($content['reviews_data']['reviews'])) {
                    $contentForView['reviews'] = $content['reviews_data']['reviews'];
                } elseif (!isset($contentForView['reviews'])) {
                    $contentForView['reviews'] = [];
                }
            }
            if ($type === 'panel' && isset($content['panel_data']) && is_array($content['panel_data'])) {
                $contentForView = array_merge($contentForView, $content['panel_data']);
                if (isset($content['panel_data']['widgets']) && is_array($content['panel_data']['widgets'])) {
                    $contentForView['widgets'] = $content['panel_data']['widgets'];
                } elseif (!isset($contentForView['widgets'])) {
                    $contentForView['widgets'] = [];
                }
            }
            if ($type === 'games' && isset($content['games_data']) && is_array($content['games_data'])) {
                $contentForView = array_merge($contentForView, $content['games_data']);
                if (isset($content['games_data']['cards']) && is_array($content['games_data']['cards'])) {
                    $contentForView['cards'] = $content['games_data']['cards'];
                } elseif (!isset($contentForView['cards'])) {
                    $contentForView['cards'] = [];
                }
            }
            if ($type === 'blog' && isset($content['blog_data']) && is_array($content['blog_data'])) {
                $contentForView = array_merge($contentForView, $content['blog_data']);
                if (isset($content['blog_data']['posts']) && is_array($content['blog_data']['posts'])) {
                    $contentForView['posts'] = $content['blog_data']['posts'];
                } elseif (!isset($contentForView['posts'])) {
                    $contentForView['posts'] = [];
                }
            }
            if ($type === 'partners' && isset($content['partners_data']) && is_array($content['partners_data'])) {
                $contentForView = array_merge($contentForView, $content['partners_data']);
                if (isset($content['partners_data']['partners']) && is_array($content['partners_data']['partners'])) {
                    $contentForView['partners'] = $content['partners_data']['partners'];
                } elseif (!isset($contentForView['partners'])) {
                    $contentForView['partners'] = [];
                }
            }
            if ($type === 'location_map' && isset($content['location_map_data']) && is_array($content['location_map_data'])) {
                $contentForView = array_merge($contentForView, $content['location_map_data']);
            }
            if ($type === 'text' && isset($content['text_data']) && is_array($content['text_data'])) {
                $contentForView = array_merge($contentForView, $content['text_data']);
            }
            if ($type === 'cta' && isset($content['cta_data']) && is_array($content['cta_data'])) {
                $contentForView = array_merge($contentForView, $content['cta_data']);
            }

            $viewPath = "pagebuilder::sections.{$type}";
            if ($type && view()->exists($viewPath)) {
                $sectionCategory = null;
                $sectionProducts = collect();
                $slug = $content['products_data']['category_slug'] ?? null;
                if ($slug) {
                    $sectionCategory = Category::where('slug', $slug)->first();
                    if ($sectionCategory) {
                        $sectionProducts = $sectionCategory->products()->where('hidden', false)->orderBy('sort')->get();
                    }
                }

                $html .= view($viewPath, [
                    'variation' => $variation,
                    'colors' => $data,
                    'data' => [
                        'category' => $sectionCategory,
                        'products' => $fast ? collect() : $sectionProducts,
                        'content' => $contentForView,
                    ],
                ])->render();
                $count++;
            }
        }

        if (empty($html)) {
            $html = '<div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <h3 class="text-lg font-medium mb-2">No Sections Configured</h3>
                <p class="text-sm">Add sections to your homepage to see them here</p>
            </div>';
        }

        return $html;
    }

    private function generateCssVariables(array $data): string
    {
        $normalize = function ($v) {
            $v = trim((string) $v);
            if (preg_match('/^hsla?\((.+)\)$/i', $v, $m)) {
                return $m[1];
            }
            return $v;
        };

        $lightMap = [
            'primary' => $data['primary'] ?? null,
            'secondary' => $data['secondary'] ?? null,
            'neutral' => $data['neutral'] ?? null,
            'base' => $data['base'] ?? null,
            'muted' => $data['muted'] ?? null,
            'inverted' => $data['inverted'] ?? null,
            'background' => $data['background'] ?? null,
        ];

        $darkMap = [
            'primary' => $data['dark-primary'] ?? null,
            'secondary' => $data['dark-secondary'] ?? null,
            'neutral' => $data['dark-neutral'] ?? null,
            'base' => $data['dark-base'] ?? null,
            'muted' => $data['dark-muted'] ?? null,
            'inverted' => $data['dark-inverted'] ?? null,
            'background' => $data['dark-background'] ?? null,
        ];

        $rootVars = [];
        foreach ($lightMap as $key => $value) {
            if ($value !== null && $value !== '') {
                $rootVars[] = "--hpb-color-{$key}: " . $normalize($value) . ";";
            }
        }

        $darkVars = [];
        foreach ($darkMap as $key => $value) {
            if ($value !== null && $value !== '') {
                $darkVars[] = "--hpb-color-{$key}: " . $normalize($value) . ";";
            }
        }

        $cardRadius = $data['card_radius'] ?? 12;
        if (!is_numeric($cardRadius)) {
            $cardRadius = 12;
        }
        $cardShadow = trim((string) ($data['card_shadow'] ?? '0 4px 12px rgba(0,0,0,0.08)'));
        $sectionSpacing = $data['section_spacing'] ?? 80;
        if (!is_numeric($sectionSpacing)) {
            $sectionSpacing = 80;
        }

        $rootVars[] = "--hpb-card-radius: " . ((int) $cardRadius) . "px;";
        $rootVars[] = "--hpb-card-shadow: " . $cardShadow . ";";
        $rootVars[] = "--hpb-section-spacing: " . ((int) $sectionSpacing) . "px;";
        $containerSize = $data['container_size'] ?? 1200;
        if (!is_numeric($containerSize)) {
            $containerSize = 1200;
        }
        $rootVars[] = "--hpb-container-size: " . ((int) $containerSize) . "px;";

        $root = ':root { ' . implode(' ', $rootVars) . ' }';
        $dark = '.dark { ' . implode(' ', $darkVars) . ' }';

        return $root . ' ' . $dark;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $ext = ExtensionModel::firstOrCreate(
            ['extension' => 'PageBuilder'],
            ['name' => 'PageBuilder', 'type' => 'other', 'enabled' => true]
        );

        $config = $this->buildConfigArrayFromState($data);

        $ext->settings()->updateOrCreate(
            ['key' => 'config'],
            ['value' => $config, 'type' => 'array', 'encrypted' => false]
        );

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    public function refreshConfigScript(): void
    {
        $current = $this->form->getState();
        $config = $this->buildConfigArrayFromState($current);
        $script = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->data['config_script'] = $script;
        $this->form->fill($this->data);
    }

    public function saveConfigScript(): void
    {

        $script = (string) ($this->data['config_script'] ?? '');
        $decoded = json_decode($script, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Notification::make()
                ->title('Invalid configuration script')
                ->body('Please provide valid JSON.')
                ->danger()
                ->send();
            return;
        }

        $ext = ExtensionModel::firstOrCreate(
            ['extension' => 'PageBuilder'],
            ['name' => 'PageBuilder', 'type' => 'other', 'enabled' => true]
        );

        if ($ext) {
            $ext->settings()->updateOrCreate(
                ['key' => 'config'],
                ['value' => $decoded, 'type' => 'array', 'encrypted' => false]
            );
        }

        Notification::make()
            ->title('Configuration script saved')
            ->success()
            ->send();

        $this->dispatch('refresh')->self();
    }

    private function buildConfigArrayFromState(array $data): array
    {
        $colorKeys = [
            'primary',
            'secondary',
            'neutral',
            'base',
            'muted',
            'inverted',
            'background',
            'dark-primary',
            'dark-secondary',
            'dark-neutral',
            'dark-base',
            'dark-muted',
            'dark-inverted',
            'dark-background',
        ];
        $generalKeys = ['card_radius', 'card_shadow', 'section_spacing', 'container_size'];

        $out = [];
        if (array_key_exists('pages', $data)) {
            $out['pages'] = is_array($data['pages']) ? $data['pages'] : [];
        }
        foreach ($colorKeys as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $data[$k];
            }
        }
        foreach ($generalKeys as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $data[$k];
            }
        }

        return $out;
    }

    private function normalizeConfigArray(array $config): array
    {
        $colorKeys = [
            'primary',
            'secondary',
            'neutral',
            'base',
            'muted',
            'inverted',
            'background',
            'dark-primary',
            'dark-secondary',
            'dark-neutral',
            'dark-base',
            'dark-muted',
            'dark-inverted',
            'dark-background',
        ];
        $generalKeys = ['card_radius', 'card_shadow', 'section_spacing', 'container_size'];

        $out = [];





        if (isset($config['pages']) && is_array($config['pages'])) {
            $out['pages'] = $config['pages'];
        }

        foreach ($colorKeys as $k) {
            if (isset($config[$k])) {
                $out[$k] = (string) $config[$k];
            }
        }

        if (array_key_exists('card_radius', $config)) {
            $out['card_radius'] = (int) $config['card_radius'];
        }
        if (array_key_exists('card_shadow', $config)) {
            $out['card_shadow'] = (string) $config['card_shadow'];
        }
        if (array_key_exists('section_spacing', $config)) {
            $out['section_spacing'] = (int) $config['section_spacing'];
        }
        if (array_key_exists('container_size', $config)) {
            $out['container_size'] = (int) $config['container_size'];
        }

        return $out;
    }

    private function normalizeSections(array $sections): array
    {
        $normalized = [];
        foreach ($sections as $s) {
            if (!is_array($s)) {
                continue;
            }
            if (!array_key_exists('content', $s) || !is_array($s['content'])) {
                $s['content'] = [];
            }
            if (!array_key_exists('hero_data', $s['content']) || !is_array($s['content']['hero_data'])) {
                $s['content']['hero_data'] = [];
            }
            if (!array_key_exists('features_data', $s['content']) || !is_array($s['content']['features_data'])) {
                $s['content']['features_data'] = [];
            }
            if (!array_key_exists('reviews_data', $s['content']) || !is_array($s['content']['reviews_data'])) {
                $s['content']['reviews_data'] = [];
            }
            if (!array_key_exists('section_title', $s['content']['reviews_data'])) {
                $s['content']['reviews_data']['section_title'] = '';
            }
            if (!array_key_exists('section_description', $s['content']['reviews_data'])) {
                $s['content']['reviews_data']['section_description'] = '';
            }
            if (!array_key_exists('reviews', $s['content']['reviews_data']) || !is_array($s['content']['reviews_data']['reviews'])) {
                $s['content']['reviews_data']['reviews'] = [];
            }
            if (!array_key_exists('games_data', $s['content']) || !is_array($s['content']['games_data'])) {
                $s['content']['games_data'] = [];
            }
            if (!array_key_exists('cards', $s['content']['games_data']) || !is_array($s['content']['games_data']['cards'])) {
                $s['content']['games_data']['cards'] = [];
            }
            if (!array_key_exists('blog_data', $s['content']) || !is_array($s['content']['blog_data'])) {
                $s['content']['blog_data'] = [];
            }
            if (!array_key_exists('posts', $s['content']['blog_data']) || !is_array($s['content']['blog_data']['posts'])) {
                $s['content']['blog_data']['posts'] = [];
            }
            if (!array_key_exists('partners_data', $s['content']) || !is_array($s['content']['partners_data'])) {
                $s['content']['partners_data'] = [];
            }
            if (!array_key_exists('partners', $s['content']['partners_data']) || !is_array($s['content']['partners_data']['partners'])) {
                $s['content']['partners_data']['partners'] = [];
            }
            if (!array_key_exists('location_map_data', $s['content']) || !is_array($s['content']['location_map_data'])) {
                $s['content']['location_map_data'] = [];
            }
            if (!array_key_exists('panel_data', $s['content']) || !is_array($s['content']['panel_data'])) {
                $s['content']['panel_data'] = [];
            }
            if (!array_key_exists('widgets', $s['content']['panel_data']) || !is_array($s['content']['panel_data']['widgets'])) {
                $s['content']['panel_data']['widgets'] = [];
            }
            if (!array_key_exists('cta_data', $s['content']) || !is_array($s['content']['cta_data'])) {
                $s['content']['cta_data'] = [];
            }
            if (!array_key_exists('buttons', $s['content']['cta_data']) || !is_array($s['content']['cta_data']['buttons'])) {
                $s['content']['cta_data']['buttons'] = [];
            }
            $normalized[] = $s;
        }
        return $normalized;
    }

    protected function getFormData(): array
    {
        $ext = ExtensionModel::firstOrCreate(
            ['extension' => 'PageBuilder'],
            ['name' => 'PageBuilder', 'type' => 'other', 'enabled' => true]
        );

        $productsCategory = '';
        $pages = [];
        $colorSettings = [];
        $generalSettings = [];

        if ($ext) {
            $configSetting = $ext->settings()->where('key', 'config')->first();
            $config = $configSetting ? (array) $configSetting->value : [];
            $settings = $ext->settings->pluck('value', 'key')->toArray();
            $productsCategory = '';

            $pages = $config['pages'] ?? ($settings['pages'] ?? []);
            if (is_string($pages)) {
                $pages = json_decode($pages, true) ?: [];
            }

            if (empty($pages)) {
                $old = ExtensionModel::where('extension', 'PageBuilder')->first();
                if ($old) {
                    $oldSettings = $old->settings->pluck('value', 'key')->toArray();
                    $productsCategory = '';
                    $pages = $oldSettings['pages'] ?? $pages;
                    if (is_string($pages)) {
                        $pages = json_decode($pages, true) ?: [];
                    }
                    foreach ([
                        'primary',
                        'secondary',
                        'neutral',
                        'base',
                        'muted',
                        'inverted',
                        'background',
                        'dark-primary',
                        'dark-secondary',
                        'dark-neutral',
                        'dark-base',
                        'dark-muted',
                        'dark-inverted',
                        'dark-background',
                        'card_radius',
                        'card_shadow',
                        'section_spacing',
                        'container_size',
                        'section_transition'
                    ] as $k) {
                        if (!array_key_exists($k, $settings) && array_key_exists($k, $oldSettings)) {
                            $settings[$k] = $oldSettings[$k];
                        }
                    }
                }
            }

            $colorKeys = [
                'primary',
                'secondary',
                'neutral',
                'base',
                'muted',
                'inverted',
                'background',
                'dark-primary',
                'dark-secondary',
                'dark-neutral',
                'dark-base',
                'dark-muted',
                'dark-inverted',
                'dark-background',
            ];

            foreach ($colorKeys as $colorKey) {
                $value = $config[$colorKey] ?? ($settings[$colorKey] ?? $this->getDefaultColor($colorKey));
                if (is_string($value) && preg_match('/^\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*$/', $value)) {
                    $value = 'hsl(' . $value . ')';
                }
                $colorSettings[$colorKey] = $value;
            }

            $generalSettings['card_radius'] = isset($config['card_radius']) ? (int) $config['card_radius'] : (isset($settings['card_radius']) ? (int) $settings['card_radius'] : 12);
            $generalSettings['card_shadow'] = $config['card_shadow'] ?? ($settings['card_shadow'] ?? '0 4px 12px rgba(0,0,0,0.08)');
            $generalSettings['section_spacing'] = isset($config['section_spacing']) ? (int) $config['section_spacing'] : (isset($settings['section_spacing']) ? (int) $settings['section_spacing'] : 80);
            $generalSettings['container_size'] = isset($config['container_size']) ? (int) $config['container_size'] : (isset($settings['container_size']) ? (int) $settings['container_size'] : 1200);
            $transition = $config['section_transition'] ?? ($settings['section_transition'] ?? 'none');
            $generalSettings['section_transition'] = in_array($transition, ['none', 'fade-in', 'fade-right', 'fade-left', 'fade-top', 'fade-bottom']) ? $transition : 'none';
        }



        $base = array_merge([
            'pages' => $pages,
        ], $colorSettings, $generalSettings);

        $base['config_script'] = json_encode(
            $this->buildConfigArrayFromState($base),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return $base;
    }

    private function getDefaultColor(string $colorKey): string
    {
        $defaults = [
            'primary' => 'hsl(229, 100%, 64%)',
            'secondary' => 'hsl(237, 33%, 60%)',
            'neutral' => 'hsl(220, 25%, 85%)',
            'base' => 'hsl(0, 0%, 0%)',
            'muted' => 'hsl(220, 28%, 25%)',
            'inverted' => 'hsl(100, 100%, 100%)',
            'background' => 'hsl(100, 100%, 100%)',
            'dark-primary' => 'hsl(229, 100%, 64%)',
            'dark-secondary' => 'hsl(237, 33%, 60%)',
            'dark-neutral' => 'hsl(220, 25%, 29%)',
            'dark-base' => 'hsl(100, 100%, 100%)',
            'dark-muted' => 'hsl(220, 28%, 25%)',
            'dark-inverted' => 'hsl(220, 14%, 60%)',
            'dark-background' => 'hsl(60, 3%, 7%)',
        ];

        return $defaults[$colorKey] ?? 'hsl(0, 0%, 0%)';
    }
}
