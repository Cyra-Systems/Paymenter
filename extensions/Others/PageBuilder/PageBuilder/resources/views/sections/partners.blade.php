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
        'bg-background' => 'var(--hpb-color-bg-background)',
        'color-background' => 'var(--hpb-color-color-background)',
    ];
@endphp

@switch($variation)
    @case('1')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title') || data_get($data, 'content.section_description'))
                    <div class="text-center space-y-4 mb-12 py-4">
                        @if(data_get($data, 'content.section_title'))
                            <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @endif
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif

                @php 
                    $partners = data_get($data, 'content.partners', data_get($data, 'partners', []));
                    $blackWhiteMode = data_get($data, 'content.black_white_mode', data_get($data, 'black_white_mode', false));
                @endphp
                
                @if(count($partners) > 0)
                    <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
                        @foreach ($partners as $partner)
                            @php $img = data_get($partner, 'image'); @endphp
                            @if($img)
                                <div class="flex items-center justify-center" style="height: 2rem; width: auto;">
                                    <img 
                                        src="{{ Storage::url($img) }}" 
                                        alt="{{ data_get($partner, 'alt', 'Partner logo') }}" 
                                        class="w-auto object-contain {{ $blackWhiteMode ? 'grayscale hover:grayscale-0 transition-all duration-300' : '' }}"
                                        style="height: 2rem;"
                                        style="filter: {{ $blackWhiteMode ? 'grayscale(100%)' : 'none' }};"
                                    />
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No partners added yet. Add some partner logos in the admin panel.</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('2')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title') || data_get($data, 'content.section_description'))
                    <div class="text-center space-y-4 mb-12 py-4">
                        @if(data_get($data, 'content.section_title'))
                            <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @endif
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif

                @php 
                    $partners = data_get($data, 'content.partners', data_get($data, 'partners', []));
                    $blackWhiteMode = data_get($data, 'content.black_white_mode', data_get($data, 'black_white_mode', false));
                @endphp
                @if(count($partners) > 0)
                    <div class="grid sm:grid-cols-2 gap-6">
                        @foreach ($partners as $partner)
                            @php $img = data_get($partner, 'image'); @endphp
                            <div class="flex items-center gap-4 p-5 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl({{ $colorMap['neutral'] }}); background-color: hsl({{ $colorMap['background'] }});">
                                <div class="w-16 h-16 shrink-0 rounded-lg overflow-hidden flex items-center justify-center">
                                    @if($img)
                                        <img src="{{ Storage::url($img) }}" alt="{{ data_get($partner, 'alt', 'Partner logo') }}" class="w-full h-full object-contain {{ $blackWhiteMode ? 'grayscale hover:grayscale-0 transition-all duration-300' : '' }}" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{!! data_get($partner, 'name', data_get($partner, 'alt', '')) !!}</h3>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No partners added yet. Add some partner logos in the admin panel.</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('3')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title') || data_get($data, 'content.section_description'))
                    <div class="text-center space-y-4 mb-12 py-4">
                        @if(data_get($data, 'content.section_title'))
                            <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @endif
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif

                @php 
                    $partners = data_get($data, 'content.partners', data_get($data, 'partners', []));
                    $blackWhiteMode = data_get($data, 'content.black_white_mode', data_get($data, 'black_white_mode', false));
                @endphp
                @if(count($partners) > 0)
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach ($partners as $partner)
                            @php $img = data_get($partner, 'image'); @endphp
                            <div class="flex items-start gap-4 p-5 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl({{ $colorMap['neutral'] }}); background-color: hsl({{ $colorMap['background'] }});">
                                <div class="w-16 h-16 shrink-0 rounded-lg overflow-hidden flex items-center justify-center">
                                    @if($img)
                                        <img src="{{ Storage::url($img) }}" alt="{{ data_get($partner, 'alt', 'Partner logo') }}" class="w-full h-full object-contain {{ $blackWhiteMode ? 'grayscale hover:grayscale-0 transition-all duration-300' : '' }}" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ data_get($partner, 'name', data_get($partner, 'alt', '')) }}</h3>
                                    @if(data_get($partner, 'description'))
                                        <p class="text-sm mt-1" style="color: hsl({{ $colorMap['text-secondary'] }});">{!! data_get($partner, 'description') !!}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No partners added yet. Add some partner logos in the admin panel.</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('4')
        <section class="py-10 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                @if(data_get($data, 'content.section_title') || data_get($data, 'content.section_description'))
                    <div class="text-center space-y-4 mb-12 py-4">
                        @if(data_get($data, 'content.section_title'))
                            <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @endif
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif

                @php 
                    $partners = data_get($data, 'content.partners', data_get($data, 'partners', []));
                    $blackWhiteMode = data_get($data, 'content.black_white_mode', data_get($data, 'black_white_mode', false));
                @endphp
                @if(count($partners) > 0)
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-8">
                        @foreach ($partners as $partner)
                            @php $img = data_get($partner, 'image'); @endphp
                            <div class="flex items-start gap-4">
                                <div class="w-16 h-16 shrink-0 rounded-lg overflow-hidden flex items-center justify-center">
                                    @if($img)
                                        <img src="{{ Storage::url($img) }}" alt="{{ data_get($partner, 'alt', 'Partner logo') }}" class="w-full h-full object-contain {{ $blackWhiteMode ? 'grayscale hover:grayscale-0 transition-all duration-300' : '' }}" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ data_get($partner, 'name', data_get($partner, 'alt', '')) }}</h3>
                                    @if(data_get($partner, 'description'))
                                        <p class="text-sm mt-1" style="color: hsl({{ $colorMap['text-secondary'] }});">{{ data_get($partner, 'description') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No partners added yet. Add some partner logos in the admin panel.</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Partners Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a partners variation in the admin panel to customise this section.
                </p>
            </div>
        </section>
@endswitch
