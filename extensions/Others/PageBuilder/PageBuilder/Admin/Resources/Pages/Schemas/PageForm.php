<?php

namespace Paymenter\Extensions\Others\PageBuilder\Admin\Resources\Pages\Schemas;

use Filament\Forms\Components\Toggle;
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


class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $sectionOptions = [
            'hero' => [
                'label' => 'Hero',
                'variations' => [
                    '1' => 'Centered headline + buttons',
                    '2' => 'Centered headline + badges',
                    '3' => 'Two-column with bullets',
                    '4' => 'Centered minimal badges row',
                    '5' => 'Headline + feature cards',
                    '6' => 'Two-column with widgets',
                    '7' => 'Two-column image right',
                    '8' => 'Two-column with bullets (no image)',
                    '9' => 'Split with big stat left',
                    '10' => 'Headline + stat blocks row',
                    '12' => 'Full-width text band',
                    '13' => 'Two-row hero (CTA band)',
                ]
            ],
            'cta' => [
                'label' => 'CTA Section',
                'variations' => [
                    '1' => 'Centered, single button',
                    '2' => 'Centered, two buttons',
                    '3' => 'Inline right button',
                    '4' => 'Card container',
                    '5' => 'Image left, text right',
                    '6' => 'Buttons vertical',
                    '7' => 'Minimal band with link CTA',
                    '8' => 'Striped with icon and button',
                    '9' => 'Centered text only',
                    '10' => 'Left-aligned with buttons',
                    '11' => 'Full-width primary banner',
                    '12' => 'Compact bar with link button',
                    '13' => 'Split with small image right',
                ],
            ],
            'features' => [
                'label' => 'Features',
                'variations' => [
                    '1' => 'Icon cards (3 columns)',
                    '2' => 'Two-column staggered features',
                    '3' => 'Core + sub features grid',
                    '4' => 'Enterprise features + metrics',
                    '5' => 'Hero with feature cards',
                    '6' => 'Minimal text columns',
                    '7' => 'Icon row above text',
                    '8' => 'Alternating row layout',
                    '9' => 'Full-width striped rows',
                    '10' => 'Metric style blocks',
                    '11' => 'Cardless with dividers',
                    '12' => 'Alternating image/text parts',
                ]
            ],
            'products' => [
                'label' => 'Products',
                'variations' => [
                    '1' => 'Grid cards (3 columns)',
                    '2' => 'Split featured list (2 columns)',
                    '3' => 'Wide image cards (2 columns)',
                    '4' => 'Compact tiles (4 up)',
                    '5' => 'Dense product grid (3-4 columns)',
                ]
            ],
            'reviews' => [
                'label' => 'Reviews',
                'variations' => [
                    '1' => 'Grid testimonials (3 cards)',
                    '2' => 'Two-column testimonials',
                    '3' => 'Grid testimonials with aside',
                    '4' => 'Alternating list with asides',
                    '5' => 'Metrics + testimonials',
                ]
            ],
            'pricing' => [
                'label' => 'Pricing',
                'variations' => [
                    '1' => 'Plan cards with logo',
                    '2' => 'Featured plan highlight',
                    '3' => 'Simple pricing cards',
                    '4' => 'Horizontal comparison table',
                    '5' => 'Stacked timeline layout',
                    '7' => 'Accordion style',
                    '8' => '4 columns cards',
                    '9' => 'Minimal three-up cards',
                    '10' => 'Vertical list with accent bar',
                    '11' => 'Circle price badges grid',
                    '12' => 'Striped comparison list',
                    '13' => 'Ladder style with badges',
                ]
            ],
            'faq' => [
                'label' => 'FAQ',
                'variations' => [
                    '1' => 'Single column accordion',
                    '2' => 'Two-column Q&A cards',
                    '3' => 'Categorised groups (two-column split)',
                    '4' => 'Categorised groups (3 columns)',
                    '5' => 'Two-column accordions',
                    '6' => 'Categorised groups (compact cards)',
                ]
            ],
            'blog' => [
                'label' => 'Blog',
                'variations' => [
                    '1' => 'Grid cards (3 columns)',
                    '2' => 'Featured article + grid',
                    '3' => 'Overlay image cards',
                ]
            ],
            'partners' => [
                'label' => 'Partners',
                'variations' => [
                    '1' => 'Logo row (optional grayscale)',
                    '2' => '2-column partner widgets (logo + name)',
                    '3' => '3-column partner cards (logo + name + description)',
                    '4' => '3-column partner list (no card)',
                ]
            ],
            'location_map' => [
                'label' => 'Location Map',
                'variations' => [
                    '1' => 'Full-width image (contain)',
                ]
            ],
            'text' => [
                'label' => 'Text',
                'variations' => [
                    '1' => 'Left-aligned with CTA',
                    '2' => 'Centered text with CTA',
                    '3' => 'Two-column text',
                ]
            ],
            'games' => [
                'label' => 'Games',
                'variations' => [
                    '1' => 'Overlay image cards',
                    '2' => 'Bordered cards grid',
                    '3' => 'Grid cards with hover overlay',
                    '4' => 'Featured wide card + stacked list',
                    '5' => 'Stacked featured wide cards',
                ]
            ],
            'panel' => [
                'label' => 'Panel preview',
                'variations' => [
                    '1' => 'Image only preview',
                    '2' => 'Image with 3 metrics widgets',
                    '3' => 'Left stacked cards, right image',
                    '4' => 'Interactive cards with image switching',
                ]
            ],
            'custom_html' => [
                'label' => 'Custom HTML/CSS',
                'variations' => [
                    '1' => 'Default',
                ]
            ],
        ];

        return $schema
            ->components([
                
                TextInput::make('title')->label('Page title')->live(onBlur: true)->partiallyRenderAfterStateUpdated()->hidden(),
                TextInput::make('route')->label('Route')->placeholder('/')->default('/')
                    ->helperText('Start with / e.g. /, /about-us')
                    ->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                     TextInput::make('nav_priority')
                    ->label('Order')
                    ->numeric()
                    ->placeholder('60')
                    ->helperText('Higher numbers appear first')
                    ->live(onBlur: true)
                    ->required()
                    ->partiallyRenderAfterStateUpdated(),
                Toggle::make('show_in_nav')
                    ->label('Show in navigation bar')
                    ->default(false)
                    ->live(onBlur: true)
                    ->partiallyRenderAfterStateUpdated(),
                Toggle::make('show_in_dropdown')
                    ->label('Show in dropdown')
                    ->default(false)
                    ->live(onBlur: true)
                    ->partiallyRenderAfterStateUpdated(),
                TextInput::make('dropdown_name')
                    ->label('Dropdown name')
                    ->placeholder('e.g. Company')
                    ->live(onBlur: true)
                    ->partiallyRenderAfterStateUpdated(),
                Fieldset::make('SEO')
                    ->schema([
                        TextInput::make('title')
                            ->label('Page title')
                            ->live(onBlur: true),
                        TextInput::make('meta_title')
                            ->label('Meta title')
                            ->live(onBlur: true),
                        Textarea::make('meta_description')
                            ->label('Meta description')
                            ->rows(2)
                            ->live(onBlur: true),
                        TextInput::make('meta_keywords')
                            ->label('Keywords')
                            ->placeholder('hosting, vps, servers')
                            ->live(onBlur: true),
                        TextInput::make('meta_og_title')
                            ->label('Open Graph title')
                            ->live(onBlur: true),
                        Textarea::make('meta_og_description')
                            ->label('Open Graph description')
                            ->rows(2)
                            ->live(onBlur: true),
                        FileUpload::make('meta_og_image')
                            ->label('Open Graph image')
                            ->disk('public')
                            ->directory('pagebuilder')
                            ->image()
                            ->imageEditor()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('630')
                            ->columnSpanFull(),

                        TextInput::make('meta_twitter_title')
                            ->label('Twitter title')
                            ->live(onBlur: true),
                        Textarea::make('meta_twitter_description')
                            ->label('Twitter description')
                            ->rows(2)
                            ->live(onBlur: true),
                        FileUpload::make('meta_twitter_image')
                            ->label('Twitter image')
                            ->disk('public')
                            ->directory('pagebuilder')
                            ->image()
                            ->imageEditor()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('630')
                            ->columnSpanFull(),
                        Select::make('meta_twitter_card')
                            ->label('Twitter card')
                            ->options([
                                'summary' => 'summary',
                                'summary_large_image' => 'summary_large_image',
                            ])
                            ->default('summary_large_image')
                            ->native(false),
                        TextInput::make('canonical_url')
                            ->label('Canonical URL')
                            ->placeholder('https://example.com/about-us')
                            ->live(onBlur: true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Fieldset::make('Sections')
                    ->schema([
                        Repeater::make('sections')
                            ->collapsible()
                            ->collapsed()
                            ->label('Sections')
                            ->schema([
                                Hidden::make('content')
                                    ->default([
                                        'hero_data' => [],
                                        'cta_data' => ['buttons' => []],
                                        'features_data' => [],
                                        'products_data' => [],
                                        'reviews_data' => ['reviews' => []],
                                        'pricing_data' => ['items' => [], 'illustration' => null],
                                        'faq_data' => ['items' => [], 'groups' => []],
                                    ]),
                                Select::make('type')
                                    ->label('Section Type')
                                    ->options([
                                        'hero' => 'Hero',
                                        'cta' => 'CTA Section',
                                        'features' => 'Features',
                                        'products' => 'Products',
                                        'reviews' => 'Reviews',
                                        'pricing' => 'Pricing',
                                        'faq' => 'FAQ',
                                        'blog' => 'Blog',
                                        'partners' => 'Partners',
                                        'location_map' => 'Location Map',
                                        'text' => 'Text',
                                        'games' => 'Games',
                                        'panel' => 'Panel preview',
                                        'custom_html' => 'Custom HTML/CSS',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('hero')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($set, $get, $state) {
                                        $defaults = [
                                            'hero' => [
                                                'hero_data' => [],
                                            ],
                                            'cta' => [
                                                'cta_data' => [
                                                    'title' => null,
                                                    'subtitle' => null,
                                                    'note' => null,
                                                    'buttons' => [],
                                                    'image' => null,
                                                    'image_alt' => null
                                                ],
                                            ],
                                            'features' => [
                                                'features_data' => [],
                                            ],
                                            'reviews' => [
                                                'reviews_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'reviews' => [],
                                                ],
                                            ],
                                            'pricing' => [
                                                'pricing_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'items' => [],
                                                    'illustration' => null,
                                                ],
                                            ],
                                            'products' => [
                                                'products_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                ],
                                            ],
                                            'faq' => [
                                                'faq_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'items' => [],
                                                    'groups' => [],
                                                ],
                                            ],
                                            'blog' => [
                                                'blog_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'posts' => [],
                                                ],
                                            ],
                                            'partners' => [
                                                'partners_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'partners' => [],
                                                    'black_white_mode' => false,
                                                ],
                                            ],
                                            'location_map' => [
                                                'location_map_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'image' => null,
                                                    'image_alt' => null,
                                                ],
                                            ],
                                            'text' => [
                                                'text_data' => [
                                                    'title' => null,
                                                    'text' => null,
                                                    'cta_label' => null,
                                                    'cta_link' => null,
                                                    'left_title' => null,
                                                    'left_text' => null,
                                                    'right_title' => null,
                                                    'right_text' => null,
                                                ],
                                            ],
                                            'games' => [
                                                'games_data' => [
                                                    'section_title' => null,
                                                    'section_description' => null,
                                                    'cards' => [],
                                                ],
                                            ],
                                            'panel' => [
                                                'panel_data' => [
                                                    'image' => null,
                                                    'widgets' => [],
                                                ],
                                            ],
                                            'custom_html' => [
                                                'custom_html_data' => [
                                                    'html' => null,
                                                    'css' => null,
                                                ],
                                            ],
                                        ];
                                        $set('content', $defaults[$state] ?? []);
                                        $set('variation', '1');
                                    }),
                                Select::make('variation')
                                    ->label('Variation')
                                    ->options(function ($get) use ($sectionOptions) {
                                        $type = $get('type');
                                        if (!$type || !isset($sectionOptions[$type])) {
                                            return [];
                                        }
                                        return $sectionOptions[$type]['variations'];
                                    })
                                    ->required()
                                    ->native(false)
                                    ->visible(fn($get) => $get('type'))
                                    ->default('1')
                                    ->live(),

                                Fieldset::make('Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.hero_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Title')
                                                    ->columnSpan(1),
                                                Textarea::make('subtitle')
                                                    ->label('Subtitle / Description')
                                                    ->rows(3)
                                                    ->columnSpan(1),
                                                TextInput::make('subtitle_strong')
                                                    ->label('Subtitle strong')
                                                    ->placeholder('Scale without limits.')
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['2', '3', '4', '6', '8']))
                                                    ->columnSpan(1),
                                                TextInput::make('primary_label')
                                                    ->label('Primary button label')
                                                    ->columnSpan(1),
                                                TextInput::make('primary_link')
                                                    ->label('Primary button link')
                                                    ->columnSpan(1),
                                                TextInput::make('secondary_label')
                                                    ->label('Secondary button label')
                                                    ->columnSpan(1),
                                                TextInput::make('secondary_link')
                                                    ->label('Secondary button link')
                                                    ->columnSpan(1),

                                                FileUpload::make('image')
                                                    ->label('Hero image')
                                                    ->disk('public')
                                                    ->directory('pagebuilder')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1920')
                                                    ->imageResizeTargetHeight('1080')
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['3', '7']))
                                                    ->columnSpan(1),
                                                TextInput::make('image_alt')
                                                    ->label('Hero image alt')
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['3', '7']))
                                                    ->columnSpan(1),

                                                Repeater::make('badges')
                                                    ->label('Badges / Highlights')
                                                    ->schema([
                                                        TextInput::make('text')->label('Text')->required(),
                                                    ])
                                                    ->default([
                                                        ['text' => '99.9% Uptime'],
                                                        ['text' => '24/7 Support'],
                                                        ['text' => 'Global Network'],
                                                        ['text' => 'DDoS Protection'],
                                                    ])
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['2', '4']))
                                                    ->columnSpanFull(),

                                                Repeater::make('bullets')
                                                    ->label('Bullet points')
                                                    ->schema([
                                                        TextInput::make('text')->label('Text')->required(),
                                                    ])
                                                    ->default([
                                                        ['text' => 'NVMe SSD storage for lightning-fast I/O'],
                                                        ['text' => 'Global data centers with low-latency connections'],
                                                        ['text' => 'Enterprise-grade security and DDoS protection'],
                                                    ])
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['3', '8']))
                                                    ->columnSpanFull(),

                                            Fieldset::make('Big Stat')
                                                ->schema([
                                                    TextInput::make('stat_value')->label('Stat value')->placeholder('99.99%'),
                                                    TextInput::make('stat_caption')->label('Stat caption')->placeholder('Uptime'),
                                                ])
                                                ->columns(2)
                                                ->visible(fn($get) => $get('../../variation') === '9')
                                                ->columnSpanFull(),

                                            Fieldset::make('Stat Blocks Row')
                                                ->schema([
                                                    Repeater::make('stats')
                                                        ->label('Stat blocks')
                                                        ->schema([
                                                            TextInput::make('value')->label('Value')->required(),
                                                            TextInput::make('label')->label('Label')->required(),
                                                            Textarea::make('description')->label('Description')->rows(2),
                                                        ])
                                                        ->maxItems(4)
                                                        ->default([])
                                                        ->columnSpanFull(),
                                                ])
                                                ->visible(fn($get) => $get('../../variation') === '10')
                                                ->columnSpanFull(),

                                            

                                                Repeater::make('feature_cards')
                                                    ->label('Feature cards')
                                                    ->schema([
                                                        TextInput::make('icon')->label('Font Awesome icon class')->placeholder('fa-bolt')->required(),
                                                        TextInput::make('title')->label('Title')->required(),
                                                        Textarea::make('description')->label('Description')->rows(2)->required(),
                                                    ])
                                                    ->default([
                                                        ['icon' => 'fa-bolt', 'title' => 'Instant Deployment', 'description' => 'Deploy VPS instances in under 60 seconds with our automated provisioning system.'],
                                                        ['icon' => 'fa-shield-halved', 'title' => 'Enterprise Security', 'description' => 'Bank-level security with advanced DDoS protection, firewalls, and 24/7 monitoring.'],
                                                        ['icon' => 'fa-globe', 'title' => 'Global Infrastructure', 'description' => 'Data centers worldwide with low-latency connections and automatic failover.'],
                                                    ])
                                                    ->visible(fn($get) => $get('../../variation') === '5')
                                                    ->columnSpanFull(),

                                                TextInput::make('footnote_text')
                                                    ->label('Footnote text')
                                                    ->placeholder('No credit card required')
                                                    ->default('No credit card required')
                                                    ->visible(fn($get) => $get('../../variation') === '5')
                                                    ->columnSpanFull(),

                                                TextInput::make('panel_title')
                                                    ->label('Panel title')
                                                    ->placeholder('Live Performance')
                                                    ->default('Live Performance')
                                                    ->visible(fn($get) => (string) $get('../../../variation') === '6')
                                                    ->columnSpan(1),
                                                TextInput::make('panel_realtime_label')
                                                    ->label('Panel realtime label')
                                                    ->placeholder('Real-time')
                                                    ->default('Real-time')
                                                    ->visible(fn($get) => (string) $get('../../../variation') === '6')
                                                    ->columnSpan(1),
                                                Repeater::make('panel_metrics')
                                                    ->label('Panel metrics')
                                                    ->schema([
                                                        TextInput::make('value')->label('Value')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('label')->label('Label')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->default([
                                                        ['value' => '99.99%', 'label' => 'Uptime'],
                                                        ['value' => '142ms', 'label' => 'Response Time'],
                                                        ['value' => '15.2k', 'label' => 'Active Servers'],
                                                        ['value' => '99.1%', 'label' => 'Success Rate'],
                                                    ])
                                                    ->visible(fn($get) => (string) $get('../../../variation') === '6')
                                                    ->columns(2)
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'hero'),

                                Fieldset::make('CTA Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.cta_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('subtitle')
                                                    ->label('Subtitle / Description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('note')
                                                    ->label('Small note (optional)')
                                                    ->rows(2)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpanFull(),
                                                Repeater::make('buttons')
                                                    ->label('Buttons')
                                                    ->schema([
                                                        TextInput::make('label')->label('Label')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('link')->label('Link')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Select::make('variant')
                                                            ->label('Variant')
                                                            ->options([
                                                                'primary' => 'Primary',
                                                                'secondary' => 'Secondary',
                                                                'outline' => 'Outline',
                                                                'link' => 'Link',
                                                            ])
                                                            ->default('primary')
                                                            ->native(false),
                                                    ])
                                                    ->default([])
                                                    ->collapsed()
                                                    ->collapsible()
                                                    ->columnSpanFull(),
                                                FileUpload::make('image')
                                                    ->label('Image (optional)')
                                                    ->disk('public')
                                                    ->directory('pagebuilder')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1200')
                                                    ->imageResizeTargetHeight('800')
                                                    ->columnSpan(1),
                                                TextInput::make('image_alt')
                                                    ->label('Image alt')
                                                    ->columnSpan(1),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'cta'),

                                Fieldset::make('Features Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.features_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->placeholder('Everything You Need')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->placeholder('Powerful features designed to help your business succeed online')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),

                                                Repeater::make('items')
                                                    ->label('Feature items (title & description)')
                                                    ->reorderable()
                                                    ->schema([
                                                        TextInput::make('icon')->label('Font Awesome icon class')->placeholder('fa-bolt')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->default([
                                                        ['title' => 'Blazing Fast', 'description' => 'Experience top-tier speed and performance for all your sites with our optimized infrastructure.'],
                                                        ['title' => 'Reliable Servers', 'description' => 'Our infrastructure is built for reliability and uptime, ensuring your services stay online.'],
                                                        ['title' => 'Advanced Security', 'description' => 'Protect your data with industry-leading security features and DDoS protection.'],
                                                        ['title' => 'Global Reach', 'description' => 'Serve your content quickly to users around the world with our global CDN.'],
                                                        ['title' => '24/7 Support', 'description' => 'Our expert team is always available to help you succeed with round-the-clock support.'],
                                                        ['title' => 'Cloud Backups', 'description' => 'Daily automated backups ensure your data is always safe and recoverable.'],
                                                    ])
                                                    ->collapsed()
                                                    ->collapsible()
                                                ->visible(fn($get) => in_array($get('../../variation'), ['1', '2', '5', '6', '7', '8', '9', '11']))
                                                    ->columnSpanFull(),

                                                Fieldset::make('Core Features')
                                                    ->schema([
                                                        Repeater::make('items_main')
                                                            ->label('Main features (4)')
                                                            ->schema([
                                                                TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                            ])
                                                            ->default([
                                                                ['title' => 'High Performance', 'description' => 'SSD storage and optimized servers for lightning-fast loading times.'],
                                                                ['title' => 'Security First', 'description' => 'DDoS protection, SSL certificates, and regular security updates.'],
                                                                ['title' => 'Global Network', 'description' => 'Data centers worldwide with CDN for optimal global performance.'],
                                                                ['title' => '24/7 Support', 'description' => 'Expert technical support available around the clock.'],
                                                            ])
                                                            ->collapsed()
                                                            ->collapsible()
                                                            ->columnSpanFull(),
                                                        Repeater::make('items_sub')
                                                            ->label('Additional features (3)')
                                                            ->schema([
                                                                TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                            ])
                                                            ->default([
                                                                ['title' => 'Automated Backups', 'description' => 'Daily backups with instant restore capabilities.'],
                                                                ['title' => 'Quick Setup', 'description' => 'Get your server running in under 60 seconds.'],
                                                                ['title' => 'Real-time Monitoring', 'description' => 'Track performance and uptime in real-time.'],
                                                            ])
                                                            ->collapsed()
                                                            ->collapsible()
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->visible(fn($get) => $get('../../variation') === '3')
                                                    ->columns(2),

                                                Fieldset::make('Enterprise Solutions')
                                                    ->schema([
                                                        Repeater::make('items_left')
                                                            ->label('Left column items (3)')
                                                            ->schema([
                                                                TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                            ])
                                                            ->default([
                                                                ['title' => 'Scalable Infrastructure', 'description' => 'Grow your resources on-demand with our flexible cloud infrastructure that scales with your business needs.'],
                                                                ['title' => 'Advanced Security', 'description' => 'Multi-layer security including firewall rules, intrusion detection, and compliance-ready infrastructure.'],
                                                                ['title' => '99.99% Uptime SLA', 'description' => 'Enterprise-grade reliability with guaranteed uptime and compensation for any downtime.'],
                                                            ])
                                                            ->collapsed()
                                                            ->collapsible()
                                                            ->columnSpanFull(),
                                                        Repeater::make('metrics')
                                                            ->label('Right column metrics (3)')
                                                            ->schema([
                                                                TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                TextInput::make('value')->label('Value')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                            ])
                                                            ->default([
                                                                ['title' => 'Performance Metrics', 'value' => '99.9%', 'description' => 'Average response time across all servers'],
                                                                ['title' => 'Global Coverage', 'value' => '15+', 'description' => 'Data centers across multiple continents'],
                                                                ['title' => 'Support Response', 'value' => '5min', 'description' => 'Average response time for urgent issues'],
                                                            ])
                                                            ->collapsed()
                                                            ->collapsible()
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->visible(fn($get) => $get('../../variation') === '4')
                                                    ->columns(2),

                                            Fieldset::make('Metric Style Blocks')
                                                ->schema([
                                                    Repeater::make('metrics_simple')
                                                        ->label('Metrics')
                                                        ->schema([
                                                            TextInput::make('value')->label('Value')->required(),
                                                            TextInput::make('label')->label('Label')->required(),
                                                        ])
                                                        ->default([
                                                            ['value' => '99.99%', 'label' => 'Uptime'],
                                                            ['value' => '15+', 'label' => 'Locations'],
                                                            ['value' => '24/7', 'label' => 'Support'],
                                                        ])
                                                        ->columnSpanFull(),
                                                ])
                                                ->visible(fn($get) => $get('../../variation') === '10')
                                                ->columnSpanFull(),

                                            Fieldset::make('Alternating Parts')
                                                ->schema([
                                                    Repeater::make('parts')
                                                        ->label('Parts')
                                                        ->schema([
                                                            FileUpload::make('image')->disk('public')->directory('pagebuilder')->image()->imageEditor()->imageResizeMode('contain')->imageResizeTargetWidth('1600')->imageResizeTargetHeight('900'),
                                                            TextInput::make('title')->label('Title')->required(),
                                                            Textarea::make('description')->label('Description')->rows(3)->required(),
                                                            TextInput::make('cta_label')->label('CTA label'),
                                                            TextInput::make('cta_link')->label('CTA link'),
                                                            \Filament\Forms\Components\Toggle::make('image_left')->label('Image on left')->default(true),
                                                        ])
                                                        ->default([])
                                                        ->columnSpanFull(),
                                                ])
                                                ->visible(fn($get) => $get('../../variation') === '12')
                                                ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'features'),

                                Fieldset::make('Reviews Content')
                                    ->schema([
                                        TextInput::make('content.reviews_data.section_title')
                                            ->label('Section title')
                                            ->live(onBlur: true)
                                            ->partiallyRenderAfterStateUpdated()
                                            ->columnSpan(1),
                                        Textarea::make('content.reviews_data.section_description')
                                            ->label('Section description')
                                            ->rows(3)
                                            ->live(onBlur: true)
                                            ->partiallyRenderAfterStateUpdated()
                                            ->columnSpan(1),

                                        Repeater::make('content.reviews_data.reviews')
                                            ->label('Reviews')
                                            ->addActionLabel('Add review')
                                            ->schema([
                                                Textarea::make('quote')
                                                    ->label('Quote')
                                                    ->rows(3)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('name')
                                                    ->label('Name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('title')
                                                    ->label('Title / Role')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('initials')
                                                    ->label('Initials (2 letters)')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('rating')
                                                    ->label('Rating (e.g. 5.0)')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                            ])
                                            ->collapsed()
                                            ->collapsible()
                                            ->maxItems(fn($get) => match ($get('variation')) {
                                                '1' => 3,
                                                '2' => 2,
                                                '3' => 3,
                                                '4' => 2,
                                                '5' => 4,
                                                default => null,
                                            })
                                            ->default([])
                                            ->columnSpanFull(),

                                        Fieldset::make('Alternating Layout Aside')
                                            ->schema([
                                                TextInput::make('content.reviews_data.aside_1_title')->label('Aside 1 title'),
                                                Textarea::make('content.reviews_data.aside_1_text')->label('Aside 1 text')->rows(3),
                                                TextInput::make('content.reviews_data.aside_2_title')->label('Aside 2 title'),
                                                Textarea::make('content.reviews_data.aside_2_text')->label('Aside 2 text')->rows(3),
                                            ])
                                            ->columns(2)
                                            ->visible(fn($get) => $get('variation') === '4')
                                            ->columnSpanFull(),

                                        Fieldset::make('Metrics')
                                            ->schema([
                                                TextInput::make('content.reviews_data.metrics.average_rating')->label('Average rating label'),
                                                TextInput::make('content.reviews_data.metrics.total_reviews')->label('Total reviews label'),
                                                TextInput::make('content.reviews_data.metrics.recommend_percent')->label('Recommend percent label'),
                                            ])
                                            ->columns(3)
                                            ->visible(fn($get) => $get('variation') === '5')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'reviews'),

                                Fieldset::make('Pricing Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.pricing_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                FileUpload::make('illustration')
                                                    ->label('Illustration (for split-screen layout)')
                                                    ->disk('public')
                                                    ->directory('pagebuilder')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageResizeMode('contain')
                                                    ->columnSpan(2)
                                                    ->hidden(fn($get) => $get('variation') !== '8'),

                                                Repeater::make('items')
                                                    ->label('Plans')
                                                    ->schema([
                                                        FileUpload::make('image')
                                                            ->label('Image')
                                                            ->disk('public')
                                                            ->directory('pagebuilder')
                                                            ->image()
                                                            ->imageEditor()
                                                            ->imageResizeMode('contain'),
                                                        TextInput::make('title')->label('Plan title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('price')->label('Price (e.g. $19)')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('period')->label('Billing period (e.g. /mo)')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Repeater::make('features')
                                                            ->label('Feature list')
                                                            ->schema([
                                                                TextInput::make('text')->label('Text')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                            ])
                                                            ->collapsed()
                                                            ->collapsible()
                                                            ->default([])
                                                            ->columnSpanFull(),
                                                        TextInput::make('cta_label')->label('Button label')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('cta_link')->label('Button link')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('badge')->label('Badge text (optional)')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->cloneable()
                                                    ->collapsed()
                                                    ->collapsible()
                                                    ->maxItems(6)
                                                    ->default([])
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'pricing'),

                                Fieldset::make('Products Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.products_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('category_slug')
                                                    ->label('Products Category Slug')
                                                    ->placeholder('e.g. shared-hosting')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'products'),

                                Fieldset::make('Blog Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.blog_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),

                                                Repeater::make('posts')
                                                    ->label('Posts')
                                                    ->reorderable()
                                                    ->schema([
                                                        FileUpload::make('image')
                                                            ->label('Image')
                                                            ->disk('public')
                                                            ->directory('pagebuilder')
                                                            ->image()
                                                            ->imageEditor()
                                                            ->imageResizeMode('contain')
                                                            ->imageResizeTargetWidth('1600')
                                                            ->imageResizeTargetHeight('900'),
                                                        TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('date')->label('Date (e.g. 2025-09-01)')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('read_time')->label('Read time (e.g. 5 min read)')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('link')->label('Link')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->collapsed()
                                                    ->collapsible()
                                                    ->cloneable()
                                                    ->default([])
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'blog'),

                                Fieldset::make('FAQ Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.faq_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),

                                                Repeater::make('items')
                                                    ->label('FAQ items')
                                                    ->schema([
                                                        TextInput::make('question')->label('Question')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Textarea::make('answer')->label('Answer')->rows(3)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['1', '2', '5']))
                                                    ->collapsed()
                                                    ->collapsible()
                                                    ->default([])
                                                    ->columnSpanFull(),

                                                Fieldset::make('Categorised FAQs')
                                                    ->schema([
                                                        Repeater::make('groups')
                                                            ->label('Groups')
                                                            ->schema([
                                                                TextInput::make('title')->label('Group title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                Repeater::make('items')
                                                                    ->label('Group items')
                                                                    ->schema([
                                                                        TextInput::make('question')->label('Question')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                        Textarea::make('answer')->label('Answer')->rows(3)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                                    ])
                                                                    ->collapsed()
                                                                    ->collapsible()
                                                                    ->default([])
                                                                    ->columnSpanFull(),
                                                            ])
                                                            ->collapsed()
                                                            ->collapsible()
                                                            ->default([])
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['3', '4', '6']))
                                                    ->columns(1),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'faq'),

                                Fieldset::make('Panel Preview')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.panel_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                FileUpload::make('image')
                                                    ->label('Panel image')
                                                    ->disk('public')
                                                    ->directory('pagebuilder')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1920')
                                                    ->imageResizeTargetHeight('1080')
                                                    ->visible(fn($get) => $get('../../variation') !== '4')
                                                    ->columnSpanFull(),

                                                Repeater::make('widgets')
                                                    ->label('Widgets')
                                                    ->schema([
                                                        TextInput::make('icon')->label('Font Awesome icon class')->placeholder('fa-bolt')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('title')->label('Title')->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Textarea::make('description')->label('Description')->rows(2)->required()->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        FileUpload::make('image')
                                                            ->label('Card image')
                                                            ->disk('public')
                                                            ->directory('pagebuilder')
                                                            ->image()
                                                            ->imageEditor()
                                                            ->imageResizeMode('cover')
                                                            ->imageResizeTargetWidth('800')
                                                            ->imageResizeTargetHeight('600')
                                                            ->visible(fn($get) => true)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->maxItems(3)
                                                    ->default([
                                                        ['icon' => 'fa-bolt', 'title' => 'Feature 1', 'description' => 'Description for feature 1'],
                                                        ['icon' => 'fa-shield-halved', 'title' => 'Feature 2', 'description' => 'Description for feature 2'],
                                                        ['icon' => 'fa-globe', 'title' => 'Feature 3', 'description' => 'Description for feature 3'],
                                                    ])
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['2', '3', '4']))
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'panel'),

                                Fieldset::make('Partners Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.partners_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),

                                                Repeater::make('partners')
                                                    ->label('Partners')
                                                    ->reorderable()
                                                    ->schema([
                                                        FileUpload::make('image')
                                                            ->label('Logo')
                                                            ->disk('public')
                                                            ->directory('pagebuilder')
                                                            ->image()
                                                            ->imageEditor()
                                                            ->imageResizeMode('contain')
                                                            ->imageResizeTargetWidth('400')
                                                            ->imageResizeTargetHeight('200'),
                                                        TextInput::make('alt')->label('Alt text')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('name')->label('Company name')->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                        Textarea::make('description')->label('Description')->rows(2)->live(onBlur: true)->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->collapsed()
                                                    ->collapsible()
                                                    ->cloneable()
                                                    ->default([])
                                                    ->columnSpanFull(),

                                                \Filament\Forms\Components\Toggle::make('black_white_mode')
                                                    ->label('Black and white mode')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'partners'),

                                Fieldset::make('Location Map Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.location_map_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),

                                                FileUpload::make('image')
                                                    ->label('Map image')
                                                    ->disk('public')
                                                    ->directory('pagebuilder')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1920')
                                                    ->imageResizeTargetHeight('1080')
                                                    ->columnSpanFull(),
                                                TextInput::make('image_alt')
                                                    ->label('Image alt text')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'location_map'),

                                Fieldset::make('Text Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.text_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Title')
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['1', '2']))
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                Textarea::make('text')
                                                    ->label('Paragraph text')
                                                    ->rows(4)
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['1', '2']))
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('cta_label')
                                                    ->label('CTA label')
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['1', '2']))
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('cta_link')
                                                    ->label('CTA link')
                                                    ->visible(fn($get) => in_array($get('../../variation'), ['1', '2']))
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),

                                                TextInput::make('left_title')
                                                    ->label('Left title')
                                                    ->visible(fn($get) => $get('../../variation') === '3')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                Textarea::make('left_text')
                                                    ->label('Left text')
                                                    ->rows(4)
                                                    ->visible(fn($get) => $get('../../variation') === '3')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                TextInput::make('right_title')
                                                    ->label('Right title')
                                                    ->visible(fn($get) => $get('../../variation') === '3')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                                Textarea::make('right_text')
                                                    ->label('Right text')
                                                    ->rows(4)
                                                    ->visible(fn($get) => $get('../../variation') === '3')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'text'),

                                Fieldset::make('Games Content')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.games_data')
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('section_title')
                                                    ->label('Section title')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),
                                                Textarea::make('section_description')
                                                    ->label('Section description')
                                                    ->rows(3)
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->columnSpan(1),

                                                Repeater::make('cards')
                                                    ->label('Games')
                                                    ->addActionLabel('Add game')
                                                    ->reorderable()
                                                    ->schema([
                                                        FileUpload::make('image')
                                                            ->label('Image')
                                                            ->disk('public')
                                                            ->directory('pagebuilder')
                                                            ->image()
                                                            ->imageEditor()
                                                            ->imageResizeMode('contain')
                                                            ->imageResizeTargetWidth('1600')
                                                            ->imageResizeTargetHeight('900'),
                                                        TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->partiallyRenderAfterStateUpdated(),
                                                        Textarea::make('description')
                                                            ->label('Description')
                                                            ->rows(2)
                                                            ->live(onBlur: true)
                                                            ->partiallyRenderAfterStateUpdated(),
                                                        TextInput::make('link')
                                                            ->label('Link')
                                                            ->live(onBlur: true)
                                                            ->partiallyRenderAfterStateUpdated(),
                                                    ])
                                                    ->collapsed()
                                                    ->collapsible()
                                                    ->default([])
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'games'),

                                Fieldset::make('Custom HTML/CSS')
                                    ->schema([
                                        Group::make()
                                            ->statePath('content.custom_html_data')
                                            ->columns(1)
        								->columnSpanFull()
                                            ->schema([
                                                Textarea::make('html')
                                                    ->label('HTML')
                                                    ->rows(14)
                                                    ->columnSpanFull(),
                                                Textarea::make('css')
                                                    ->label('CSS')
                                                    ->rows(10)
                                                    ->helperText('Will be wrapped in <style> tags on render')
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull()
                                    ->hidden(fn($get) => $get('type') !== 'custom_html'),
                            ])
                            ->default([
                                ['type' => 'hero', 'variation' => '1', 'content' => []]
                            ])
                            ->addActionLabel('Add section')
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['type'])) {
                                    return null;
                                }
                                $type = (string) $state['type'];
                                if ($type === 'location_map') {
                                    return 'Location Map Section';
                                }
                                return ucfirst(str_replace('_', ' ', $type)) . ' Section';
                            }),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make('Configuration Script')
                    ->schema([
                        Textarea::make('config_script')
                            ->label('Configuration Script')
                            ->rows(16)
                            ->helperText('Edit as JSON. Click Sync to apply to form, or Refresh to regenerate.'),
                        \Filament\Forms\Components\Placeholder::make('config_actions')
                            ->content(fn() => new \Illuminate\Support\HtmlString(view('pagebuilder::components.config-actions')->render())),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }
}
