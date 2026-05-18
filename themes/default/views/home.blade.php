<div>
    <div class="flex flex-col gap-10">
        <section class="w-full bg-background-secondary border-b border-neutral">
            <div class="container py-16 md:py-24">
                <article class="prose dark:prose-invert max-w-3xl">
                    {!! Str::markdown(theme('home_page_text', 'Welcome.'), [
                    'allow_unsafe_links' => false,
                    'renderer' => [
                    'soft_break' => "<br>"
                    ]]) !!}
                </article>
            </div>
        </section>

        <div class="container flex flex-col gap-6 pb-16">
            <div class="flex items-baseline justify-between">
                <h2 class="text-2xl font-semibold tracking-tight">{{ __('Services') }}</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach ($categories as $category)
                <a href="{{ route('category.show', ['category' => $category->slug]) }}" wire:navigate
                    class="group flex flex-col bg-background-secondary hover:bg-background-secondary/70 border border-neutral rounded-xl overflow-hidden transition-colors">
                    @if ($category->image)
                    <div class="{{ theme('small_images', false) ? 'flex items-center gap-4 p-4' : '' }}">
                        <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}"
                            class="{{ theme('small_images', false) ? 'w-14 h-14 rounded-md object-cover' : 'aspect-[16/10] w-full object-cover' }}">
                        @if(theme('small_images', false))
                        <h3 class="text-lg font-semibold">{{ $category->name }}</h3>
                        @endif
                    </div>
                    @endif

                    <div class="flex flex-col p-5 gap-3 flex-grow">
                        @if (!theme('small_images', false) || !$category->image)
                        <h3 class="text-lg font-semibold leading-snug">{{ $category->name }}</h3>
                        @endif

                        @if(theme('show_category_description', true))
                        <article class="prose prose-sm dark:prose-invert text-muted">
                            {!! $category->description !!}
                        </article>
                        @endif

                        <div class="mt-auto pt-2 flex items-center gap-1.5 text-sm font-medium text-primary group-hover:gap-2.5 transition-all">
                            {{ __('common.button.view_all') }}
                            <x-ri-arrow-right-fill class="size-4" />
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    {!! hook('pages.home') !!}
</div>
