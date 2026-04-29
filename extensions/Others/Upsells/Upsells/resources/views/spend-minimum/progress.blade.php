@php
    $borderRadius = $settings->border_radius ?? 8;
    
    $colorToRgba = function($color, $opacity = 1) {
        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $color, $matches)) {
            return "rgba({$matches[1]}, {$matches[2]}, {$matches[3]}, {$opacity})";
        }
        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $color, $matches)) {
            $r = hexdec(substr($color, 1, 2));
            $g = hexdec(substr($color, 3, 2));
            $b = hexdec(substr($color, 5, 2));
            return "rgba($r, $g, $b, $opacity)";
        }
        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $color, $matches)) {
            $r = hexdec($matches[1][0] . $matches[1][0]);
            $g = hexdec($matches[1][1] . $matches[1][1]);
            $b = hexdec($matches[1][2] . $matches[1][2]);
            return "rgba($r, $g, $b, $opacity)";
        }
        return $color;
    };
    
    $primaryColorRgba = $colorToRgba($primaryColor, 0.87);
    $primaryColorRgbaLight = $colorToRgba($primaryColor, 0.27);
    $primaryColorRgbaMedium = $colorToRgba($primaryColor, 0.4);
@endphp

<div style="background-color: {{ $bgSecondary }}; border-radius: {{ $borderRadius }}px; padding: 1.25rem; margin-bottom: 1rem;">
    <div style="display: flex; align-items: center; gap: 1.25rem;">
        <div style="flex: 1; min-width: 0;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem 0; color: {{ $textColor }}; line-height: 1.3;">
                {{ $title }}
            </h3>
            
            <p style="font-size: 0.875rem; margin: 0 0 0.875rem 0; line-height: 1.5; color: {{ $mutedColor }};">
                {{ $description }}
            </p>
            
            <div style="display: flex; align-items: center; gap: 0.875rem;">
                <div style="flex: 1; height: 10px; background-color: {{ $bgColor ?? 'hsl(var(--color-background))' }}; border-radius: 100px; overflow: hidden; position: relative;">
                    <div style="height: 100%; width: {{ $progressPercentage }}%; background: linear-gradient(to right, {{ $primaryColor }}, {{ $primaryColorRgba }}); border-radius: 100px; transition: width 0.5s ease; box-shadow: 0 2px 8px {{ $primaryColorRgbaLight }};"></div>
                </div>
                <div style="min-width: 70px; text-align: right;">
                    <span style="font-size: 1rem; font-weight: 700">{{ number_format($progressPercentage, 0) }}%</span>
                </div>
            </div>
        </div>
        
        @if($isUnlocked)
            <div style="flex-shrink: 0;">
                <button 
                    wire:click="claimProduct"
                    style="background-color: {{ $primaryColor }}; color: white; padding: 0.75rem 1.5rem; border-radius: {{ $borderRadius }}px; font-weight: 600; font-size: 0.9375rem; border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s; box-shadow: 0 4px 12px {{ $primaryColorRgbaLight }};"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px {{ $primaryColorRgbaMedium }}'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px {{ $primaryColorRgbaLight }}'"
                >
                    Claim Free Product
                </button>
            </div>
        @endif
    </div>
</div>

