@props(['upsell', 'item', 'settings' => null])

@php
    $settings = $settings ?? \Paymenter\Extensions\Others\Upsells\Models\UpsellSettings::get();
    $targetProduct = $upsell->targetProduct;
    
    if (!$targetProduct || !$targetProduct->category) {
        return;
    }
    
    $targetPlan = $targetProduct->plans->first();
    if (!$targetPlan) {
        return;
    }

    $primaryColor = $settings->primary_color ?? config('settings.primary_color', '#3b82f6');
    $template = $settings->template ?? 'card';
    
    $bgColor = $settings->preview_background ?? 'hsl(240, 18%, 9%)';
    $bgSecondary = $settings->preview_card_background ?? 'hsl(240, 13%, 11%)';
    $textColor = $settings->preview_text ?? 'hsl(0, 0%, 100%)';
    $mutedColor = $settings->preview_muted ?? 'hsl(220, 14%, 60%)';
    $borderColor = $settings->preview_border ?? 'hsl(0, 0%, 20%)';
    
    $templateMap = [
        'card' => 'default',
        'banner' => 'minimal',
        'highlight' => 'bold',
        'transparent' => 'transparent',
        'colorblock' => 'gradient',
        'subtle' => 'outlined',
        'default' => 'default',
        'minimal' => 'minimal',
        'bold' => 'bold',
        'transparent' => 'transparent',
        'gradient' => 'gradient',
        'outlined' => 'outlined',
    ];
    
    $templateFile = $templateMap[$template] ?? 'default';
@endphp

@include('upsells::components.templates.' . $templateFile, [
    'upsell' => $upsell,
    'item' => $item,
    'settings' => $settings,
    'targetProduct' => $targetProduct,
    'targetPlan' => $targetPlan,
    'primaryColor' => $primaryColor,
    'bgColor' => $bgColor,
    'bgSecondary' => $bgSecondary,
    'textColor' => $textColor,
    'mutedColor' => $mutedColor,
    'borderColor' => $borderColor,
])

