<?php

namespace Paymenter\Extensions\Others\Blog\Support;

use App\Helpers\ExtensionHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;
use Paymenter\Extensions\Others\Blog\Models\BlogPostView;
use Throwable;

class BlogViewTracker
{
    public const MODE_PAGE_VISITS = 'page_visits';

    public const MODE_PER_BROWSER = 'per_browser';

    public const MODE_PER_IP = 'per_ip';

    public const MODE_PER_BROWSER_PER_IP = 'per_browser_per_ip';

    public function currentMode(): string
    {
        try {
            $value = ExtensionHelper::getExtension('other', 'Blog')->config('view_count_mode');
        } catch (Throwable) {
            $value = null;
        }

        return $this->normalizeMode($value);
    }

    public function track(BlogPost $post, ?string $mode = null): int
    {
        $mode = $this->normalizeMode($mode);
        $sessionId = $this->sessionId();
        $ip = $this->ipAddress();

        // Raw page visits are the only mode where every request becomes a new row.
        if ($mode === self::MODE_PAGE_VISITS) {
            BlogPostView::query()->create([
                'blog_post_id' => $post->getKey(),
                'session_id' => $sessionId,
                'ip_address' => $ip,
            ]);

            return $this->syncCount($post, $mode);
        }

        $query = BlogPostView::query()->where('blog_post_id', $post->getKey());

        if ($mode === self::MODE_PER_BROWSER) {
            $query->where('session_id', $sessionId);
        } elseif ($mode === self::MODE_PER_IP) {
            $query->where('ip_address', $ip);
        } else {
            $query->where('session_id', $sessionId)->where('ip_address', $ip);
        }

        // For deduplicated modes we only create a visit when that visitor shape has not been seen yet.
        if (!$query->exists()) {
            BlogPostView::query()->create([
                'blog_post_id' => $post->getKey(),
                'session_id' => $sessionId,
                'ip_address' => $ip,
            ]);
        }

        return $this->syncCount($post, $mode);
    }

    public function count(BlogPost $post, ?string $mode = null): int
    {
        $mode = $this->normalizeMode($mode);
        $query = BlogPostView::query()->where('blog_post_id', $post->getKey());

        return $this->applyCountMode($query, $mode);
    }

    public function applyPopularOrdering(Builder $query, ?string $mode = null): Builder
    {
        $mode = $this->normalizeMode($mode);

        return $query
            ->orderByDesc($this->countSubquery($mode))
            ->orderByRaw('COALESCE(published_at, created_at) desc');
    }

    public function countSubquery(?string $mode = null): QueryBuilder
    {
        $mode = $this->normalizeMode($mode);
        $query = DB::table('ext_blog_post_views')
            ->whereColumn('ext_blog_post_views.blog_post_id', 'ext_blog_posts.id');

        if ($mode === self::MODE_PAGE_VISITS) {
            return $query->selectRaw('count(*)');
        }

        if ($mode === self::MODE_PER_BROWSER) {
            return $query->selectRaw('count(distinct session_id)');
        }

        if ($mode === self::MODE_PER_IP) {
            return $query->selectRaw('count(distinct ip_address)');
        }

        // We intentionally keep this in SQL so "popular" sorting can stay in one query.
        return $query->selectRaw("count(distinct concat(coalesce(session_id, ''), '|', coalesce(ip_address, '')))");
    }

    protected function syncCount(BlogPost $post, string $mode): int
    {
        $count = $this->count($post, $mode);

        $post->forceFill(['views' => $count])->saveQuietly();
        $post->views = $count;

        return $count;
    }

    protected function applyCountMode(Builder $query, string $mode): int
    {
        if ($mode === self::MODE_PAGE_VISITS) {
            return $query->count();
        }

        if ($mode === self::MODE_PER_BROWSER) {
            return (int) $query->distinct('session_id')->count('session_id');
        }

        if ($mode === self::MODE_PER_IP) {
            return (int) $query->distinct('ip_address')->count('ip_address');
        }

        return (int) $query
            ->selectRaw("count(distinct concat(coalesce(session_id, ''), '|', coalesce(ip_address, ''))) as aggregate")
            ->value('aggregate');
    }

    protected function normalizeMode(?string $mode): string
    {
        return in_array($mode, [
            self::MODE_PAGE_VISITS,
            self::MODE_PER_BROWSER,
            self::MODE_PER_IP,
            self::MODE_PER_BROWSER_PER_IP,
        ], true) ? $mode : self::MODE_PAGE_VISITS;
    }

    protected function sessionId(): string
    {
        // The tracker can run before the session is touched anywhere else on the request.
        if (!session()->isStarted()) {
            session()->start();
        }

        return session()->getId();
    }

    protected function ipAddress(): string
    {
        return (string) request()->ip();
    }
}
