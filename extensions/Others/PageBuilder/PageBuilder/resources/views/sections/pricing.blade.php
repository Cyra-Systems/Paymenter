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
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Simple, transparent pricing that scales with you') !!}</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php $plans = data_get($data, 'content.items', []); @endphp
                    @foreach ($plans as $idx => $plan)
                        <div class="border-1 rounded-2xl p-6 flex flex-col hover:-translate-y-1 transition-transform duration-200 hover:shadow-lg  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            @php $img = data_get($plan, 'image'); @endphp
                            @if($img)
                                <img src="{{ Storage::url($img) }}" alt="{{ data_get($plan, 'title', 'Plan') }}" class="h-16 w-auto max-w-full object-contain mb-4 block self-start" style="border-radius: var(--hpb-card-radius);" />
                            @endif
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                            <div class="mt-2 text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}<span class="text-base text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</span></div>
                            <ul class="mt-6 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                                @foreach (data_get($plan, 'features', []) as $f)
                                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                @endforeach
                            </ul>
                            <div class="mt-6">
                                <a href="{{ data_get($plan, 'cta_link', '#') }}" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose plan') !!}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('2')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Choose the plan that fits your needs') !!}</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php $plans = data_get($data, 'content.items', []); @endphp
                    @foreach ($plans as $idx => $plan)
                        <div class="border-1rounded-2xl p-6 flex flex-col relative {{ $idx === 1 ? 'lg:scale-105 lg:shadow-xl' : '' }} hover:-translate-y-1 transition-transform duration-200  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            @if(data_get($plan, 'badge'))
                                <span class="absolute -top-3 right-4 text-xs font-semibold px-2 py-1 rounded-full text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{{ data_get($plan, 'badge') }}</span>
                            @endif
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{!! data_get($plan, 'title', 'Plan') !!}</div>
                            <div class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">{!! data_get($plan, 'price', '$5') !!}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', 'per month') !!}</div>
                            <div class="mt-4 h-px" style="background-color: hsl({{ $colorMap['neutral'] }});"></div>
                            <ul class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                @foreach (data_get($plan, 'features', []) as $f)
                                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                @endforeach
                            </ul>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-6 inline-flex items-center justify-center px-4 py-2 rounded-lg {{ $idx === 1 ? 'text-white' : '' }}" style="border-radius: var(--hpb-card-radius); {{ $idx === 1 ? 'background-color: hsl(' . $colorMap['primary'] . ');' : 'border-color: hsl(' . $colorMap['primary'] . '); color: hsl(' . $colorMap['primary'] . ');' }}">{!! data_get($plan, 'cta_label', $idx === 1 ? 'Get started' : 'Get started') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('3')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Flexible options for every stage of growth') !!}</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php $plans = data_get($data, 'content.items', []); @endphp
                    @foreach ($plans as $idx => $plan)
                        <div class="border-1rounded-2xl p-6 flex flex-col  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                                @if(data_get($plan, 'badge'))
                                    <span class="text-xs px-2 py-1 rounded-full" style="background-color: hsl({{ $colorMap['primary'] }}); color: white;">{{ data_get($plan, 'badge') }}</span>
                                @endif
                            </div>
                            <div class="mt-3 text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$12') !!}<span class="text-base text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</span></div>
                            <ul class="mt-6 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                                @foreach (data_get($plan, 'features', []) as $f)
                                    <li>{!! data_get($f, 'text', '') !!}</li>
                                @endforeach
                            </ul>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-6 inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Select') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('4')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Compare all features side by side') !!}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="p-4 text-left"></th>
                                @php $plans = data_get($data, 'content.items', []); @endphp
                                @foreach ($plans as $plan)
                                    <th class="p-4 text-center">
                                        <div class="font-semibold text-lg text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</div>
                                        @if(data_get($plan, 'badge'))
                                            <span class="text-xs px-2 py-1 rounded-full mt-2 inline-block" style="background-color: hsl({{ $colorMap['primary'] }}); color: white;">{{ data_get($plan, 'badge') }}</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="p-4 text-left"></th>
                                @foreach ($plans as $plan)
                                    <th class="p-4 text-center">
                                        <div class="text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $allFeatures = [];
                                foreach ($plans as $plan) {
                                    foreach (data_get($plan, 'features', []) as $feature) {
                                        $featureText = data_get($feature, 'text', '');
                                        if (!in_array($featureText, $allFeatures)) {
                                            $allFeatures[] = $featureText;
                                        }
                                    }
                                }
                            @endphp
                            @foreach ($allFeatures as $feature)
                                <tr class="border-t">
                                    <td class="p-4 text-sm text-gray-700 dark:text-gray-200">{!! $feature !!}</td>
                                    @foreach ($plans as $plan)
                                        <td class="p-4 text-center">
                                            @php
                                                $hasFeature = false;
                                                foreach (data_get($plan, 'features', []) as $planFeature) {
                                                    if (data_get($planFeature, 'text', '') === $feature) {
                                                        $hasFeature = true;
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            @if($hasFeature)
                                                <i class="fa-solid fa-check text-green-500"></i>
                                            @else
                                                <i class="fa-solid fa-times text-gray-400"></i>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="border-t">
                                <td class="p-4"></td>
                                @foreach ($plans as $plan)
                                    <td class="p-4 text-center">
                                        <a href="{{ data_get($plan, 'cta_link', '#') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose plan') !!}</a>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        @break

    @case('5')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Start your journey and grow with us') !!}</p>
                </div>

                <div class="relative max-w-4xl mx-auto">
                    <div class="absolute left-8 top-0 bottom-0 w-0.5" style="background-color: hsl({{ $colorMap['primary'] }});"></div>
                    @php $plans = data_get($data, 'content.items', []); @endphp
                    @foreach ($plans as $idx => $plan)
                        <div class="relative flex items-start mb-12 last:mb-0">
                            <div class="flex-shrink-0 w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-lg" style="background-color: hsl({{ $colorMap['primary'] }});">
                                {{ $idx + 1 }}
                            </div>
                            <div class="ml-8 flex-1">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                                        @if(data_get($plan, 'badge'))
                                            <span class="text-xs px-2 py-1 rounded-full mt-2 inline-block" style="background-color: hsl({{ $colorMap['primary'] }}); color: white;">{{ data_get($plan, 'badge') }}</span>
                                        @endif
                                        <div class="mt-2 text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}<span class="text-base text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</span></div>
                                    </div>
                                    <div class="mt-4 lg:mt-0 lg:ml-8 lg:w-80">
                                        <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                            @foreach (data_get($plan, 'features', []) as $f)
                                                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                            @endforeach
                                        </ul>
                                        <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-4 inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Get started') !!}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('7')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Click to explore each plan') !!}</p>
                </div>

                <div class="max-w-2xl mx-auto space-y-4">
                    @php $plans = data_get($data, 'content.items', []); @endphp
                    @foreach ($plans as $idx => $plan)
                        <div class="border-1 rounded-2xl page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <button class="rounded-lg w-full p-6 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" onclick="toggleAccordion({{ $idx }})">
                                <div class="flex items-center gap-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                                    @if(data_get($plan, 'badge'))
                                        <span class="text-xs px-2 py-1 rounded-full" style="background-color: hsl({{ $colorMap['primary'] }}); color: white;">{{ data_get($plan, 'badge') }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-2xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{{ data_get($plan, 'price', '$9') }}<span class="text-sm text-gray-600 dark:text-gray-300">{{ data_get($plan, 'period', '/mo') }}</span></div>
                                    <i class="fa-solid fa-chevron-down transition-transform duration-200" id="chevron-{{ $idx }}"></i>
                                </div>
                            </button>
                            <div class="px-6 pb-6 pt-4 hidden" id="content-{{ $idx }}">
                                <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-200 mb-4">
                                    @foreach (data_get($plan, 'features', []) as $f)
                                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                    @endforeach
                                </ul>
                                <a href="{{ data_get($plan, 'cta_link', '#') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose plan') !!}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <script>
                function toggleAccordion(index) {
                    const content = document.getElementById('content-' + index);
                    const chevron = document.getElementById('chevron-' + index);
                    
                    if (content.classList.contains('hidden')) {
                        content.classList.remove('hidden');
                        chevron.style.transform = 'rotate(180deg)';
                    } else {
                        content.classList.add('hidden');
                        chevron.style.transform = 'rotate(0deg)';
                    }
                }
            </script>
        </section>
        @break

    @case('8')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-10 py-8">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Choose the perfect plan for your needs') !!}</p>
                </div>
                @php $plans = data_get($data, 'content.items', []); @endphp
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($plans as $idx => $plan)
                        <div class="border-1 rounded-2xl p-6 hover:-translate-y-1 transition-transform duration-200 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                                @if(data_get($plan, 'badge'))
                                    <span class="text-xs px-2 py-1 rounded-full" style="background-color: hsl({{ $colorMap['primary'] }}); color: white;">{{ data_get($plan, 'badge') }}</span>
                                @endif
                            </div>
                            <div class="text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}<span class="text-base text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</span></div>
                            <ul class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-200 min-h-[96px]">
                                @foreach (array_slice((array) data_get($plan, 'features', []), 0, 6) as $f)
                                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                @endforeach
                            </ul>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-6 w-full inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose plan') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('9')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1200px] px-4">
                <div class="text-center space-y-3 mb-10">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Plans') !!}</h2>
                    <p class="text-lg text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.section_description', 'Choose a plan that fits your needs') !!}</p>
                </div>
                @php $plans = data_get($data, 'content.items', []); @endphp
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($plans as $idx => $plan)
                        <div class="rounded-2xl p-6 border-1 hover:-translate-y-1 transition-transform page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="text-sm text-gray-600 dark:text-gray-300">{!! data_get($plan, 'title', 'Plan') !!}</div>
                            <div class="mt-2 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">{!! data_get($plan, 'price', '$9') !!}<span class="text-sm text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</span></div>
                            <ul class="mt-5 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                @foreach (array_slice((array) data_get($plan, 'features', []), 0, 5) as $f)
                                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                @endforeach
                            </ul>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-6 w-full inline-flex items-center justify-center px-4 py-2 rounded-lg border-1" style="border-color: hsl({{ $colorMap['primary'] }}); color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('10')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1100px] px-4">
                <div class="text-center mb-10">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Pricing') !!}</h2>
                </div>
                @php $plans = data_get($data, 'content.items', []); @endphp
                <div class="space-y-4">
                    @foreach ($plans as $plan)
                        <div class="relative flex items-start gap-6 border-1 rounded-2xl p-6 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl" style="background-color: hsl({{ $colorMap['primary'] }});"></div>
                            <div class="flex-1 md:flex md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</div>
                                    <div class="mt-1 text-gray-700 dark:text-gray-200 text-sm">@php $first = data_get($plan, 'features.0.text'); @endphp {!! $first !!}</div>
                                </div>
                                <div class="mt-4 md:mt-0 md:text-right">
                                    <div class="text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</div>
                                </div>
                            </div>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="ml-auto inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Select') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('11')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1200px] px-4">
                <div class="text-center space-y-2 mb-12">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Find your plan') !!}</h2>
                    <p class="text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.section_description', 'Clear prices, no surprises') !!}</p>
                </div>
                @php $plans = data_get($data, 'content.items', []); @endphp
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($plans as $plan)
                        <div class="text-center border-1 rounded-3xl p-8 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="mx-auto w-24 h-24 rounded-full flex items-center justify-center mb-4" style="background-color: hsla({{ $colorMap['primary'] }}, .12); color: hsl({{ $colorMap['primary'] }});">
                                <div class="text-2xl font-extrabold">{!! data_get($plan, 'price', '$9') !!}</div>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mb-1">{!! data_get($plan, 'period', '/mo') !!}</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                            <ul class="mt-5 space-y-2 text-sm text-gray-700 dark:text-gray-200 text-left">
                                @foreach (array_slice((array) data_get($plan, 'features', []), 0, 4) as $f)
                                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-green-500"></i>{!! data_get($f, 'text', '') !!}</li>
                                @endforeach
                            </ul>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-6 inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose plan') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('12')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1100px] px-4">
                <div class="text-center mb-10">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Compare plans') !!}</h2>
                </div>
                @php $plans = data_get($data, 'content.items', []); @endphp
                <div class="rounded-2xl overflow-hidden border-1 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                    @foreach ($plans as $idx => $plan)
                        <div class="grid md:grid-cols-[1fr_auto_auto] items-center px-5 py-4 {{ $idx % 2 === 0 ? 'bg-white/50 dark:bg-gray-800/40' : '' }}">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</div>
                                <div class="text-xs text-gray-700 dark:text-gray-300">@php $first = data_get($plan, 'features.0.text'); @endphp {!! $first !!}</div>
                            </div>
                            <div class="text-right pr-4">
                                <div class="text-2xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</div>
                            </div>
                            <div class="text-right">
                                <a href="{{ data_get($plan, 'cta_link', '#') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Select') !!}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('13')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1200px] px-4">
                <div class="text-center space-y-2 mb-12">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Choose your path') !!}</h2>
                    <p class="text-gray-700 dark:text-gray-200">{!! data_get($data, 'content.section_description', 'From starter to enterprise') !!}</p>
                </div>
                @php $plans = data_get($data, 'content.items', []); @endphp
                <div class="grid md:grid-cols-3 gap-8">
                    @foreach ($plans as $plan)
                        <div class="relative rounded-2xl p-6 border-1 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            @if(data_get($plan, 'badge'))
                                <span class="absolute top-4 right-4 text-xs font-semibold px-2 py-1 rounded-full text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{{ data_get($plan, 'badge') }}</span>
                            @endif
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($plan, 'title', 'Plan') !!}</h3>
                            <div class="mt-2 text-3xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{!! data_get($plan, 'price', '$9') !!}<span class="text-base text-gray-600 dark:text-gray-300">{!! data_get($plan, 'period', '/mo') !!}</span></div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach (array_slice((array) data_get($plan, 'features', []), 0, 6) as $f)
                                    <span class="text-xs px-2 py-1 rounded-full" style="background-color: hsla({{ $colorMap['primary'] }}, .10); color: hsl({{ $colorMap['primary'] }});">{!! data_get($f, 'text', '') !!}</span>
                                @endforeach
                            </div>
                            <a href="{{ data_get($plan, 'cta_link', '#') }}" class="mt-6 inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! data_get($plan, 'cta_label', 'Choose plan') !!}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Pricing Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a pricing variation in the admin panel to customise this section.
                </p>
            </div>
        </section>
@endswitch


