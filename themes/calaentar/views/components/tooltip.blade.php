<div x-data="{ open: false }" class="relative inline-block">
    <div aria-describedby="tooltip" class="underline decoration-dotted cursor-help"
        @mouseover="open = true" @mouseout="open = false">
        {{ $slot }}
    </div>
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 text-base text-sm w-max max-w-xs px-2 py-1 glass-card z-10 pointer-events-none"
        role="tooltip">
        {{ $message }}
        <div class="absolute w-2 h-2 rotate-45 -bottom-1 left-1/2 -translate-x-1/2 glass"></div>
    </div>
</div>
