<x-filament-panels::page>
    <div class="border-b border-gray-200 dark:border-white/10">
        <nav class="flex -mb-px space-x-8" aria-label="Tabs">
            <button
                wire:click="$set('activeTab', 'installed')"
                @class([
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                    'border-primary-500 text-primary-600' => $activeTab === 'installed',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600' => $activeTab !== 'installed',
                ])>
                Installed Themes
            </button>
            <button
                wire:click="$set('activeTab', 'marketplace')"
                @class([
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                    'border-primary-500 text-primary-600' => $activeTab === 'marketplace',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600' => $activeTab !== 'marketplace',
                ])>
                Browse Marketplace
            </button>
        </nav>
    </div>

    @if ($activeTab === 'installed')
        <div class="mt-4">
            {{ $this->table }}
        </div>
    @else
        <div class="mt-4">
            <div class="relative mb-4">
                <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
                    <x-ri-search-line class="w-5 h-5 text-gray-400" />
                </div>
                <input type="search" placeholder="Search themes..." wire:model.live.debounce.500ms="search" class="block w-full p-3 border-gray-300 rounded-lg shadow-sm ps-10 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-primary-500 focus:border-primary-500">
            </div>

            @if($this->marketplaceThemes->isEmpty())
                <div class="p-4 text-center text-gray-500 bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">No themes available</h3>
                    <p class="mt-2">Configure a Marketplace Manifest URL in Settings, then click "Resync Marketplace".</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->marketplaceThemes as $theme)
                        <x-theme-card :theme="$theme" :key="$theme['id']" />
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
