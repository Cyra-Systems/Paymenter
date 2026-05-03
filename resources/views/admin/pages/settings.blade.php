<x-filament-panels::page>
    <div class="cal-settings-shell">
        {{ $this->form }}
    </div>

    @push('styles')
    <style>
        /* Calaentar — wrap the auto-generated form in our glass card so the
           Settings page card matches the rest of the admin surfaces. The
           inner .fi-fo-component-ctn (Filament's form wrapper) gets its
           glass background reset since this outer shell now provides it. */
        .cal-settings-shell {
            background-color: hsl(var(--color-background-secondary) / var(--glass-opacity, 0.75)) !important;
            backdrop-filter: blur(var(--glass-blur, 14px)) saturate(140%);
            -webkit-backdrop-filter: blur(var(--glass-blur, 14px)) saturate(140%);
            border: 1px solid hsl(var(--color-base) / var(--glass-border-opacity, 0.12));
            border-radius: var(--radius-lg, 1rem);
            padding: 1.5rem;
            box-shadow:
                0 1px 0 hsl(var(--color-base) / 0.06) inset,
                0 1px 3px hsl(0 0% 0% / 0.25),
                0 12px 32px -12px hsl(0 0% 0% / 0.35);
        }
        .cal-settings-shell > form,
        .cal-settings-shell .fi-fo-component-ctn,
        .cal-settings-shell .fi-form,
        .cal-settings-shell .fi-fo-form {
            background-color: transparent !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border: 0 !important;
            box-shadow: none !important;
        }
    </style>
    @endpush
</x-filament-panels::page>
