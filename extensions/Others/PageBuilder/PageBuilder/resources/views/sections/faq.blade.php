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
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Frequently Asked Questions') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Find answers to common questions about our hosting services') !!}</p>
                </div>

                <div class="max-w-4xl mx-auto space-y-6">
                    @php $items = data_get($data, 'content.items', []); @endphp
                    @foreach ($items as $item)
                        <div class="border-1 rounded-3xl  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <button class="w-full px-8 py-6 text-left focus:outline-none" onclick="toggleFAQ(this)">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($item, 'question', '') !!}</h3>
                                    <i class="fa-solid fa-chevron-down text-gray-500 dark:text-gray-400 transition-transform duration-300"></i>
                                </div>
                            </button>
                            <div class="px-8 pb-6 hidden">
                                <p class="text-gray-700 dark:text-gray-200 leading-relaxed">{!! data_get($item, 'answer', '') !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <script>
                function toggleFAQ(button) {
                    const content = button.nextElementSibling;
                    const icon = button.querySelector('i');
                    
                    if (content.classList.contains('hidden')) {
                        content.classList.remove('hidden');
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        content.classList.add('hidden');
                        icon.style.transform = 'rotate(0deg)';
                    }
                }
            </script>
        </section>
        @break

    @case('2')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Got Questions?') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Everything you need to know about our services and support') !!}</p>
                </div>

                <div class="grid lg:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        @foreach (array_slice(data_get($data, 'content.items', []), 0, 3) as $item)
                            <div class="border-1 rounded-3xl p-6  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                <div class="flex items-start space-x-4">
                                    <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                        <i class="fa-solid fa-question text-white text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{!! data_get($item, 'question', '') !!}</h3>
                                        <p class="text-gray-700 dark:text-gray-200 text-sm leading-relaxed">{!! data_get($item, 'answer', '') !!}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-6">
                        @foreach (array_slice(data_get($data, 'content.items', []), 3, 3) as $item)
                            <div class="border-1 rounded-3xl p-6  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                <div class="flex items-start space-x-4">
                                    <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                        <i class="fa-solid fa-question text-white text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{!! data_get($item, 'question', '') !!}</h3>
                                        <p class="text-gray-700 dark:text-gray-200 text-sm leading-relaxed">{!! data_get($item, 'answer', '') !!}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        @break

    @case('3')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Common Questions') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Quick answers to help you get started with our platform') !!}</p>
                </div>

                <div class="max-w-5xl mx-auto">
                    <div class="grid md:grid-cols-2 gap-8">
                        @php
                            $groups = data_get($data, 'content.groups', []);
                            $half = (int) ceil(count($groups) / 2);
                            $leftGroups = array_slice($groups, 0, $half);
                            $rightGroups = array_slice($groups, $half);
                        @endphp
                        <div class="space-y-6">
                            @foreach ($leftGroups as $group)
                                <div class="border-1 rounded-3xl p-8  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                            <i class="fa-solid fa-rocket text-white text-lg"></i>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{!! data_get($group, 'title', '') !!}</h3>
                                    </div>
                                    <div class="space-y-4">
                                        @foreach (data_get($group, 'items', []) as $item)
                                            <div class="border-l-4 pl-4" style="border-color: hsl({{ $colorMap['primary'] }});">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">{!! data_get($item, 'question', '') !!}</h4>
                                                <p class="text-gray-700 dark:text-gray-300 text-sm">{!! data_get($item, 'answer', '') !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-6">
                            @foreach ($rightGroups as $group)
                                <div class="border-1 rounded-3xl p-8  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                            <i class="fa-solid fa-cog text-white text-lg"></i>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{!! data_get($group, 'title', '') !!}</h3>
                                    </div>
                                    <div class="space-y-4">
                                        @foreach (data_get($group, 'items', []) as $item)
                                            <div class="border-l-4 pl-4" style="border-color: hsl({{ $colorMap['primary'] }});">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">{!! data_get($item, 'question', '') !!}</h4>
                                                <p class="text-gray-700 dark:text-gray-300 text-sm">{!! data_get($item, 'answer', '') !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break

    @case('4')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Need Help?') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Quick answers to the most common questions about our platform') !!}</p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <div class="grid md:grid-cols-3 gap-8">
                        @foreach (data_get($data, 'content.groups', []) as $group)
                            <div class="border-1rounded-2xl p-6  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                <div class="text-center mb-6">
                                    <div class="inline-flex items-center justify-center rounded-full p-4 w-16 h-16 mb-4" style="background-color: hsl({{ $colorMap['primary'] }});">
                                        <i class="fa-solid fa-server text-white text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{!! data_get($group, 'title', '') !!}</h3>
                                </div>
                                <div class="space-y-4 text-sm">
                                    @foreach (data_get($group, 'items', []) as $item)
                                        <div class="text-gray-700 dark:text-gray-200">
                                            <strong>{!! data_get($item, 'question', '') !!}</strong>
                                            <p class="mt-1">{!! data_get($item, 'answer', '') !!}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        @break

    @case('5')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Frequently Asked Questions') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Everything you need to know about our hosting services and platform') !!}</p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-8">
                        @php
                            $items = data_get($data, 'content.items', []);
                            $half = (int) ceil(count($items) / 2);
                            $leftItems = array_slice($items, 0, $half);
                            $rightItems = array_slice($items, $half);
                        @endphp
                        <div class="space-y-4">
                            @foreach ($leftItems as $item)
                                <div class="borderdark:border-gray-700 rounded-xl  page-builder__card card-gradient">
                                    <details class="group">
                                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($item, 'question', '') !!}</h3>
                                            <i class="fa-solid fa-chevron-down text-gray-500 dark:text-gray-400 transition-transform duration-300 group-open:rotate-180"></i>
                                        </summary>
                                        <div class="px-6 pb-6">
                                            <p class="text-gray-700 dark:text-gray-200 leading-relaxed">{!! data_get($item, 'answer', '') !!}</p>
                                        </div>
                                    </details>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-4">
                            @foreach ($rightItems as $item)
                                <div class="borderdark:border-gray-700 rounded-xl  page-builder__card card-gradient">
                                    <details class="group">
                                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($item, 'question', '') !!}</h3>
                                            <i class="fa-solid fa-chevron-down text-gray-500 dark:text-gray-400 transition-transform duration-300 group-open:rotate-180"></i>
                                        </summary>
                                        <div class="px-6 pb-6">
                                            <p class="text-gray-700 dark:text-gray-200 leading-relaxed">{!! data_get($item, 'answer', '') !!}</p>
                                        </div>
                                    </details>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break

    @case('6')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Questions & Answers') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Find quick answers to help you get the most out of our platform') !!}</p>
                </div>

                <div class="max-w-5xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-8">
                        @php
                            $groups = data_get($data, 'content.groups', []);
                            $half = (int) ceil(count($groups) / 2);
                            $leftGroups = array_slice($groups, 0, $half);
                            $rightGroups = array_slice($groups, $half);
                        @endphp
                        <div class="space-y-6">
                            @foreach ($leftGroups as $group)
                                <div class="border-1 page-builder__card card-gradient rounded-3xl p-6" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl({{ $colorMap['primary'] }});">
                                            <i class="fa-solid fa-rocket text-white text-lg"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($group, 'title', '') !!}</h3>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach (data_get($group, 'items', []) as $item)
                                            <div class="rounded-lg p-3">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">{!! data_get($item, 'question', '') !!}</h4>
                                                <p class="text-gray-700 dark:text-gray-300 text-xs">{!! data_get($item, 'answer', '') !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-6">
                            @foreach ($rightGroups as $group)
                                <div class="border-1 border-gray-200 rounded-3xl p-6">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl({{ $colorMap['primary'] }});">
                                            <i class="fa-solid fa-headset text-white text-lg"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($group, 'title', '') !!}</h3>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach (data_get($group, 'items', []) as $item)
                                            <div class="rounded-lg p-3 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">{!! data_get($item, 'question', '') !!}</h4>
                                                <p class="text-gray-700 dark:text-gray-300 text-xs">{!! data_get($item, 'answer', '') !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">FAQ Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose an FAQ variation in the admin panel to customize this section.
                </p>
            </div>
        </section>
@endswitch


