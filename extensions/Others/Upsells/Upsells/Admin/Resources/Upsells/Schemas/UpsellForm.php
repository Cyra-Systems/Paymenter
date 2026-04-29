<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Product;
use Paymenter\Extensions\Others\Upsells\Models\UpsellSettings;

class UpsellForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->columnSpan('full')
                ->schema([
                    Select::make('type')
                        ->label('Upsell Type')
                        ->options([
                            'upgrade' => 'Product Upgrade',
                            'cross_sell' => 'Often ordered with',
                            'spend_minimum' => 'Spend minimum',
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->helperText('Upgrade: Replace cart item | Often ordered with: Add to cart'),
                    
                    Select::make('template')
                        ->label('Template Style')
                        ->options([
                            'progress' => 'Progress Slider - Visual progress bar with amount tracking and large icons',
                            'card' => 'Simple Card - Clean card with text information and alert box',
                            'gradient' => 'Gradient Card - Eye-catching gradient background with animations',
                            'minimal' => 'Minimal - Compact text-only display with border',
                            'sidebar' => 'Sidebar Widget - Compact widget with icon and description',
                            'sidebar-progress' => 'Sidebar Widget with Progress - Compact widget with full-width progress bar',
                        ])
                        ->default('progress')
                        ->required()
                        ->native(false)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum')
                        ->helperText('Choose how the spend minimum offer is displayed'),
                    
                    Placeholder::make('spend_minimum_preview')
                        ->label('Preview')
                        ->columnSpan('full')
                        ->content(function (Get $get) {
                            if ($get('type') !== 'spend_minimum') {
                                return null;
                            }
                            
                            $template = $get('template') ?? 'progress';
                            $settings = UpsellSettings::get();
                            $primaryColorRaw = $settings->primary_color ?? config('settings.primary_color', '#3b82f6');
                            $borderRadius = $settings->border_radius ?? 8;
                            
                            $primaryColor = $primaryColorRaw;
                            
                            $bgColor = $settings->preview_background ?? 'hsl(240, 18%, 9%)';
                            $bgSecondary = $settings->preview_card_background ?? 'hsl(240, 13%, 11%)';
                            $textColor = $settings->preview_text ?? 'hsl(0, 0%, 100%)';
                            $mutedColor = $settings->preview_muted ?? 'hsl(220, 14%, 60%)';
                            $borderColor = $settings->preview_border ?? 'hsl(0, 0%, 20%)';
                            
                            $title = $get('title') ?: 'Spend ${{ total_left }} more to unlock {{ product_name }}';
                            $description = $get('description') ?: 'Add more to your cart to unlock this free product';
                            $minimumSpend = $get('minimum_spend') ?? 50;
                            $label = null;
                            
                            $targetProductId = $get('target_product_id');
                            $targetProductName = 'Free Product';
                            if ($targetProductId) {
                                $product = Product::find($targetProductId);
                                if ($product) {
                                    $targetProductName = $product->name;
                                }
                            }
                            
                            $demoCartTotal = $minimumSpend * 0.6;
                            $demoTotalLeft = max(0, $minimumSpend - $demoCartTotal);
                            $progressPercentage = $minimumSpend > 0 ? min(100, max(0, ($demoCartTotal / $minimumSpend) * 100)) : 0;
                            
                            $title = str_replace('{{ total_left }}', number_format($demoTotalLeft, 2), $title);
                            $title = str_replace('{{ minimum_spend }}', number_format($minimumSpend, 2), $title);
                            $title = str_replace('{{ product_name }}', $targetProductName, $title);
                            
                            $description = str_replace('{{ total_left }}', number_format($demoTotalLeft, 2), $description);
                            $description = str_replace('{{ minimum_spend }}', number_format($minimumSpend, 2), $description);
                            $description = str_replace('{{ product_name }}', $targetProductName, $description);
                            
                            $demoUpsell = (object)[
                                'label' => null,
                                'title' => $title,
                                'description' => $description,
                                'minimum_spend' => $minimumSpend,
                                'template' => $template,
                            ];
                            
                            $demoSettings = (object)[
                                'show_label' => false,
                                'show_description' => true,
                                'border_radius' => $borderRadius,
                            ];
                            
                            try {
                                $html = view('upsells::spend-minimum.' . $template, [
                                    'upsell' => $demoUpsell,
                                    'settings' => $demoSettings,
                                    'primaryColor' => $primaryColor,
                                    'bgColor' => $bgColor,
                                    'bgSecondary' => $bgSecondary,
                                    'textColor' => $textColor,
                                    'mutedColor' => $mutedColor,
                                    'borderColor' => $borderColor,
                                    'title' => $title,
                                    'description' => $description,
                                    'progressPercentage' => $progressPercentage,
                                    'isUnlocked' => false,
                                    'totalLeft' => $demoTotalLeft,
                                    'cartTotal' => $demoCartTotal,
                                ])->render();
                                
                                $maxWidth = (in_array($template, ['sidebar', 'sidebar-progress'])) ? 'max-width: 350px;' : '';
                                return new \Illuminate\Support\HtmlString('<div style="background-color: ' . $bgColor . '; padding: 1rem; min-height: 200px; ' . $maxWidth . '">' . $html . '</div>');
                            } catch (\Exception $e) {
                                \Log::error('Spend minimum preview error: ' . $e->getMessage());
                                return new \Illuminate\Support\HtmlString('<div style="color: red; font-size: 0.875rem;">Preview error: ' . htmlspecialchars($e->getMessage()) . '</div>');
                            }
                        })
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum'),

                    Select::make('trigger_product_id')
                        ->label('Trigger Product')
                        ->options(function () {
                            return Product::with('category')
                                ->get()
                                ->groupBy(fn ($product) => $product->category->name ?? 'Uncategorised')
                                ->map(fn ($products) => $products->pluck('name', 'id'))
                                ->toArray();
                        })
                        ->required(fn (Get $get): bool => $get('type') !== 'spend_minimum')
                        ->searchable()
                        ->native(false)
                        ->helperText('Show this upsell when this product is in cart')
                        ->hidden(fn (Get $get): bool => $get('type') === 'spend_minimum'),

                    Select::make('target_product_id')
                        ->label(fn (Get $get): string => $get('type') === 'spend_minimum' ? 'Free Product/Addon' : 'Target Product')
                        ->options(function () {
                            return Product::with('category')
                                ->get()
                                ->groupBy(fn ($product) => $product->category->name ?? 'Uncategorised')
                                ->map(fn ($products) => $products->pluck('name', 'id'))
                                ->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->helperText(fn (Get $get): string => $get('type') === 'spend_minimum' ? 'The free product/addon unlocked when threshold is met' : 'The product to upsell to'),
                    
                    TextInput::make('minimum_spend')
                        ->label('Minimum Spend Amount')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->helperText('Cart total must reach this amount to unlock the free product')
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum'),
                    
                    Toggle::make('allow_multiple_claims')
                        ->label('Allow Multiple Claims')
                        ->helperText('Allow users to claim this free product multiple times')
                        ->default(false)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum'),
                    
                    TextInput::make('max_claims')
                        ->label('Maximum Claims Per User')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Maximum number of times a user can claim this offer. Leave empty for unlimited (if multiple claims enabled).')
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum' && $get('allow_multiple_claims')),

                    TextInput::make('priority')
                        ->label('Priority')
                        ->numeric()
                        ->default(0)
                        ->helperText('Higher numbers appear first'),

                    Toggle::make('enabled')
                        ->label('Enabled')
                        ->default(true),
                ])
                ->columns(1),

            Section::make('Display Configuration')
                ->columnSpan('full')
                ->schema([
                    TextInput::make('title')
                        ->label(fn (Get $get): string => $get('type') === 'spend_minimum' ? 'Title (Before Threshold)' : 'Title')
                        ->maxLength(255)
                        ->helperText(fn (Get $get): string => $get('type') === 'spend_minimum' ? 'Use {{ total_left }} to show remaining amount, {{ minimum_spend }} for threshold, {{ product_name }} for free product' : 'Leave empty to use default based on type')
                        ->hidden(fn (Get $get): bool => $get('type') === 'cross_sell'),

                    Textarea::make('description')
                        ->label(fn (Get $get): string => $get('type') === 'spend_minimum' ? 'Description (Before Threshold)' : 'Description')
                        ->rows(3)
                        ->helperText(fn (Get $get): string => $get('type') === 'spend_minimum' ? 'Use {{ total_left }}, {{ minimum_spend }}, {{ product_name }} variables' : 'Explain the benefits of this upsell'),
                    
                    TextInput::make('unlocked_title')
                        ->label('Title (After Threshold Met)')
                        ->maxLength(255)
                        ->helperText('Title shown when customer has unlocked the free product. Use {{ product_name }} variable')
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum'),
                    
                    Textarea::make('unlocked_description')
                        ->label('Description (After Threshold Met)')
                        ->rows(3)
                        ->helperText('Description shown when unlocked. Use {{ product_name }} variable')
                        ->visible(fn (Get $get): bool => $get('type') === 'spend_minimum'),

                    TextInput::make('label')
                        ->label('Label')
                        ->maxLength(50)
                        ->helperText('e.g. "Recommended", "Best Value", "Popular Choice"')
                        ->placeholder('Recommended')
                        ->hidden(fn (Get $get): bool => $get('type') === 'spend_minimum'),
                ])
                ->columns(1),

            Section::make('Auto-add Configuration')
                ->columnSpan('full')
                ->schema([
                    Toggle::make('auto_add_to_cart')
                        ->label('Auto-add to Cart')
                        ->helperText('Skip checkout page and add directly to cart with pre-configured options')
                        ->live(),

                    Repeater::make('preconfigured_options')
                        ->label('Pre-configured Options')
                        ->schema([
                            Select::make('option_id')
                                ->label('Config Option')
                                ->options(function (Get $get) {
                                    $targetProductId = $get('../../target_product_id');
                                    if (!$targetProductId) {
                                        return [];
                                    }
                                    $product = Product::with('configOptions')->find($targetProductId);
                                    if (!$product) {
                                        return [];
                                    }
                                    
                                    $allOptions = $product->configOptions->pluck('name', 'id')->toArray();
                                    
                                    $allPreconfiguredOptions = $get('../../preconfigured_options') ?? [];
                                    $currentOptionId = $get('option_id');
                                    
                                    $selectedOptionIds = collect($allPreconfiguredOptions)
                                        ->pluck('option_id')
                                        ->filter()
                                        ->reject(fn ($id) => $id == $currentOptionId)
                                        ->toArray();
                                    
                                    return collect($allOptions)
                                        ->reject(fn ($name, $id) => in_array($id, $selectedOptionIds))
                                        ->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('value', null)),

                            TextInput::make('value')
                                ->label('Value')
                                ->helperText('Enter the default value')
                                ->visible(function (Get $get) {
                                    $optionId = $get('option_id');
                                    if (!$optionId) {
                                        return false;
                                    }
                                    $option = \App\Models\ConfigOption::find($optionId);
                                    return $option && in_array($option->type, ['text', 'number']);
                                }),

                            Select::make('value')
                                ->label('Value')
                                ->options(function (Get $get) {
                                    $optionId = $get('option_id');
                                    if (!$optionId) {
                                        return [];
                                    }
                                    $option = \App\Models\ConfigOption::with('children')->find($optionId);
                                    if (!$option) {
                                        return [];
                                    }
                                    if ($option->type === 'checkbox') {
                                        return ['1' => 'Yes', '0' => 'No'];
                                    }
                                    return $option->children->pluck('name', 'name')->toArray();
                                })
                                ->searchable()
                                ->native(false)
                                ->helperText('Select a value or leave empty for first available')
                                ->visible(function (Get $get) {
                                    $optionId = $get('option_id');
                                    if (!$optionId) {
                                        return false;
                                    }
                                    $option = \App\Models\ConfigOption::find($optionId);
                                    return $option && !in_array($option->type, ['text', 'number']);
                                }),
                        ])
                        ->addActionLabel('Add Config Option')
                        ->columns(1)
                        ->helperText('Configure default values for the target product. Text/number fields: enter value as text. Select/radio: choose from dropdown.')
                        ->hidden(fn (Get $get): bool => !$get('auto_add_to_cart')),
                ])
                ->columns(1)
                ->hidden(fn (Get $get): bool => $get('type') === 'upgrade' || $get('type') === 'spend_minimum'),
        ]);
    }
}

