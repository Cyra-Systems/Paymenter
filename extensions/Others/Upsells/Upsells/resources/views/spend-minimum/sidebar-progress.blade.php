@props(['upsell', 'settings', 'primaryColor', 'bgColor', 'bgSecondary', 'textColor', 'mutedColor', 'borderColor', 'title', 'description', 'progressPercentage', 'isUnlocked', 'totalLeft', 'cartTotal'])

@php
    $borderRadius = $settings->border_radius ?? 8;
    
    $makeBold = function($text) {
        $text = preg_replace('/\$[\d,]+\.?\d*/', '<strong>$0</strong>', $text);
        $text = preg_replace('/\b\d+(\.\d+)?\b/', '<strong>$0</strong>', $text);
        return $text;
    };
    
    $boldDescription = $makeBold($description);
@endphp

<div style="background-color: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: {{ $borderRadius }}px; padding: 1rem; margin-bottom: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
        <svg style="width: 1.5rem; height: 1.5rem; color: {{ $primaryColor }}; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <h4 style="font-size: 0.875rem; font-weight: 600; margin: 0; color: {{ $textColor }}; line-height: 1.3; flex: 1;">
            {!! $title !!}
        </h4>
    </div>

    @if(!$isUnlocked)
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
            <div style="flex: 1; height: 6px; background-color: {{ $bgColor }}; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; background: linear-gradient(90deg, {{ $primaryColor }}, {{ $primaryColor }}dd); width: {{ $progressPercentage }}%; transition: width 0.3s ease;"></div>
            </div>
            <span style="font-size: 0.75rem; color: {{ $textColor }}; font-weight: 600; white-space: nowrap;">
                ${{ number_format($upsell->minimum_spend, 2) }}
            </span>
        </div>
        
        <p style="font-size: 0.75rem; margin: 0; line-height: 1.4; color: {{ $mutedColor }};">
            {!! $boldDescription !!}
        </p>
    @else
        <button 
            wire:click="claimProduct"
            style="width: 100%; background-color: {{ $primaryColor }}; color: white; border: none; padding: 0.625rem; border-radius: {{ $borderRadius }}px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s; margin-top: 0.75rem;"
            onmouseover="this.style.opacity='0.9';"
            onmouseout="this.style.opacity='1';"
        >
            Claim Now
        </button>
        
        <p style="font-size: 0.75rem; margin: 0.75rem 0 0 0; line-height: 1.4; color: {{ $mutedColor }}; text-align: center;">
            {!! $boldDescription !!}
        </p>
    @endif
</div>

