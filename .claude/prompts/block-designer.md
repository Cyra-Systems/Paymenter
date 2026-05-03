# Calaentar block-designer prompts

Three reusable prompts for asking Claude (or any LLM with the right context) to
generate blocks for the Calaentar Paymenter theme. Paste one of these into a
fresh chat — fill in the `[BRACKETED]` parts before sending.

These prompts assume the assistant has access to either this repo or the
[Calaentar token reference](#token-reference-paste-this-if-the-assistant-has-no-repo-access)
at the bottom of this file.

---

## 1. HTML-only block

Use when you already have your CSS / utility setup and just need semantic
markup that composes existing Calaentar utilities.

```text
You are designing a Blade-flavoured HTML block for the Calaentar theme.

GOAL
[Describe the block in one sentence. e.g. "A pricing comparison section
with three plans, the middle one highlighted as Recommended."]

CONSTRAINTS
- HTML / Blade only. No <style> tag. No inline style attributes except for
  CSS-variable indirection (`style="--gradient-angle: 45deg"`).
- Compose the utilities already in themes/calaentar/css/app.css:
  glass, glass-card, glow, gradient-bg, gradient-text, container.
- Tailwind v4 utilities are fair game: bg-primary, text-base, text-muted,
  rounded-full, backdrop-blur-xl, border-white/5, etc.
- Wrap the block in a <section class="container py-20"> or full-bleed
  equivalent.
- Dark only — do not add any `dark:` variants.
- Use Remix Icon components for icons: <x-ri-icon-name class="size-5" />.
- Must be accessible: heading order, aria-labelledby for region wrappers,
  focus-visible rings on interactive elements.

OUTPUT
1. The block as a single ```blade fenced code snippet.
2. A bullet list of which Calaentar utilities you used and why.
3. If the block needs new content / props from the page builder, list the
   exact $content[...] keys you read.

Do NOT:
- Invent new utility class names.
- Add new theme settings.
- Reach for external image URLs, fonts, or icon libraries.
```

---

## 2. CSS-only block

Use when you already have markup (e.g. a vendor component or an existing
PageBuilder section) and need a stylesheet that re-skins it to match
Calaentar.

```text
You are styling an existing HTML structure to match the Calaentar theme.

EXISTING MARKUP
[Paste the HTML / Blade you need styled. Include id/class hooks the CSS
should target.]

GOAL
[One sentence describing the visual target. e.g. "Make this look like a
glass-morphism stat tile with a magenta border-glow on hover."]

CONSTRAINTS
- Pure CSS, scoped to the markup above. No JS, no class additions.
- Use Calaentar design tokens — never raw colors / radii / blurs:
  hsl(var(--color-primary)), hsl(var(--color-base) / var(--glass-border-opacity)),
  var(--radius-lg), var(--glass-blur), etc.
- Glass surfaces: bg = hsl(var(--color-background-secondary) / var(--glass-opacity));
  border = 1px solid hsl(var(--color-base) / var(--glass-border-opacity));
  backdrop-filter: blur(var(--glass-blur)) saturate(140%).
- Brand gradient: linear-gradient(var(--gradient-angle, 135deg),
  hsl(var(--color-primary)) 0%, hsl(var(--color-accent)) 100%).
- Glow shadow: 0 0 24px -4px hsl(var(--glow-color) / 0.55),
  0 0 60px -10px hsl(var(--glow-color) / 0.35).
- Respect prefers-reduced-motion: wrap any non-trivial animation in a
  @media (prefers-reduced-motion: no-preference) block.
- No !important unless overriding a Filament/PageBuilder class — call out
  every !important in the explanation.

OUTPUT
1. The CSS as a single ```css fenced snippet.
2. A bullet list of which CSS variables you used.
3. If new variables are needed (e.g. a hover-tint color), list them as a
   "Add to themes/calaentar/theme.php settings:" addendum — don't silently
   reference variables that don't exist.

Do NOT:
- Hardcode hex / rgb / hsl values.
- Use vendor-specific prefixes other than -webkit-backdrop-filter.
- Add @import statements.
```

---

## 3. Combined HTML + CSS block (self-contained)

Use when you want a complete drop-in component — markup *and* its scoped
styles in one snippet.

```text
You are designing a complete, self-contained block for the Calaentar theme:
HTML markup + scoped CSS in a single snippet.

GOAL
[One sentence. e.g. "A waitlist signup card with a glassy backdrop, an
email input, and a magenta-gradient submit button. Compact, ~360px wide,
suitable for a hero section sidebar."]

CONSTRAINTS
- Single ```html block containing the markup followed by a <style> tag
  with the CSS. Treat it as a self-contained snippet that someone can
  paste anywhere on the site.
- Scope every selector with a unique root class so the styles can't leak
  (use a BEM-style prefix derived from the block's purpose, e.g.
  `.cal-waitlist`, `.cal-pricing-tier`).
- Use Calaentar tokens for every color / radius / blur / shadow:
  hsl(var(--color-primary)), var(--radius-lg), var(--glass-blur), etc.
  No raw color values, no hardcoded radii.
- Tailwind classes are fine but optional — if you use them, use them only
  for layout (flex, grid, gap, container, py-*). All visual styling
  (colors, glass, glow) goes in the <style> block.
- Dark only.
- Accessible: heading order, labels for inputs, focus-visible rings using
  hsl(var(--color-primary)).
- If the block has interactive state (open/closed, tab switching, etc.),
  use inline `x-data="{...}"` Alpine — no external JS.

OUTPUT
1. The complete snippet in one ```html block.
2. A bullet list naming every CSS variable referenced and any utility from
   themes/calaentar/css/app.css you composed.
3. Where to put it. Examples:
   - drop into a Blade view at themes/calaentar/views/components/<name>.blade.php
   - paste into a PageBuilder Custom HTML section
   - inline anywhere in a page

Do NOT:
- Render to React / Vue / vanilla JS frameworks.
- Use shadcn/HeadlessUI/Bootstrap class names.
- Reach for external fonts, icon libraries, or images.
- Use !important. The block is self-contained — specificity should be enough.
```

---

## Modern block ideas (use as a menu)

When the user asks for "a block" without specifying, offer one or two
variations from this menu and ask which they want. Each entry lists what
makes the variation modern in 2026 — pick patterns that ship today, not 2018.

### Hero variations
- **Centered headline + gradient text + ambient glow** — `gradient-text` on
  the H1, `gradient-bg glow rounded-full` on the primary CTA, soft grid or
  noise SVG behind, no hero image.
- **Split hero** — Left: copy + CTA, Right: a glass card with a fake
  product preview (rounded-xl, drop-shadow, an arrow + label badge in the
  corner). Emphasize negative space.
- **Marquee under hero** — Auto-scrolling row of partner / customer logos,
  each in a glass chip with `mask-image` fade on the edges.
- **Spotlight cursor** — `position: fixed` div with a radial gradient
  following the mouse via Alpine `x-on:mousemove`.

### Card / feature grid variations
- **Bento grid** — Asymmetric grid (some cards span 2 cols, others 1 col)
  with `grid-template-areas`. Each card is a `glass-card` with a unique
  icon in a magenta gradient circle.
- **Hover-reveal cards** — Card shows a title + icon by default. On
  `:hover`, a description and CTA fade in via `opacity` + a subtle
  `translate-y`. Use `transition` not Alpine.
- **Tilted cards** — `transform: perspective(1000px) rotateY(...)` on
  hover, using `pointer-events: auto` and `mouse-position` as a CSS
  custom property.
- **Animated border** — `::before` pseudo-element with a conic-gradient
  that rotates via `@property` + `@keyframes`, masked to a 1px ring
  around the card.

### Pricing variations
- **Three-tier with featured plan** — Middle plan is taller (`scale-105`)
  with a magenta-gradient border ring (use a `padding: 1px;
  background: gradient` wrapper trick). Recommended badge sits in the
  top-right corner.
- **Toggle for monthly / yearly** — Pill toggle at the top, prices
  swap with `x-show` + `x-transition`. Yearly shows a "Save 20%" badge.
- **Comparison table on mobile / cards on desktop** — Use
  `@container` queries to swap layouts based on container width.

### Form / CTA variations
- **Glass sign-up bar** — Single-line form (email + submit) inside a
  `glass-card` with the submit as a `gradient-bg glow rounded-full`
  pill button.
- **Inline CTA with stat counters** — Three big numbers (each one
  animating up on intersection observer), with a CTA button below.
- **Progressive disclosure** — Single CTA. On click, expands into a
  full multi-field form (Alpine `x-show` + `x-collapse`).

### Testimonials / social-proof variations
- **Masonry grid of cards** — Different heights, each with a quote +
  avatar (use the same dark-circle-with-initial pattern as the
  navigation avatar).
- **Carousel of large quotes** — One quote at a time, large pull-quote
  styling, dots for navigation. Use Alpine `x-data="{i: 0}"`.
- **Logo grid** — Just rows of partner logos in glass chips, no
  testimonials. Quietest variant.

### FAQ variations
- **Two-column accordion** — Questions split across left/right columns.
  Each question is a `glass-card` that expands inline using `<details>`.
- **Searchable FAQ** — Filter input at top, items hide via Alpine
  reactivity as the user types. Highlight matched substring.
- **Categorized tabs** — Pill tabs at top (Billing / Servers / Account)
  switch between FAQ sets.

### Stats / metric variations
- **Big-numbers row** — 3-4 large numbers across a row, each with a
  small label below, separated by thin glass dividers.
- **Animated counters** — Numbers count up on intersection-observer
  using Alpine `x-intersect="$el.dataset.target"` + a small JS easing fn.
- **Sparkline chart per stat** — Inline SVG sparkline in magenta below
  each number for the trend.

### Modern interaction primitives to layer on top
- **Scroll-driven reveal** — `transition` on `opacity` + `translate-y`
  triggered by `x-intersect`. Subtle (`translate-y-2`, `duration-700`).
- **Subtle parallax** — `transform: translateY(...)` driven by
  `window.scrollY` via Alpine. 3-5% movement, never more.
- **Magnetic buttons** — Buttons that translate slightly toward the
  cursor on hover. CSS-only via `mask-image: radial-gradient(...
  pointer-events: auto)`.
- **Glow-on-hover** — Buttons emit a stronger `glow` shadow on hover.

## Token reference (paste this if the assistant has no repo access)

```css
:root {
    /* Brand */
    --color-primary: 320 90% 60%;          /* magenta */
    --color-secondary: 270 70% 55%;        /* purple */
    --color-accent: 290 95% 70%;           /* highlight pink */
    /* Surfaces */
    --color-neutral: 260 20% 22%;
    --color-background: 260 35% 6%;
    --color-background-secondary: 260 30% 10%;
    /* Text */
    --color-base: 260 20% 96%;
    --color-muted: 260 15% 65%;
    --color-inverted: 260 30% 10%;
    /* Ambient */
    --gradient-from: 320 80% 35%;
    --gradient-via: 280 70% 25%;
    --gradient-to: 260 35% 6%;
    --gradient-angle: 135deg;
    --glow-color: 320 90% 60%;
    /* Radius */
    --radius-sm: 0.5rem;
    --radius-md: 0.75rem;
    --radius-lg: 1rem;
    --radius-xl: 1.5rem;
    /* Glass */
    --glass-blur: 14px;
    --glass-opacity: 0.5;
    --glass-border-opacity: 0.12;
}

/* Compose with: hsl(var(--color-primary))                   solid magenta */
/*               hsl(var(--color-primary) / 0.4)             40% magenta   */
/*               hsl(var(--color-background-secondary) / var(--glass-opacity)) */

/* Useful utilities (in themes/calaentar/css/app.css) */
.glass        { background-color: hsl(var(--color-background-secondary) / var(--glass-opacity));
                border: 1px solid hsl(var(--color-base) / var(--glass-border-opacity));
                backdrop-filter: blur(var(--glass-blur)) saturate(140%); }
.glass-card   { @apply glass rounded-xl shadow-2xl; }
.glow         { box-shadow: 0 0 24px -4px hsl(var(--glow-color) / 0.55),
                            0 0 60px -10px hsl(var(--glow-color) / 0.35); }
.gradient-bg  { background-image: linear-gradient(var(--gradient-angle),
                  hsl(var(--color-primary)) 0%, hsl(var(--color-accent)) 100%); }
.gradient-text { background-image: linear-gradient(var(--gradient-angle),
                   hsl(var(--color-primary)) 0%, hsl(var(--color-accent)) 100%);
                 background-clip: text; color: transparent; }
```
