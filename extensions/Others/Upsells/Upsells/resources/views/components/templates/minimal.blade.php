@props(['upsell', 'item', 'settings', 'targetProduct', 'targetPlan', 'primaryColor', 'bgColor' => 'hsl(240, 18%, 9%)', 'bgSecondary' => 'hsl(240, 13%, 11%)', 'textColor' => 'hsl(0, 0%, 100%)', 'mutedColor' => 'hsl(220, 14%, 60%)', 'borderColor' => 'hsl(0, 0%, 20%)'])

@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid {{ $borderColor }}; background-color: transparent;">
    <div style="flex: 1; min-width: 0;">
        <div style="display: flex; align-items: baseline; flex-wrap: wrap; gap: 0.5rem;">
            @if($settings->show_label && $upsell->label)
                <span style="background-color: {{ $primaryColor }}22; color: {{ $primaryColor }}; padding: 0.125rem 0.5rem; font-size: 0.6875rem; font-weight: 600; border-radius: {{ $borderRadius }}px; text-transform: uppercase; letter-spacing: 0.05em;">{{ $upsell->label }}</span>
            @endif
            
            <span style="font-size: 0.9375rem; font-weight: 500; color: {{ $textColor }};">
                {{ $upsell->type === 'cross_sell' ? $targetProduct->name : ($upsell->title ?: ($upsell->type === 'upgrade' ? 'Upgrade to ' . $targetProduct->name : $targetProduct->name)) }}
            </span>
        </div>
        
        @if($settings->show_description && $upsell->description)
            <p style="font-size: 0.8125rem; margin: 0.375rem 0 0 0; line-height: 1.4; color: {{ $mutedColor }};">{{ $upsell->description }}</p>
        @endif
    </div>
    
    <div style="flex-shrink: 0;">
        @if(isset($item->id))
            @if($upsell->type === 'upgrade')
                @livewire('upsells-upgrade-button', [
                    'cartItemId' => $item->id, 
                    'newProductId' => $targetProduct->id,
                    'label' => 'Upgrade',
                    'primaryColor' => $primaryColor,
                    'borderRadius' => $borderRadius
                ])
            @else
                @livewire('upsells-add-to-cart-button', [
                    'targetProductId' => $targetProduct->id,
                    'upsellId' => $upsell->id,
                    'autoAdd' => $upsell->auto_add_to_cart,
                    'preconfiguredOptions' => $upsell->preconfigured_options ?? [],
                    'primaryColor' => $primaryColor,
                    'borderRadius' => $borderRadius
                ])
            @endif
        @else
            <button 
                style="background-color: {{ $primaryColor }}; color: white; padding: 0.5rem 1rem; border-radius: {{ $borderRadius }}px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; white-space: nowrap;"
            >
                {{ $upsell->type === 'upgrade' ? 'Upgrade' : 'Add' }}
            </button>
        @endif
    </div>
</div>
