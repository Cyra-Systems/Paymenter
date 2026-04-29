@props(['upsell', 'settings', 'targetProduct', 'primaryColor', 'bgColor' => 'hsl(240, 18%, 9%)', 'bgSecondary' => 'hsl(240, 13%, 11%)', 'textColor' => 'hsl(0, 0%, 100%)', 'mutedColor' => 'hsl(220, 14%, 60%)', 'borderColor' => 'hsl(0, 0%, 20%)'])

@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div class="preview-outlined" style="border: 2px solid {{ $primaryColor }}; border-radius: {{ $borderRadius }}px; padding: 1.5rem; margin-bottom: 1rem; background-color: transparent;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="flex: 1;">
            @if($settings->show_label && $upsell->label)
                <div style="margin-bottom: 0.75rem;">
                    <span style="color: {{ $primaryColor }}; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">{{ $upsell->label }}</span>
                </div>
            @endif
            
            <h4 style="font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem 0; color: {{ $textColor }}; line-height: 1.3;">
                {{ $upsell->title ?: 'Upgrade to ' . $targetProduct->name }}
            </h4>
            
            @if($settings->show_description && $upsell->description)
                <p style="font-size: 0.875rem; margin: 0; line-height: 1.5; color: {{ $mutedColor }};">{{ $upsell->description }}</p>
            @endif
        </div>
        
        <div style="flex-shrink: 0; display: flex; align-items: center;">
            <button 
                style="background-color: transparent; color: {{ $primaryColor }}; border: 2px solid {{ $primaryColor }}; padding: 0.625rem 1.25rem; border-radius: {{ $borderRadius }}px; font-size: 0.875rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s;"
                onmouseover="this.style.backgroundColor='{{ $primaryColor }}'; this.style.color='white';"
                onmouseout="this.style.backgroundColor='transparent'; this.style.color='{{ $primaryColor }}';"
            >
                Upgrade
            </button>
        </div>
    </div>
</div>
