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
                @if(data_get($data, 'content.section_title') || data_get($data, 'content.section_description'))
                    <div class="text-center space-y-4 mb-12 py-8">
                        @if(data_get($data, 'content.section_title'))
                            <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">{!! data_get($data, 'content.section_title') !!}</h2>
                        @endif
                        @if(data_get($data, 'content.section_description'))
                            <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{!! data_get($data, 'content.section_description') !!}</p>
                        @endif
                    </div>
                @endif

                @php 
                    $image = data_get($data, 'content.image');
                    $imageAlt = data_get($data, 'content.image_alt', 'Location map');
                @endphp
                
                @if($image)
                    <div class="w-full">
                        <img 
                            src="{{ Storage::url($image) }}" 
                            alt="{{ $imageAlt }}" 
                            class="w-full h-auto object-contain"
                            style="border-radius: var(--hpb-card-radius); object-fit: contain;"
                        />
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No map image uploaded yet. Upload a map image in the admin panel.</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Location Map Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a location map variation in the admin panel to customise this section.
                </p>
            </div>
        </section>
@endswitch
