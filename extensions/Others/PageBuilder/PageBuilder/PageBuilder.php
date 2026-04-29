<?php

namespace Paymenter\Extensions\Others\PageBuilder;

use App\Classes\Extension\Extension;
use App\Models\Category;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Paymenter\Extensions\Others\PageBuilder\Models\Page;
use App\Helpers\ExtensionHelper;

View::prependLocation(__DIR__ . '/resources/views/overrides');

class PageBuilder extends Extension
{
	public function getConfig($values = [])
	{
		return [
			[
				'name' => 'primary',
				'label' => 'Primary – Brand Colour (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(229, 100%, 64%)',
			],
			[
				'name' => 'secondary',
				'label' => 'Secondary – Brand Colour (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(237, 33%, 60%)',
			],
			[
				'name' => 'neutral',
				'label' => 'Neutral – Borders/Accents (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(220, 25%, 85%)',
			],
			[
				'name' => 'base',
				'label' => 'Base – Text Colour (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(0, 0%, 0%)',
			],
			[
				'name' => 'muted',
				'label' => 'Muted – Text Colour (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(220, 28%, 25%)',
			],
			[
				'name' => 'inverted',
				'label' => 'Inverted – Text Colour (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(100, 100%, 100%)',
			],
			[
				'name' => 'background',
				'label' => 'Background – Colour (Light)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(100, 100%, 100%)',
			],
			[
				'name' => 'dark-primary',
				'label' => 'Primary – Brand Colour (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(229, 100%, 64%)',
			],
			[
				'name' => 'dark-secondary',
				'label' => 'Secondary – Brand Colour (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(237, 33%, 60%)',
			],
			[
				'name' => 'dark-neutral',
				'label' => 'Neutral – Borders/Accents (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(220, 25%, 29%)',
			],
			[
				'name' => 'dark-base',
				'label' => 'Base – Text Colour (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(100, 100%, 100%)',
			],
			[
				'name' => 'dark-muted',
				'label' => 'Muted – Text Colour (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(220, 28%, 25%)',
			],
			[
				'name' => 'dark-inverted',
				'label' => 'Inverted – Text Colour (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(220, 14%, 60%)',
			],
			[
				'name' => 'dark-background',
				'label' => 'Background – Colour (Dark)',
				'type' => 'color',
				'color_mode' => 'hsl',
				'default' => 'hsl(60, 3%, 7%)',
			],
			[
				'name' => 'card_radius',
				'label' => 'Card Border Radius',
				'type' => 'number',
				'default' => 12,
				'database_type' => 'integer',
			],
			[
				'name' => 'card_shadow',
				'label' => 'Card Shadow (CSS)',
				'type' => 'text',
				'default' => '0 4px 12px rgba(0,0,0,0.08)',
			],
			[
				'name' => 'section_spacing',
				'label' => 'Section Spacing (px)',
				'type' => 'number',
				'default' => 80,
				'database_type' => 'integer',
			],
			[
				'name' => 'container_size',
				'label' => 'Container Size (px)',
				'type' => 'number',
				'default' => 1200,
				'database_type' => 'integer',
			],
			[
				'name' => 'section_transition',
				'label' => 'Section Transition',
				'type' => 'select',
				'options' => [
					'none' => 'None',
					'fade-in' => 'Fade in',
					'fade-right' => 'Slide from right',
					'fade-left' => 'Slide from left',
					'fade-top' => 'Slide from top',
					'fade-bottom' => 'Slide from bottom',
				],
				'default' => 'none',
			],
		];
	}

    public function installed()
    {
        $this->runExtensionMigrations();
		$this->seedDefaultSettings();
    }

    public function uninstalled()
    {
        $this->rollbackExtensionMigrations();
    }

    public function upgraded($oldVersion = null)
    {
        $this->runExtensionMigrations();
    }

	private function seedDefaultSettings(): void
	{
		$ext = \App\Models\Extension::firstOrCreate(
			['extension' => 'PageBuilder'],
			['name' => 'PageBuilder', 'type' => 'other', 'enabled' => true]
		);
		if (!$ext) {
			return;
		}
		$items = (array) $this->getConfig();
		$defaults = [];
		foreach ($items as $item) {
			$name = $item['name'] ?? null;
			if (!$name) {
				continue;
			}
			if (array_key_exists('default', $item)) {
				$defaults[$name] = $item['default'];
			}
		}
		$configArray = $defaults;
		$ext->settings()->updateOrCreate(
			['key' => 'config'],
			['value' => $configArray, 'type' => 'array', 'encrypted' => false]
		);
		foreach ($defaults as $key => $value) {
			$type = is_int($value) ? 'integer' : (is_array($value) ? 'array' : 'string');
			$ext->settings()->updateOrCreate(
				['key' => $key],
				['value' => $value, 'type' => $type, 'encrypted' => false]
			);
		}
	}

	private function ensureDefaultsIfMissing(): void
	{
		$ext = \App\Models\Extension::where('extension', 'PageBuilder')->first();
		if (!$ext) {
			return;
		}
		$configSetting = $ext->settings()->where('key', 'config')->first();
		if (!$configSetting || empty($configSetting->value)) {
			$this->seedDefaultSettings();
		}
	}

    private function runExtensionMigrations(): void
    {
        if (method_exists(ExtensionHelper::class, 'runMigrations')) {
            ExtensionHelper::runMigrations(__DIR__ . '/database/migrations');
            return;
        }
        Artisan::call('migrate', [
            '--path' => 'extensions/Others/PageBuilder/database/migrations',
            '--force' => true,
        ]);
    }

    private function rollbackExtensionMigrations(): void
    {
        if (method_exists(ExtensionHelper::class, 'rollbackMigrations')) {
            ExtensionHelper::rollbackMigrations(__DIR__ . '/database/migrations');
            return;
        }
        Artisan::call('migrate:rollback', [
            '--path' => 'extensions/Others/PageBuilder/database/migrations',
            '--force' => true,
        ]);
    }

    public function boot()
    {
        View::addNamespace('pagebuilder', __DIR__ . '/resources/views');
		$this->ensureDefaultsIfMissing();

		Blade::directive('pbMarkdown', function ($expression) {
			return "<?php echo \Paymenter\Extensions\Others\PageBuilder\PageBuilder::parseMarkdown($expression); ?>";
		});

		View::share('pbMarkdown', function($text) {
			return self::parseMarkdown($text);
		});

		if (app()->runningInConsole()) {
            Artisan::command('pagebuilder:list-pages', function () {
                try {
                    $pages = Page::all();
                    if ($pages->isEmpty()) {
                        $this->info('No pages found in database.');
                        return self::SUCCESS;
                    }

                    $this->info('PageBuilder Pages:');
                    $this->info('');

                    foreach ($pages as $page) {
                        $route = trim((string) ($page->route ?? '/'));
                        if ($route !== '/' && str_ends_with($route, '/')) {
                            $route = rtrim($route, '/');
                        }
                        if ($route === '') {
                            $route = '/';
                        }

                        $routeName = $route === '/' ? 'home' : 'pagebuilder.page.' . $page->id;
                        $registered = $route === '/' 
                            ? '✓ Registered (via Home component)' 
                            : (\Route::has($routeName) ? '✓ Registered' : '✗ NOT Registered');

                        $showInNav = $page->show_in_nav ? 'Yes' : 'No';
                        $showInDropdown = $page->show_in_dropdown ? 'Yes (in: ' . ($page->dropdown_name ?? 'N/A') . ')' : 'No';

                        $this->line(sprintf(
                            'ID: %d | Route: %s | Name: %s | %s',
                            $page->id,
                            $route,
                            $routeName,
                            $registered
                        ));
                        $this->line(sprintf(
                            '  Title: %s | Show in Nav: %s | Dropdown: %s',
                            $page->title ?? $page->meta_title ?? 'N/A',
                            $showInNav,
                            $showInDropdown
                        ));
                        $this->line('');
                    }

                    $this->info('Total pages: ' . $pages->count());

                    return self::SUCCESS;
                } catch (\Exception $e) {
                    $this->error('Error: ' . $e->getMessage());
                    return self::FAILURE;
                }
            })->purpose('List all PageBuilder pages and their route registration status');

            Artisan::command('pagebuilder:check-routes', function () {
                $this->info('Checking PageBuilder route registration...');
                $this->info('');

                try {
                    $pages = Page::all();
                    $this->info('Found ' . $pages->count() . ' pages in database');
                    $this->info('');

                    $allRoutes = collect(\Route::getRoutes())->map(function($route) {
                        return $route->getName();
                    })->filter()->toArray();

                    $pbRoutes = array_filter($allRoutes, function($name) {
                        return str_starts_with($name, 'pagebuilder.page.');
                    });

                    $this->info('Registered PageBuilder routes in Laravel: ' . count($pbRoutes));
                    foreach ($pbRoutes as $route) {
                        $this->line('  - ' . $route);
                    }

                    $this->info('');
                    $this->info('Expected routes based on database:');
                    foreach ($pages as $page) {
                        if ($page->route === '/') {
                            $this->line('  - home (homepage)');
                        } else {
                            $expectedName = 'pagebuilder.page.' . $page->id;
                            $exists = \Route::has($expectedName) ? '✓' : '✗';
                            $this->line(sprintf('  %s %s (route: %s)', $exists, $expectedName, $page->route));
                        }
                    }

                    return self::SUCCESS;
                } catch (\Exception $e) {
                    $this->error('Error: ' . $e->getMessage());
                    $this->error($e->getTraceAsString());
                    return self::FAILURE;
                }
            })->purpose('Check PageBuilder route registration in Laravel router');

            Artisan::command('pagebuilder:reset-settings', function () {
                $ext = \App\Models\Extension::where('extension', 'PageBuilder')->first();
                if (!$ext) {
                    $this->error('PageBuilder extension is not installed.');
                    return self::FAILURE;
                }

                $current = optional($ext->settings()->where('key', 'config')->first())->value ?? [];
                if (is_string($current)) {
                    $decoded = json_decode($current, true);
                    $current = is_array($decoded) ? $decoded : [];
                }

                $defaults = [
                    'container_size' => 1200,
                    'section_spacing' => 60,
                    'section_transition' => 'none',
                    'card_radius' => 10,
                    'card_shadow' => 'none',
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

                $new = (array) $current;
                foreach ($defaults as $k => $v) {
                    $new[$k] = $v;
                }

                $ext->settings()->updateOrCreate(
                    ['key' => 'config'],
                    ['value' => $new, 'type' => 'array', 'encrypted' => false]
                );

                foreach ($defaults as $key => $value) {
                    $type = is_int($value) ? 'integer' : 'string';
                    $ext->settings()->updateOrCreate(
                        ['key' => $key],
                        ['value' => $value, 'type' => $type, 'encrypted' => false]
                    );
                }

                $this->info('PageBuilder settings have been reset to defaults.');
                return self::SUCCESS;
            })->purpose('Reset PageBuilder extension settings to defaults');
        }

        View::composer('home', function ($view) {
            try {
                $homePage = Page::where('route', '/')->first();
                if (!$homePage) {
                    return;
                }
            } catch (\Exception $e) {
                return;
            }

            $sections = $this->processSections($homePage->toArray());
            $colorSettings = $this->getColorSettings();
            $cssVariables = $this->generateCssVariables($colorSettings);
            
            $view->with([
                'sections' => $sections,
                'colors' => $colorSettings,
                'hpbCssVariables' => $cssVariables,
            ]);
        });

        Event::listen('head', function () {
            $colors = $this->getColorSettings();
            $cssVariables = $this->generateCssVariables($colors);
            $head = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">';
            if (config('settings.logo')) {
                $head .= '<link rel="icon" href="' . e(url(\Storage::url(config('settings.logo')))) . '" type="image/png">';
            }

            try {
                $pagesCollection = Page::all();
            } catch (\Exception $e) {
                $head .= '<style>' . $cssVariables . ' section{padding-top: var(--hpb-section-spacing)!important;padding-bottom: var(--hpb-section-spacing)!important;} .container, .max-w-\\[1320px\\], .max-w-\\[1200px\\], .max-w-\\[1280px\\]{max-width: var(--hpb-container-size)!important;}</style>';
                return ['view' => new HtmlString($head)];
            }

            $path = '/' . ltrim(request()->path(), '/');
            foreach ($pagesCollection as $pageModel) {
                $p = $pageModel->toArray();
                $route = trim((string) ($p['route'] ?? '/'));
                if ($route === '') {
                    $route = '/';
                }
                if ($route !== '/' && str_ends_with($route, '/')) {
                    $route = rtrim($route, '/');
                }
                if ($route !== '/' && str_ends_with($route, '/')) {
                    $route = rtrim($route, '/');
                }
                if ($path !== '/' && str_ends_with($path, '/')) {
                    $path = rtrim($path, '/');
                }
                if ($path === $route) {
                    $title = $p['meta_title'] ?? null;
                    $desc = $p['meta_description'] ?? null;
                    $keywords = $p['meta_keywords'] ?? null;
                    $ogTitle = $p['meta_og_title'] ?? $title;
                    $ogDesc = $p['meta_og_description'] ?? $desc;
                    $ogImage = $p['meta_og_image'] ?? null;
                    $twTitle = $p['meta_twitter_title'] ?? $ogTitle;
                    $twDesc = $p['meta_twitter_description'] ?? $ogDesc;
                    $twImage = $p['meta_twitter_image'] ?? $ogImage;
                    $twCard = $p['meta_twitter_card'] ?? 'summary_large_image';
                    $canonical = $p['canonical_url'] ?? null;
                    if ($title) {
                        $head .= '<title>' . e($title) . '</title>';
                    }
                    if ($desc) {
                        $head .= '<meta name="description" content="' . e($desc) . '">';
                    }
                    if ($keywords) {
                        $head .= '<meta name="keywords" content="' . e($keywords) . '">';
                    }
                    if ($canonical) {
                        $head .= '<link rel="canonical" href="' . e($canonical) . '">';
                    }
                    if ($ogTitle) {
                        $head .= '<meta property="og:title" content="' . e($ogTitle) . '">';
                    }
                    if ($ogDesc) {
                        $head .= '<meta property="og:description" content="' . e($ogDesc) . '">';
                    }
                    $currentUrl = request()->fullUrl();
                    $head .= '<meta property="og:url" content="' . e($canonical ?: $currentUrl) . '">';
                    $head .= '<meta property="og:type" content="website">';
					if ($ogImage) {
						$head .= '<meta property="og:image" content="' . e(url(\Storage::url($ogImage))) . '">';
					}
                    if ($twTitle) {
                        $head .= '<meta name="twitter:title" content="' . e($twTitle) . '">';
                    }
                    if ($twDesc) {
                        $head .= '<meta name="twitter:description" content="' . e($twDesc) . '">';
                    }
                    $head .= '<meta name="twitter:card" content="' . e($twCard) . '">';
					if ($twImage) {
						$head .= '<meta name="twitter:image" content="' . e(url(\Storage::url($twImage))) . '">';
					}
                    break;
                }
            }

            $head .= '<style>' . $cssVariables . ' section{padding-top: var(--hpb-section-spacing)!important;padding-bottom: var(--hpb-section-spacing)!important;} .container, .max-w-\\[1320px\\], .max-w-\\[1200px\\], .max-w-\\[1280px\\]{max-width: var(--hpb-container-size)!important;}</style>';
            return ['view' => new HtmlString($head)];
        });

        $this->registerDynamicPages();

        try {
            $pagesCollection = Page::all();
        } catch (\Exception $e) {
            return;
        }

        $priority = 60;
        $singles = [];
        $groups = [];
        foreach ($pagesCollection as $pageModel) {
            $p = $pageModel->toArray();
            $route = trim((string) ($p['route'] ?? '/'));
            if ($route === '') { $route = '/'; }
            if ($route !== '/' && str_ends_with($route, '/')) { $route = rtrim($route, '/'); }
            $name = (string) ($p['title'] ?? $p['meta_title'] ?? $route);
            $pageId = $p['id'] ?? null;
            if (!$pageId) { continue; }
            $routeName = $route === '/' ? 'home' : 'pagebuilder.page.' . $pageId;
            $itemPriority = is_numeric($p['nav_priority'] ?? null) ? (int) $p['nav_priority'] : $priority;

            if (!empty($p['show_in_dropdown']) && !empty($p['dropdown_name'])) {
                $groups[$p['dropdown_name']] = $groups[$p['dropdown_name']] ?? ['name' => $p['dropdown_name'], 'children' => [], 'priority' => $itemPriority, 'separator' => false];
                $groups[$p['dropdown_name']]['children'][] = ['name' => $name, 'route' => $routeName, 'priority' => $itemPriority];
            } elseif (!empty($p['show_in_nav'])) {
                $singles[] = ['name' => $name, 'route' => $routeName, 'priority' => $itemPriority];
            }
            $priority += 1;
        }

        usort($singles, function ($a, $b) { return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0); });
        foreach ($groups as &$g) {
            usort($g['children'], function ($a, $b) { return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0); });
        }
        unset($g);

        foreach ($singles as $item) {
            Event::listen('navigation', function () use ($item) {
                if (!\Route::has($item['route'])) { return; }
                return $item;
            });
        }
        foreach ($groups as $group) {
            Event::listen('navigation', function () use ($group) {
                $children = array_values(array_filter($group['children'], function ($c) { return \Route::has($c['route']); }));
                if (empty($children)) { return; }
                return ['name' => $group['name'], 'children' => $children, 'separator' => true, 'spa' => true, 'priority' => $group['priority']];
            });
        }
    }

    private function registerDynamicPages(): void
    {
        try {
            $pages = Page::all();
        } catch (\Exception $e) {
            return;
        }

        foreach ($pages as $pageModel) {
            $page = $pageModel->toArray();
			$route = trim((string) ($page['route'] ?? '/'));
			if ($route !== '/' && str_ends_with($route, '/')) {
				$route = rtrim($route, '/');
			}
            if ($route === '') {
                $route = '/';
            }
			$pageId = $page['id'] ?? null;
            if (!$pageId) {
                continue;
            }
            
            if ($route === '/') {
                continue;
            }
            
            $routeName = 'pagebuilder.page.' . $pageId;
            
            $self = $this;
            \Route::get($route, function () use ($pageId, $self) {
                $pageModel = Page::find($pageId);
                if (!$pageModel) {
                    abort(404);
                }
                $page = $pageModel->toArray();
                $sections = $self->processSections($page);
                $colorSettings = $self->getColorSettings();
                $cssVariables = $self->generateCssVariables($colorSettings);
                $inner = view('pagebuilder::page-sections', [
                    'sections' => $sections,
                    'colors' => $colorSettings,
                    'hpbCssVariables' => $cssVariables,
                ]);
                return view('layouts.app', [
                    'title' => $page['meta_title'] ?? null,
                    'description' => $page['meta_description'] ?? null,
                    'slot' => new HtmlString('<style>' . $cssVariables . ' section{padding-top: var(--hpb-section-spacing)!important;padding-bottom: var(--hpb-section-spacing)!important;} .container, .max-w-\\[1320px\\], .max-w-\\[1200px\\], .max-w-\\[1280px\\]{max-width: var(--hpb-container-size)!important;}</style>' . $inner->render()),
                ]);
            })->middleware(['web'])->name($routeName);
        }
    }

    private function getConfigArray(): array
    {
        $config = $this->config('config');
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        } elseif (is_array($config)) {
            return $config;
        }

        return [];
    }

    private function getColorFromConfigOrLegacy(string $key)
    {
        $direct = $this->config($key);
        if ($direct !== null && $direct !== '') {
            return $direct;
        }
        $config = $this->getConfigArray();
        if (array_key_exists($key, $config)) {
            return $config[$key];
        }
        return null;
    }

    private function getColorSettings(): array
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

        $colors = [];
        $oldSettings = [];
        $oldExt = \App\Models\Extension::where('extension', 'PageBuilder')->first();
        if ($oldExt) {
            $oldSettings = $oldExt->settings->pluck('value', 'key')->toArray();
            if (isset($oldSettings['config']) && is_array($oldSettings['config'])) {
                foreach ($colorKeys as $k) {
                    if (!isset($oldSettings[$k]) && isset($oldSettings['config'][$k])) {
                        $oldSettings[$k] = $oldSettings['config'][$k];
                    }
                }
            }
        }
        foreach ($colorKeys as $colorKey) {
            $value = $this->getColorFromConfigOrLegacy($colorKey);
            if ($value !== null && $value !== '') {
                $colors[$colorKey] = $value;
            }
        }

        foreach ($colorKeys as $colorKey) {
            if (!isset($colors[$colorKey]) && isset($oldSettings[$colorKey])) {
                $colors[$colorKey] = $oldSettings[$colorKey];
            }
        }

        $cardRadius = $this->getColorFromConfigOrLegacy('card_radius');
        if ($cardRadius === null || $cardRadius === '') {
            $cardRadius = $oldSettings['card_radius'] ?? 10;
        }
        $colors['card_radius'] = $cardRadius;

        $cardShadow = $this->getColorFromConfigOrLegacy('card_shadow');
        if ($cardShadow === null || $cardShadow === '') {
            $cardShadow = $oldSettings['card_shadow'] ?? 'none';
        }
        $colors['card_shadow'] = $cardShadow;

        $sectionSpacing = $this->getColorFromConfigOrLegacy('section_spacing');
        if ($sectionSpacing === null || $sectionSpacing === '') {
            $sectionSpacing = $oldSettings['section_spacing'] ?? 60;
        }
        $colors['section_spacing'] = $sectionSpacing;

        $containerSize = $this->getColorFromConfigOrLegacy('container_size');
        if ($containerSize === null || $containerSize === '') {
            $containerSize = $oldSettings['container_size'] ?? 1200;
        }
        $colors['container_size'] = $containerSize;

        $transitionFromCurrent = $this->getColorFromConfigOrLegacy('section_transition');
        $transitionFromCurrent = is_string($transitionFromCurrent) ? trim($transitionFromCurrent) : $transitionFromCurrent;
        $transition = $transitionFromCurrent !== null && $transitionFromCurrent !== ''
            ? $transitionFromCurrent
            : ($oldSettings['section_transition'] ?? null);
        if ($transition === null || $transition === '') {
            $transition = 'none';
        }
        $colors['section_transition'] = $transition;
        $normalized = strtolower(trim((string) $transition));
        $normalized = preg_replace('/[^a-z]+/', '-', $normalized);
        $normalized = trim($normalized, '-');
        if (!in_array($normalized, ['none', 'fade-in', 'fade-right', 'fade-left', 'fade-top', 'fade-bottom'])) {
            $normalized = 'none';
        }
        $colors['section_transition_class'] = 'hpb-transition-' . $normalized;

        return $colors;
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

        $transition = trim((string) ($data['section_transition'] ?? 'none'));
        $animName = 'none';
        $opacity = '1';
        $tx = '0';
        $ty = '0';
        $duration = '0ms';
        $willChange = 'auto';
        switch ($transition) {
            case 'fade-in':
                $animName = 'hpb-fade-in';
                $opacity = '0';
                $duration = '700ms';
                $willChange = 'opacity';
                break;
            case 'fade-right':
                $animName = 'hpb-slide-right';
                $opacity = '0';
                $tx = '16px';
                $duration = '700ms';
                $willChange = 'opacity, transform';
                break;
            case 'fade-left':
                $animName = 'hpb-slide-left';
                $opacity = '0';
                $tx = '-16px';
                $duration = '700ms';
                $willChange = 'opacity, transform';
                break;
            case 'fade-top':
                $animName = 'hpb-slide-top';
                $opacity = '0';
                $ty = '-16px';
                $duration = '700ms';
                $willChange = 'opacity, transform';
                break;
            case 'fade-bottom':
                $animName = 'hpb-slide-bottom';
                $opacity = '0';
                $ty = '16px';
                $duration = '700ms';
                $willChange = 'opacity, transform';
                break;
        }

        $rootVars[] = "--hpb-transition-opacity: {$opacity};";
        $rootVars[] = "--hpb-transition-translate-x: {$tx};";
        $rootVars[] = "--hpb-transition-translate-y: {$ty};";
        $rootVars[] = "--hpb-transition-animation-name: {$animName};";
        $rootVars[] = "--hpb-transition-animation-duration: {$duration};";
        $rootVars[] = "--hpb-transition-will-change: {$willChange};";

        $root = ':root { ' . implode(' ', $rootVars) . ' }';
        $dark = '.dark { ' . implode(' ', $darkVars) . ' }';

        return $root . ' ' . $dark;
    }

    private function processSections(array $page): array
    {
        $sections = [];
        $sectionsData = $page['sections'] ?? [];
        if (is_string($sectionsData)) {
            $sectionsData = json_decode($sectionsData, true) ?: [];
        }
        
        if (!is_array($sectionsData)) {
            return [];
        }

        foreach ($sectionsData as $item) {
            if (!is_array($item) || !isset($item['type'])) {
                continue;
            }
            
            $type = $item['type'];
            $variation = $item['variation'] ?? null;
            $rawContent = $item['content'] ?? [];
            $contentForView = $rawContent;

            $dataKeys = [
                'hero' => 'hero_data',
                'features' => 'features_data',
                'reviews' => 'reviews_data',
                'pricing' => 'pricing_data',
                'faq' => 'faq_data',
                'cta' => 'cta_data',
                'games' => 'games_data',
                'panel' => 'panel_data',
                'blog' => 'blog_data',
                'partners' => 'partners_data',
                'location_map' => 'location_map_data',
                'text' => 'text_data',
                'custom_html' => 'custom_html_data',
            ];

            if (isset($dataKeys[$type]) && isset($rawContent[$dataKeys[$type]]) && is_array($rawContent[$dataKeys[$type]])) {
                $contentForView = array_merge($contentForView, $rawContent[$dataKeys[$type]]);
                
                $arrayFields = ['reviews' => 'reviews', 'games' => 'cards', 'panel' => 'widgets', 'blog' => 'posts', 'partners' => 'partners'];
                if (isset($arrayFields[$type])) {
                    $field = $arrayFields[$type];
                    if (isset($rawContent[$dataKeys[$type]][$field]) && is_array($rawContent[$dataKeys[$type]][$field])) {
                        $contentForView[$field] = $rawContent[$dataKeys[$type]][$field];
                    } elseif (!isset($contentForView[$field])) {
                        $contentForView[$field] = [];
                    }
                }
            }

            $sectionCategory = null;
            $sectionProducts = collect();
            $slug = $rawContent['products_data']['category_slug'] ?? null;
            if ($slug) {
                $sectionCategory = Category::where('slug', $slug)->first();
                if ($sectionCategory) {
                    $sectionProducts = $sectionCategory->products()->where('hidden', false)->orderBy('sort')->get();
                }
            }
            
            $sections[] = [
                'type' => $variation ? ($type . '_' . $variation) : $type,
                'data' => [
                    'category' => $sectionCategory,
                    'products' => $sectionProducts,
                    'content' => $contentForView
                ]
            ];
        }

        return $sections;
    }

	public static function parseMarkdown($text): string
	{
		if (empty($text)) {
			return '';
		}

		$text = trim((string) $text);
		
		$text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
		$text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
		$text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
		$text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
		
		$text = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function($matches) {
			$linkText = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
			$url = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
			return '<a href="' . $url . '">' . $linkText . '</a>';
		}, $text);
		
		$text = preg_replace('/^#{6}\s+(.+)$/m', '<h6>$1</h6>', $text);
		$text = preg_replace('/^#{5}\s+(.+)$/m', '<h5>$1</h5>', $text);
		$text = preg_replace('/^#{4}\s+(.+)$/m', '<h4>$1</h4>', $text);
		$text = preg_replace('/^#{3}\s+(.+)$/m', '<h3>$1</h3>', $text);
		$text = preg_replace('/^#{2}\s+(.+)$/m', '<h2>$1</h2>', $text);
		$text = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $text);
		
		$text = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $text);
		
		$text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
		
		$text = preg_replace('/^-{3,}$/m', '<hr>', $text);
		$text = preg_replace('/^\*{3,}$/m', '<hr>', $text);
		
		$text = preg_replace_callback('/^(\s*[-*+]\s+.+)$/m', function($matches) {
			static $inList = false;
			$line = $matches[1];
			$item = preg_replace('/^\s*[-*+]\s+/', '', $line);
			
			if (!$inList) {
				$inList = true;
				return '<ul><li>' . $item . '</li>';
			}
			return '<li>' . $item . '</li>';
		}, $text);
		
		if (substr_count($text, '<ul>') > substr_count($text, '</ul>')) {
			$text .= '</ul>';
		}
		
		$text = preg_replace_callback('/^(\s*\d+\.\s+.+)$/m', function($matches) {
			static $inList = false;
			$line = $matches[1];
			$item = preg_replace('/^\s*\d+\.\s+/', '', $line);
			
			if (!$inList) {
				$inList = true;
				return '<ol><li>' . $item . '</li>';
			}
			return '<li>' . $item . '</li>';
		}, $text);
		
		if (substr_count($text, '<ol>') > substr_count($text, '</ol>')) {
			$text .= '</ol>';
		}
		
		$lines = explode("\n", $text);
		$result = [];
		$inParagraph = false;
		
		foreach ($lines as $line) {
			$trimmed = trim($line);
			
			if (empty($trimmed)) {
				if ($inParagraph) {
					$result[] = '</p>';
					$inParagraph = false;
				}
				continue;
			}
			
			if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|hr|div|section|article|aside|header|footer|nav|main)/', $trimmed)) {
				if ($inParagraph) {
					$result[] = '</p>';
					$inParagraph = false;
				}
				$result[] = $line;
			} else {
				if (!$inParagraph) {
					$result[] = '<p>' . $line;
					$inParagraph = true;
				} else {
					$result[] = $line;
				}
			}
		}
		
		if ($inParagraph) {
			$result[] = '</p>';
		}
		
		return implode("\n", $result);
	}

}



