---
name: block-designer
description: Use when designing HTML/CSS blocks (sections, cards, hero banners, feature grids, CTAs) for the Calaentar Paymenter theme — including PageBuilder sections, standalone marketing blocks, or storefront components. Activate whenever the request mentions "block", "section", "hero", "card", "CTA", "landing", or "PageBuilder". Output must use Calaentar design tokens, never raw color values.
---

# Calaentar Block Designer

Use this skill when generating HTML / CSS / Blade blocks meant to drop into the
Calaentar theme — PageBuilder sections, marketing blocks, dashboard cards,
or any standalone snippet.

## The aesthetic in one sentence

Dark, glassy, magenta-accented. Translucent surfaces with a soft purple glow
behind them. Pill buttons with brand gradient. Generous spacing. Radial
gradients in the page background you can sense but rarely look directly at.

## Hard constraints

- **Use design tokens, never raw values.** No literal `#f037a5`, no
  `rgba(0,0,0,0.5)`, no fixed `12px` border-radius. Every color, radius and
  glass parameter has a CSS variable already declared in
  `themes/calaentar/views/layouts/colors.blade.php`.
- **Dark only.** Don't add `dark:` Tailwind variants — the public site is
  dark always. Body has a hardcoded `class="dark"`.
- **No external images** for decoration. Logo / brand marks are fine; stock
  hero images are not. Use gradient backgrounds instead.
- **No external fonts.** Nunito is already loaded via the theme's
  `@theme { --font-sans: ... }`.
- **Accessible markup.** Heading order, `aria-*` attributes, focus styles
  via the existing `--color-primary` ring.

## Tokens you must use

| Need                | Use                                                         |
|---------------------|-------------------------------------------------------------|
| Brand magenta       | `hsl(var(--color-primary))`                                 |
| Brand purple        | `hsl(var(--color-secondary))`                               |
| Highlight pink      | `hsl(var(--color-accent))`                                  |
| Body text           | `hsl(var(--color-base))` / Tailwind `text-base`             |
| Muted text          | `hsl(var(--color-muted))` / Tailwind `text-muted`           |
| Page background     | `hsl(var(--color-background))` / Tailwind `bg-background`   |
| Card surface        | Use the `glass` or `glass-card` utility                     |
| Soft border         | `border-white/5` to `border-white/10`                       |
| Radius - small      | `var(--radius-sm)` / Tailwind `rounded-md`                  |
| Radius - medium     | `var(--radius-md)` / Tailwind `rounded-lg`                  |
| Radius - large      | `var(--radius-lg)` / Tailwind `rounded-xl`                  |
| Pill                | `var(--radius-xl)` / Tailwind `rounded-full`                |
| Brand gradient      | `gradient-bg` utility (or recreate via `--gradient-angle` + primary→accent) |
| Glow shadow         | `glow` utility                                              |
| Backdrop blur       | `backdrop-blur-xl` or the `glass` utility                   |

## Utility recipes

### Glass card with subtle hover lift

```html
<div class="glass-card p-6 transition hover:-translate-y-1 hover:shadow-2xl">
    ...
</div>
```

### Primary CTA button

```html
<button class="gradient-bg glow rounded-full px-6 py-3 text-white font-semibold hover:brightness-110 transition">
    Get started
</button>
```

### Section with ambient gradient title

```html
<section class="container py-20">
    <h2 class="text-4xl font-bold gradient-text">Beautiful things ship faster</h2>
    <p class="mt-4 text-muted max-w-prose">...</p>
</section>
```

### Feature grid

```html
<div class="container py-20 grid md:grid-cols-3 gap-6">
    @foreach ($features as $feature)
    <div class="glass-card p-6">
        <div class="size-12 rounded-full gradient-bg glow mb-4 flex items-center justify-center">
            <x-ri-icon-name class="size-6 text-white" />
        </div>
        <h3 class="text-xl font-semibold mb-2">{{ $feature['title'] }}</h3>
        <p class="text-muted">{{ $feature['body'] }}</p>
    </div>
    @endforeach
</div>
```

## When asked for a PageBuilder section

PageBuilder sections live at
`extensions/Others/PageBuilder/PageBuilder/resources/views/sections/<name>.blade.php`
and receive a `$content` array. The block should:

1. Read everything from `$content[...]` with sensible fallbacks.
2. Use only Calaentar tokens (PageBuilder's own `--hpb-*` tokens are aliased
   to `--color-*` in `themes/calaentar/css/app.css`, so either works).
3. Wrap everything in a `<section>` with `class="container py-20"` or a full
   bleed wrapper with a `<div class="container">` inside.

## Output format

When the user asks for a block, **always**:

1. State which tokens you used and why.
2. Show the snippet in a fenced ```html (or ```css) block.
3. Note any new utility you added to `themes/calaentar/css/app.css`.
4. If the block needs new theme settings (e.g. a new color stop), list them
   as a follow-up — don't silently invent settings.

## Don't

- Don't use shadcn/Headless UI/Bootstrap class names — this is plain Tailwind v4.
- Don't import a JS framework. Inline `x-data="{}"` Alpine is fine.
- Don't generate React. The site is server-rendered Blade.
- Don't reproduce someone else's design pixel-for-pixel. Use a reference for
  inspiration; build with Calaentar's tokens.

## Reference files when in doubt

- `themes/calaentar/theme.php` — full settings catalogue (every token's name + default)
- `themes/calaentar/css/app.css` — utilities + body gradient
- `themes/calaentar/views/layouts/colors.blade.php` — variable declarations
- `themes/calaentar/views/home.blade.php` — example minimal page
- `themes/calaentar/views/components/button/primary.blade.php` — gradient pill button
- `extensions/Others/PageBuilder/PageBuilder/resources/views/sections/` — existing PageBuilder sections to mirror
