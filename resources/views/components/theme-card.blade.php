@props(['theme'])

@php
    $name = $theme['meta']['name'] ?? $theme['name'];
    $author = $theme['meta']['author'] ?? '';
    $description = $theme['meta']['description'] ?? '';
    $version = $theme['meta']['version'] ?? null;
    $icon = $theme['meta']['icon'] ?? null;
    $signed = !empty($theme['signature']);
    $requireSig = config('settings.marketplace_require_signature', true);
@endphp

<div class="flex flex-col overflow-hidden transition-all duration-300 bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg hover:border-primary-500 dark:hover:border-primary-500">
    <div class="relative flex items-center justify-center h-32 bg-gray-100 dark:bg-gray-900">
        @if($icon && (str_starts_with($icon, 'data:') || str_starts_with($icon, 'http')))
            <img src="{{ $icon }}" alt="{{ $name }}" class="object-contain w-20 h-20">
        @else
            <x-ri-palette-fill class="w-16 h-16 text-gray-400" />
        @endif
        @if($signed)
            <span class="absolute px-2 py-1 text-xs font-semibold text-white rounded-full top-2 left-2 bg-green-600" title="Signature available">
                <x-ri-shield-check-fill class="inline w-3 h-3" /> Signed
            </span>
        @elseif(!$requireSig)
            <span class="absolute px-2 py-1 text-xs font-semibold text-white rounded-full top-2 left-2 bg-yellow-600">
                <x-ri-error-warning-fill class="inline w-3 h-3" /> Unsigned
            </span>
        @endif
    </div>

    <div class="flex flex-col flex-grow p-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $name }}</h3>
        @if($author)
            <p class="text-sm text-gray-500 dark:text-gray-400">By {{ $author }}{{ $version ? ' · v' . $version : '' }}</p>
        @endif
        <p class="flex-grow mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-3">{{ $description }}</p>

        <div class="flex items-center justify-between pt-3 mt-4 border-t border-gray-200 dark:border-gray-700">
            <button
                wire:click="downloadAndInstallTheme({{ $theme['id'] }})"
                wire:loading.attr="disabled"
                wire:target="downloadAndInstallTheme({{ $theme['id'] }})"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-50">
                <x-ri-download-2-line wire:loading.remove wire:target="downloadAndInstallTheme({{ $theme['id'] }})" class="w-4 h-4" />
                <x-filament::loading-indicator wire:loading wire:target="downloadAndInstallTheme({{ $theme['id'] }})" class="w-4 h-4" />
                Download & Install
            </button>
        </div>
    </div>
</div>
