<?php

namespace Paymenter\Extensions\Others\Blog\Livewire\Blog;

use App\Livewire\Component;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;
use Paymenter\Extensions\Others\Blog\Support\BlogFeedbackManager;
use Paymenter\Extensions\Others\Blog\Support\BlogMarkdownRenderer;
use Paymenter\Extensions\Others\Blog\Support\BlogViewTracker;

class Show extends Component
{
    protected const FEEDBACK_REASONS = [
        'It did not answer my question',
        'It was hard to follow',
        'It seems outdated',
        'It needs more examples',
        'Something appears broken',
        'I was looking for something else',
    ];

    public BlogPost $post;

    public string $contentHtml = '';

    public array $tableOfContents = [];

    public ?BlogPost $previousPost = null;

    public ?BlogPost $nextPost = null;

    public array $feedbackReasonOptions = [];

    public bool $hasSubmittedFeedback = false;

    public ?bool $feedbackWasHelpful = null;

    public int $helpfulResponses = 0;

    public bool $requiresLoginForFeedback = false;

    public function mount(): void
    {
        abort_unless($this->post->is_published, 404);
        abort_if($this->post->published_at?->isFuture(), 404);

        $this->feedbackReasonOptions = self::FEEDBACK_REASONS;
        $this->buildContent();
        $this->loadAdjacentPosts();
        $this->post->views = app(BlogViewTracker::class)->track($this->post);
        $this->refreshFeedbackState();
    }

    public function render()
    {
        return view('blog::show')->layoutData([
            'title' => $this->post->seoTitle(),
            'description' => $this->post->seoDescription(),
            'image' => $this->post->seoImageUrl(),
        ]);
    }

    protected function buildContent(): void
    {
        $document = app(BlogMarkdownRenderer::class)->render($this->post->content);

        $this->contentHtml = $document->html;
        $this->tableOfContents = $document->headings;
    }

    protected function loadAdjacentPosts(): void
    {
        $publishedAt = $this->post->published_at ?? $this->post->created_at;

        if (!$publishedAt) {
            return;
        }

        $column = 'COALESCE(published_at, created_at)';

        $this->previousPost = BlogPost::published()
            ->whereKeyNot($this->post->getKey())
            ->whereRaw($column . ' > ?', [$publishedAt])
            ->orderByRaw($column . ' asc')
            ->first();

        $this->nextPost = BlogPost::published()
            ->whereKeyNot($this->post->getKey())
            ->whereRaw($column . ' < ?', [$publishedAt])
            ->orderByRaw($column . ' desc')
            ->first();
    }

    protected function refreshFeedbackState(): void
    {
        $feedbackManager = app(BlogFeedbackManager::class);

        $this->requiresLoginForFeedback = !auth()->check();

        if (!auth()->check()) {
            $this->hasSubmittedFeedback = false;
            $this->feedbackWasHelpful = null;
            $this->helpfulResponses = $feedbackManager->helpfulResponses($this->post);

            return;
        }

        $feedback = $feedbackManager->feedbackForUser($this->post, (int) auth()->id());

        $this->hasSubmittedFeedback = (bool) $feedback;
        $this->feedbackWasHelpful = $feedback?->is_helpful;
        $this->helpfulResponses = $feedbackManager->helpfulResponses($this->post);
    }
}
