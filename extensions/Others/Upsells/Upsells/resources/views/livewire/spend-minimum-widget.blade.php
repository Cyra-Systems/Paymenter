@php
    $settings = $upsellSettings ?? null;
    $primaryColor = $settings->primary_color ?? config('settings.primary_color', '#3b82f6');
    
    $bgColor = 'hsl(var(--color-background))';
    $bgSecondary = 'hsl(var(--color-background-secondary))';
    $textColor = 'hsl(var(--color-base))';
    $mutedColor = 'hsl(var(--color-muted))';
    $borderColor = 'hsl(var(--color-neutral))';
    
    $targetProduct = $upsell->targetProduct;
    
    $title = $isUnlocked ? ($upsell->unlocked_title ?: 'Congratulations! You\'ve unlocked {{ product_name }}') : ($upsell->title ?: 'Spend {{ total_left }} more to unlock {{ product_name }}');
    $description = $isUnlocked ? ($upsell->unlocked_description ?: 'Click below to add it to your cart for free!') : ($upsell->description ?: 'Add more to your cart to unlock this free product');
    
    $title = str_replace('{{ total_left }}', '$' . number_format($totalLeft, 2), $title);
    $title = str_replace('{{ minimum_spend }}', '$' . number_format($upsell->minimum_spend, 2), $title);
    $title = str_replace('{{ product_name }}', $targetProduct->name, $title);
    
    $description = str_replace('{{ total_left }}', '$' . number_format($totalLeft, 2), $description);
    $description = str_replace('{{ minimum_spend }}', '$' . number_format($upsell->minimum_spend, 2), $description);
    $description = str_replace('{{ product_name }}', $targetProduct->name, $description);
    
    $progressPercentage = min(100, ($cartTotal / $upsell->minimum_spend) * 100);
    
    $template = $upsell->template ?? 'progress';
@endphp

@include('upsells::spend-minimum.' . $template, [
    'upsell' => $upsell,
    'settings' => $settings,
    'primaryColor' => $primaryColor,
    'bgColor' => $bgColor,
    'bgSecondary' => $bgSecondary,
    'textColor' => $textColor,
    'mutedColor' => $mutedColor,
    'borderColor' => $borderColor,
    'title' => $title,
    'description' => $description,
    'progressPercentage' => $progressPercentage,
    'isUnlocked' => $isUnlocked,
    'totalLeft' => $totalLeft,
    'cartTotal' => $cartTotal,
])

