<?php

namespace Paymenter\Extensions\Others\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $table = 'ext_blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_canonical_url',
        'seo_image_url',
        'seo_robots',
        'content',
        'tags',
        'views',
        'published_at',
        'is_published',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'tags' => 'array',
        'views' => 'integer',
        'is_published' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeOrdered(Builder $query, string $sortOption = 'published_at_desc'): Builder
    {
        return match ($sortOption) {
            'published_at_asc' => $query
                ->orderByRaw('COALESCE(published_at, created_at) asc')
                ->orderBy('id'),
            'updated_at_desc' => $query
                ->orderByDesc('updated_at')
                ->orderByRaw('COALESCE(published_at, created_at) desc'),
            'views_desc' => $query
                ->orderByDesc('views')
                ->orderByRaw('COALESCE(published_at, created_at) desc'),
            default => $query
                ->orderByRaw('COALESCE(published_at, created_at) desc')
                ->orderByDesc('id'),
        };
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(BlogPostFeedback::class, 'blog_post_id');
    }

    public function trackedViews(): HasMany
    {
        return $this->hasMany(BlogPostView::class, 'blog_post_id');
    }

    public function helpfulFeedback(): HasMany
    {
        return $this->feedback()->where('is_helpful', true);
    }

    public function unhelpfulFeedback(): HasMany
    {
        return $this->feedback()->where('is_helpful', false);
    }

    public function excerpt(int $limit = 180): string
    {
        return $this->description ?: Str::limit($this->plainTextContent(), $limit);
    }

    public function seoTitle(): string
    {
        return trim((string) ($this->seo_title ?: $this->title));
    }

    public function seoDescription(int $limit = 160): string
    {
        $description = trim((string) ($this->seo_description ?: $this->description));

        if ($description !== '') {
            return $description;
        }

        return Str::limit($this->plainTextContent(), $limit);
    }

    public function seoImageUrl(): ?string
    {
        $image = trim((string) ($this->seo_image_url ?: $this->cover_image_url));

        return $image !== '' ? $image : null;
    }

    public function seoCanonicalUrl(): string
    {
        $canonical = trim((string) $this->seo_canonical_url);

        return $canonical !== '' ? $canonical : route('blog.show', $this);
    }

    public function seoRobots(): string
    {
        return trim((string) ($this->seo_robots ?: 'index,follow'));
    }

    public function seoKeywords(): string
    {
        return trim((string) $this->seo_keywords);
    }

    public function readingTimeMinutes(int $wordsPerMinute = 220): int
    {
        $wordCount = str_word_count($this->plainTextContent());

        return max(1, (int) ceil($wordCount / max(1, $wordsPerMinute)));
    }

    protected function plainTextContent(): string
    {
        $content = $this->content ?? '';

        $content = preg_replace('/```.*?```/s', ' ', $content) ?? $content;
        $content = preg_replace('/`([^`]+)`/', '$1', $content) ?? $content;
        $content = preg_replace('/!\[[^\]]*]\([^)]+\)/', ' ', $content) ?? $content;
        $content = preg_replace('/\[(.*?)\]\([^)]+\)/', '$1', $content) ?? $content;
        $content = preg_replace('/^#{1,6}\s+/m', '', $content) ?? $content;
        $content = preg_replace('/[*_>#~\-|]/', ' ', $content) ?? $content;

        return trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');
    }
}
