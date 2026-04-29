@include('blog::partials.theme-bridge')

@php
    $sortOptions = [
        'published_at_desc' => __('Latest'),
        'published_at_asc' => __('Oldest'),
        'updated_at_desc' => __('Updated'),
        'views_desc' => __('Popular'),
    ];
    $archiveBaseParams = array_filter([
        'search' => $search !== '' ? $search : null,
        'sort' => $sortOption !== 'published_at_desc' ? $sortOption : null,
    ], fn ($value) => filled($value));
@endphp

<div class="blog-ui container py-8 sm:py-10">
    <div class="space-y-6">
        <section class="blog-archive-header">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold leading-tight text-color-base sm:text-3xl">{{ $title }}</h1>

                @if($description)
                    <p class="max-w-3xl text-sm leading-7 text-color-muted sm:text-base">{{ $description }}</p>
                @endif
            </div>

            <form method="GET" action="{{ route('blog.index') }}" class="blog-archive-controls">
                @if($tag !== '')
                    <input type="hidden" name="tag" value="{{ $tag }}">
                @endif

                <div class="relative group">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-500 transition-colors group-focus-within:text-primary">
                        <x-ri-search-line class="h-4 w-4" />
                    </div>

                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        x-data
                        x-on:input.debounce.350ms="$el.form.requestSubmit()"
                        class="blog-archive-input blog-archive-search pl-10 pr-4"
                        placeholder="{{ __('Search...') }}"
                    >
                </div>

                <div class="relative blog-archive-sort-wrap">
                    <select
                        name="sort"
                        x-data
                        x-on:change="$el.form.requestSubmit()"
                        class="blog-archive-input blog-archive-select w-full appearance-none pl-4 pr-10"
                    >
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sortOption === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-neutral-500">
                        <x-ri-sort-desc class="h-4 w-4" />
                    </div>
                </div>
            </form>
        </section>

        @if($tagSummary->isNotEmpty())
            <section class="space-y-3 pt-2">
                <div
                    x-data="{
                        dragging: false,
                        moved: false,
                        suppressClick: false,
                        startX: 0,
                        startScrollLeft: 0,
                        start(event) {
                            this.dragging = true;
                            this.moved = false;
                            this.startX = event.clientX;
                            this.startScrollLeft = this.$el.scrollLeft;
                        },
                        move(event) {
                            if (!this.dragging) {
                                return;
                            }

                            const delta = event.clientX - this.startX;

                            if (Math.abs(delta) > 6) {
                                this.moved = true;
                            }

                            this.$el.scrollLeft = this.startScrollLeft - delta;
                        },
                        stop() {
                            if (!this.dragging) {
                                return;
                            }

                            this.dragging = false;

                            if (this.moved) {
                                this.suppressClick = true;
                                setTimeout(() => this.suppressClick = false, 0);
                            }
                        },
                    }"
                    x-on:mousedown="start($event)"
                    x-on:mousemove="move($event)"
                    x-on:mouseleave="stop()"
                    x-on:mouseup="stop()"
                    x-on:wheel.prevent="if ($el.scrollWidth > $el.clientWidth) { $el.scrollLeft += Math.abs($event.deltaX) > Math.abs($event.deltaY) ? $event.deltaX : $event.deltaY; }"
                    x-on:click.capture="if (suppressClick) { $event.preventDefault(); $event.stopPropagation(); }"
                    class="overflow-x-auto py-1 cursor-grab select-none active:cursor-grabbing"
                >
                    <div class="flex min-w-max flex-wrap items-center gap-2 pr-1">
                        <a
                            href="{{ route('blog.index', $archiveBaseParams) }}"
                            wire:navigate
                            class="blog-archive-topic-chip {{ $tag === '' ? 'blog-archive-topic-chip--active' : '' }}"
                        >
                            {{ __('All') }}
                        </a>

                        @foreach($tagSummary as $tagItem)
                            <a
                                href="{{ route('blog.index', array_merge($archiveBaseParams, ['tag' => $tagItem['label']])) }}"
                                wire:navigate
                                class="blog-archive-topic-chip {{ $tag === $tagItem['label'] ? 'blog-archive-topic-chip--active' : '' }}"
                            >
                                {{ $tagItem['label'] }}
                            </a>
                        @endforeach

                        @if($activeFilters->isNotEmpty())
                            <a
                                href="{{ route('blog.index') }}"
                                wire:navigate
                                class="blog-archive-clear-chip"
                            >
                                <x-ri-close-circle-line class="h-3.5 w-3.5" />
                                <span>{{ __('Clear filters') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($posts->count() > 0)
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($posts as $post)
                    @include('blog::partials.post-card', [
                        'post' => $post,
                        'cardHoverEnabled' => $cardHoverEnabled ?? true,
                        'cardGlowEnabled' => $cardGlowEnabled ?? true,
                    ])
                @endforeach
            </section>

            @if($posts->hasPages())
                @php
                    $pages = collect(range(1, $posts->lastPage()))
                        ->filter(fn ($page) => $page === 1 || $page === $posts->lastPage() || abs($posts->currentPage() - $page) <= 1)
                        ->values();
                    $previousPage = null;
                @endphp

                <nav class="flex items-center justify-center gap-3 pt-2" aria-label="{{ __('Pagination') }}">
                    <button
                        type="button"
                        aria-label="{{ __('Previous') }}"
                        wire:click="previousPage"
                        @disabled($posts->onFirstPage())
                        class="blog-archive-page-button"
                    >
                        <svg width="9" height="16" viewBox="0 0 12 18" fill="none" aria-hidden="true">
                            <path d="M11 1L2 9.24242L11 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>

                    <div class="flex gap-3 text-sm">
                        @foreach($pages as $page)
                            @if($previousPage !== null && $page - $previousPage > 1)
                                <span class="blog-archive-page-button blog-archive-page-button--ghost">...</span>
                            @endif

                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }})"
                                class="blog-archive-page-button {{ $page === $posts->currentPage() ? 'blog-archive-page-button--active' : 'blog-archive-page-button--ghost' }}"
                            >
                                {{ $page }}
                            </button>

                            @php($previousPage = $page)
                        @endforeach
                    </div>

                    <button
                        type="button"
                        aria-label="{{ __('Next') }}"
                        wire:click="nextPage"
                        @disabled($posts->onLastPage())
                        class="blog-archive-page-button"
                    >
                        <svg width="9" height="16" viewBox="0 0 12 18" fill="none" aria-hidden="true">
                            <path d="M1 1L10 9.24242L1 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </nav>
            @endif
        @endif

        @if($posts->count() === 0)
            <section class="blog-archive-empty">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-neutral/10 text-neutral-400">
                    <x-ri-file-search-line class="text-xl" />
                </div>

                <div class="space-y-2">
                    <p class="text-base font-semibold text-color-base">
                        {{ $search !== '' || $tag !== '' ? __('No posts found') : __('No posts have been published yet') }}
                    </p>
                    <p class="text-sm text-color-muted">
                        {{ $search !== '' || $tag !== '' ? __('Try adjusting your search or filters.') : __('Check back later for new updates.') }}
                    </p>
                </div>

                @if($activeFilters->isNotEmpty())
                    <button type="button" wire:click="clearFilters" class="blog-archive-empty-action">
                        {{ __('Clear all filters') }}
                    </button>
                @endif
            </section>
        @endif
    </div>
</div>
