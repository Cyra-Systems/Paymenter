<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Paymenter\Extensions\Others\Upsells\Models\UpsellSettings;
use BackedEnum;
use UnitEnum;

class UpsellDesignSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;
    protected static string|UnitEnum|null $navigationGroup = 'Extensions';
    protected static ?string $navigationLabel = 'Upsell Design';
    protected static ?string $title = 'Upsell Design Settings';
    protected static ?string $slug = 'upsell-design';

    protected string $view = 'upsells::admin.design-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = UpsellSettings::get();
        $data = $settings->toArray();
        
        if (empty($data['primary_color'])) {
            $data['primary_color'] = config('settings.primary_color', '#3b82f6');
        }
        
        if (empty($data['preview_background'])) {
            $data['preview_background'] = 'hsl(240, 18%, 9%)';
        }
        
        if (empty($data['preview_card_background'])) {
            $data['preview_card_background'] = 'hsl(240, 13%, 11%)';
        }
        
        if (empty($data['preview_text'])) {
            $data['preview_text'] = 'hsl(0, 0%, 100%)';
        }
        
        if (empty($data['preview_muted'])) {
            $data['preview_muted'] = 'hsl(220, 14%, 60%)';
        }
        
        if (empty($data['preview_border'])) {
            $data['preview_border'] = 'hsl(0, 0%, 20%)';
        }
        
        $this->form->fill($data);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Template Style')
                ->schema([
                    Select::make('template')
                        ->label('Template Design')
                        ->options([
                            'card' => 'Card',
                            'banner' => 'Banner',
                            'highlight' => 'Left Border',
                            'transparent' => 'Transparent Left Border',
                            'colorblock' => 'Color Block',
                            'subtle' => 'Subtle',
                        ])
                        ->default('card')
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('show_label', true);
                            $set('show_description', true);
                        })
                        ->helperText('Select a pre-designed template style'),
                    
                    ColorPicker::make('primary_color')
                        ->label('Primary Color')
                        ->helperText('Main color used for buttons, badges, and accents in the upsells')
                        ->default(config('settings.primary_color', '#3b82f6'))
                        ->live(),
                ])
                ->columns(1),
            
            Section::make('Preview Colors')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            ColorPicker::make('preview_background')
                                ->label('Background Color')
                                ->helperText('Main background color for the preview')
                                ->default('hsl(240, 18%, 9%)')
                                ->live(),
                            
                            ColorPicker::make('preview_card_background')
                                ->label('Card Background Color')
                                ->helperText('Background color for cards/boxes')
                                ->default('hsl(240, 13%, 11%)')
                                ->live(),
                            
                            ColorPicker::make('preview_text')
                                ->label('Text Color')
                                ->helperText('Main text color')
                                ->default('hsl(0, 0%, 100%)')
                                ->live(),
                            
                            ColorPicker::make('preview_muted')
                                ->label('Muted Text Color')
                                ->helperText('Secondary/muted text color')
                                ->default('hsl(220, 14%, 60%)')
                                ->live(),
                            
                            ColorPicker::make('preview_border')
                                ->label('Border Color')
                                ->helperText('Border color for elements')
                                ->default('hsl(0, 0%, 20%)')
                                ->live(),
                        ]),
                    
                    Placeholder::make('template_preview')
                        ->label('Preview')
                        ->columnSpan('full')
                        ->content(function (Get $get) {
                            $template = $get('template') ?? 'card';
                            $primaryColor = $get('primary_color') ?? config('settings.primary_color', '#3b82f6');
                            $showLabel = $get('show_label') ?? true;
                            $showDescription = $get('show_description') ?? true;
                            
                            $bgColor = $get('preview_background') ?? 'hsl(240, 18%, 9%)';
                            $bgSecondary = $get('preview_card_background') ?? 'hsl(240, 13%, 11%)';
                            $textColor = $get('preview_text') ?? 'hsl(0, 0%, 100%)';
                            $mutedColor = $get('preview_muted') ?? 'hsl(220, 14%, 60%)';
                            $borderColor = $get('preview_border') ?? 'hsl(0, 0%, 20%)';
                            $borderRadius = $get('border_radius') ?? 8;
                            
                            $templateMap = [
                                'card' => 'default',
                                'banner' => 'minimal',
                                'highlight' => 'bold',
                                'transparent' => 'transparent',
                                'colorblock' => 'gradient',
                                'subtle' => 'outlined',
                            ];
                            
                            $templateFile = $templateMap[$template] ?? 'default';
                            
                            $demoUpsell = (object)[
                                'label' => 'Recommended',
                                'title' => 'Premium VPS Hosting',
                                'description' => 'Upgrade to get 2x more RAM, dedicated CPU cores, and priority support.',
                                'type' => 'upgrade',
                                'auto_add_to_cart' => false,
                            ];
                            
                            $demoSettings = (object)[
                                'show_label' => $showLabel,
                                'show_description' => $showDescription,
                                'show_icon' => true,
                                'button_style' => 'primary',
                                'border_radius' => $borderRadius,
                            ];
                            
                            $demoItem = (object)[];
                            
                            $demoTargetProduct = (object)[
                                'id' => 1,
                                'name' => 'Premium VPS',
                                'category' => (object)[
                                    'slug' => 'vps',
                                    'name' => 'VPS Hosting',
                                ],
                            ];
                            
                            $demoTargetPlan = (object)[
                                'id' => 1,
                                'name' => 'Monthly',
                            ];
                            
                            try {
                                $html = view('upsells::components.templates.preview.' . $templateFile, [
                                    'upsell' => $demoUpsell,
                                    'settings' => $demoSettings,
                                    'targetProduct' => $demoTargetProduct,
                                    'primaryColor' => $primaryColor,
                                    'bgColor' => $bgColor,
                                    'bgSecondary' => $bgSecondary,
                                    'textColor' => $textColor,
                                    'mutedColor' => $mutedColor,
                                    'borderColor' => $borderColor,
                                ])->render();
                                
                                return new \Illuminate\Support\HtmlString('<div class="upsell-preview-wrapper" style="border: 1px solid ' . $borderColor . '; border-radius: 0.5rem; padding: 1rem; background-color: ' . $bgColor . ';">' . $html . '</div>');
                            } catch (\Exception $e) {
                                \Log::error('Upsell preview error: ' . $e->getMessage());
                                return new \Illuminate\Support\HtmlString('<div class="text-red-500 text-sm">Preview error: ' . htmlspecialchars($e->getMessage()) . '</div>');
                            }
                        }),
                    
                    TextInput::make('border_radius')
                        ->label('Border Radius (px)')
                        ->helperText('Affects cards, badges, and buttons')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(32)
                        ->default(8)
                        ->suffix('px')
                        ->live()
                        ->columnSpan('full'),
                ])
                ->columns(1),

            Section::make('Display Options')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Toggle::make('show_label')
                                ->label('Show Label Badge')
                                ->helperText('Display "Recommended" badge'),

                            Toggle::make('show_description')
                                ->label('Show Description')
                                ->helperText('Display upsell description'),

                            Toggle::make('show_icon')
                                ->label('Show Icon')
                                ->helperText('Display icon/image'),
                        ]),
                ])
                ->columns(1),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = UpsellSettings::first();
        if ($settings) {
            $settings->update($data);
        } else {
            UpsellSettings::create($data);
        }

        Notification::make()
            ->title('Design settings saved successfully')
            ->success()
            ->send();
    }
}

