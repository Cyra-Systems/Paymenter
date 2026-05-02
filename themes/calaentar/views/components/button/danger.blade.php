<x-button.primary {{ $attributes->merge(['class' => 'bg-red-600 bg-none shadow-[0_0_24px_-4px_rgba(220,38,38,0.6)] text-white py-2 px-5 rounded-full hover:bg-red-500 hover:brightness-110'])}}>
    {{ $slot }}
</x-button.primary>