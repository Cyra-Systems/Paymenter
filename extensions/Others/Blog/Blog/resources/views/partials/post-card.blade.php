@php
    $publishedAt = $post->published_at ?? $post->created_at;
    $cardTags = collect($post->tags)->filter()->values()->take(2);
    $cardClasses = collect([
        'blog-post-card',
        'group',
        'flex',
        'h-full',
        'flex-col',
        'overflow-hidden',
        'border',
        'border-neutral',
        'bg-background-secondary',
        $cardHoverEnabled ? 'blog-post-card--hover' : null,
        $cardGlowEnabled ? 'blog-post-card--glow' : null,
    ])->filter()->implode(' ');
@endphp

<a href="{{ route('blog.show', $post) }}" wire:navigate class="block h-full" wire:key="blog-post-{{ $post->id }}">
    <article class="{{ $cardClasses }}">
        <div class="blog-post-card-media">
            @if($post->cover_image_url)
                <img
                    src="{{ $post->cover_image_url }}"
                    alt="{{ $post->title }}"
                    class="blog-post-card-image"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="blog-post-card-placeholder">
                    <x-ri-image-line class="h-6 w-6" />
                </div>
            @endif

            <div class="blog-post-card-arrow">
                <x-ri-arrow-right-up-line class="h-4 w-4" />
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center gap-2">
                @if($publishedAt && $publishedAt->diffInDays(now()) < 7)
                    <span class="blog-post-card-badge">{{ __('New') }}</span>
                @endif

                @foreach($cardTags as $tagLabel)
                    <span class="blog-post-card-tag">{{ $tagLabel }}</span>
                @endforeach
            </div>

            <div class="space-y-3">
                <h2 class="blog-post-card-title">{{ $post->title }}</h2>

                <p class="blog-post-card-description">
                    {{ $post->excerpt(160) }}
                </p>
            </div>

            <div class="blog-post-card-footer mt-auto">
                <span class="inline-flex items-center gap-1.5 text-xs text-color-muted">
                    <x-ri-calendar-line class="h-3 w-3" />
                    <span>{{ optional($publishedAt)->format('M d, Y') }}</span>
                </span>

                <span class="blog-post-card-read">
                    <span>{{ __('Read article') }}</span>
                    <x-ri-arrow-right-line class="h-3.5 w-3.5" />
                </span>
            </div>
        </div>
    </article>
</a>
