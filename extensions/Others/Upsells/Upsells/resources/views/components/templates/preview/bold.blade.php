@props(['upsell', 'settings', 'targetProduct', 'primaryColor', 'bgColor' => 'hsl(240, 18%, 9%)', 'bgSecondary' => 'hsl(240, 13%, 11%)', 'textColor' => 'hsl(0, 0%, 100%)', 'mutedColor' => 'hsl(220, 14%, 60%)', 'borderColor' => 'hsl(0, 0%, 20%)'])

@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div class="preview-bold" style="background-color: {{ $bgSecondary }}; border-radius: {{ $borderRadius }}px; padding: 1.25rem; margin-bottom: 1rem; border: 1px solid {{ $borderColor }}; border-left: 4px solid {{ $primaryColor }}; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
    <div style="flex: 1;">
        @if($settings->show_label && $upsell->label)
            <span style="color: {{ $primaryColor }}; display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">{{ $upsell->label }}</span>
        @endif
        
        <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 0.375rem 0; color: {{ $textColor }}; line-height: 1.4;">
            {{ $upsell->title ?: 'Upgrade to ' . $targetProduct->name }}
        </h3>
        
        @if($settings->show_description && $upsell->description)
            <p style="font-size: 0.875rem; margin: 0; line-height: 1.5; color: {{ $mutedColor }};">{{ $upsell->description }}</p>
        @endif
    </div>
    
    <div style="display: flex; align-items: center;">
        <button 
            style="background-color: {{ $primaryColor }}; color: white; padding: 0.75rem 1.5rem; border-radius: {{ $borderRadius }}px; font-weight: 500; font-size: 0.875rem; border: none; cursor: pointer; transition: opacity 0.2s; white-space: nowrap;"
            onmouseover="this.style.opacity='0.9'"
            onmouseout="this.style.opacity='1'"
        >
            Upgrade Now
        </button>
    </div>
</div>
