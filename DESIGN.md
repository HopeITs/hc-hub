# DESIGN.md — HC-Hub visual identity

## Source of truth

**media-request is the single source of truth for everything brand-related.**
Reference: `C:\laragon\www\media-request` — `public/css/app.css` (the real, prebuilt stylesheet),
`resources/views/components/**`, `public/fonts`, `public/img`. It is read-only; when the two disagree,
the hub changes, never media-request.

Brand rule (from media-request): **one red, `#BE1F24`, flat; the Hope Center neutrals; Somar Sans.**
Status is shown with icon + words on neutrals. Red is kept for the main action, the current place
(active nav), what waits on you (counts, required marks) and errors. Never a tint of the red
(`bg-primary/10` reads as pink), never a second red (`red-500`, the old hc-hr red, a darker hover red).

Where the tokens live:

| File | What |
|---|---|
| `resources/css/app.css` | `@font-face`, Tailwind 4 `@theme` colours/shadows/font stack, body rendering |
| `resources/css/tokens.css` | Hub scale (`--space-*`, `--text-h1…caption`, `--radius-inset/control/surface`, `--shadow-e1…e3`, surfaces/ink/lines) **and** media-request's own variables (`--brand`, `--line-soft`, `--text-2` …) with its dark palette |

## Colour

| Role | media-request | Hub token(s) | Value |
|---|---|---|---|
| Brand red | `--brand` | `--color-primary`, `--accent`, `--brand` | `#BE1F24` |
| Brand hover | `.btn-p:hover{opacity:.9}` | `--color-primary-hover`, `--accent-hover` | `color-mix(in srgb, #BE1F24 90%, transparent)` |
| On brand | `--on-brand` | `text-white` | `#FFFFFF` |
| Text / ink | `--text`, `--ink`, `--focus` | `neutral-800`, `ink`, `--color-black`, `--ink-body`, `--ink-strong` | `#414042` |
| Text 2 | `--text-2` | `neutral-600`, `secondary-medium`, `--ink-muted` | `#6D6E71` |
| Text 3 | `--text-3` | `neutral-400`, `secondary-light`, `--ink-subtle` | `#939598` |
| Line | `--line` | `neutral-300`, `--line-control` | `#BCBEC0` |
| Surface / line-soft | `--surface`, `--line-soft` | `neutral-200`, `secondary-soft`, `--color-selection`, `--line-hairline` | `#E6E7E8` |
| Soft / hover | `--soft`, `--hover` | `neutral-50`, `soft`, `hover`, `--color-app-surface`, `--surface-base`, `--surface-sunken` | `#F3F3F4` |
| Page chrome / card | `--bg`, `--card`, `--field` | `white`, `--color-sidebar-surface`, `--surface-raised`, `--surface-chrome` | `#FFFFFF` |
| Online / success | presence dot | `--color-success` | `#2E9E5B` |
| Dark background | dark `--bg` | `neutral-950` | `#161617` |

Hub-only in-between greys (no media-request equivalent; kept so existing hover/contrast pairs keep a step):
`neutral-100 #EEEFF0` (between soft and surface), `neutral-500 #808184`, `neutral-700 #575759`,
`neutral-900 #2A2A2D` (a step darker than ink, = media-request's dark `--surface`), `secondary-dark #58595B`.

Semantic Tailwind colours with media-request's names (new work uses these; they flip in dark mode):
`ink`, `ink-2`, `ink-3`, `on-ink`, `on-brand`, `line`, `line-soft`, `surface`, `soft`, `hover`, `card`,
`field`, `base`, `focus` — e.g. `bg-card text-ink border-line-soft`.

No warning / info colours exist — media-request has none.

### Dark mode

Ported from hc-hr's dark mode (branch `enhance-from-media-request`), with media-request's palette
verbatim. It follows the system, overridden by `<html data-theme="light|dark">`:

- `partials/head.blade.php` applies the stored choice (`localStorage` key `hopehr-theme`) before first
  paint, with the CSP nonce, and keeps it across `wire:navigate`; `theme-color` is the pair
  `#FFFFFF` (light) / `#161617` (dark), as media-request.
- `<x-theme-toggle>` is in the header (and, on phones, the account menu).
- `app.css` flips the Tailwind variables in dark: the semantic colours, the `neutral`, `gray`, `zinc`,
  `slate`, `stone` and `secondary-*` ramps, `black`, the app/sidebar surfaces. `bg-white` becomes the
  card colour, white text on a dark fill becomes on-ink, translucent black fills stay backdrops,
  shadows turn black, the logo turns white. Write `theme-dark:` in CSS; Flux's `dark:` (`.dark`
  class) is never set in the hub and stays dormant.
- Light mode is unchanged by any of this.
- Not yet dark-aware: coloured Tailwind families (`green-*`, `blue-*`, `amber-*` …, the legacy status
  pairs, task 1.3), amCharts dashboard charts, the digital ID card, the contract preview.

The same palette under media-request's own names (`tokens.css`):

| Variable | Light | Dark |
|---|---|---|
| `--bg` | `#FFFFFF` | `#161617` |
| `--soft` | `#F3F3F4` | `#1E1E20` |
| `--card` | `#FFFFFF` | `#232325` |
| `--field` | `#FFFFFF` | `#1B1B1D` |
| `--surface` | `#E6E7E8` | `#2A2A2D` |
| `--hover` | `#F3F3F4` | `#2C2C2F` |
| `--line` | `#BCBEC0` | `#4A4A4E` |
| `--line-soft` | `#E6E7E8` | `#333336` |
| `--text` / `--ink` / `--focus` | `#414042` | `#E6E7E8` |
| `--text-2` | `#6D6E71` | `#BCBEC0` |
| `--text-3` | `#939598` | `#939598` |
| `--on-ink` | `#FFFFFF` | `#161617` |
| `--brand` | `#BE1F24` | `#BE1F24` (red never changes) |
| `--logo` (filter) | `none` | `brightness(0) invert(1)` |


## Shape and elevation

| | media-request | Hub | Value |
|---|---|---|---|
| Small radius (tags, badges) | `.tag` 4px | `rounded-inset` | 4px |
| Control radius | `--r` | `rounded-control` | 8px |
| Container radius | `--r-lg` | `rounded-surface` | 12px |
| Pills, avatars | 999px / 50% | `rounded-full` | — |
| Resting shadow | `--shadow-sm` | `shadow-e1`, `shadow-sm` | `0 1px 3px rgba(65,64,66,.08)` |
| Overlay shadow | `--shadow` | `shadow-e3`, `shadow-lg` | `0 6px 24px rgba(65,64,66,.14)` |
| Focus | `outline:2px solid var(--focus); offset 2px` | `focus-visible:outline-2 outline-offset-2 outline-neutral-800` | — |

## Type

- Family: `'Somar Sans', Arial, Tahoma, sans-serif` (the hub also accepts the older name `'SomarSans'`).
- Files: media-request's hinted TTFs v1.002 for 400/500/600/700/800 (`resources/fonts/SomarSans/*.ttf`);
  the hub adds 100/300/900 as WOFF2.
- Rendering: `text-rendering: optimizeLegibility; font-kerning: normal; font-synthesis: none;` antialiased.
- Arabic pages: line-height 1.7 (Latin 1.55).
- Weights: buttons and labels 700, nav 600, page and card titles 800.
- Size: media-request's body is 16px (17px Arabic). **The hub keeps its 14px base and its scale**
  (`--text-h1 34 / h2 27 / h3 22 / h4 18 / body 14 / small 12 / caption 11`), because the HR screens are
  dense data tables. This is a deliberate user decision (keep 14px), the one difference.

## Components (media-request class → hub component)

| media-request | Hub |
|---|---|
| `.btn .btn-p` (44px, 0 18px, 700, opacity .9 hover, disabled .45) | `x-primary-button`, `x-primary-link` |
| `.btn .btn-g` (surface fill, opacity .85 hover) | `x-secondary-button` |
| `.btn .btn-o` (text-coloured 1.5px border, hover fill) | `x-outline-button` |
| `.card` (white, line-soft border, 12px, 20px padding, shadow-sm; h2 18px/800) | `x-card` |
| `.tag` (4px radius, 13px, 700; `.tag.soft` surface + text, `.tag.act` brand) | `x-badge` (default `neutral`; `brand`) |
| `.nav a[aria-current]` (text colour + red bar; others text-2, 600; hover `--hover`) | sidebar `single-item`, `nav-item`, `dropdown-nav-list` |
| `.cnt` (red count pill) | sidebar badge, notification counter |

## Logos

- App wordmark: `public/assets/img/001_HC.png` ("HOPE CENTER Hub"), red `#BE1F24`.
- Icon: `public/assets/img/hope-center-icon.svg`, favicons in `public/favicons/` — all `#BE1F24`.
- Browser `theme-color`: `#FFFFFF` light / `#161617` dark, as media-request (the colour of its top bar).

## Status pills

Status pills (contract and employee states, leave states …) still use the legacy coloured pairs
(`bg-green-100 text-green-800` …). Task 1.3 moves them to media-request's style — icon + words on
neutrals (`.chip`), red only for what is late or waits on you — and removes the pink tints
(`bg-primary/10`). Do not restyle them piecemeal before then.
