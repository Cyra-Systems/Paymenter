@include('blog::partials.theme-bridge')

<div class="blog-ui container py-8 sm:py-10">
    <div class="space-y-6">
        <nav aria-label="{{ __('Breadcrumb') }}" class="flex items-center gap-3 rounded-sm border border-neutral bg-background-secondary px-3 py-2 text-sm text-base/60">
            <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center transition hover:text-base" aria-label="{{ __('Home') }}">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M18.333 10.17v1.267c0 3.251 0 4.876-.977 5.886-.976 1.01-2.547 1.01-5.69 1.01H8.333c-3.143 0-4.714 0-5.69-1.01-.977-1.01-.977-2.635-.977-5.886V10.17c0-1.907 0-2.86.433-3.651.432-.79 1.223-1.281 2.804-2.262l1.666-1.035C8.241 2.185 9.076 1.667 10 1.667s1.76.518 3.43 1.555l1.667 1.035c1.58.98 2.371 1.471 2.804 2.262M12.5 15h-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </a>
            <svg width="12" height="20" viewBox="0 0 13 20" fill="none" aria-hidden="true">
                <path d="M11.527 1 1.13 18.429" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <a href="{{ route('blog.index') }}" wire:navigate class="transition hover:text-base">{{ __('Blog') }}</a>
            <svg width="12" height="20" viewBox="0 0 13 20" fill="none" aria-hidden="true">
                <path d="M11.527 1 1.13 18.429" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="truncate text-base">{{ $post->title }}</span>
        </nav>

        <div class="blog-article-layout flex flex-col gap-6">
            <div class="blog-article-main order-2 min-w-0 flex-1 space-y-6 lg:order-1">
                <article class="overflow-hidden rounded-sm border border-neutral bg-background-secondary">
                    @if($post->cover_image_url)
                        <div class="border-b border-neutral/70 bg-background">
                            <img
                                src="{{ $post->cover_image_url }}"
                                alt="{{ $post->title }}"
                                class="max-h-[460px] w-full object-cover"
                                loading="eager"
                                decoding="async"
                            >
                        </div>
                    @endif

                    <div class="space-y-6 p-4 sm:p-5">
                        <header class="space-y-4 border-b border-neutral/70 pb-4">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-base/60">
                                <span>{{ optional($post->published_at ?? $post->created_at)->toFormattedDateString() }}</span>
                                <span>{{ number_format($post->views ?? 0) }} {{ __('views') }}</span>
                            </div>

                            <div class="space-y-3">
                                <h1 class="text-2xl font-semibold tracking-tight text-base sm:text-3xl">{{ $post->title }}</h1>

                                @if($post->description)
                                    <p class="max-w-3xl text-base leading-7 text-base/75">{{ $post->description }}</p>
                                @endif
                            </div>

                            @if(!empty($post->tags))
                                <div class="flex flex-wrap gap-2 text-sm">
                                    @foreach($post->tags as $tag)
                                        <a
                                            href="{{ route('blog.index', ['tag' => $tag]) }}"
                                            wire:navigate
                                            class="inline-flex items-center rounded-sm border border-neutral px-2.5 py-1 text-sm text-base/70 transition hover:bg-background hover:text-base"
                                        >
                                            {{ $tag }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </header>

                        <div class="blog-markdown">
                            {!! $contentHtml !!}
                        </div>
                    </div>
                </article>

                <section
                    wire:ignore
                    x-data="blogFeedback({
                        helpfulUrl: @js(route('blog.feedback.helpful', $post)),
                        unhelpfulUrl: @js(route('blog.feedback.unhelpful', $post)),
                        loginUrl: @js(route('login')),
                        csrfToken: @js(csrf_token()),
                        helpfulResponses: @js($helpfulResponses),
                        hasSubmittedFeedback: @js($hasSubmittedFeedback),
                        feedbackWasHelpful: @js($feedbackWasHelpful),
                        requiresLogin: @js($requiresLoginForFeedback),
                        reasonOptions: @js($feedbackReasonOptions),
                    })"
                    class="rounded-sm border border-neutral bg-background-secondary px-4 py-4 sm:px-5 sm:py-5"
                >
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1 space-y-3">
                            <p class="text-xl font-semibold tracking-tight text-base">{{ __('Did you find this blog helpful?') }}</p>

                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-base/68">
                                <span class="inline-flex items-center gap-2.5">
                                    <x-ri-checkbox-circle-fill class="size-4 text-primary" />
                                    <span>
                                        <span x-text="formattedHelpfulResponses()"></span>
                                        <span x-text="helpfulResponses === 1 ? 'person' : 'people'"></span>
                                        {{ __('found this blog helpful.') }}
                                    </span>
                                </span>

                                <span x-cloak x-show="hasSubmittedFeedback" class="inline-flex items-center gap-2.5 text-base/80">
                                    <x-ri-check-line class="size-4 text-primary" />
                                    <span x-text="feedbackWasHelpful ? @js(__('You marked this post as helpful.')) : @js(__('You told us this post needs work.'))"></span>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-neutral/70 pt-4 sm:flex-row sm:justify-end sm:border-t-0 sm:pt-0 lg:ml-8 lg:shrink-0 lg:self-center lg:py-1">
                            <button
                                type="button"
                                x-on:click="handleHelpfulClick()"
                                x-bind:disabled="busy || hasSubmittedFeedback"
                                class="inline-flex h-9 items-center justify-center gap-2 rounded-sm border border-transparent bg-primary px-4 text-sm font-semibold text-white transition hover:bg-primary/80 disabled:cursor-not-allowed disabled:opacity-55"
                            >
                                <x-ri-check-line class="size-4" />
                                <span>{{ __('Yes') }}</span>
                            </button>

                            <button
                                type="button"
                                x-on:click="handleUnhelpfulClick()"
                                x-bind:disabled="busy || hasSubmittedFeedback"
                                class="inline-flex h-9 items-center justify-center gap-2 rounded-sm border border-neutral bg-background px-4 text-sm font-medium text-base transition hover:bg-background-secondary disabled:cursor-not-allowed disabled:opacity-55"
                            >
                                <x-ri-close-line class="size-4" />
                                <span>{{ __('No') }}</span>
                            </button>
                        </div>
                    </div>

                    <template x-teleport="body">
                        <div
                            x-cloak
                            x-show="open"
                            x-on:keydown.escape.window="closeModal()"
                            x-on:click="closeModal()"
                            tabindex="-1"
                            class="fixed inset-0 z-30 flex items-center justify-center bg-black/55 px-4 py-8"
                        >
                            <div
                                x-on:click.stop
                                class="w-full max-w-3xl rounded-sm border border-neutral bg-background-secondary"
                            >
                                <div class="flex items-start justify-between gap-4 border-b border-neutral/70 px-4 py-4 sm:px-5">
                                    <div class="space-y-2">
                                        <p class="text-xl font-semibold tracking-tight text-base">{{ __('What should we improve?') }}</p>
                                        <p class="max-w-2xl text-sm leading-7 text-base/68">{{ __('Pick the reasons that fit, then add context if needed.') }}</p>
                                    </div>

                                    <button
                                        type="button"
                                        x-on:click="closeModal()"
                                        class="inline-flex size-9 items-center justify-center rounded-sm border border-transparent text-base/65 transition hover:border-neutral hover:bg-background hover:text-base"
                                        aria-label="{{ __('Close feedback dialog') }}"
                                    >
                                        <x-ri-close-fill class="size-5" />
                                    </button>
                                </div>

                                <form x-on:submit.prevent="submitUnhelpful()" class="space-y-5 px-4 py-4 sm:px-5 sm:py-5">
                                    <div class="space-y-4">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <template x-for="reason in reasonOptions" :key="reason">
                                                <label class="flex items-start gap-3 rounded-sm border border-neutral bg-background px-3 py-3 text-sm leading-6 text-base/82 transition hover:border-primary/30 hover:bg-background-secondary">
                                                    <input
                                                        type="checkbox"
                                                        x-bind:value="reason"
                                                        x-model="selectedReasons"
                                                        class="mt-1 size-4 border-neutral bg-background text-primary"
                                                    >
                                                    <span x-text="reason"></span>
                                                </label>
                                            </template>
                                        </div>

                                        <p x-cloak x-show="errorMessage" class="text-sm text-red-500" x-text="errorMessage"></p>
                                    </div>

                                    <div class="space-y-3">
                                        <label for="blog-feedback-note" class="text-sm font-medium text-base">{{ __('Anything else?') }}</label>
                                        <textarea
                                            id="blog-feedback-note"
                                            x-model="note"
                                            rows="5"
                                            maxlength="1000"
                                            class="w-full rounded-sm border border-neutral bg-background px-3 py-2.5 text-sm leading-6 text-base outline-none transition focus:border-primary focus:ring-1 focus:ring-primary/50"
                                            placeholder="{{ __('Tell us what was missing, confusing, or outdated.') }}"
                                        ></textarea>
                                    </div>

                                    <div class="flex flex-col gap-3 border-t border-neutral/70 pt-5 sm:flex-row sm:justify-end">
                                        <button
                                            type="button"
                                            x-on:click="closeModal()"
                                            class="inline-flex min-h-9 items-center justify-center rounded-sm border border-neutral bg-background px-4 py-2 text-sm font-medium text-base transition hover:bg-background-secondary"
                                        >
                                            {{ __('Cancel') }}
                                        </button>

                                        <button
                                            type="submit"
                                            x-bind:disabled="busy"
                                            class="inline-flex min-h-9 items-center justify-center gap-2 rounded-sm border border-transparent bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/80 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <x-ri-check-line class="size-4" />
                                            <span>{{ __('Send feedback') }}</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </section>

                @if($previousPost || $nextPost)
                    <footer class="grid gap-3 md:grid-cols-2">
                        @if($previousPost)
                            <a
                                href="{{ route('blog.show', $previousPost) }}"
                                wire:navigate
                                class="flex h-full flex-col gap-2 rounded-sm border border-neutral bg-background-secondary px-3 py-3 text-left transition hover:bg-background"
                            >
                                <span class="text-xs text-base/45">{{ __('Previous article') }}</span>
                                <span class="font-medium text-base">{{ $previousPost->title }}</span>
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if($nextPost)
                            <a
                                href="{{ route('blog.show', $nextPost) }}"
                                wire:navigate
                                class="flex h-full flex-col gap-2 rounded-sm border border-neutral bg-background-secondary px-3 py-3 text-left transition hover:bg-background md:text-right"
                            >
                                <span class="text-xs text-base/45">{{ __('Next article') }}</span>
                                <span class="font-medium text-base">{{ $nextPost->title }}</span>
                            </a>
                        @endif
                    </footer>
                @endif
            </div>

            <aside class="blog-article-sidebar order-1 self-start lg:order-2">
                <section class="blog-article-sidebar-panel rounded-sm border border-neutral bg-background-secondary p-4">
                    <div class="space-y-3 border-b border-neutral/70 pb-4">
                        <p class="text-sm font-medium text-base">{{ __('Article map') }}</p>
                        <div class="space-y-2 text-sm text-base/65">
                            <p>{{ number_format($post->views ?? 0) }} {{ __('views') }}</p>
                        </div>
                    </div>

                    @if(!empty($tableOfContents))
                        <nav class="mt-4 space-y-1" aria-label="{{ __('Table of contents') }}">
                            @foreach($tableOfContents as $heading)
                                <a
                                    href="#{{ $heading['id'] }}"
                                    data-blog-toc-link
                                    data-target="{{ $heading['id'] }}"
                                    class="blog-toc-link block rounded-sm border border-transparent px-3 py-2 text-sm leading-5 text-base/65 transition hover:border-neutral hover:bg-background hover:text-base"
                                    style="padding-left: calc(0.75rem + {{ max(0, $heading['level'] - 1) * 0.55 }}rem);"
                                >
                                    {{ $heading['text'] }}
                                </a>
                            @endforeach
                        </nav>
                    @else
                        <p class="mt-4 text-sm leading-6 text-base/65">{{ __('Add H1-H4 headings to the markdown content to build the article map automatically.') }}</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</div>

@once
    <style>
        .blog-markdown {
            max-width: none;
            color: color-mix(in srgb, var(--blog-base) 86%, transparent);
            font-size: 0.9375rem;
            line-height: 1.75;
        }

        .blog-markdown [data-blog-table-wrapper="true"] {
            overflow-x: auto;
        }

        .blog-markdown p,
        .blog-markdown ul,
        .blog-markdown ol,
        .blog-markdown pre,
        .blog-markdown table,
        .blog-markdown blockquote {
            margin: 1rem 0;
        }

        .blog-markdown h1,
        .blog-markdown h2,
        .blog-markdown h3,
        .blog-markdown h4 {
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: var(--blog-base);
            font-weight: 600;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }

        .blog-markdown h1 { font-size: 1.875rem; }
        .blog-markdown h2 { font-size: 1.5rem; }
        .blog-markdown h3 { font-size: 1.25rem; }
        .blog-markdown h4 { font-size: 1.125rem; }

        .blog-markdown a {
            color: var(--blog-primary);
            text-decoration: underline;
            text-underline-offset: 0.2rem;
        }

        .blog-markdown ul,
        .blog-markdown ol {
            padding-left: 1.25rem;
        }

        .blog-markdown ul {
            list-style: disc;
        }

        .blog-markdown ol {
            list-style: decimal;
        }

        .blog-markdown li + li {
            margin-top: 0.35rem;
        }

        .blog-markdown blockquote {
            border-left: 2px solid color-mix(in srgb, var(--blog-primary) 35%, transparent);
            padding-left: 1rem;
            color: color-mix(in srgb, var(--blog-base) 72%, transparent);
            font-style: italic;
        }

        .blog-markdown code {
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 58%, transparent);
            border-radius: 0.125rem;
            background: var(--blog-background);
            padding: 0.1rem 0.35rem;
            font-size: 0.92em;
        }

        .blog-markdown pre {
            overflow-x: auto;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 58%, transparent);
            border-radius: 0.125rem;
            background: var(--blog-background);
            padding: 0.875rem 1rem;
        }

        .blog-markdown pre code {
            border: 0;
            background: transparent;
            padding: 0;
        }

        .blog-markdown hr {
            margin: 1.5rem 0;
            border-color: color-mix(in srgb, var(--blog-neutral) 58%, transparent);
        }

        .blog-markdown img {
            width: 100%;
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 58%, transparent);
            border-radius: 0.125rem;
        }

        .blog-markdown table {
            width: 100%;
            min-width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .blog-markdown th,
        .blog-markdown td {
            border: 1px solid color-mix(in srgb, var(--blog-neutral) 58%, transparent);
            padding: 0.625rem 0.75rem;
            text-align: left;
            vertical-align: top;
        }

        .blog-markdown th {
            background: var(--blog-background);
            color: var(--blog-base);
            font-weight: 600;
        }

        .blog-markdown tbody tr:nth-child(even) {
            background: color-mix(in srgb, var(--blog-background) 60%, transparent);
        }

        .blog-article-main :is(h1, h2, h3, h4)[id] {
            scroll-margin-top: 6.75rem;
        }

        @media (min-width: 1024px) {
            .blog-article-layout {
                display: flex;
                flex-direction: row;
                align-items: flex-start;
                gap: 1.5rem;
            }

            .blog-article-main {
                flex: 1 1 auto;
                min-width: 0;
            }

            .blog-article-sidebar {
                position: relative;
                width: 19rem;
                flex: 0 0 19rem;
                align-self: flex-start;
            }

            .blog-article-sidebar-panel {
                width: 100%;
                height: fit-content;
                max-height: var(--blog-sidebar-max-height, calc(100svh - 7rem));
                overflow-y: auto;
                overscroll-behavior: contain;
            }
        }

        @media (min-width: 1280px) {
            .blog-article-sidebar {
                width: 20rem;
                flex-basis: 20rem;
            }
        }
    </style>

    <script>
        (function () {
            window.blogFeedback = function (config) {
                return {
                    open: false,
                    busy: false,
                    helpfulResponses: Number(config.helpfulResponses || 0),
                    hasSubmittedFeedback: Boolean(config.hasSubmittedFeedback),
                    feedbackWasHelpful: config.feedbackWasHelpful,
                    requiresLogin: Boolean(config.requiresLogin),
                    reasonOptions: Array.isArray(config.reasonOptions) ? config.reasonOptions : [],
                    selectedReasons: [],
                    note: '',
                    errorMessage: '',
                    formattedHelpfulResponses() {
                        return new Intl.NumberFormat().format(this.helpfulResponses);
                    },
                    handleHelpfulClick() {
                        if (this.requiresLogin) {
                            window.location.href = config.loginUrl;
                            return;
                        }

                        if (this.busy || this.hasSubmittedFeedback) {
                            return;
                        }

                        this.submitHelpful();
                    },
                    handleUnhelpfulClick() {
                        if (this.requiresLogin) {
                            window.location.href = config.loginUrl;
                            return;
                        }

                        if (this.busy || this.hasSubmittedFeedback) {
                            return;
                        }

                        this.errorMessage = '';
                        this.open = true;
                    },
                    closeModal() {
                        this.open = false;
                        this.errorMessage = '';
                        this.selectedReasons = [];
                        this.note = '';
                    },
                    async submitHelpful() {
                        this.busy = true;
                        this.errorMessage = '';

                        try {
                            const response = await fetch(config.helpfulUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': config.csrfToken,
                                },
                                body: JSON.stringify({}),
                            });

                            if (response.status === 401) {
                                window.location.href = config.loginUrl;
                                return;
                            }

                            const payload = await response.json();

                            if (!response.ok) {
                                throw new Error(payload.message || 'Unable to save feedback.');
                            }

                            this.helpfulResponses = Number(payload.helpfulResponses || this.helpfulResponses);
                            this.hasSubmittedFeedback = Boolean(payload.hasSubmittedFeedback);
                            this.feedbackWasHelpful = payload.feedbackWasHelpful;
                        } catch (error) {
                            this.errorMessage = error.message || 'Unable to save feedback.';
                        } finally {
                            this.busy = false;
                        }
                    },
                    async submitUnhelpful() {
                        this.busy = true;
                        this.errorMessage = '';

                        try {
                            const response = await fetch(config.unhelpfulUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': config.csrfToken,
                                },
                                body: JSON.stringify({
                                    reasons: this.selectedReasons,
                                    note: this.note,
                                }),
                            });

                            if (response.status === 401) {
                                window.location.href = config.loginUrl;
                                return;
                            }

                            const payload = await response.json();

                            if (!response.ok) {
                                this.errorMessage = payload?.errors?.reasons?.[0] || payload.message || 'Unable to save feedback.';
                                return;
                            }

                            this.helpfulResponses = Number(payload.helpfulResponses || this.helpfulResponses);
                            this.hasSubmittedFeedback = Boolean(payload.hasSubmittedFeedback);
                            this.feedbackWasHelpful = payload.feedbackWasHelpful;
                            this.closeModal();
                        } catch (error) {
                            this.errorMessage = error.message || 'Unable to save feedback.';
                        } finally {
                            this.busy = false;
                        }
                    },
                };
            };

            var observer = null;

            function scrollToHeading(targetId, behavior) {
                if (!targetId) {
                    return;
                }

                var element = document.getElementById(targetId.replace(/^#/, ''));
                if (!element) {
                    return;
                }

                element.scrollIntoView({
                    block: 'start',
                    behavior: behavior || 'smooth',
                });
            }

            function bindTocLinks() {
                document.querySelectorAll('[data-blog-toc-link]').forEach(function (link) {
                    if (link.dataset.bound === 'true') {
                        return;
                    }

                    link.dataset.bound = 'true';

                    link.addEventListener('click', function (event) {
                        event.preventDefault();

                        var targetId = link.dataset.target;

                        if (!targetId) {
                            return;
                        }

                        history.replaceState(null, '', '#' + targetId);
                        scrollToHeading(targetId, 'smooth');
                    });
                });
            }

            function observeHeadings() {
                var links = Array.from(document.querySelectorAll('[data-blog-toc-link]'));
                var headings = links
                    .map(function (link) {
                        return document.getElementById(link.dataset.target || '');
                    })
                    .filter(Boolean);

                if (observer) {
                    observer.disconnect();
                }

                if (!headings.length) {
                    return;
                }

                observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        var id = entry.target.getAttribute('id');

                        links.forEach(function (link) {
                            var active = link.dataset.target === id;

                            link.classList.toggle('border-neutral', active);
                            link.classList.toggle('bg-background', active);
                            link.classList.toggle('text-base', active);
                            link.classList.toggle('text-base/65', !active);
                        });
                    });
                }, {
                    rootMargin: '-18% 0px -65% 0px',
                    threshold: 0,
                });

                headings.forEach(function (heading) {
                    observer.observe(heading);
                });
            }

            function initBlogArticle() {
                bindTocLinks();
                observeHeadings();

                if (window.location.hash) {
                    setTimeout(function () {
                        scrollToHeading(window.location.hash, 'auto');
                    }, 60);
                }
            }

            document.addEventListener('DOMContentLoaded', initBlogArticle);
            document.addEventListener('livewire:navigated', initBlogArticle);
        })();
    </script>
@endonce
