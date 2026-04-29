@php
    $colorMap = [
        'primary' => 'var(--hpb-color-primary)',
        'primary-20' => 'var(--hpb-color-primary-20)',
        'primary-dark' => 'var(--hpb-color-primary-dark)',
        'secondary' => 'var(--hpb-color-secondary)',
        'secondary-20' => 'var(--hpb-color-secondary-20)',
        'background' => 'var(--hpb-color-background)',
        'text-primary' => 'var(--hpb-color-base)',
        'text-secondary' => 'var(--hpb-color-muted)',
        'base' => 'var(--hpb-color-base)',
        'muted' => 'var(--hpb-color-muted)',
        'neutral' => 'var(--hpb-color-neutral)',
        'inverted' => 'var(--hpb-color-inverted)',
        'dark-primary' => 'var(--hpb-color-dark-primary)',
        'dark-secondary' => 'var(--hpb-color-dark-secondary)',
        'dark-neutral' => 'var(--hpb-color-dark-neutral)',
        'dark-base' => 'var(--hpb-color-dark-base)',
        'dark-muted' => 'var(--hpb-color-dark-muted)',
        'dark-inverted' => 'var(--hpb-color-dark-inverted)',
        'dark-background' => 'var(--hpb-color-dark-background)',
    ];
@endphp

<x-filament::page>
    @vite(['themes/' . config('settings.theme') . '/js/app.js', 'themes/' . config('settings.theme') . '/css/app.css'], config('settings.theme'))
    <div class="w-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <form wire:submit="save" onsubmit="$wire.save(); return false;">
                {{ $this->form }}
                <div class="mt-6">
                    <button style="background: #60A5FA" type="submit" class="w-full hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .dark .overflow-y-auto::-webkit-scrollbar-track {
            background: #374151;
        }
        
        .dark .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        
        .dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</x-filament::page>



