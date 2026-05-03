# Calaentar / Paymenter — Claude project notes

This file is read by Claude at the start of every session in this repo. Keep it
short and factual.

## What this project is

A fork of [Paymenter](https://paymenter.org) (Laravel 12 + Filament 5 + Livewire 4)
running a custom theme called **Calaentar** — glassy, dark-only, magenta /
purple accent, configurable through admin Settings.

## Layout

- `app/` — Laravel app code. The admin panel is Filament; resources live in
  `app/Admin/Resources`, pages in `app/Admin/Pages`.
- `app/Providers/Filament/AdminPanelProvider.php` — wires the admin panel.
  Reads the active theme from `config('settings.theme')` and injects the
  theme's `views/layouts/colors.blade.php` into the admin head via the
  `panels::head.end` render hook so admin uses the same CSS variables as the
  public site. Forces `<html class="dark">` via `panels::head.start`.
- `themes/calaentar/` — the active theme. **Modify the theme here, not in
  `themes/default/`.**
  - `theme.php` — settings schema (colors, gradients, radius scale, glass
    effect, logo display, admin-primary hex).
  - `views/layouts/colors.blade.php` — emits `<style>:root{...}</style>` from
    the `theme()` helper. CSS variables are HSL channels (no `hsl()`
    wrapper, no commas) so they compose with `hsl(var(--color-primary) / 0.4)`.
  - `views/layouts/app.blade.php` — public layout. Body has hardcoded
    `class="dark"` (no light mode), x-cloak removed (don't re-add — Alpine
    isn't bound to body anymore).
  - `css/app.css` — Tailwind v4 + glass utilities (`.glass`, `.glass-card`,
    `.glow`, `.gradient-bg`, `.gradient-text`) + body radial gradient. Also
    bridges PageBuilder's `--hpb-*` tokens to our `--color-*` tokens with
    `!important` so PageBuilder pages inherit theme colors.
  - `views/components/` — Blade components. The 4 user-avatar `<img>` tags
    in nav + tickets show the user's first initial inside a gradient circle
    instead of an external avatar fetch.
- `resources/css/filament/admin/theme.css` — admin Filament overrides. Heavy
  `!important` use because Filament's primary palette is generated server-side
  and we need to beat its component-class defaults. Glass surfaces, magenta
  accents, settings-card border-radius via `:has(> .fi-tabs)`, and a hard
  `display:none` rule for the theme switcher.
- `extensions/` — Paymenter extensions. PageBuilder lives at
  `extensions/Others/PageBuilder/PageBuilder/` (note the doubled directory
  name — that's PageBuilder's own packaging convention).

## Key conventions

- **Settings** are declared in `app/Classes/Settings.php` (global) and
  `themes/calaentar/theme.php` (theme-scoped, prefixed with `theme_calaentar_`
  in the DB). Defaults from `theme.php` only feed the admin form's
  `->default()` and the reset action — they aren't auto-loaded into config.
  To use a default at runtime, pass it as the second arg of `theme()`.
- **Color tokens** are HSL channels in CSS variables, e.g.
  `--color-primary: 320 90% 60%`. Use them as `hsl(var(--color-primary))`,
  with optional alpha via `hsl(var(--color-primary) / 0.4)`.
- **Radius tokens** accept any CSS value (`0.5rem`, `12px`, `9999px`).
- **Glass surface** = `bg-background-secondary/40` + `backdrop-blur-xl` +
  `border border-white/5`. Or use the `.glass` / `.glass-card` utility.
- **Admin primary** is set via the `admin_primary_hex` theme setting (hex
  string). `AdminPanelProvider` reads it and runs `Color::hex(...)` to
  regenerate Filament's full 50–950 shade palette.

## Build

- `./build.sh` — full deploy: composer install + npm install + build all
  themes + storage:link + migrate + optimize:clear. `--dev` flag keeps
  composer dev deps. Halts on first error.
- `npm run build calaentar` — calaentar theme assets only.
- `npm run build` — defaults to building `themes/default/` (kept as
  fallback). `themes/default/js/app.js` imports Livewire from
  `vendor/livewire/livewire/dist/livewire.esm` — that path varies between
  Livewire majors and may need the path swapped or the import dropped in
  favour of `window.Livewire` / `window.Alpine` if the build fails.

## Things to avoid

- Don't put `x-cloak` on `<body>` in calaentar's layout — Alpine has no
  `x-data` on body since the theme toggle was removed, so `x-cloak` would
  hide the entire page forever.
- Don't `require base_path("themes/$active/theme.php")` inside
  `AdminPanelProvider::panel()`. PHP fatals from a stale opcached theme.php
  aren't catchable as `Exception` and silently break extension discovery.
- Don't change the admin panel's `discoverResources` loop unless you also
  account for nested extension layouts (`extensions/Others/PageBuilder/PageBuilder/`).
- Don't re-import Filament's `vendor/filament/filament/resources/css/theme.css`
  from the admin CSS — that path moves between Filament majors. Our admin
  theme.css adds overrides on top of what Filament's panel asset pipeline
  already loads.

## When making visual changes

1. Reach for theme settings first — colors, gradient stops, radius scale,
   glass blur/opacity, glow color are all already configurable.
2. If a setting doesn't exist, add it to `themes/calaentar/theme.php` and
   wire the variable through `views/layouts/colors.blade.php`.
3. New utilities go in `themes/calaentar/css/app.css` as `@utility` blocks.
4. Admin overrides go in `resources/css/filament/admin/theme.css`. Use
   `!important` to beat Filament's component classes.

## Skills

- `.claude/skills/block-designer/` — invoke when designing PageBuilder
  sections or standalone HTML/CSS blocks that need to match Calaentar.
- `.claude/prompts/block-designer.md` — three prompt templates (HTML-only,
  CSS-only, combined) you can paste into a fresh Claude session that
  doesn't have repo context.
