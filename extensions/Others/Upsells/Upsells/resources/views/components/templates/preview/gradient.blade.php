@props(['upsell', 'settings', 'targetProduct', 'primaryColor', 'bgColor' => 'hsl(240, 18%, 9%)', 'bgSecondary' => 'hsl(240, 13%, 11%)', 'textColor' => 'hsl(0, 0%, 100%)', 'mutedColor' => 'hsl(220, 14%, 60%)', 'borderColor' => 'hsl(0, 0%, 20%)'])

@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div class="preview-gradient" style="background: linear-gradient(to right, hsl(270, 60%, 55%) 0%, hsl(240, 60%, 55%) 100%); border-radius: {{ $borderRadius * 2 }}px; padding: 1.25rem; margin-bottom: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1.5rem;">
        <div style="flex: 1; min-width: 0;">
            @if($settings->show_label && $upsell->label)
                <span style="background-color: rgba(255, 255, 255, 0.2); color: white; display: inline-block; padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: {{ $borderRadius }}px; margin-bottom: 1rem; backdrop-filter: blur(4px); text-transform: uppercase; letter-spacing: 0.05em;">{{ $upsell->label }}</span>
            @endif
            
            <h3 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 0.625rem 0; color: white; line-height: 1.2;">
                {{ $upsell->title ?: 'Upgrade to ' . $targetProduct->name }}
            </h3>
            
            @if($settings->show_description && $upsell->description)
                <p style="font-size: 0.875rem; margin: 0; line-height: 1.5; color: rgba(255, 255, 255, 0.95);">{{ $upsell->description }}</p>
            @endif
        </div>
        
        <div style="flex-shrink: 0;">
            <button 
                style="background-color: rgba(255, 255, 255, 0.25); color: white; padding: 0.75rem 1.5rem; border-radius: {{ $borderRadius }}px; font-weight: 600; font-size: 0.9375rem; border: 1px solid rgba(255, 255, 255, 0.35); cursor: pointer; backdrop-filter: blur(4px); white-space: nowrap; transition: all 0.2s;"
                onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.35)';"
                onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.25)';"
            >
                Upgrade Now
            </button>
        </div>
    </div>
</div>
