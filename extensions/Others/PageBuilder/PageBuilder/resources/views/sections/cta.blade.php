@php
    $data = $data ?? [];
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
    ];
    $buttons = (array) data_get($data, 'content.buttons', []);
    $image = data_get($data, 'content.image');
    $imageAlt = data_get($data, 'content.image_alt', 'CTA image');
    $renderButtons = function(array $buttons) use ($colorMap) {
        foreach ($buttons as $b) {
            $label = data_get($b, 'label');
            if (!$label) { continue; }
            $link = data_get($b, 'link', '#');
            $variant = data_get($b, 'variant', 'primary');
            $classes = 'inline-flex items-center justify-center px-5 py-3 rounded-lg text-sm font-semibold';
            if ($variant === 'primary') {
                echo '<a href="' . e($link) . '" class="' . $classes . ' text-white" style="background-color: hsl(' . $colorMap['primary'] . '); border-radius: var(--hpb-card-radius);">' . $label . '</a>';
            } elseif ($variant === 'secondary') {
                echo '<a href="' . e($link) . '" class="' . $classes . '" style="background-color: hsla(' . $colorMap['primary'] . ', .12); color: hsl(' . $colorMap['primary'] . '); border-radius: var(--hpb-card-radius);">' . $label . '</a>';
            } elseif ($variant === 'outline') {
                echo '<a href="' . e($link) . '" class="' . $classes . ' border-1" style="border-color: hsl(' . $colorMap['primary'] . '); color: hsl(' . $colorMap['primary'] . '); border-radius: var(--hpb-card-radius);">' . $label . '</a>';
            } else {
                echo '<a href="' . e($link) . '" class="inline-flex items-center justify-center text-sm font-semibold" style="color: hsl(' . $colorMap['primary'] . ');">' . $label . '</a>';
            }
        }
    };
@endphp

@switch($variation)
    @case('1')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Need more help?') !!}</h2>
                    @if(data_get($data, 'content.subtitle'))
                        <p class="text-gray-700 dark:text-gray-200 max-w-2xl mx-auto">{!! data_get($data, 'content.subtitle') !!}</p>
                    @endif
                    <div class="flex items-center justify-center gap-3">
                        {!! $renderButtons($buttons) !!}
                    </div>
                    @if(data_get($data, 'content.note'))
                        <div class="text-xs text-gray-500 dark:text-gray-400">{!! data_get($data, 'content.note') !!}</div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('2')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Still unsure?') !!}</h2>
                    <p class="text-gray-700 dark:text-gray-200 max-w-2xl mx-auto">{!! data_get($data, 'content.subtitle', 'Contact our team and we’ll help you choose the right solution.') !!}</p>
                    <div class="flex items-center justify-center gap-3 flex-wrap">
                        {!! $renderButtons($buttons ?: [
                            ['label' => 'Contact sales', 'link' => '#', 'variant' => 'primary'],
                            ['label' => 'Documentation', 'link' => '#', 'variant' => 'link'],
                        ]) !!}
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('3')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-1 rounded-2xl px-6 py-5 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                    <div class="max-w-2xl">
                        <h3 class="text-xl md:text-2xl font-semibold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Need more help?') !!}</h3>
                        @if(data_get($data, 'content.subtitle'))
                            <p class="text-gray-700 dark:text-gray-200 mt-1">{!! data_get($data, 'content.subtitle') !!}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        {!! $renderButtons($buttons ?: [['label' => 'Contact us', 'link' => '#', 'variant' => 'primary']]) !!}
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('4')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="max-w-4xl mx-auto border-1 rounded-3xl p-8 page-builder__card card-gradient text-center" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'We’re here to help') !!}</h3>
                    @if(data_get($data, 'content.subtitle'))
                        <p class="text-gray-700 dark:text-gray-200 mt-3">{!! data_get($data, 'content.subtitle') !!}</p>
                    @endif
                    <div class="mt-6 flex items-center justify-center gap-3 flex-wrap">
                        {!! $renderButtons($buttons) !!}
                    </div>
                    @if(data_get($data, 'content.note'))
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-4">{!! data_get($data, 'content.note') !!}</div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('5')
        <section class="py-14 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="grid md:grid-cols-2 gap-8 items-center">
                    @if($image)
                        <div>
                            <img src="{{ Storage::url($image) }}" alt="{{ $imageAlt }}" class="w-full h-72 object-cover rounded-2xl" style="border-radius: var(--hpb-card-radius);" />
                        </div>
                    @endif
                    <div class="space-y-4">
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Talk to our team') !!}</h3>
                        @if(data_get($data, 'content.subtitle'))
                            <p class="text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle') !!}</p>
                        @endif
                        <div class="flex items-center gap-3 flex-wrap">
                            {!! $renderButtons($buttons) !!}
                        </div>
                        @if(data_get($data, 'content.note'))
                            <div class="text-xs text-gray-500 dark:text-gray-400">{!! data_get($data, 'content.note') !!}</div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('6')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="relative grid md:grid-cols-[1fr_auto] items-start gap-6 border-1 rounded-2xl p-6 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                    <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl" style="background-color: hsl({{ $colorMap['primary'] }});"></div>
                    <div class="md:pl-3">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'How can we help?') !!}</h3>
                            @if(data_get($data, 'content.subtitle'))
                                <p class="text-gray-700 dark:text-gray-200 mt-2">{!! data_get($data, 'content.subtitle') !!}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        {!! $renderButtons($buttons ?: [['label' => 'Open a ticket', 'link' => '#', 'variant' => 'primary'], ['label' => 'Live chat', 'link' => '#', 'variant' => 'outline']]) !!}
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('7')
        <section class="py-8 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="h-px" style="background-color: hsl({{ $colorMap['neutral'] }});"></div>
                <div class="px-0 py-5">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Have questions?') !!}</h3>
                            @if(data_get($data, 'content.subtitle'))
                                <p class="text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle') !!}</p>
                            @endif
                        </div>
                        <div class="shrink-0">
                            {!! $renderButtons($buttons ?: [['label' => 'Read the docs', 'link' => '#', 'variant' => 'link']]) !!}
                        </div>
                    </div>
                </div>
                <div class="h-px" style="background-color: hsl({{ $colorMap['neutral'] }});"></div>
            </div>
        </section>
        @break
    @case('8')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="border-1 px-6 py-6 page-builder__card card-gradient" style="background-color: hsla({{ $colorMap['primary'] }}, .06); border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        @if($image)
                            <div class="w-16 h-16 rounded-xl overflow-hidden flex items-center justify-center bg-white/70">
                                <img src="{{ Storage::url($image) }}" alt="{{ $imageAlt }}" class="w-full h-full object-cover" />
                            </div>
                        @endif
                        <div class="flex-1">
                            <h3 class="text-xl md:text-2xl font-semibold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Ready to get started?') !!}</h3>
                            @if(data_get($data, 'content.subtitle'))
                                <p class="text-gray-700 dark:text-gray-200 mt-1">{!! data_get($data, 'content.subtitle') !!}</p>
                            @endif
                        </div>
                        <div class="shrink-0 flex items-center gap-3">
                            {!! $renderButtons($buttons ?: [['label' => 'Contact us', 'link' => '#', 'variant' => 'primary']]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('9')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[900px] px-4">
                <div class="text-center space-y-3">
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Need more information?') !!}</h3>
                    @if(data_get($data, 'content.subtitle'))
                        <p class="text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.subtitle') !!}</p>
                    @endif
                    @if(!empty($buttons))
                        <div class="flex items-center justify-center gap-3">
                            {!! $renderButtons($buttons) !!}
                        </div>
                    @endif
                    @if(data_get($data, 'content.note'))
                        <div class="text-xs text-gray-500 dark:text-gray-400">{!! data_get($data, 'content.note') !!}</div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('10')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="max-w-3xl">
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Let’s talk solutions') !!}</h3>
                    @if(data_get($data, 'content.subtitle'))
                        <p class="text-gray-700 dark:text-gray-200 mt-2">{!! data_get($data, 'content.subtitle') !!}</p>
                    @endif
                    @if(!empty($buttons))
                        <div class="mt-5 flex items-center gap-3 flex-wrap">
                            {!! $renderButtons($buttons) !!}
                        </div>
                    @endif
                    @if(data_get($data, 'content.note'))
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-3">{!! data_get($data, 'content.note') !!}</div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('11')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center px-6 py-10 rounded-3xl" style="background-color: hsl({{ $colorMap['primary'] }});">
                    <h3 class="text-2xl md:text-3xl font-bold text-white">{!! data_get($data, 'content.title', 'We’re ready when you are') !!}</h3>
                    @if(data_get($data, 'content.subtitle'))
                        <p class="text-white/90 max-w-2xl mx-auto mt-2">{!! data_get($data, 'content.subtitle') !!}</p>
                    @endif
                    @if(!empty($buttons))
                        <div class="mt-6 flex items-center justify-center gap-3 flex-wrap">
                            @foreach($buttons as $b)
                                @php $label = data_get($b, 'label'); @endphp
                                @if($label)
                                    <a href="{{ data_get($b, 'link', '#') }}" class="inline-flex items-center justify-center px-5 py-3 rounded-lg text-sm font-semibold" style="background-color: #ffffff; color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $label !!}</a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if(data_get($data, 'content.note'))
                        <div class="text-xs text-white/80 mt-3">{!! data_get($data, 'content.note') !!}</div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('12')
        <section class="py-6 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="flex items-center justify-between gap-4 rounded-xl px-5 py-4 border-1" style="border-color: hsl({{ $colorMap['neutral'] }});">
                    <div class="min-w-0">
                        <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white truncate">{!! data_get($data, 'content.title', 'Quick question?') !!}</h3>
                        @if(data_get($data, 'content.subtitle'))
                            <p class="text-sm text-gray-700 dark:text-gray-200 truncate">{!! data_get($data, 'content.subtitle') !!}</p>
                        @endif
                    </div>
                    <div class="shrink-0">
                        {!! $renderButtons($buttons ?: [['label' => 'Get in touch', 'link' => '#contact', 'variant' => 'link']]) !!}
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('13')
        <section class="py-12 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="grid md:grid-cols-[1fr_auto] items-center gap-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Let’s build something great') !!}</h3>
                        @if(data_get($data, 'content.subtitle'))
                            <p class="text-gray-700 dark:text-gray-200 mt-2">{!! data_get($data, 'content.subtitle') !!}</p>
                        @endif
                        @if(!empty($buttons))
                            <div class="mt-5 flex items-center gap-3 flex-wrap">
                                {!! $renderButtons($buttons) !!}
                            </div>
                        @endif
                    </div>
                    @if($image)
                        <div class="w-24 h-24 rounded-2xl overflow-hidden border-1" style="border-color: hsl({{ $colorMap['neutral'] }});">
                            <img src="{{ Storage::url($image) }}" alt="{{ $imageAlt }}" class="w-full h-full object-cover" />
                        </div>
                    @endif
                </div>
                @if(data_get($data, 'content.note'))
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-4">{!! data_get($data, 'content.note') !!}</div>
                @endif
            </div>
        </section>
        @break
    @default
        <section class="py-8 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">CTA Section</h3>
            </div>
        </section>
@endswitch


