@props(['variation' => '1', 'data' => []])

@php
    $image = data_get($data, 'content.image') ?: data_get($data, 'content.panel_data.image');
    $widgets = data_get($data, 'content.widgets', []);
    if (empty($widgets)) {
        $widgets = data_get($data, 'content.panel_data.widgets', []);
    }
@endphp

@switch($variation)
    @case('1')
        <section class="relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="rounded-2xl overflow-hidden" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                    @if($image)
                        <div class="relative w-full aspect-[16/9]">
                            <img src="{{ Storage::url($image) }}" alt="Panel image" class="absolute inset-0 w-full h-full object-cover block">
                        </div>
                    @else
                        <div class="w-full h-64 bg-gray-200 dark:bg-gray-700"></div>
                    @endif
                </div>
            </div>
        </section>
        @break
    @case('2')
        <section class="relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="rounded-2xl overflow-hidden" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                    @if($image)
                        <div class="relative w-full aspect-[16/9]">
                            <img src="{{ Storage::url($image) }}" alt="Panel image" class="absolute inset-0 w-full h-full object-cover block">
                        </div>
                    @else
                        <div class="w-full h-64 bg-gray-200 dark:bg-gray-700"></div>
                    @endif
                </div>
                @if(!empty($widgets))
                <div class="grid md:grid-cols-3 gap-6 mt-4">
                    @foreach($widgets as $w)
                        <div class="rounded-2xl p-6 bg-white dark:bg-gray-800 page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                            <div class="flex items-start space-x-4">
                                <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl(var(--hpb-color-primary));">
                                    <i class="w-4 h-4 fa-solid {{ data_get($w, 'icon', 'fa-bolt') }}" style="color: #ffffff;"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{!! data_get($w, 'title', '') !!}</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{!! data_get($w, 'description', '') !!}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @break
    @case('3')
        <section class="relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="grid md:grid-cols-[0.3fr_0.7fr] gap-6 items-stretch">
                    <div class="flex flex-col gap-6">
                        @foreach(array_slice($widgets, 0, 3) as $w)
                            <div class="rounded-2xl p-6 bg-white dark:bg-gray-800 page-builder__card card-gradient h-full" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                                <div class="flex items-start space-x-4">
                                    <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl(var(--hpb-color-primary));">
                                        <i class="w-4 h-4 fa-solid {{ data_get($w, 'icon', 'fa-bolt') }}" style="color: #ffffff;"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{!! data_get($w, 'title', '') !!}</h3>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{!! data_get($w, 'description', '') !!}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="rounded-2xl overflow-hidden h-full" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                        @if($image)
                            <img src="{{ Storage::url($image) }}" alt="Panel image" class="w-full h-full object-cover block">
                        @else
                            <div class="w-full h-full min-h-[200px] bg-gray-200 dark:bg-gray-700"></div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @break
    @case('4')
        <section class="relative panel-interactive">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="grid md:grid-cols-[0.3fr_0.7fr] gap-6 items-stretch">
                    <div class="flex flex-col gap-6">
                        @foreach(array_slice($widgets, 0, 3) as $index => $w)
                            <div class="rounded-2xl p-6 bg-white dark:bg-gray-800 page-builder__card card-gradient h-full cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-[1.02] card-selector {{ $index === 0 ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : '' }}" 
                                 data-image="{{ data_get($w, 'image') ? Storage::url(data_get($w, 'image')) : '' }}"
                                 data-index="{{ $index }}"
                                 style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                                <div class="flex items-start space-x-4">
                                    <div class="flex items-center justify-center rounded-2xl p-3 w-12 h-12" style="background-color: hsl(var(--hpb-color-primary));">
                                        <i class="w-4 h-4 fa-solid {{ data_get($w, 'icon', 'fa-bolt') }}" style="color: #ffffff;"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{!! data_get($w, 'title', '') !!}</h3>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{!! data_get($w, 'description', '') !!}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="rounded-2xl overflow-hidden h-full relative" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl(var(--hpb-color-neutral));">
                        <div class="w-full h-full min-h-[400px] bg-gray-200 dark:bg-gray-700 relative">
                            @if($image)
                                <img data-main-image src="{{ Storage::url($image) }}" alt="Panel image" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-200">
                            @elseif(!empty($widgets) && data_get($widgets[0], 'image'))
                                <img data-main-image src="{{ Storage::url(data_get($widgets[0], 'image')) }}" alt="Panel image" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-200">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.panel-interactive').forEach(function(container) {
                    const cards = container.querySelectorAll('.card-selector');
                    const mainImage = container.querySelector('[data-main-image]');
                    cards.forEach(function(card, index) {
                        card.addEventListener('click', function() {
                            cards.forEach(function(c) { c.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20'); });
                            this.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                            const imageUrl = this.getAttribute('data-image');
                            if (imageUrl && mainImage) {
                                mainImage.style.opacity = '0';
                                setTimeout(function() {
                                    mainImage.src = imageUrl;
                                    mainImage.style.opacity = '1';
                                }, 100);
                            }
                        });
                        if (index === 0) {
                            card.click();
                        }
                    });
                });
            });
        </script>
        @break
    @default
        <section></section>
@endswitch


