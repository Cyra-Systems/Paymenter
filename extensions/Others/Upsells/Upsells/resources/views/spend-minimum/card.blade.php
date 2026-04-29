@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div style="background-color: {{ $bgSecondary }}; border: 1px solid {{ $isUnlocked ? $primaryColor : $borderColor }}; border-radius: {{ $borderRadius }}px; padding: 1.5rem; margin-bottom: 1rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="flex: 1; min-width: 0;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0 0 1rem 0; color: {{ $textColor }}; line-height: 1.3;">
                {{ $title }}
            </h3>
            
            <div style="padding: 0.75rem; background-color: {{ $primaryColor }}11; border-left: 3px solid {{ $primaryColor }}; border-radius: {{ $borderRadius }}px;">
                <span style="font-size: 0.875rem; color: {{ $textColor }}; font-weight: 500; line-height: 1.5;">
                    {{ $description }}
                </span>
            </div>
        </div>
        
        @if($isUnlocked)
            <div style="flex-shrink: 0;">
                <button 
                    wire:click="claimProduct"
                    style="background-color: {{ $primaryColor }}; color: white; padding: 0.875rem 1.75rem; border-radius: {{ $borderRadius }}px; font-weight: 600; font-size: 0.9375rem; border: none; cursor: pointer; white-space: nowrap; transition: opacity 0.2s;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'"
                >
                    Claim Free Product
                </button>
            </div>
        @endif
    </div>
</div>

