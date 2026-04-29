<div class="container mt-14">
    <div>
        <div class="flex flex-row items-center pb-4">
            <span class="text-2xl font-bold">{{ $service->product->name }}</span>
        </div>

        <div class="mb-10 mt-3 flex flex-row gap-2">
            {{-- Overview tab: always shown --}}
            <div class="flex flex-col items-center">
                <a href="{{ route('pelican.overview', ['service' => $service->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'overview' ? 'text-base' : 'text-base/70' }}">
                    Overview
                </a>
                @if($view === 'overview')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>

            {{-- Console tab: only shown if product has show_console enabled --}}
            @if(!empty($settings['show_console']))
            <div class="flex flex-col items-center">
                <a href="{{ route('pelican.console', ['service' => $service->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'console' ? 'text-base' : 'text-base/70' }}">
                    Console
                </a>
                @if($view === 'console')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{ $slot }}
</div>
