<?php

namespace Paymenter\Extensions\Others\Blog\Support;

use Paymenter\Extensions\Others\Blog\Models\BlogPost;
use Paymenter\Extensions\Others\Blog\Models\BlogPostFeedback;

class BlogFeedbackManager
{
    public function helpfulResponses(BlogPost $post): int
    {
        return BlogPostFeedback::query()
            ->where('blog_post_id', $post->getKey())
            ->helpful()
            ->count();
    }

    public function feedbackForUser(BlogPost $post, int $userId): ?BlogPostFeedback
    {
        return BlogPostFeedback::query()
            ->where('blog_post_id', $post->getKey())
            ->where('user_id', $userId)
            ->first();
    }

    public function store(BlogPost $post, int $userId, bool $isHelpful, array $reasons = [], string $note = ''): BlogPostFeedback
    {
        $sessionId = $this->feedbackSessionId();

        $feedback = BlogPostFeedback::query()
            ->where('blog_post_id', $post->getKey())
            ->where(function ($query) use ($userId, $sessionId) {
                $query->where('user_id', $userId)
                    ->orWhere(function ($query) use ($sessionId) {
                        $query->whereNull('user_id')
                            ->where('session_id', $sessionId);
                    });
            })
            ->first();

        if (!$feedback) {
            $feedback = new BlogPostFeedback([
                'blog_post_id' => $post->getKey(),
            ]);
        }

        $feedback->fill([
            'user_id' => $userId,
            'is_helpful' => $isHelpful,
            'reasons' => $reasons ?: null,
            'note' => $note !== '' ? $note : null,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 512),
        ]);

        $feedback->save();

        return $feedback;
    }

    protected function feedbackSessionId(): string
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        return session()->getId();
    }
}
