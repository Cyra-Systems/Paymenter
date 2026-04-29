@include('blog::partials.theme-bridge')

@if($posts->isNotEmpty())
    <section class="blog-ui container pb-10 pt-8 sm:pb-12">
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-2xl font-semibold text-base sm:text-3xl">{{ __('From the blog') }}</h2>
                <a
                    href="{{ route('blog.index') }}"
                    wire:navigate
                    class="hidden h-9 items-center justify-center rounded-sm border border-transparent bg-primary px-4 text-sm font-medium text-inverted transition hover:opacity-90 sm:inline-flex"
                >
                    {{ __('See more') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($posts as $post)
                    @include('blog::partials.post-card', [
                        'post' => $post,
                        'cardHoverEnabled' => $cardHoverEnabled ?? true,
                        'cardGlowEnabled' => $cardGlowEnabled ?? true,
                    ])
                @endforeach
            </div>

            <div class="sm:hidden">
                <a
                    href="{{ route('blog.index') }}"
                    wire:navigate
                    class="inline-flex h-9 items-center justify-center rounded-sm border border-transparent bg-primary px-4 text-sm font-medium text-inverted transition hover:opacity-90"
                >
                    {{ __('See more') }}
                </a>
            </div>
        </div>
    </section>
@endif
