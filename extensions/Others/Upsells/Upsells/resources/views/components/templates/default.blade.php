@props(['upsell', 'item', 'settings', 'targetProduct', 'targetPlan', 'primaryColor', 'bgColor' => 'hsl(240, 18%, 9%)', 'bgSecondary' => 'hsl(240, 13%, 11%)', 'textColor' => 'hsl(0, 0%, 100%)', 'mutedColor' => 'hsl(220, 14%, 60%)', 'borderColor' => 'hsl(0, 0%, 20%)'])

@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div style="background-color: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: {{ $borderRadius }}px; padding: 1.25rem; margin-bottom: 1rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="flex: 1; min-width: 0;">
            @if($settings->show_label && $upsell->label)
                <span style="background-color: {{ $primaryColor }}22; color: {{ $primaryColor }}; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 600; border-radius: {{ $borderRadius }}px; display: inline-block; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">{{ $upsell->label }}</span>
            @endif
            
            <h4 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 0.5rem 0; color: {{ $textColor }}; line-height: 1.4;">
                {{ $upsell->type === 'cross_sell' ? $targetProduct->name : ($upsell->title ?: ($upsell->type === 'upgrade' ? 'Upgrade to ' . $targetProduct->name : $targetProduct->name)) }}
            </h4>
            
            @if($settings->show_description && $upsell->description)
                <p style="font-size: 0.875rem; margin: 0; line-height: 1.5; color: {{ $mutedColor }};">{{ $upsell->description }}</p>
            @endif
        </div>
        
        <div style="flex-shrink: 0; display: flex; align-items: center;">
            @if(isset($item->id))
                @if($upsell->type === 'upgrade')
                    @livewire('upsells-upgrade-button', [
                        'cartItemId' => $item->id, 
                        'newProductId' => $targetProduct->id,
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
                    style="background-color: {{ $primaryColor }}; color: white; padding: 0.625rem 1.25rem; border-radius: {{ $borderRadius }}px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; white-space: nowrap;"
                >
                    {{ $upsell->type === 'upgrade' ? 'Upgrade Now' : 'Add to Cart' }}
                </button>
            @endif
        </div>
    </div>
</div>
