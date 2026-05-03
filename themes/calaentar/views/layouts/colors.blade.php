@php
    $hsl = fn ($v) => str_replace(',', '', preg_replace('/^hsl\((.+)\)$/', '$1', $v));
@endphp
<style>
    :root {
        /* Brand colors */
        --color-primary: {{ $hsl(theme('primary', 'hsl(320 90% 60%)')) }};
        --color-secondary: {{ $hsl(theme('secondary', 'hsl(270 70% 55%)')) }};
        --color-accent: {{ $hsl(theme('accent', 'hsl(290 95% 70%)')) }};

        /* Surface tones */
        --color-neutral: {{ $hsl(theme('neutral', 'hsl(260 20% 22%)')) }};
        --color-background: {{ $hsl(theme('background', 'hsl(260 35% 6%)')) }};
        --color-background-secondary: {{ $hsl(theme('background-secondary', 'hsl(260 30% 10%)')) }};

        /* Text tones */
        --color-base: {{ $hsl(theme('base', 'hsl(260 20% 96%)')) }};
        --color-muted: {{ $hsl(theme('muted', 'hsl(260 15% 65%)')) }};
        --color-inverted: {{ $hsl(theme('inverted', 'hsl(260 30% 10%)')) }};

        /* State colors */
        --color-success: 142 71% 45%;
        --color-error: 0 75% 60%;
        --color-warning: 25 95% 53%;
        --color-inactive: 0 0% 63%;
        --color-info: 210 100% 60%;

        /* Ambient gradient stops + accent glow */
        --gradient-from: {{ $hsl(theme('gradient-from', 'hsl(320 80% 35%)')) }};
        --gradient-via:  {{ $hsl(theme('gradient-via',  'hsl(280 70% 25%)')) }};
        --gradient-to:   {{ $hsl(theme('gradient-to',   'hsl(260 35% 6%)')) }};
        --gradient-angle: {{ theme('gradient-angle', '135') }}deg;
        --glow-color: {{ $hsl(theme('glow-color', 'hsl(320 90% 60%)')) }};

        /* Border-radius scale */
        --radius-sm: {{ theme('radius-sm', '0.5rem') }};
        --radius-md: {{ theme('radius-md', '0.75rem') }};
        --radius-lg: {{ theme('radius-lg', '1rem') }};
        --radius-xl: {{ theme('radius-xl', '1.5rem') }};

        /* Glass-morphism */
        --glass-blur: {{ theme('glass-blur', '14px') }};
        --glass-opacity: {{ theme('glass-opacity', '0.75') }};
        --glass-border-opacity: {{ theme('glass-border-opacity', '0.12') }};
    }
</style>
