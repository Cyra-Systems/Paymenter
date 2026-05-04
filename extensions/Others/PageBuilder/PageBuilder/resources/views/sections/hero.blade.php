@props(['variation' => '1', 'data' => []])

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
        'bg-background' => 'var(--hpb-color-bg-background)',
        'color-background' => 'var(--hpb-color-color-background)',
    ];
@endphp

@switch($variation)
    @case('1')
        <section class="py-15 relative">
            <div class="relative z-10 container mx-auto px-4 py-15 text-center">
                <h1 
                    class="text-5xl md:text-6xl font-bold mb-6 text-gray-900 dark:text-white"
                >
                    {!! data_get($data, 'content.title', 'Enterprise-Grade Hosting Solutions') !!}
                </h1>

                <p 
                    class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto text-gray-700 dark:text-gray-200"
                >
                    {!! data_get($data, 'content.subtitle', 'Premium VPS, dedicated servers, and colocation services for businesses that demand reliability and performance') !!}
                </p>

                @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                @if($pl || $sl)
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($pl)
                    <a href="{{ data_get($data, 'content.primary_link', '#get-started') }}" 
                       class="inline-flex items-center px-8 py-4 text-white font-semibold rounded-lg"
                       style="background-color: hsl({{ $colorMap['primary'] }});"
                    >
                        {!! $pl !!}
                    </a>
                    @endif

                    @if($sl)
                    <a href="{{ data_get($data, 'content.secondary_link', '#learn-more') }}" 
                       class="inline-flex items-center px-8 py-4 font-semibold rounded-lg border-1"
                       style="border-color: hsl({{ $colorMap['primary'] }}); color: hsl({{ $colorMap['primary'] }});"
                    >
                        {!! $sl !!}
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </section>
        @break
    @case('9')
        <section class="py-15 flex items-center px-4">
            <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-1 lg:order-none">
                    <div class="rounded-2xl p-8 page-builder__card" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                        <div class="text-5xl md:text-7xl font-extrabold tracking-tight" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($data, 'content.stat_value', '99.99%') !!}</div>
                        <div class="mt-2 text-lg text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.stat_caption', 'Uptime') !!}</div>
                    </div>
                </div>
                <div class="space-y-6">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Scale without limits') !!}</h1>
                    <p class="text-lg leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'High-performance infrastructure for modern applications.') !!}</p>
                    @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                    @if($pl || $sl)
                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($pl)
                        <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                        @endif
                        @if($sl)
                        <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('10')
        <section class="py-15 flex items-center px-4">
            <div class="max-w-6xl mx-auto space-y-10">
                <div class="text-center space-y-4">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Built for performance') !!}</h1>
                    <p class="text-lg max-w-2xl mx-auto leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'Deliver exceptional user experiences with our global infrastructure.') !!}</p>
                </div>
                @php $stats = data_get($data, 'content.stats', []); @endphp
                @if(!empty($stats))
                <div class="flex flex-wrap justify-center gap-4">
                    @foreach($stats as $s)
                    <div class="rounded-2xl p-6 page-builder__card text-center min-w-[180px]" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                        <div class="text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($s, 'value', '') !!}</div>
                        <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{!! data_get($s, 'label', '') !!}</div>
                        @if(data_get($s, 'description'))
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{!! data_get($s, 'description') !!}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
                @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                @if($pl || $sl)
                <div class="text-center">
                    @if($pl)
                    <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg text-white text-lg" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                    @endif
                    @if($sl)
                    <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg ml-3" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                    @endif
                </div>
                @endif
            </div>
        </section>
        @break
    @case('12')
        <section class="py-15 px-4">
            <div class="max-w-5xl mx-auto text-center space-y-6">
                <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Edge-to-edge simplicity') !!}</h1>
                <p class="text-lg max-w-2xl mx-auto leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'A clean hero with nothing but your message and action.') !!}</p>
                @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                @if($pl || $sl)
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($pl)
                    <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg text-white text-lg" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                    @endif
                    @if($sl)
                    <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg border-1 bg-transparent text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600" style="border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                    @endif
                </div>
                @endif
                @php $badges = data_get($data, 'content.badges', []); @endphp
                @if(!empty($badges))
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    @foreach($badges as $i => $b)
                        @if($i>0)<span class="mx-2">•</span>@endif
                        <span>{{ data_get($b, 'text', '') }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @break
    @case('13')
        <section class="py-0">
            <div class="w-full">
                <div class="px-4 py-12 text-center">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Focus on action') !!}</h1>
                    <p class="mt-4 text-lg max-w-2xl mx-auto leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'Keep your hero clean with CTAs highlighted below.') !!}</p>
                </div>
                <div class="px-4 py-6" style="background: hsl(var(--hpb-color-background)); border-top: 1px solid hsl(var(--hpb-color-neutral)); border-bottom: 1px solid hsl(var(--hpb-color-neutral));">
                    <div class="max-w-4xl mx-auto flex flex-col sm:flex-row gap-4 justify-center">
                        @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                        @if($pl)
                        <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg text-white text-lg" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                        @endif
                        @if($sl)
                        <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg border-1 bg-transparent text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600" style="border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('2')
        <section class="py-15 flex items-center justify-center px-4">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <h1 class="text-5xl md:text-6xl font-bold tracking-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Deploy with confidence.') !!}<span class="block" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($data, 'content.subtitle_strong', 'Scale without limits.') !!}</span></h1>
                <p class="text-xl max-w-2xl mx-auto leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'Enterprise infrastructure that grows with your business. From VPS to dedicated servers, we provide the performance and reliability you need.') !!}</p>
                @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                @if($pl || $sl)
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($pl)
                    <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center text-lg px-8 py-3 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!} <svg class="ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg></a>
                    @endif
                    @if($sl)
                    <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center text-lg px-8 py-3 rounded-lg border-1 bg-transparent text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600" style="border-radius: var(--hpb-card-radius);"><svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>{!! $sl !!}</a>
                    @endif
                </div>
                @endif
                @php $badges = data_get($data, 'content.badges', []); @endphp
                @if(!empty($badges))
                <div class="flex items-center justify-center gap-8 pt-8 opacity-60 text-gray-600 dark:text-gray-400">
                    @foreach($badges as $b)
                        <div class="text-sm font-medium">{!! data_get($b, 'text', '') !!}</div>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @break
    @case('3')
        <section class="py-15 flex items-center px-4">
            <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
                <div class="space-y-8">
                    <div class="space-y-6">
                        <h1 class="text-3xl md:text-4xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Infrastructure that powers') !!}<span class="block" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($data, 'content.subtitle_strong', 'the modern web') !!}</span></h1>
                        <p class="text-lg leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'High-performance servers with enterprise-grade hardware, global data centers, and 24/7 expert support to keep your applications running smoothly.') !!}</p>
                    </div>
                    @php $bullets = data_get($data, 'content.bullets', []); @endphp
                    @if(!empty($bullets))
                    <div class="space-y-3">
                        @foreach($bullets as $bl)
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span class="text-base text-gray-700 dark:text-gray-200">{{ data_get($bl, 'text', '') }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                    @if($pl || $sl)
                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($pl)
                        <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                        @endif
                        @if($sl)
                        <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="relative">
                    @php $img = data_get($data, 'content.image'); @endphp
                    @if($img)
                        <img src="{{ Storage::url($img) }}" alt="{{ data_get($data, 'content.image_alt', 'Hero image') }}" class="w-full h-96 object-cover rounded-2xl" style="border-radius: var(--hpb-card-radius);" />
                    @else
                        <div class="w-full h-96 bg-gray-200 dark:bg-gray-700 rounded-2xl"></div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('8')
        <section class="py-15 px-4">
            <div class="max-w-6xl mx-auto grid lg:grid-cols-1 gap-16">
                <div class="space-y-8 lg:col-span-2">
                    <div class="space-y-2">
                        <h1 class="text-3xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Infrastructure that powers') !!}<span class="block" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($data, 'content.subtitle_strong', 'the modern web') !!}</span></h1>
                        <p class="text-lg leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'High-performance servers with enterprise-grade hardware, global data centers, and 24/7 expert support to keep your applications running smoothly.') !!}</p>
                    </div>
                    @php $bullets = data_get($data, 'content.bullets', []); @endphp
                    @if(!empty($bullets))
                    <div class="space-y-3">
                        @foreach($bullets as $bl)
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span class="text-base text-gray-700 dark:text-gray-200">{{ data_get($bl, 'text', '') }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                    @if($pl || $sl)
                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($pl)
                        <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                        @endif
                        @if($sl)
                        <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('4')
        <section class="py-15 flex items-center px-4">
            <div class="max-w-5xl mx-auto text-center space-y-8">
                <h1 class="text-5xl md:text-6xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Enterprise-grade infrastructure') !!}<span class="block" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($data, 'content.subtitle_strong', 'for mission-critical applications') !!}</span></h1>
                <p class="text-xl leading-relaxed max-w-3xl mx-auto text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'Deploy with confidence using our enterprise-grade security features, compliance-ready infrastructure, and global network of data centers.') !!}</p>
                @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                @if($pl || $sl)
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($pl)
                    <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                    @endif
                    @if($sl)
                    <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border-1 bg-transparent text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                    @endif
                </div>
                @endif
                @php $badges = data_get($data, 'content.badges', []); @endphp
                @if(!empty($badges))
                <div class="flex items-center justify-center gap-2 pt-8 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                    @foreach($badges as $i => $b)
                        @if($i>0)<div>•</div>@endif
                        <div>{{ data_get($b, 'text', '') }}</div>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @break
    @case('5')
        <section class="py-15 flex items-center px-4">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16 space-y-6">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Everything you need to deploy and scale') !!}</h1>
                    <p class="text-xl max-w-2xl mx-auto leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', "From VPS to dedicated servers, colocation to managed hosting - we provide the infrastructure and tools your team needs to succeed.") !!}</p>
                </div>
                @php $cards = data_get($data, 'content.feature_cards', []); @endphp
                @if(!empty($cards))
                <div class="grid md:grid-cols-3 gap-6 mb-12">
                    @foreach($cards as $card)
                        <div class="rounded-xl p-6 shadow-sm border page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); background-color: hsl(var(--hpb-color-primary)); box-shadow: var(--hpb-card-shadow);">
                            <div class="rounded-lg w-12 h-12 flex items-center justify-center mb-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                <i class="fa-solid {{ data_get($card, 'icon', 'fa-bolt') }}" style="color: white;"></i>
                            </div>
                            <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">{{ data_get($card, 'title', 'Feature') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ data_get($card, 'description', '') }}</p>
                        </div>
                    @endforeach
                </div>
                @endif
                @php $pl = trim((string) data_get($data, 'content.primary_label')); @endphp
                <div class="text-center">
                    @if($pl)
                    <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-lg text-white text-lg" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                    @endif
                    @if(data_get($data, 'content.footnote_text'))
                        <p class="text-sm mt-3 text-gray-600 dark:text-gray-300">{!! data_get($data, 'content.footnote_text') !!}</p>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('6')
        <section class="py-15">
            <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 gap-16 items-center">
                <div class="space-y-8">
                    <div class="space-y-6">
                        <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Monitor everything.') !!}<span class="block" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($data, 'content.subtitle_strong', 'Optimize performance.') !!}</span></h1>
                        <p class="text-lg leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'Real-time server monitoring, performance analytics, and automated optimization tools that help you deliver exceptional user experiences.') !!}</p>
                    </div>
                    @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                    @if($pl || $sl)
                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($pl)
                        <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                        @endif
                        @if($sl)
                        <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border-1 bg-transparent text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="relative">
                    <div class="rounded-2xl p-6 bg-white dark:bg-gray-800 borderdark:border-gray-700  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{!! data_get($data, 'content.panel_title', 'Live Performance') !!}</h3>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full animate-pulse bg-green-500"></div>
                                    <span class="text-xs text-gray-600 dark:text-gray-300">{!! data_get($data, 'content.panel_realtime_label', 'Real-time') !!}</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                @foreach(data_get($data, 'content.panel_metrics', []) as $metric)
                                <div class="rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                                    <div class="text-2xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{{ data_get($metric, 'value', '') }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ data_get($metric, 'label', '') }}</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('7')
        <section class="py-15 flex items-center px-4">
            <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white">{!! data_get($data, 'content.title', 'Build faster with our platform') !!}</h1>
                    <p class="text-lg leading-relaxed text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.subtitle', 'Launch projects quickly using our reliable infrastructure and simple tooling.') !!}</p>
                    @php $pl = trim((string) data_get($data, 'content.primary_label')); $sl = trim((string) data_get($data, 'content.secondary_label')); @endphp
                    @if($pl || $sl)
                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($pl)
                        <a href="{{ data_get($data, 'content.primary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pl !!}</a>
                        @endif
                        @if($sl)
                        <a href="{{ data_get($data, 'content.secondary_link', '#') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border-1 bg-transparent text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600" style="color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $sl !!}</a>
                        @endif
                    </div>
                    @endif
                </div>
                @php $img = data_get($data, 'content.image'); @endphp
                @if($img)
                <div class="relative">
                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($data, 'content.image_alt', 'Hero image') }}" class="w-full h-96 object-cover rounded-2xl" style="border-radius: var(--hpb-card-radius);" />
                </div>
                @endif
            </div>
        </section>
        @break
    @case('14')
        @php
            // Full-screen hero with positionable content. Autoplays a video
            // on page load (muted + looped — no click-to-play, browsers
            // require muted for autoplay). Falls back to a still image, then
            // to the brand gradient when no media is configured.
            //
            // content keys:
            //   title, subtitle, primary_label, primary_link,
            //   secondary_label, secondary_link
            //   video_url        — mp4 / webm
            //   image_url        — still backdrop, also used as <video poster>
            //                       while the video buffers
            //   image_position   — object-position for the image
            //                       (e.g. 'center', 'top', '50% 30%')
            //   fullscreen       — bool; min-h-screen vs min-h-[70vh]
            //   content_position — center / top-* / bottom-* / center-*
            //   overlay_opacity  — 0..1 dark overlay over the media
            $title = data_get($data, 'content.title', 'Build Something Beautiful');
            $subtitle = data_get($data, 'content.subtitle', 'Use this space to talk about what makes your product different.');
            $pl = trim((string) data_get($data, 'content.primary_label', 'Get started'));
            $sl = trim((string) data_get($data, 'content.secondary_label', 'Learn more'));
            $primaryLink = data_get($data, 'content.primary_link', '#');
            $secondaryLink = data_get($data, 'content.secondary_link', '#');

            // Accept full URLs ("https://…", "//cdn.example.com/…") as-is,
            // and resolve bare paths ("/storage/heroes/foo.mp4" or
            // "storage/heroes/foo.mp4") against the app's public root.
            $resolveMediaUrl = function ($url) {
                $url = trim((string) $url);
                if ($url === '') return null;
                if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:')) return $url;
                return asset(ltrim($url, '/'));
            };

            $videoUrl = $resolveMediaUrl(data_get($data, 'content.video_url'));
            $imageUrl = $resolveMediaUrl(data_get($data, 'content.image_url'));
            $imagePosition = data_get($data, 'content.image_position', 'center');

            $fullscreen = (bool) data_get($data, 'content.fullscreen', true);
            $position = data_get($data, 'content.content_position', 'bottom-center');
            $overlay = (float) data_get($data, 'content.overlay_opacity', 0.5);

            $heightClass = $fullscreen ? 'min-h-screen' : 'min-h-[70vh]';

            $positionMap = [
                'center'        => 'items-center justify-center text-center',
                'top-center'    => 'items-start justify-center text-center pt-20',
                'top-left'      => 'items-start justify-start text-left pt-20 pl-4 md:pl-16',
                'top-right'     => 'items-start justify-end text-right pt-20 pr-4 md:pr-16',
                'center-left'   => 'items-center justify-start text-left pl-4 md:pl-16',
                'center-right'  => 'items-center justify-end text-right pr-4 md:pr-16',
                'bottom-center' => 'items-end justify-center text-center pb-20',
                'bottom-left'   => 'items-end justify-start text-left pb-20 pl-4 md:pl-16',
                'bottom-right'  => 'items-end justify-end text-right pb-20 pr-4 md:pr-16',
            ];
            $posClasses = $positionMap[$position] ?? $positionMap['bottom-center'];
        @endphp

        <section class="relative w-full {{ $heightClass }} overflow-hidden">
            @if ($videoUrl)
                <video
                    class="absolute inset-0 w-full h-full object-cover"
                    autoplay muted loop playsinline preload="auto"
                    @if ($imageUrl) poster="{{ $imageUrl }}" @endif
                >
                    <source src="{{ $videoUrl }}">
                </video>
            @elseif ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt=""
                    class="absolute inset-0 w-full h-full object-cover"
                    style="object-position: {{ $imagePosition }};"
                    loading="eager"
                    decoding="async"
                >
            @else
                <div class="absolute inset-0"
                    style="background-image: linear-gradient({{ data_get($data, 'content.gradient_angle', '135deg') }}, hsl({{ $colorMap['primary'] }}) 0%, hsl({{ $colorMap['secondary'] }}) 100%);">
                </div>
            @endif

            <div class="absolute inset-0"
                style="background:
                    linear-gradient(to bottom, hsl({{ $colorMap['background'] }} / 0) 0%, hsl({{ $colorMap['background'] }} / {{ $overlay }}) 100%),
                    radial-gradient(ellipse 80% 60% at 15% 0%, hsl({{ $colorMap['primary'] }} / 0.20), transparent 60%);">
            </div>

            <div class="relative z-10 w-full h-full flex {{ $posClasses }} container mx-auto px-4">
                <div class="max-w-3xl">
                    <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight"
                        style="color: hsl({{ $colorMap['base'] }});">
                        {!! $title !!}
                    </h1>
                    <p class="text-xl md:text-2xl mb-8"
                        style="color: hsl({{ $colorMap['muted'] }});">
                        {!! $subtitle !!}
                    </p>
                    @if ($pl || $sl)
                    <div class="flex flex-col sm:flex-row gap-4 {{ str_contains($position, 'right') ? 'sm:justify-end' : (str_contains($position, 'center') ? 'sm:justify-center' : '') }}">
                        @if ($pl)
                        <a href="{{ $primaryLink }}"
                            class="inline-flex items-center justify-center px-8 py-4 text-white font-semibold rounded-full transition hover:brightness-110"
                            style="background-image: linear-gradient(135deg, hsl({{ $colorMap['primary'] }}) 0%, hsl({{ $colorMap['secondary'] }}) 100%);
                                   box-shadow: 0 0 24px -4px hsl({{ $colorMap['primary'] }} / 0.55);">
                            {!! $pl !!}
                        </a>
                        @endif
                        @if ($sl)
                        <a href="{{ $secondaryLink }}"
                            class="inline-flex items-center justify-center px-8 py-4 font-semibold rounded-full backdrop-blur-md border transition hover:bg-white/10"
                            style="background-color: hsl({{ $colorMap['background'] }} / 0.4);
                                   border-color: hsl({{ $colorMap['base'] }} / 0.15);
                                   color: hsl({{ $colorMap['base'] }});">
                            {!! $sl !!}
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @break

    @default
        <section class="py-15 relative">
            <div class="relative z-10 container mx-auto px-4 py-15 text-center">
                <h1
                    class="text-5xl md:text-6xl font-bold mb-6 text-gray-900 dark:text-white"
                >
                    Enterprise-Grade Hosting Solutions
                </h1>

                <p 
                    class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto text-gray-700 dark:text-gray-200"
                >
                    Premium VPS, dedicated servers, and colocation services for businesses that demand reliability and performance
                </p>

                @php $pl = 'View Plans'; $sl = 'Read me'; @endphp
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($pl)
                    <a href="#get-started" 
                       class="inline-flex items-center px-8 py-4 text-white font-semibold rounded-lg"
                       style="background-color: hsl({{ $colorMap['primary'] }});"
                    >
                        {{ $pl }}
                    </a>
                    @endif

                    @if($sl)
                    <a href="#learn-more" 
                       class="inline-flex items-center px-8 py-4 font-semibold rounded-lg border-1"
                       style="border-color: hsl({{ $colorMap['primary'] }}); color: hsl({{ $colorMap['primary'] }});"
                    >
                        {{ $sl }}
                    </a>
                    @endif
                </div>
            </div>
        </section>
@endswitch
