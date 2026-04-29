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
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'From our blog') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Insights, announcements, and best practices') !!}</p>
                </div>

                @php $posts = data_get($data, 'content.posts', data_get($data, 'posts', [])); @endphp
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($posts as $post)
                        <a href="{{ data_get($post, 'link', '#') }}" class="block group  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="overflow-hidden" style="border-top-left-radius: var(--hpb-card-radius); border-top-right-radius: var(--hpb-card-radius);">
                                @php $img = data_get($post, 'image'); @endphp
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($post, 'title', '') }}" class="w-full h-48 object-cover group-hover:scale-[1.02] transition-transform" />
                                @else
                                    <div class="w-full h-48"></div>
                                @endif
                            </div>
                            <div class="p-6">
                                <div class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-3">
                                    <span>{{ data_get($post, 'date', '') }}</span>
                                    <span>•</span>
                                    <span>{{ data_get($post, 'read_time', '') }}</span>
                                </div>
                                <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($post, 'title', 'Blog post') !!}</h3>
                                <p class="mt-2 text-gray-700 dark:text-gray-200 text-sm">{!! data_get($post, 'description', '') !!}</p>
                                <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium" style="color: hsl({{ $colorMap['primary'] }});">Read more <i class="fa-solid fa-arrow-right text-xs"></i></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('2')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 py-16">
                    <div>
                        <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'Latest articles') !!}</h2>
                        <p class="text-xl text-gray-700 dark:text-gray-200 max-w-2xl">{!! data_get($data, 'content.section_description', 'News and updates from our team') !!}</p>
                    </div>
                    <a href="#" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">View all <i class="fa-solid fa-arrow-right text-xs"></i></a>
                </div>

                @php $posts = array_values(data_get($data, 'content.posts', data_get($data, 'posts', []))); @endphp
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @if(count($posts) > 0)
                        @php $featured = $posts[0]; @endphp
                        <a href="{{ data_get($featured, 'link', '#') }}" class="md:col-span-2 block group  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="overflow-hidden" style="border-top-left-radius: var(--hpb-card-radius); border-top-right-radius: var(--hpb-card-radius);">
                                @php $img = data_get($featured, 'image'); @endphp
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($featured, 'title', '') }}" class="w-full h-72 object-cover group-hover:scale-[1.02] transition-transform" />
                                @else
                                    <div class="w-full h-72"></div>
                                @endif
                            </div>
                            <div class="p-6">
                                <div class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-3">
                                    <span>{{ data_get($featured, 'date', '') }}</span>
                                    <span>•</span>
                                    <span>{{ data_get($featured, 'read_time', '') }}</span>
                                </div>
                                <h3 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{!! data_get($featured, 'title', 'Featured post') !!}</h3>
                                <p class="mt-2 text-gray-700 dark:text-gray-200">{!! data_get($featured, 'description', '') !!}</p>
                                <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium" style="color: hsl({{ $colorMap['primary'] }});">Read more <i class="fa-solid fa-arrow-right text-xs"></i></span>
                            </div>
                        </a>
                    @endif

                    @foreach (array_slice($posts, 1) as $post)
                        <a href="{{ data_get($post, 'link', '#') }}" class="block group  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                            <div class="overflow-hidden" style="border-top-left-radius: var(--hpb-card-radius); border-top-right-radius: var(--hpb-card-radius);">
                                @php $img = data_get($post, 'image'); @endphp
                                @if($img)
                                    <img src="{{ Storage::url($img) }}" alt="{{ data_get($post, 'title', '') }}" class="w-full h-48 object-cover group-hover:scale-[1.02] transition-transform" />
                                @else
                                    <div class="w-full h-48"></div>
                                @endif
                            </div>
                            <div class="p-6">
                                <div class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-3">
                                    <span>{{ data_get($post, 'date', '') }}</span>
                                    <span>•</span>
                                    <span>{{ data_get($post, 'read_time', '') }}</span>
                                </div>
                                <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{!! data_get($post, 'title', 'Blog post') !!}</h3>
                                <p class="mt-2 text-gray-700 dark:text-gray-200 text-sm">{!! data_get($post, 'description', '') !!}</p>
                                <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium" style="color: hsl({{ $colorMap['primary'] }});">Read more <i class="fa-solid fa-arrow-right text-xs"></i></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('3')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title', 'From our blog') !!}</h2>
                    <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description', 'Insights, announcements, and best practices') !!}</p>
                </div>

                @php $posts = data_get($data, 'content.posts', data_get($data, 'posts', [])); @endphp
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($posts as $post)
                        <a href="{{ data_get($post, 'link', '#') }}" class="group relative rounded-2xl overflow-hidden block" style="border-radius: var(--hpb-card-radius);">
                            @php $img = data_get($post, 'image'); @endphp
                            @if($img)
                                <img src="{{ Storage::url($img) }}" alt="{{ data_get($post, 'title', '') }}" class="w-full h-64 object-cover group-hover:scale-[1.02] transition-transform" />
                            @else
                                <div class="w-full h-64"></div>
                            @endif
                            <div class="absolute inset-0" style="background-image: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0.3), rgba(0,0,0,0));"></div>
                            <div class="absolute bottom-0 left-0 p-4 md:p-5">
                                <div class="text-sm text-white/80 flex items-center gap-3 mb-2">
                                    <span>{{ data_get($post, 'date', '') }}</span>
                                    <span>•</span>
                                    <span>{{ data_get($post, 'read_time', '') }}</span>
                                </div>
                                <h3 class="text-white text-lg font-semibold mb-2">{!! data_get($post, 'title', 'Blog post') !!}</h3>
                                <p class="text-white/90 text-sm">{!! data_get($post, 'description', '') !!}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Blog Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a blog variation in the admin panel to customise this section.
                </p>
            </div>
        </section>
@endswitch


