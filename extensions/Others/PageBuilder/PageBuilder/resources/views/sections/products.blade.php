@php
$category = $data['category'] ?? null;
$products = $data['products'] ?? collect();

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
        <section class="py-20 relative" >
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">
                        @if($data['category'])
                            {{ $data['category']->name }}
                        @else
                            Our Products
                        @endif
                    </h2>
                    @if($data['category'] && $data['category']->description)
                        <article class="prose dark:prose-invert mx-auto max-w-3xl text-gray-700 dark:text-gray-200">
                            {!! $data['category']->description !!}
                        </article>
                    @endif
                </div>

                @if($data['products']->count() > 0)
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-8 h-fit">
                        @foreach($data['products']->take(6) as $product)
                            <div class="flex flex-col rounded-3xl overflow-hidden group relative page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl({{ $colorMap['neutral'] }});">
                                <div class="relative w-full aspect-[4/2] flex items-center justify-center overflow-hidden">
                                    @php
                                        $productLink = ($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false)
                                            ? route('products.checkout', ['category' => $data['category'], 'product' => $product->slug])
                                            : route('products.show', ['category' => $product->category, 'product' => $product->slug]);
                                    @endphp
                                    <a href="{{ $productLink }}" wire:navigate class="absolute inset-0 z-10"></a>
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover object-center" style="border-radius: var(--hpb-card-radius);" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-3xl" style="color: hsl({{ $colorMap['muted'] }});">
                                            <i class="fa-solid fa-image text-4xl"></i>
                                        </div>
                                    @endif
                                    @if($product->stock > 0 || !$product->stock)
                                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full" style="background-color: hsla({{ $colorMap['primary'] }}, .15); color: hsl({{ $colorMap['primary'] }});">{{ __('product.in_stock') }}</span>
                                    @else
                                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full" style="background-color: hsla(0, 75%, 50%, .12); color: hsl(0, 75%, 50%);">{{ __('product.out_of_stock') }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-col flex-1 p-6 gap-3">
                                    <h2 class="text-lg text-gray-900 dark:text-white font-semibold">{{ $product->name }}</h2>
                                    @if(theme('direct_checkout', false) && $product->description)
                                        <article class="prose dark:prose-invert text-sm line-clamp-3" style="color: hsl({{ $colorMap['muted'] }});">{!! $product->description !!}</article>
                                    @endif
                                    <div class="flex items-center justify-between mt-auto pt-4">
                                        <span class="text-xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{{ $product->price() }}</span>
                                        @if (($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false))
                                            <a href="{{ route('products.checkout', ['category' => $data['category'], 'product' => $product->slug]) }}" wire:navigate class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                                {{ __('product.add_to_cart') }}
                                            </a>
                                        @else
                                            <a href="{{ route('products.show', ['category' => $product->category, 'product' => $product->slug]) }}" wire:navigate class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                                {{ __('common.button.view') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">
                            <i class="fa-solid fa-box text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Products Found</h3>
                        <p class="text-gray-700 dark:text-gray-200">Sorry, we're still restocking!</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('2')
        <section class="py-20 relative" style="background-color: hsl({{ $colorMap['background'] }});">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">
                        @if($data['category'])
                            {{ $data['category']->name }}
                        @else
                            Featured Products
                        @endif
                    </h2>
                    @if($data['category'] && $data['category']->description)
                        <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{{ Str::limit(strip_tags($data['category']->description), 200) }}</p>
                    @endif
                </div>

                @if($data['products']->count() > 0)
                    <div class="space-y-8">
                        @foreach($data['products']->take(4) as $product)
                            <div class="rounded-3xl overflow-hidden group relative page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow); border: 1px solid hsl({{ $colorMap['neutral'] }});">
                                <div class="grid md:grid-cols-2 gap-0">
                                    <div class="relative aspect-[4/3] flex items-center justify-center overflow-hidden">
                                        @php
                                            $productLink = ($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false)
                                                ? route('products.checkout', ['category' => $data['category'], 'product' => $product->slug])
                                                : route('products.show', ['category' => $product->category, 'product' => $product->slug]);
                                        @endphp
                                        <a href="{{ $productLink }}" wire:navigate class="absolute inset-0 z-10"></a>
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover object-center" style="border-radius: var(--hpb-card-radius);" />
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 text-3xl">
                                                <i class="fa-solid fa-image text-4xl"></i>
                                            </div>
                                        @endif
                                        @if($product->stock > 0 || !$product->stock)
                                            <span class="absolute top-3 right-3 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-xs font-semibold px-3 py-1 rounded-full">{{ __('product.in_stock') }}</span>
                                        @else
                                            <span class="absolute top-3 right-3 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 text-xs font-semibold px-3 py-1 rounded-full">{{ __('product.out_of_stock') }}</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col justify-center p-8 gap-4">
                                        <h2 class="text-2xl text-gray-900 dark:text-white font-bold">{{ $product->name }}</h2>
                                        @if(theme('direct_checkout', false) && $product->description)
                                            <article class="prose dark:prose-invert text-sm line-clamp-4 text-gray-700 dark:text-gray-200">{!! $product->description !!}</article>
                                        @endif
                                        <div class="flex items-center justify-between mt-auto pt-4">
                                            <span class="text-2xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{{ $product->price() }}</span>
                                            @if (($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false))
                                                <a href="{{ route('products.checkout', ['category' => $data['category'], 'product' => $product->slug]) }}" wire:navigate class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                                    {{ __('product.add_to_cart') }}
                                                </a>
                                            @else
                                                <a href="{{ route('products.show', ['category' => $product->category, 'product' => $product->slug]) }}" wire:navigate class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                                    {{ __('common.button.view') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">
                            <i class="fa-solid fa-box text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Products Found</h3>
                        <p class="text-gray-700 dark:text-gray-200">Sorry, we're still restocking!</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('3')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">
                        @if($data['category'])
                            {{ $data['category']->name }}
                        @else
                            Premium Products
                        @endif
                    </h2>
                    @if($data['category'] && $data['category']->description)
                        <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{{ Str::limit(strip_tags($data['category']->description), 200) }}</p>
                    @endif
                </div>

                @if($data['products']->count() > 0)
                    <div class="grid lg:grid-cols-2 gap-12">
                        @foreach($data['products']->take(6) as $product)
                            <div class="border-1 rounded-3xl overflow-hidden group relative page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                <div class="relative aspect-[16/9] bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                    @php
                                        $productLink = ($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false)
                                            ? route('products.checkout', ['category' => $data['category'], 'product' => $product->slug])
                                            : route('products.show', ['category' => $product->category, 'product' => $product->slug]);
                                    @endphp
                                    <a href="{{ $productLink }}" wire:navigate class="absolute inset-0 z-10"></a>
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-cover object-center" style="border-radius: var(--hpb-card-radius);" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-3xl" style="color: hsl({{ $colorMap['muted'] }});">
                                            <i class="fa-solid fa-image text-4xl"></i>
                                        </div>
                                    @endif
                                    @if($product->stock > 0 || !$product->stock)
                                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full" style="background-color: hsla({{ $colorMap['primary'] }}, .15); color: hsl({{ $colorMap['primary'] }});">{{ __('product.in_stock') }}</span>
                                    @else
                                        <span class="absolute top-3 right-3 text-xs font-semibold px-3 py-1 rounded-full" style="background-color: hsla(0, 75%, 50%, .12); color: hsl(0, 75%, 50%);">{{ __('product.out_of_stock') }}</span>
                                    @endif
                                </div>
                                <div class="p-8">
                                    <h2 class="text-xl text-gray-900 dark:text-white font-bold mb-3">{{ $product->name }}</h2>
                                    @if(theme('direct_checkout', false) && $product->description)
                                        <article class="prose dark:prose-invert text-sm line-clamp-3 text-gray-700 dark:text-gray-200 mb-4">{!! $product->description !!}</article>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        <span class="text-2xl font-bold" style="color: hsl({{ $colorMap['primary'] }});">{{ $product->price() }}</span>
                                        @if (($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false))
                                            <a href="{{ route('products.checkout', ['category' => $data['category'], 'product' => $product->slug]) }}" wire:navigate class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                                {{ __('product.add_to_cart') }}
                                            </a>
                                        @else
                                            <a href="{{ route('products.show', ['category' => $product->category, 'product' => $product->slug]) }}" wire:navigate class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">
                                                {{ __('common.button.view') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">
                            <i class="fa-solid fa-box text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Products Found</h3>
                        <p class="text-gray-700 dark:text-gray-200">Sorry, we're still restocking!</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('4')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">
                        @if($data['category'])
                            {{ $data['category']->name }}
                        @else
                            Product Showcase
                        @endif
                    </h2>
                    @if($data['category'] && $data['category']->description)
                        <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{{ Str::limit(strip_tags($data['category']->description), 200) }}</p>
                    @endif
                </div>

                @if($data['products']->count() > 0)
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($data['products']->take(8) as $product)
                            <div class="text-center border-1 rounded-2xl p-6 overflow-hidden group  page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                <div class="relative w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center overflow-hidden">
                                    @php
                                        $productLink = ($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false)
                                            ? route('products.checkout', ['category' => $data['category'], 'product' => $product->slug])
                                            : route('products.show', ['category' => $product->category, 'product' => $product->slug]);
                                    @endphp
                                    <a href="{{ $productLink }}" wire:navigate class="absolute inset-0 z-10"></a>
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover object-center rounded-full" style="border-radius: var(--hpb-card-radius);" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                            <i class="fa-solid fa-image text-2xl"></i>
                                        </div>
                                    @endif
                                </div>
                                
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">{{ $product->name }}</h3>
                                
                                @if($product->stock > 0 || !$product->stock)
                                    <span class="inline-block bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-xs font-semibold px-2 py-1 rounded-full mb-3">{{ __('product.in_stock') }}</span>
                                @else
                                    <span class="inline-block bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 text-xs font-semibold px-2 py-1 rounded-full mb-3">{{ __('product.out_of_stock') }}</span>
                                @endif
                                
                                <div class="text-xl font-bold mb-4" style="color: hsl({{ $colorMap['primary'] }});">{{ $product->price() }}</div>
                                
                                @if (($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false))
                                    <a href="{{ route('products.checkout', ['category' => $data['category'], 'product' => $product->slug]) }}" wire:navigate class="w-full">
                                        <x-button.primary size="sm" class="w-full" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{{ __('product.add_to_cart') }}</x-button.primary>
                                    </a>
                                @else
                                    <a href="{{ route('products.show', ['category' => $product->category, 'product' => $product->slug]) }}" wire:navigate class="w-full">
                                        <x-button.primary size="sm" class="w-full" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{{ __('common.button.view') }}</x-button.primary>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">
                            <i class="fa-solid fa-box text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Products Found</h3>
                        <p class="text-gray-700 dark:text-gray-200">Sorry, we're still restocking!</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @case('5')
        <section class="py-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4">
                <div class="text-center space-y-4 mb-8 py-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white">
                        @if($data['category'])
                            {{ $data['category']->name }}
                        @else
                            Product Grid
                        @endif
                    </h2>
                    @if($data['category'] && $data['category']->description)
                        <p class="text-xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto">{{ Str::limit(strip_tags($data['category']->description), 200) }}</p>
                    @endif
                </div>

                @if($data['products']->count() > 0)
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($data['products']->take(12) as $product)
                            <div class="overflow-hidden group relative page-builder__card card-gradient" style="border-radius: var(--hpb-card-radius); box-shadow: var(--hpb-card-shadow);">
                                <div class="relative aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden mb-4" style="border: 1px solid hsl({{ $colorMap['text-secondary'] }});">
                                    @php
                                        $productLink = ($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false)
                                            ? route('products.checkout', ['category' => $data['category'], 'product' => $product->slug])
                                            : route('products.show', ['category' => $product->category, 'product' => $product->slug]);
                                    @endphp
                                    <a href="{{ $productLink }}" wire:navigate class="absolute inset-0 z-10"></a>
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover object-center" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                            <i class="fa-solid fa-image text-4xl"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="absolute top-3 left-3">
                                        @if($product->stock > 0 || !$product->stock)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                                {{ __('product.in_stock') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300">
                                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                                {{ __('product.out_of_stock') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="absolute bottom-3 right-3">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold text-white" style="background-color: hsl({{ $colorMap['primary'] }});">
                                            {{ $product->price() }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">{{ $product->name }}</h3>
                                    
                                    @if (($product->stock > 0 || !$product->stock) && $product->price()->available && theme('direct_checkout', false))
                                        <a href="{{ route('products.checkout', ['category' => $data['category'], 'product' => $product->slug]) }}" wire:navigate>
                                            <x-button.primary size="sm" class="mb-4 inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{{ __('product.add_to_cart') }}</x-button.primary>
                                        </a>
                                    @else
                                         <a href="{{ route('products.show', ['category' => $product->category, 'product' => $product->slug]) }}" wire:navigate class="mb-4 inline-flex items-center justify-center px-6 py-3 rounded-lg text-white font-semibold" style="background-color: hsl({{ $colorMap['primary'] }}); border-radius: var(--hpb-card-radius);">{{ __('common.button.view') }}</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">
                            <i class="fa-solid fa-box text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Products Found</h3>
                        <p class="text-gray-700 dark:text-gray-200">Sorry, we're still restocking!</p>
                    </div>
                @endif
            </div>
        </section>
        @break

    @default
        <section class="pb-20 relative">
            <div class="container mx-auto max-w-[1320px] px-4 text-center py-16">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 dark:text-white mb-8">Products Section</h2>
                <p class="text-xl text-gray-700 dark:text-gray-200 mb-12">
                    Choose a products variation in the admin panel to customize this section.
                </p>
            </div>
        </section>
@endswitch


