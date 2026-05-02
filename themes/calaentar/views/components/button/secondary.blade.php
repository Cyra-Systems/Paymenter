<button 
    {{ $attributes->merge(['class' => 'flex items-center gap-2 justify-center glass text-base text-sm font-semibold hover:bg-background-secondary/70 py-2.5 lg:py-2 px-5 rounded-full w-full duration-300 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50']) }}>
    @if (isset($type) && $type === 'submit')
        <div role="status" wire:loading>
            <x-ri-loader-5-fill aria-hidden="true" class="size-6 me-2 fill-background animate-spin" />
            <span class="sr-only">Loading...</span>
        </div>
        <div wire:loading.remove>
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</button>
