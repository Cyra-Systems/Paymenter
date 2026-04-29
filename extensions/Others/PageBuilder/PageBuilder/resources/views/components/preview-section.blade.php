@props(['section', 'colorMap', 'data' => null])

@php
    $type = $section['type'] ?? '';
    $variation = $section['variation'] ?? '1';
    
    // Default data for preview
    if (!$data) {
        $data = [
            'category' => null,
            'products' => collect([]),
        ];
    }
    
    // Ensure products collection is properly formatted for preview
    if (isset($data['products']) && $data['products'] instanceof \Illuminate\Support\Collection) {
        $data['products'] = $data['products']->map(function($product) {
            if (is_object($product) && method_exists($product, 'price')) {
                // Handle price method
                $product->price = $product->price();
            }
            return $product;
        });
    }
@endphp

@switch($type)
    @case('hero')
        @include('pagebuilder::sections.hero', ['variation' => $variation, 'colorMap' => $colorMap])
        @break
    @case('features')
        @include('pagebuilder::sections.features', ['variation' => $variation, 'colorMap' => $colorMap])
        @break
    @case('products')
        @include('pagebuilder::sections.products', ['variation' => $variation, 'colorMap' => $colorMap, 'data' => $data])
        @break
    @case('reviews')
        @include('pagebuilder::sections.reviews', ['variation' => $variation, 'colorMap' => $colorMap])
        @break
    @case('faq')
        @include('pagebuilder::sections.faq', ['variation' => $variation, 'colorMap' => $colorMap])
        @break
    @case('panel')
        @include('pagebuilder::sections.panel', ['variation' => $variation, 'colorMap' => $colorMap, 'data' => $data])
        @break
    @case('games')
        @include('pagebuilder::sections.games', ['variation' => $variation, 'colorMap' => $colorMap, 'data' => $data])
        @break
    @default
        <div class="p-8 text-center text-gray-500 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
            <div class="text-4xl mb-2">
                <i class="fa-solid fa-question-circle text-gray-400"></i>
            </div>
            <p class="text-gray-500">Unknown section type: {{ $type }}</p>
        </div>
@endswitch
