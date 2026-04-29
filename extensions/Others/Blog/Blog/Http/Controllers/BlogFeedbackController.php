<?php

namespace Paymenter\Extensions\Others\Blog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;
use Paymenter\Extensions\Others\Blog\Support\BlogFeedbackManager;

class BlogFeedbackController extends Controller
{
    protected const FEEDBACK_REASONS = [
        'It did not answer my question',
        'It was hard to follow',
        'It seems outdated',
        'It needs more examples',
        'Something appears broken',
        'I was looking for something else',
    ];

    public function helpful(Request $request, BlogPost $post, BlogFeedbackManager $feedback): JsonResponse
    {
        $this->abortIfUnavailable($post);
        $userId = $this->authenticatedUserId();

        $feedback->store($post, $userId, true);

        return response()->json([
            'helpfulResponses' => $feedback->helpfulResponses($post),
            'hasSubmittedFeedback' => true,
            'feedbackWasHelpful' => true,
            'message' => 'Thanks for the feedback.',
        ]);
    }

    public function unhelpful(Request $request, BlogPost $post, BlogFeedbackManager $feedback): JsonResponse
    {
        $this->abortIfUnavailable($post);
        $userId = $this->authenticatedUserId();

        $validated = $request->validate([
            'reasons' => ['array'],
            'reasons.*' => ['string', Rule::in(self::FEEDBACK_REASONS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reasons = array_values(array_filter($validated['reasons'] ?? []));
        $note = trim((string) ($validated['note'] ?? ''));

        if ($reasons === [] && $note === '') {
            return response()->json([
                'message' => 'Select at least one reason or share a short note.',
                'errors' => [
                    'reasons' => ['Select at least one reason or share a short note.'],
                ],
            ], 422);
        }

        $feedback->store($post, $userId, false, $reasons, $note);

        return response()->json([
            'helpfulResponses' => $feedback->helpfulResponses($post),
            'hasSubmittedFeedback' => true,
            'feedbackWasHelpful' => false,
            'message' => 'Thanks. We will use this feedback to improve the post.',
        ]);
    }

    protected function abortIfUnavailable(BlogPost $post): void
    {
        abort_unless($post->is_published, 404);
        abort_if($post->published_at?->isFuture(), 404);
    }

    protected function authenticatedUserId(): int
    {
        abort_unless(auth()->check(), 401);

        return (int) auth()->id();
    }
}
