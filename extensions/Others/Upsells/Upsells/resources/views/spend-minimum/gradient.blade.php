@php
    $borderRadius = $settings->border_radius ?? 8;
    $gradientStart = $isUnlocked ? 'hsl(160, 60%, 45%)' : 'hsl(270, 60%, 55%)';
    $gradientEnd = $isUnlocked ? 'hsl(180, 60%, 45%)' : 'hsl(240, 60%, 55%)';
@endphp

<div style="background: linear-gradient(135deg, {{ $gradientStart }} 0%, {{ $gradientEnd }} 100%); border-radius: {{ $borderRadius * 2 }}px; padding: 1.2rem; margin-bottom: 1rem; box-shadow: 0 8px 24px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background-color: rgba(255,255,255,0.1); border-radius: 50%; filter: blur(40px);"></div>
    <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background-color: rgba(255,255,255,0.08); border-radius: 50%; filter: blur(30px);"></div>
    
    <div style="position: relative; z-index: 1;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 2rem;">
            <div style="flex: 1; min-width: 0;">
                <h3 style="font-size: 1.475rem; font-weight: 700; margin: 0 0 0.75rem 0; color: white; line-height: 1.2; text-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                    {{ $title }}
                </h3>
                
                <p style="font-size: 0.96rem; margin: 0 0 1.25rem 0; line-height: 1.5; color: rgba(255, 255, 255, 0.95); text-shadow: 0 1px 4px rgba(0,0,0,0.15);">
                    {{ $description }}
                </p>
                
                @if(!$isUnlocked)
                    <div style="background-color: rgba(255, 255, 255, 0.2); border-radius: 100px; height: 14px; overflow: hidden; backdrop-filter: blur(10px); box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="height: 100%; width: {{ $progressPercentage }}%; background: linear-gradient(to right, rgba(255,255,255,0.6), rgba(255,255,255,0.4)); border-radius: 100px; transition: width 0.5s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                        <span style="font-size: 0.875rem; color: rgba(255,255,255,0.9); font-weight: 600;">${{ number_format($cartTotal, 2) }}</span>
                        <span style="font-size: 0.875rem; color: rgba(255,255,255,0.9); font-weight: 600;">${{ number_format($upsell->minimum_spend, 2) }}</span>
                    </div>
                @endif
            </div>
            
            @if($isUnlocked)
                <div style="flex-shrink: 0;">
                    <button 
                        wire:click="claimProduct"
                        style="background-color: rgba(255, 255, 255, 0.95); color: {{ $gradientStart }}; padding: 1rem 2rem; border-radius: {{ $borderRadius }}px; font-weight: 700; font-size: 1rem; border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s; box-shadow: 0 4px 16px rgba(0,0,0,0.2);"
                        onmouseover="this.style.backgroundColor='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.25)'"
                        onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.95)'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.2)'"
                    >
                        Claim Free Product
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

