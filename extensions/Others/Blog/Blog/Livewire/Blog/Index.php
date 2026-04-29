<?php

namespace Paymenter\Extensions\Others\Blog\Livewire\Blog;

use App\Helpers\ExtensionHelper;
use App\Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;
use Paymenter\Extensions\Others\Blog\Support\BlogViewTracker;
use Throwable;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'tag', except: '')]
    public string $tag = '';

    #[Url(as: 'sort', except: 'published_at_desc')]
    public string $sortOption = 'published_at_desc';

    public string $pageTitle = 'Blog';

    public ?string $pageDescription = null;

    public bool $cardHoverEnabled = true;

    public bool $cardGlowEnabled = true;

    public function mount(): void
    {
        try {
            $extension = ExtensionHelper::getExtension('other', 'Blog');
            $this->pageTitle = $extension->config('blog_title') ?: 'Blog';
            $this->pageDescription = $extension->config('blog_description') ?: null;
            $this->cardHoverEnabled = $this->booleanConfig($extension->config('card_hover_enabled'), true);
            $this->cardGlowEnabled = $this->booleanConfig($extension->config('card_glow_enabled'), true);
        } catch (Throwable) {
            // Keep defaults if the extension config is not available yet.
        }

        $this->search = trim($this->search);
        $this->tag = trim($this->tag);
    }

    public function updatedSearch(string $value): void
    {
        $this->search = trim($value);
        $this->resetPage();
    }

    public function updatedTag(string $value): void
    {
        $this->tag = trim($value);
        $this->resetPage();
    }

    public function updatedSortOption(): void
    {
        $this->resetPage();
    }

    public function selectTag(string $tag): void
    {
        $tag = trim($tag);

        $this->tag = $this->tag === $tag ? '' : $tag;
        $this->resetPage();
    }

    public function applyTag(string $tag): void
    {
        $this->selectTag($tag);
    }

    public function clearSearch(): void
    {
        $this->clearFilters();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'tag']);
        $this->sortOption = 'published_at_desc';
        $this->resetPage();
    }

    public function render()
    {
        $posts = $this->postsQuery()
            ->paginate((int) config('settings.pagination', 9));

        return view('blog::index', [
            'posts' => $posts,
            'title' => $this->pageTitle,
            'description' => $this->pageDescription,
            'tagSummary' => $this->tagSummary(),
            'tagSuggestions' => $this->tagSuggestions(),
            'cardHoverEnabled' => $this->cardHoverEnabled,
            'cardGlowEnabled' => $this->cardGlowEnabled,
            'activeFilters' => collect([
                'search' => $this->search,
                'tag' => $this->tag,
            ])->filter(),
        ])->layoutData([
            'title' => $this->pageTitle,
            'description' => $this->pageDescription,
        ]);
    }

    protected function postsQuery(): Builder
    {
        $query = BlogPost::query()->published();
        $viewTracker = app(BlogViewTracker::class);
        $search = trim($this->search);

        if ($search !== '') {
            $normalizedSearch = Str::lower($search);

            // Tags are stored as JSON, so partial tag matching is easier to do in PHP
            // than trying to force vendor-specific JSON search SQL here.
            $matchingTagIds = BlogPost::published()
                ->get(['id', 'tags'])
                ->filter(function (BlogPost $post) use ($normalizedSearch) {
                    return collect($post->tags ?? [])
                        ->contains(fn ($tag) => Str::contains(Str::lower((string) $tag), $normalizedSearch));
                })
                ->pluck('id')
                ->all();

            $query->where(function (Builder $query) use ($search, $matchingTagIds) {
                $like = '%' . addcslashes($search, '\\%_') . '%';

                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('content', 'like', $like);

                if ($matchingTagIds !== []) {
                    $query->orWhereIn('id', $matchingTagIds);
                }
            });
        }

        if ($this->tag !== '') {
            $query->whereJsonContains('tags', $this->tag);
        }

        if ($this->sortOption === 'views_desc') {
            return $viewTracker->applyPopularOrdering($query, $viewTracker->currentMode());
        }

        return $query->ordered($this->sortOption);
    }

    protected function tagSummary(): Collection
    {
        return BlogPost::published()
            ->get(['tags'])
            ->flatMap(fn (BlogPost $post) => collect($post->tags ?? []))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->values();
    }

    protected function tagSuggestions(): Collection
    {
        return $this->tagSummary()
            ->pluck('label')
            ->values();
    }

    protected function booleanConfig(mixed $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
