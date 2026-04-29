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
                <div class="max-w-3xl">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! $pbMarkdown(data_get($data, 'content.title', 'Section title')) !!}</h2>
                    <div class="mt-4 text-lg text-gray-700 dark:text-gray-200">{!! $pbMarkdown(data_get($data, 'content.text', 'Section paragraph text goes here.')) !!}</div>
                    @if(data_get($data, 'content.cta_label') && data_get($data, 'content.cta_link'))
                        <a href="{{ data_get($data, 'content.cta_link') }}" class="mt-6 inline-flex items-center justify-center px-4 py-2 rounded-lg text-white" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{!! $pbMarkdown(data_get($data, 'content.cta_label')) !!}</a>
                    @endif
                </div>
            </div>
        </section>
        @break

    @case('2')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="max-w-3xl mx-auto text-center">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! $pbMarkdown(data_get($data, 'content.title', 'Section title')) !!}</h2>
                    <div class="mt-4 text-lg text-gray-700 dark:text-gray-200">{!! $pbMarkdown(data_get($data, 'content.text', 'Section paragraph text goes here.')) !!}</div>
                </div>
            </div>
        </section>
        @break

    @case('3')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-semibold text-gray-900 dark:text-white">{!! $pbMarkdown(data_get($data, 'content.left_title', 'Left title')) !!}</h3>
                        <div class="mt-3 text-lg text-gray-700 dark:text-gray-200">{!! $pbMarkdown(data_get($data, 'content.left_text', 'Left side text content goes here.')) !!}</div>
                    </div>
                    <div>
                        <h3 class="text-2xl md:text-3xl font-semibold text-gray-900 dark:text-white">{!! $pbMarkdown(data_get($data, 'content.right_title', 'Right title')) !!}</h3>
                        <div class="mt-3 text-lg text-gray-700 dark:text-gray-200">{!! $pbMarkdown(data_get($data, 'content.right_text', 'Right side text content goes here.')) !!}</div>
                    </div>
                </div>
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Text Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a text variation in the admin panel to customise this section.
                </p>
            </div>
        </section>
@endswitch


