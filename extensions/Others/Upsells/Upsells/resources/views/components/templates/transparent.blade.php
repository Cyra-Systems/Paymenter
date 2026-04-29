@props(['upsell', 'item', 'settings', 'targetProduct', 'targetPlan', 'primaryColor', 'bgColor' => 'hsl(240, 18%, 9%)', 'bgSecondary' => 'hsl(240, 13%, 11%)', 'textColor' => 'hsl(0, 0%, 100%)', 'mutedColor' => 'hsl(220, 14%, 60%)', 'borderColor' => 'hsl(0, 0%, 20%)'])

<div style="background-color: transparent; border-radius: 0; padding: 0 1.25rem; margin-bottom: 1rem; border: none; border-left: 5px solid {{ $primaryColor }}; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
    <div style="flex: 1;">
        @if($settings->show_label && $upsell->label)
            <span style="color: {{ $primaryColor }}; display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">{{ $upsell->label }}</span>
        @endif
        
        <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 0.375rem 0; color: {{ $textColor }}; line-height: 1.4;">
            {{ $upsell->type === 'cross_sell' ? $targetProduct->name : ($upsell->title ?: ($upsell->type === 'upgrade' ? 'Upgrade to ' . $targetProduct->name : $targetProduct->name)) }}
        </h3>
        
        @if($settings->show_description && $upsell->description)
            <p style="font-size: 0.875rem; margin: 0; line-height: 1.5; margin-right: 2rem; color: {{ $mutedColor }};">{{ $upsell->description }}</p>
        @endif
    </div>
    
    <div style="display: flex; align-items: center;">
        @if(isset($item->id))
            @if($upsell->type === 'upgrade')
                @livewire('upsells-upgrade-button', [
            'cartItemId' => $item->id,
            'newProductId' => $targetProduct->id,
            'primaryColor' => $primaryColor,
            'borderRadius' => 8
        ])
            @else
                @livewire('upsells-add-to-cart-button', [
            'targetProductId' => $targetProduct->id,
            'upsellId' => $upsell->id,
            'autoAdd' => $upsell->auto_add_to_cart,
            'preconfiguredOptions' => $upsell->preconfigured_options ?? [],
            'primaryColor' => $primaryColor,
            'borderRadius' => 8
        ])
            @endif
        @else
            <button 
                style="background-color: {{ $primaryColor }}; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; font-size: 0.875rem; border: none; cursor: pointer; transition: opacity 0.2s; white-space: nowrap;"
                onmouseover="this.style.opacity='0.9'"
                onmouseout="this.style.opacity='1'"
            >
                {{ $upsell->type === 'upgrade' ? 'Upgrade Now' : 'Add to Cart' }}
            </button>
        @endif
    </div>
</div>

