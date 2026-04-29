<?php

namespace Paymenter\Extensions\Others\Blog;

use App\Attributes\ExtensionMeta;
use App\Classes\Extension\Extension;
use App\Helpers\ExtensionHelper;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource;
use Paymenter\Extensions\Others\Blog\Livewire\Blog\Index;
use Paymenter\Extensions\Others\Blog\Livewire\Blog\Show;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;

// YOU ARE NBOT ALLOWED TO SELL THIS EXTENSION OR INCLUDE IT IN ANY OTHER PRODUCT WITHOUT PRIOR INFO
// Contact - Contact@cloee.pro
// Discord - https://discord.gg/A6TJh5GdVg
// Author - Cloee


#[ExtensionMeta(
    name: 'Blog',
    description: 'Publish a proper blog inside the client area with markdown posts and a cleaner reading experience.',
    version: '2.3.0',
    author: 'Cloee',
    url: 'https://paymenter.org',
    icon: 'ri-article-line'
)]
class Blog extends Extension
{
    public function getConfig($values = [])
    {
        $fields = [
            [
                'name' => 'blog_title',
                'label' => 'Blog Title',
                'type' => 'text',
                'default' => $values['blog_title'] ?? 'Blog',
                'required' => true,
                'validation' => 'string|max:255',
                'description' => 'Used for the public blog index page title.',
            ],
            [
                'name' => 'blog_description',
                'label' => 'Blog Description',
                'type' => 'text',
                'default' => $values['blog_description'] ?? '',
                'validation' => 'nullable|string|max:255',
                'description' => 'Short copy displayed above the post archive.',
            ],
            [
                'name' => 'view_count_mode',
                'label' => 'View Counting',
                'type' => 'select',
                'default' => $values['view_count_mode'] ?? 'page_visits',
                'options' => [
                    'page_visits' => 'Every page visit',
                    'per_browser' => 'One view per browser session',
                    'per_ip' => 'One view per IP address',
                    'per_browser_per_ip' => 'One view per browser session and IP',
                ],
                'description' => 'Controls how article views are counted for display and popular sorting.',
            ],
            [
                'name' => 'card_hover_enabled',
                'label' => 'Enable Card Hover Animation',
                'type' => 'checkbox',
                'default' => $values['card_hover_enabled'] ?? true,
                'description' => 'Adds a small lift and media motion when hovering blog cards.',
            ],
            [
                'name' => 'card_glow_enabled',
                'label' => 'Enable Card Hover Glow',
                'type' => 'checkbox',
                'default' => $values['card_glow_enabled'] ?? true,
                'description' => 'Shows the blog card accent glow only while hovering.',
            ],
        ];

        try {
            $fields[] = [
                'name' => 'notice',
                'type' => 'placeholder',
                'label' => new HtmlString('Manage posts from the <a class="text-primary-600" href="' . BlogPostResource::getUrl() . '">Blog admin</a> page.'),
            ];
        } catch (\Throwable) {
            $fields[] = [
                'name' => 'notice',
                'type' => 'placeholder',
                'label' => new HtmlString('Enable the extension to start publishing posts.'),
            ];
        }

        return $fields;
    }

    public function installed(): void
    {
        $this->syncSchema();
    }

    public function uninstalled(): void
    {
        ExtensionHelper::rollbackMigrations('extensions/Others/Blog/database/migrations');
    }

    public function upgraded($oldVersion = null): void
    {
        $this->syncSchema();
    }

    public function enabled(): void
    {
        $this->syncSchema();
    }

    public function boot(): void
    {
        require __DIR__ . '/routes/web.php';

        View::addNamespace('blog', __DIR__ . '/resources/views');

        Livewire::component('blog.index', Index::class);
        Livewire::component('blog.show', Show::class);

        Gate::policy(BlogPost::class, Policies\BlogPostPolicy::class);

        Event::listen('navigation', function () {
            return [
                'name' => 'Blog',
                'route' => 'blog.index',
                'icon' => 'ri-article',
                'separator' => true,
                'children' => [],
            ];
        });

        Event::listen('permissions', fn () => $this->permissions());
        Event::listen('api.permissions', fn () => $this->permissions());
        Event::listen('head', function () {
            if (! request()->routeIs('blog.show')) {
                return;
            }

            $post = request()->route('post');

            if (!$post instanceof BlogPost) {
                return;
            }

            return [
                'view' => view('blog::partials.head-seo', [
                    'post' => $post,
                ]),
            ];
        });
        Event::listen('body', function () {
            if (! request()->routeIs('blog.show')) {
                return;
            }

            return [
                'view' => view('blog::partials.article-sticky-bridge'),
            ];
        });
        Event::listen('pages.home', function () {
            $posts = BlogPost::query()
                ->published()
                ->ordered()
                ->limit(3)
                ->get();

            if ($posts->isEmpty()) {
                return;
            }

            return [
                'view' => view('blog::home-widget', [
                    'posts' => $posts,
                    'cardHoverEnabled' => $this->isFeatureEnabled('card_hover_enabled'),
                    'cardGlowEnabled' => $this->isFeatureEnabled('card_glow_enabled'),
                ]),
            ];
        });

        $this->ensureTutorialPost();
    }

    protected function permissions(): array
    {
        return [
            'admin.blog.view' => 'View Blog Posts',
            'admin.blog.create' => 'Create Blog Posts',
            'admin.blog.update' => 'Update Blog Posts',
            'admin.blog.delete' => 'Delete Blog Posts',
        ];
    }

    protected function syncSchema(): void
    {
        ExtensionHelper::runMigrations('extensions/Others/Blog/database/migrations');
        $this->ensureTutorialPost();
    }

    protected function isFeatureEnabled(string $key, bool $default = true): bool
    {
        $value = $this->config($key);

        if ($value === null || $value === '') {
            return $default;
        }

        // Extension settings come back as mixed strings/bools depending on how they were saved.
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    protected function ensureTutorialPost(): void
    {
        if (!Schema::hasTable('ext_blog_posts')) {
            return;
        }

        BlogPost::firstOrCreate(
            ['slug' => 'getting-started-with-the-paymenter-blog'],
            [
                'title' => 'Getting Started with the Paymenter Blog',
                'description' => 'Step-by-step tutorial on publishing your first article with Markdown, images, tables, and code blocks.',
                'cover_image_url' => 'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=1200&q=80',
                'content' => <<<'MARKDOWN'
# Create Your First Blog Post

Welcome to the **Paymenter Blog Extension**. This guide walks through publishing your first article, formatting content with Markdown, embedding media, and sharing useful updates with your readers.

---

## What You'll Learn

- Navigating to the blog administration panel
- Drafting rich content with headings, lists, tables, quotes, and callouts
- Embedding images and referencing assets
- Publishing or scheduling your work when it is ready

> Draft in Markdown to keep your content portable and easy to maintain.

---

## 1. Open the Blog Admin Area

1. Sign in as an administrator.
2. Head to **Administration -> Blog**.
3. Click **Create Blog Post** to open the editor.

![Author writing notes](https://images.unsplash.com/photo-1517430816045-df4b7de11d1d?auto=format&fit=crop&w=1200&q=80)

---

## 2. Add Core Details

Fill in the essentials to help readers find your article:

| Field | Purpose |
| --- | --- |
| **Title** | Displayed on the archive card and detail page |
| **Slug** | Used in the URL |
| **Description** | Shown on the archive page as a teaser |
| **Cover Image URL** | Optional lead image for the post |

Set **Published At** to the moment you want the post to appear. Leave it blank to publish immediately when toggling **Is Published**.

---

## 3. Write Markdown Content

The editor supports headings, links, lists, images, tables, code blocks, and quotes.

```markdown
### Example Markdown

**Bold text** and *italic text*

- Bullet lists keep content scannable
- Use `inline code` for commands

![Inline image example](https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=80)

> Quotes are great for key notes.

| Shortcut | Result |
| ------- | ------ |
| `# Heading` | H1 Heading |
| `## Heading` | H2 Heading |
| `* item` | Bullet item |
```

You can also reference external resources:

- [Markdown Guide](https://www.markdownguide.org/basic-syntax/)
- [Unsplash](https://unsplash.com/) for imagery

---

## 4. Preview and Publish

When you are satisfied:

1. Toggle **Is Published** on.
2. Click **Save**.
3. Visit `/blog` to confirm the new post appears in the archive.

Need to schedule content? Set a future **Published At** timestamp and keep the post published. The article will appear automatically once that time is reached.

---

## Next Steps

- Update the blog title or description from the Blog extension settings.
- Invite team members with the `admin.blog.*` permissions.
- Build article structure with clear H1-H4 headings so the reading sidebar stays useful.
MARKDOWN,
                'tags' => ['Tutorial', 'Getting Started', 'Markdown'],
                'is_published' => true,
                'published_at' => now(),
            ]
        );
    }
}
