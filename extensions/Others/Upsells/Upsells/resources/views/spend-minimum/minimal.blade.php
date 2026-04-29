@php
    $borderRadius = $settings->border_radius ?? 8;
@endphp

<div style="padding: 1rem; border-bottom: 1px solid {{ $borderColor }}; margin-bottom: 0.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="flex: 1; min-width: 0;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.375rem;">
                @if($isUnlocked)
                    <svg style="width: 1.25rem; height: 1.25rem; color: {{ $primaryColor }};" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg style="width: 1.25rem; height: 1.25rem; color: {{ $primaryColor }};" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @endif
            </div>
            
            <h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.25rem 0; color: {{ $textColor }}; line-height: 1.4;">
                {{ $title }}
            </h4>
            
            <p style="font-size: 0.875rem; margin: 0; line-height: 1.4; color: {{ $mutedColor }};">
                {{ $description }}
            </p>
        </div>
        
        @if($isUnlocked)
            <div style="flex-shrink: 0;">
                <button 
                    wire:click="claimProduct"
                    style="background-color: {{ $primaryColor }}; color: white; padding: 0.625rem 1.25rem; border-radius: {{ $borderRadius }}px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; white-space: nowrap; transition: opacity 0.2s;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'"
                >
                    Claim
                </button>
            </div>
        @else
            <div style="text-align: right; flex-shrink: 0;">
                <span style="font-size: 0.8125rem; color: {{ $mutedColor }}; display: block;">{{ number_format($progressPercentage, 0) }}% complete</span>
                <span style="font-size: 0.9375rem; font-weight: 700; color: {{ $primaryColor }};">${{ number_format($totalLeft, 2) }} left</span>
            </div>
        @endif
    </div>
</div>

