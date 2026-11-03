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
Three legacy light greys that only existed as arbitrary hex classes are tokens now, with a dark value:
`row-alt #F1F2F2` (table stripe), `capsule #F6F6F6` (header capsules, search), `ink-faint #888888`.
Never write an arbitrary hex colour class (`bg-[#…]`) — it cannot follow the theme;
`tests/Feature/Hub/DarkModeTest.php` fails on one.

No warning / info colours exist — media-request has none.

### Dark mode

Ported from hc-hr's dark mode (branch `enhance-from-media-request`), with media-request's palette
verbatim. It follows the system, overridden by `<html data-theme="light|dark">`:

- `partials/head.blade.php` applies the stored choice (`localStorage` key `hopehr-theme`) before first
  paint, with the CSP nonce, and keeps it across `wire:navigate`; `theme-color` is the pair
  `#FFFFFF` (light) / `#161617` (dark), as media-request.
- `<x-theme-toggle>` is in the header (and, on phones, the account menu).
- `app.css` flips the Tailwind variables in dark: the semantic colours, the `neutral`, `gray`, `zinc`,
  `slate`, `stone` and `secondary-*` ramps, `black`, the app/sidebar surfaces. `--color-white` itself
  flips to the card colour, so every white fill, border and ring follows the theme in any variant
  (`odd:`, `group-odd:`, `hover:`, `md:`, `/80` …); `text-white`, `fill-white` and `stroke-white` read
  their own theme keys (`--text-color-white` …), which stay `#FFFFFF`, so text on the red stays white.
  White text on a dark fill becomes on-ink, translucent black fills stay backdrops,
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

## Identity components and dashboard (from hc-hr)

Ported from hc-hr (b6254414 and follow-ups). Where these notes differ from the sections above, the hub's decisions above win (14px type base, `theme-dark:` instead of `dark:`).

### Palette lock

`palette-lock.css` (imported after Flowbite's theme) redefines every Tailwind, Flowbite and Flux
colour family in terms of the brand: red and rose 50–200 → neutral 50–200, 300–600 → primary,
700–950 → primary-hover; every other hue → the neutral step of the same number. A stray
`bg-green-600` or a runtime-built `bg-{$color}-100` can therefore only paint an identity colour.
That is a safety net, not a design tool: choose colour deliberately from the tokens above.

### Status

**Icon + words + fill shape, never colour.** Every status must be readable in greyscale.

| Status | Shape |
|---|---|
| in progress | solid ink fill, on-ink text |
| done | surface fill |
| needs you | ink outline (1.5px) |
| stopped | dashed ink-2 outline |
| waiting | card fill, line outline |
| late / acts | solid brand red tag |
| urgent | brand red outline tag |

### Focus and touch

- Focus: 2px solid ink outline, 2px offset — **not red**. Inputs on focus: ink border + 3px ring
  (`ring` token). Both are unlayered rules in `app.css`, so they override older
  `focus:*-primary` utilities.
- Touch targets: 44px minimum, inputs 46px.

### Components

Classes in `resources/css/components.css`; every component in every state is on the component
sheet at `/en/_ui` and `/ar/_ui` (local and testing only).

| Component | Use | Props |
|---|---|---|
| `<x-status>` | every status, everywhere | `status` (a `HasStatusTone`), or `tone` (work, done, attn, stop, wait) + `icon` + `label` (or slot) |
| `<x-tag>` | a fact beside a status or title | `variant` (act, late, rush = red; soft = surface), `icon`, `label` (or slot) |
| `<x-button>` | every new button or button-link | `variant` (primary, outline, quiet, link), `size` (sm, md, lg), `href`, `type` (button), `icon`, `full`, `loading` (true), `target` |
| `<x-page-header>` | page title row | `title`, `lead`; slots `crumb`, `actions` |
| `<x-card>` | a titled block | `title`; slot `icon`; body is the slot |
| `<x-center-empty-state>` | empty / filtered / error | `variant`, `title`, `description`; slots `icon`, `action` |
| `<x-ic>` | line icons | `name` (check, x, clock, play, pause, pen, alert, warning, info, ban, minus, flame, bolt, user, users, eye, calendar, calendar-x, box, return, inbox, shield, star, plus, trash, question, search, check-circle, pin, layout, reset, cake, file, dot) |

- Status enums implement `App\Support\Status\HasStatusTone` (`tone()`, `icon()`, `label()`):
  `LeaveRequestStatus` (`::of($value)`), `EmployeeStatus::for($employee)`,
  `ContractStatus::for($contract)`.
- Buttons: primary = red fill; outline = card fill + ink border; quiet = surface fill; link =
  underlined text. 44px tall (sm is 38px with a fine pointer, 44px on touch). While Livewire works
  on the button's own `wire:click` method, it is disabled, `aria-busy` and shows a spinner. The
  legacy `<x-primary-button>`, `<x-secondary-button>` (= quiet), `<x-outline-button>` and
  `<x-primary-link>` keep their APIs and render the same classes. `<flux:button variant="primary">`
  is re-skinned through Flux's accent variables.
- Fields (`<x-forms.*>`): 46px, 1.5px line border, field fill; hover ink-2; focus ink + 3px ring;
  invalid (`aria-invalid="true"`) = 2px brand border plus the error line (`.hc-err`: red, 14px
  bold, alert icon). Checkboxes and radios are ink when checked; checkbox cards and the
  segmented control show the choice as ink border + surface fill, never red.
- Cards: card fill, 1px line-soft border, 12px radius, shadow-sm, 18px/800 title.
- Empty state: dashed line border, ink-3 icon, bold ink line, one action.
- Tables (livewire-tables theme): 15px body, soft header with 13px bold ink-2 labels, hover row,
  no striping. Below 820px a table without its own phone cards becomes stacked cards, each cell
  led by its column name (`data-label`).
- Notes and warnings: `border-s-4 border-ink bg-soft` with a `warning` icon, never amber.

### Dashboard tiles

The dashboard is a home screen of tiles the person arranges, per active role
(`App\Support\Dashboard\DashboardLayout`; browser side `resources/js/dashboard/home-screen.js`,
its own Vite entry, with SortableJS 1.15.7 fetched on the first Customise; styles
`resources/css/dashboard.css`).

- **Grid**: 12 columns once the grid is 1200px wide (a ~1600px screen with the sidebar open),
  8 on a computer, 4 below 760px (phones, square cells). Row height
  `clamp(88px, 0.75 × cell width, 116px)`, gap 14px (12px on phones). A tile wider than the grid
  takes its full width. At rest gaps are filled (packing); while customising, tiles flow in order.
- **A tile** is the grid cell `.hc-tile` (`data-key`; `data-cols` / `data-rows` = the size it is
  shown at; `data-look` for a tile with looks). The card sits in `.hc-tile-body`, clipped to the
  tile and stretched to fill it. Every tile is a **size container named `tile`**
  (`container: tile / size`), so a card adapts to its own size, never the screen's: in CSS
  `@container tile (max-height: 140px) { … }`; for width also Tailwind's `@sm/tile:`-style
  variants. Use `data-cols` / `data-rows` only when a look is tied to an exact cell count.
- **Size thresholds** (px, from the grid's real metrics, so they hold on every screen):
  height — one row < 140, two rows 140–245, three ≥ 245, four ≥ 340;
  width — narrow (two cells or fewer) < 300, medium 300–560, wide ≥ 560.
- **Sizes** are `CxR` cells. `WIDGETS` gives each widget `min` (its *card* size: the smallest it
  can show itself at), `max` and `default`; `bounds()` lets every widget go down to 1×1, and
  below its card size it is an icon. A shortcut is 1×1 to 4×4 and from 2×2 shows its data
  (`AppCard`). At rest `layout-math.js` `pack()` places tiles in order and grows cards into the
  empty cells beside and below them, never past `max`; a size set by hand (`fixed`) is shown
  exactly as set. Only what is shown changes, never the saved size. Customise starts from the sizes seen (`bake()`),
  except where the grid cut them down: on a phone, or a grid narrower than the saved width, the
  saved size stays (`bakedSize()`), so a phone never overwrites a computer's sizes.
- **Looks**: `DashboardLayout::LOOKS` (`clock => [digital, analog]`, the first is the default),
  saved as the item's `style`, drawn as `data-look`, switched by the look button while
  customising. **On a phone** the clock is always one row and digital, whatever its saved size and
  look (`dashboard.css` "phone clock"); the packer is told (`phoneView`, `PHONE_ONE_ROW`), so the
  tiles below close up under it. A phone does not draw `PHONE_HIDDEN` (the today's-events slideshow).
- **States at rest** (none of them while customising: there every tile is shown whole):
  - *Card*: the widget itself.
  - *Icon* (`.mini`, smaller than its card size; every 1×1 shortcut): icon + name. One click
    opens the card in the **side panel**: the tile body itself as a `popover="manual"` in the top
    layer, so the Livewire card is never moved or mounted twice; along the inline-end edge, and
    **a sheet from the bottom on phones** (≤ 760px, `min(78dvh, 640px)`, 20px top corners). ×,
    Escape or the same icon closes it and focus returns to the icon.
  - *Folded* (`.closed`): a card with nothing in it right now (and not pinned: a pinned tile stays
    in place and shows its empty state) goes to the bottom as **one line**,
    one row high, at most four cells wide (`closedSize`), never grown taller: icon, name, and the
    card's own words. Its card says so with **`data-tile-empty`** on its empty-state element
    (`data-state="empty"` and `.hc-nt-empty` also count); the line shows that element's h2/h3 (else its
    text), or `dashboard.home.nothing` when there is none or it runs past 80 characters. Put the marker only
    on a real "nothing here" state, never on loading or a load error. The grid re-reads it on every
    change (lazy load, polling, retry), so a card that fills up unfolds by itself.
- **Lazy loading**: every widget is mounted `lazy`, and a lazy card loads only once it is shown.
  A hidden tile is not rendered at all; an icon's body is `display: none`, so its card loads only
  when its panel opens; a folded tile's body is `display: none` after the one load that found it
  empty. None of them query in the background.
- **Double-click** on a widget with a page (`DashboardLayout::page()`, sent as `data-page`, only
  pages the role may open) opens that page: not while customising, not on its own controls,
  fields or links, not inside an open panel. On an icon, one click = panel, double-click = page.
  A shortcut with a card: on a computer one click shows the card in the panel, a double-click opens
  the page; on a phone a tap opens the page.
- **Ending Customise**: Done, Escape, or a click on empty space. Both the press and the click must
  land outside every tile, control, dialog, sheet backdrop and the phone-bar picker, so ending a drag
  or a resize never counts; not while Add or a confirmation is open.
- **Red on the home screen**, only for its three jobs: the primary action (Done), what is current
  (today in the calendar; the clock's second hand, hub and working-day bar mark *now*), and what
  waits on the viewer or is late (shortcut counters, the late strip, a decision waiting over a day).
  Pins, grips, size labels, guide cells, icons and folded lines are ink and neutrals, never red,
  and red is never tinted pale.
- **Customising**: "Customise" or a long press; tiles wiggle once and faint cells (`soft` fill,
  dashed `line`) appear. Drag to move, or focus a tile and use the arrow keys (they follow the
  reading direction; Home / End). The dark grip sits in the inline-end corner: dragging towards
  the inline end or down grows the tile, cell by cell, with a "2×3" label; arrow keys on the grip
  do the same. × hides, the pin keeps a tile first (pressed = ink fill, not red), Add brings a
  tile back, Reset (with a confirmation) returns to the role's default. Every control is 44px to
  the finger and labelled. `prefers-reduced-motion` turns off the wiggle and the glides.
  `?customise=1` opens the page in this mode (the command palette's "Customise dashboard").
- **Loading**: each widget is its own Livewire component, mounted lazily inside its tile with
  the card placeholder (see Lazy loading above). A card
  shows its own load failure (`<x-ui.load-error>`) inside the tile; a tile whose lazy load fails
  outright says so in place, and the rest of the page carries on. Saving is background and
  renderless, one request at a time; Add (for a tile the page never drew) and Reset render.

**Adding a tile:**

1. `DashboardLayout::WIDGETS`: `key => [icon, min [c, r], max [c, r], default [c, r] on the
   8-column grid, shown by default]`; its name in `resources/lang/{en,ar}/dashboard.php`
   (`tiles.<key>`); its permission and scope rule in `DashboardLayout::allowed()` if it is not
   for everyone.
2. `DashboardLayout::COMPONENTS`: `key => YourCard::class` (the one registration point; a key
   without a line is kept in layouts but never drawn nor offered).
3. The component: `use IsDashboardTile` (its placeholder) and `HandlesLoadFailure` with
   `<x-ui.load-error>` for a failed load. Its root fills the tile (`h-full`) and draws the card:
   card fill, 1px `line-soft` border, 12px radius, `shadow-sm`. Make it size-aware with the
   `tile` container.
4. It arrives in a Livewire update, not with the page: an inline `<script>` does not run and
   `@push` never reaches the layout. Use `@script` (it runs just before the card replaces its
   placeholder, so wait a frame for the markup) or `@assets`.
5. List its public methods (`placeholder`, `retryLoad`, …) in `config/access-control.php`
   `livewire_exempt`.

A card may read the home screen's Alpine state: `editing`, and `styleOf('<key>')` for its look.

**The calendar tile** (`Dashboard\CalendarCard`, data from `App\Services\Dashboard\CalendarFeed`,
styles `.hc-cal-*` / `.hc-mark*` in `dashboard.css`):

- **Markers are shapes, all in ink** (`ink-3` on days outside the month), never a hue: my leave =
  solid dot, team leave = ring (1.5px), holiday = diamond, birthday = triangle, contract end =
  square, news = short bar. At most four under a day. A new event type needs a new *shape*, its
  name in the legend and in the day dialog.
- **Today** is the only red: a solid brand-red day number (`aria-current="date"`).
- **Size**: the month grid at every size (min 3×3); from 6×4 up, "Next 7 days" and the legend
  beside it.
- **Navigation**: previous / next month chevrons mirror under `[dir=rtl]`; "Today" appears only
  while another month is shown.
- **Day dialog**: a real modal (`role="dialog"`, `aria-modal`, named by its heading, `x-trap`
  holds and returns focus, Escape closes), listing that day's events with links, and "Request
  leave for this day" (the leave form, `leaves.index?new=1`, with the date filled in) only for people
  who may request leave, and only for today or a later day.
- **Scope**: each type is read once per month behind its page's own permission and scope (see the
  class docblock); a projection never carries a reason, a balance, pay or a year of birth.

### Out of reach on purpose

The contract PDF (`resources/views/pdf`, `livewire/contract/details/**`) and the mail templates do
not load `app.css` and keep their own styling. They are not part of this theme.
