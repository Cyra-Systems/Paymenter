<div>
    @if(isset($hpbCssVariables) && $hpbCssVariables)
        <style>{!! $hpbCssVariables !!}</style>
    @endif
    <style>
        .page-builder__card {
            background: hsl(var(--hpb-color-background)) !important;
            border: 1px solid hsl(var(--hpb-color-neutral));
            box-shadow: var(--hpb-card-shadow);
            border-radius: var(--hpb-card-radius);
        }

        @prefers-color-scheme: dark {
            .page-builder__card {
                background: hsl(var(--hpb-color-background)) !important;
                border: 1px solid hsl(var(--hpb-color-neutral));
                box-shadow: var(--hpb-card-shadow);
                border-radius: var(--hpb-card-radius);
            }
        }
        section { padding-top: var(--hpb-section-spacing) !important; padding-bottom: var(--hpb-section-spacing) !important; }
        .container, .max-w-\[1320px\], .max-w-\[1200px\], .max-w-\[1280px\] { max-width: var(--hpb-container-size) !important; }
    </style>
    <style>
        .hpb-transition-none { opacity: 1; transform: translateX(0) translateY(0); animation: none; }
        .hpb-transition-fade-in { opacity: 0; animation: hpb-fade-in 700ms ease-out forwards; }
        .hpb-transition-fade-right { opacity: 0; transform: translateX(16px); animation: hpb-slide-right 700ms ease-out forwards; }
        .hpb-transition-fade-left { opacity: 0; transform: translateX(-16px); animation: hpb-slide-left 700ms ease-out forwards; }
        .hpb-transition-fade-top { opacity: 0; transform: translateY(-16px); animation: hpb-slide-top 700ms ease-out forwards; }
        .hpb-transition-fade-bottom { opacity: 0; transform: translateY(16px); animation: hpb-slide-bottom 700ms ease-out forwards; }

        @keyframes hpb-fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes hpb-slide-right { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes hpb-slide-left { from { opacity: 0; transform: translateX(-16px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes hpb-slide-top { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes hpb-slide-bottom { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    @php $transitionClass = $colors['section_transition_class'] ?? 'hpb-transition-none'; @endphp
    @foreach(($sections ?? []) as $section)
        @php
            $type = $section['type'] ?? '';
            $data = $section['data'] ?? [];
            $base = $type;
            $variation = '1';
            if (is_string($type)) {
                if (preg_match('/^(.*)_([0-9]+)$/', $type, $m)) {
                    $base = $m[1];
                    $variation = $m[2];
                }
            }
        @endphp
        <div class="{{ $transitionClass }}" style="animation-delay: {{ ($loop->index ?? 0) * 200 }}ms;">
            @includeIf("pagebuilder::sections.$base", ['variation' => $variation, 'data' => $data])
        </div>
    @endforeach
</div>


