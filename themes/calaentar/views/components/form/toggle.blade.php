@props([
    'label',
    'id' => 'toggle-' . \Illuminate\Support\Str::random(8),
    'disabled' => false,
])

<label for="{{ $id }}" class="inline-flex items-center gap-3 {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
    <input
        id="{{ $id }}"
        type="checkbox"
        class="peer absolute w-0 h-0 opacity-0 pointer-events-none"
        {{ $attributes->except('disabled') }}
        {{ $disabled ? 'disabled' : '' }}
    >
    <span class="relative inline-block w-11 h-6 rounded-full bg-neutral transition-colors duration-300 peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40">
        <span class="absolute top-0.5 left-0.5 inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 peer-checked:translate-x-5"></span>
    </span>
    @isset($label)
    <span class="text-sm font-medium text-base/80">{{ $label }}</span>
    @endisset
</label>
