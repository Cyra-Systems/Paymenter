<div>
    @if($buttonStyle === 'gradient')
    <button 
        wire:click="addToCart" 
        style="background-color: rgba(255, 255, 255, 0.25); color: white; padding: 0.75rem 1.5rem; border-radius: {{ $borderRadius }}px; font-weight: 600; font-size: 0.9375rem; border: 1px solid rgba(255, 255, 255, 0.35); cursor: pointer; backdrop-filter: blur(4px); white-space: nowrap; transition: all 0.2s;"
        onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.35)';"
        onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.25)';"
    >
        {{ $autoAdd ? 'Add to Cart' : 'Configure & Add' }}
    </button>
    @else
    <button 
        wire:click="addToCart" 
        style="background-color: {{ $primaryColor }}; color: white; border-radius: {{ $borderRadius }}px;"
        class="px-4 py-2 font-semibold whitespace-nowrap border-none cursor-pointer"
    >
        {{ $autoAdd ? 'Add to Cart' : 'Configure & Add' }}
    </button>
    @endif
</div>

