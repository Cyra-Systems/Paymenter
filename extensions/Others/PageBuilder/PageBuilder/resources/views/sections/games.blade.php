@props(['variation' => '1', 'data' => []])

@php
    $cards = data_get($data, 'content.cards', []);
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
        'bg-background' => 'var(--hpb-color-bg-background)',
        'color-background' => 'var(--hpb-color-color-background)',
    ];
@endphp

@switch($variation)
    @case('1')
        <section class="relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title'))
                    <div class="text-center space-y-3 mb-8">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-lg text-gray-700 dark:text-gray-300">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($cards as $card)
                        @php $img = data_get($card, 'image'); @endphp
                        <a href="{{ data_get($card, 'link', '#') }}" class="group relative rounded-2xl overflow-hidden block" style="border-radius: var(--hpb-card-radius);">
                            @if($img)
                                <img src="{{ Storage::url($img) }}" alt="{{ data_get($card, 'title', '') }}" class="w-full h-48 md:h-56 object-cover">
                            @else
                                <div class="w-full h-48 md:h-56 bg-gray-200 dark:bg-gray-700"></div>
                            @endif
                            <div class="absolute inset-0" style="background-image: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0.3), rgba(0,0,0,0));"></div>
                            <div class="absolute bottom-0 left-0 p-4 md:p-5">
                                <h3 class="text-white text-lg font-semibold">{!! data_get($card, 'title', 'Game') !!}</h3>
                                @if(data_get($card, 'description'))
                                    <p class="text-white/90 text-sm">{!! data_get($card, 'description') !!}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break
    @case('2')
        <section class="relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title'))
                    <div class="text-center space-y-3 mb-8">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-lg text-gray-700 dark:text-gray-300">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($cards as $card)
                        @php $img = data_get($card, 'image'); @endphp
                        <a href="{{ data_get($card, 'link', '#') }}" class="group block rounded-2xl overflow-hidden border bg-white dark:bg-gray-800 dark:border-gray-700 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="w-full h-40 md:h-48 overflow-hidden">
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($card, 'title', '') }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gray-200 dark:bg-gray-700"></div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="text-gray-900 dark:text-white font-semibold">{!! data_get($card, 'title', 'Game') !!}</h3>
                                @if(data_get($card, 'description'))
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{!! data_get($card, 'description') !!}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break
    @case('3')
        <section class="relative py-12">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title'))
                    <div class="text-center space-y-3 mb-10">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-lg text-gray-700 dark:text-gray-300">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($cards as $card)
                        @php $img = data_get($card, 'image'); @endphp
                        <a href="{{ data_get($card, 'link', '#') }}" class="group block overflow-hidden border page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); background-color: hsl({{ $colorMap['background'] }}); border-color: hsl({{ $colorMap['neutral'] }});">
                            <div class="relative w-full aspect-[4/3] overflow-hidden">
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($card, 'title', '') }}" class="absolute inset-0 w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full bg-gray-200 dark:bg-gray-700"></div>
                                @endif
                                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity" style="background: linear-gradient(to top, hsla({{ $colorMap['primary'] }}, .35), hsla({{ $colorMap['primary'] }}, .0));"></div>
                            </div>
                            <div class="p-5">
                                <h3 class="text-gray-900 dark:text-white font-semibold">{!! data_get($card, 'title', 'Game') !!}</h3>
                                @if(data_get($card, 'description'))
                                    <p class="text-sm mt-1" style="color: hsl({{ $colorMap['text-secondary'] }});">{{ data_get($card, 'description') }}</p>
                                @endif
                                <div class="mt-4 inline-flex items-center text-sm font-medium" style="color: hsl({{ $colorMap['primary'] }});">
                                    {{ __('Learn more') }}
                                    <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break
    @case('4')
        <section class="relative py-12">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title'))
                    <div class="text-center space-y-3 mb-10">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-lg text-gray-700 dark:text-gray-300">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif
                @php $featured = $cards[0] ?? null; $rest = array_slice($cards, 1); @endphp
                <div class="grid md:grid-cols-3 gap-6">
                    @if($featured)
                        @php $img = data_get($featured, 'image'); @endphp
                        <a href="{{ data_get($featured, 'link', '#') }}" class="group relative md:col-span-2 overflow-hidden page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl({{ $colorMap['neutral'] }});">
                            <div class="relative w-full h-64 md:h-full overflow-hidden">
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($featured, 'title', '') }}" class="absolute inset-0 w-full h-full object-cover" />
                                @endif
                                <div class="absolute inset-0" style="background: linear-gradient(to top right, hsla({{ $colorMap['primary'] }}, .45), hsla({{ $colorMap['primary'] }}, 0));"></div>
                                <div class="absolute bottom-0 left-0 p-6">
                                    <h3 class="text-white text-2xl font-semibold">{{ data_get($featured, 'title', 'Featured Game') }}</h3>
                                    @if(data_get($featured, 'description'))
                                        <p class="text-white/90 text-sm mt-1">{{ data_get($featured, 'description') }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endif
                    <div class="grid grid-cols-1 gap-6">
                        @foreach($rest as $card)
                            @php $img = data_get($card, 'image'); @endphp
                            <a href="{{ data_get($card, 'link', '#') }}" class="group flex overflow-hidden border page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); background-color: hsl({{ $colorMap['background'] }}); border-color: hsl({{ $colorMap['neutral'] }});">
                                <div class="w-28 shrink-0 aspect-square overflow-hidden">
                                    @if($img)
                                        <img src="{{ Storage::url($img) }}" alt="{{ data_get($card, 'title', '') }}" class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full bg-gray-200 dark:bg-gray-700"></div>
                                    @endif
                                </div>
                                <div class="p-4 flex-1">
                                    <h4 class="text-gray-900 dark:text-white font-semibold">{{ data_get($card, 'title', 'Game') }}</h4>
                                    @if(data_get($card, 'description'))
                                        <p class="text-sm mt-1" style="color: hsl({{ $colorMap['text-secondary'] }});">{!! data_get($card, 'description') !!}</p>
                                    @endif
                                    <div class="mt-3 inline-flex items-center text-sm font-medium" style="color: hsl({{ $colorMap['primary'] }});">
                                        {{ __('View') }}
                                        <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('5')
        <section class="relative py-12">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title'))
                    <div class="text-center space-y-3 mb-10">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-lg text-gray-700 dark:text-gray-300">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif
                <div class="space-y-6">
                    @foreach($cards as $card)
                        @php $img = data_get($card, 'image'); @endphp
                        <a href="{{ data_get($card, 'link', '#') }}" class="group relative block overflow-hidden page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl({{ $colorMap['neutral'] }});">
                            <div class="relative w-full h-64 md:h-80 overflow-hidden">
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($card, 'title', '') }}" class="absolute inset-0 w-full h-full object-cover" />
                                @endif
                                <div class="absolute inset-0" style="background: linear-gradient(to top right, hsla({{ $colorMap['primary'] }}, .45), hsla({{ $colorMap['primary'] }}, 0));"></div>
                                <div class="absolute bottom-0 left-0 p-6">
                                    <h3 class="text-white text-2xl font-semibold">{{ data_get($card, 'title', 'Featured Game') }}</h3>
                                    @if(data_get($card, 'description'))
                                        <p class="text-white/90 text-sm mt-1">{{ data_get($card, 'description') }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break
    @default
        <section></section>
@endswitch


