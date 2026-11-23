# HC Hub — UI/UX Improvement Plan (all screens)

Date: 2026-11-20 · Scope: every screen in `resources/views/pages`, `modules/Hr`, `modules/Tracking` (~440 page files) · Standard: `hc-ui-ux` skill (`hope-center-brand.md`, `hope-center-sizes.md`, `rtl-arabic.md`).

## 1. Diagnosis

Evidence: three code audits (shared foundation, Tracking, HR + core) and a live walk-through of 12 screens at 1366px and 375px, dark theme. Light theme was **not** checked live.

The app is not broken screen by screen. It feels poor because **the same thing is built several different ways**, and the base sizes are too small. Fixing the foundation fixes most screens at once.

| # | Root cause | Evidence |
|---|---|---|
| R1 | Three design languages side by side | `x-ui.*` components, the desk `dk-*` classes (~5,300 CSS lines, 982 uses in Tracking), and `ap-*` in annual plans |
| R2 | Text is too small | 139 CSS declarations and ~300 view classes under the 12px floor; form labels and table headers are 11px uppercase; 58 distinct font sizes in CSS; no Arabic 17px/1.7 body rule |
| R3 | Controls are too small | Buttons 24–40px (spec 44), 462 uses of 32px `size="sm"`; inputs 44px/14px text (spec 46/16, iOS zooms under 16) |
| R4 | Duplicate components | 5 button systems, 5 text-input styles, 8+ badge/chip styles, 4 dialog mechanisms, 2 pagers, 3 tab styles, 3 filter patterns, 3 hand-rolled sticky save bars |
| R5 | Overloaded screens | Proposal detail: up to 13 header actions and ~25 modals; contract detail 8 header buttons; applicants 11 columns and 13 filters |
| R6 | Weak feedback | No shared skeleton, alert or tooltip; 8 large Tracking pages with no loading state; 8 destructive actions with no confirmation |
| R7 | No tokens for shape | 20+ radius values, shadows tinted slate instead of ink, three parallel grey ramps |
| R8 | Focus is nearly invisible | No global focus ring; 30+ `outline:none`; several replacements at ~1.2–1.7:1 contrast |

What is already good and must be kept: RTL hygiene (zero physical `ml-/mr-/pl-/pr-` in views), strings are translated, icon buttons carry `aria-label`, `x-ui.empty-state` is widely used (141 + 116 uses), `x-ui.data-table` stacks into cards on phones, the modal shell is solid.

## 2. Decisions needed before Phase 1

These conflict with the brand reference but look deliberate in `tokens.css`. Each needs a yes/no from the owner; the plan does not assume the answer.

- **D1 Status colours.** Green `#1d7a4c`, blue `#2563a8`, teal `#2b5f6b` and six series colours are outside the closed brand palette. Keep as a documented data-viz exception, or move to the brand mapping? **Decided 2026-11-20: brand palette only.** Status and chart series move to red + neutrals, and must carry an icon/text/pattern so colour is never the only signal.
- **D2 Dark-mode red.** Brand red is brightened to `#d4343a` / `#ff5a60` for contrast; the brand file forbids tints. Keep, or re-derive? **Decided: original `#BE1F24` always.** In dark mode red is for fills and buttons only (white text on it); small red text becomes white/light grey with an icon.
- **D3 Grey ramp.** Move ink to the canonical `#414042 / #6D6E71 / #939598 / #BCBEC0 / #E6E7E8` and retire `#58595b`, `#dcddde` and the desk ramp? **Decided: yes, brand greys only.**
- **D4 Desk language.** Recommended: keep the Desk *look* but make `dk-*` read the same tokens and reuse the same button/chip/input components, rather than rewriting the Desk. **Decided: as recommended.**
- **D5 Phone navigation.** Spec says a bottom bar under 640px; the app uses an off-canvas drawer. Keep the drawer (works today) or build the bottom bar? **Decided: bottom bar** — 4 fixed shortcuts + a "More" item that opens the full menu (task 2.16).
- **D6 Density.** Raising buttons to 44px and text to the 12px floor makes dense screens (registers, Gantt, calendar chips) taller. Recommended: full spec on touch and forms; a documented 38px "compact" size for desktop toolbars and table rows only. **Decided: as recommended.**

## 3. Phases

Size: S = under a day, M = 1–3 days, L = more. Model column follows the session-orchestrator table.

### Phase 0 — Quick fixes and guardrails (no design decisions needed)

| ID | Task | Size | Model | Done when |
|---|---|---|---|---|
| 0.1 | Recruitment breadcrumbs not clickable: `detail-header.blade.php:17` reads `url`, 13 headers pass `href`. Mirror the `›` separator in RTL. | S | sonnet | Crumbs link on all 13 recruitment headers; test covers both keys |
| 0.2 | `flux:badge` colours likely not compiled (`app.css:45` excludes the stub); 13 uses in HR | S | sonnet | Badges render their colour, or are replaced by `x-ui.enum-badge` |
| 0.3 | Employees table: avatars taller than the row and overlapping (seen live) | S | sonnet | Avatar fits the row at desktop and in the stacked phone card |
| 0.4 | Desk card link `/hr/employees/{id}/show` returned 404 for id 502 (seen live). Investigate: stale record, wrong route, or permission | S | sonnet | Cause known; link fixed or hidden when the target is unavailable |
| 0.5 | Add confirmation to 8 unguarded destructive actions: `projects/_show-funding:164`, `annual-plans/_update-numbers:37`, `_plan-review:34`, `_logframe-risks-drawer:39`, `strategic-plans/⚡show:380`, `tasks/_show-properties`, `news/⚡index:299`, `hr desk/⚡notes:122` | S | sonnet | Each asks first (or offers undo); tests cover it |
| 0.6 | Applicants filters: 7 selects show only "All" with no name (seen live) | S | sonnet | Every filter names what it filters when empty |
| 0.7 | `group-header` collapse button has no `aria-label` | S | haiku | Label present in EN and AR |
| 0.8 | Arabic comma `'، '` used in English lists (4 annual-plan partials) | S | haiku | Separator follows locale |
| 0.9 | Hardcoded person name in `hr/print/contract-legal/**` (4 files) | S | sonnet | Name comes from settings/data |
| 0.10 | Guardrail test (ratchet): counts of text under 12px, arbitrary `text-[..]`, raw `<button>`, `!` overrides, raw hex in views. Fails if a count goes **up** | M | sonnet | Test in CI with today's numbers as the ceiling |
| 0.11 | Baseline screenshots: 20 key screens × EN/AR × light/dark × 1366/375 | M | haiku | Stored outside the repo; used for before/after |

### Phase 1 — Foundation tokens (one place, every screen benefits)

Depends on D1–D3, D6. All work in `resources/css/tokens.css`, `typography.css`, `base.css`, `forms.css`, `flux-overrides.css`.

| ID | Task | Size | Done when |
|---|---|---|---|
| 1.1 | Type scale tokens: h1 `clamp(26–34px)`/800, h2 27, h3 22, h4 18, body 16, table 15, small 14, th 13, caption 12. Arabic body 17px/1.7 on `html[lang=ar]` | M | Tokens exist as utilities; page title uses them |
| 1.2 | Raise the floor: `.form-label`, `.type-label`, table headers from 11px to 13px/700; replace sub-12px sizes in CSS (139) | M | Zero CSS declarations under 12px |
| 1.3 | Shape tokens: radius inset 4 / control 8 / surface 12 / round 999; elevation e1–e3 tinted ink | M | Radius values in CSS drop from 20+ to 4 (+ documented exceptions) |
| 1.4 | Control heights: `--control-h` 44, `--field-h` 46, input text 16px, border 1.5px; compact 38px variant per D6; 44px on `pointer: coarse` | M | Flux, `x-ui` and `dk-*` controls all read the same tokens |
| 1.5 | One global focus ring: 2px ink outline, 2px offset; inputs ink border + 3px ring. Remove the weak replacements and the red modal ring | M | Keyboard focus visible on every interactive element, both themes |
| 1.6 | One grey ramp and one semantic layer (per D3); alias `secondary-*`, `desk-ink*`, `paper/tint/sunk` to it | L | Old names resolve to the new layer; no visual regressions beyond the intended shift |
| 1.7 | Muted text contrast: `#939598` (3.0:1) is used as text ~200 times; limit it to non-text and use `#6D6E71` for text | M | No body/meta text under 4.5:1 |
| 1.8 | Global `prefers-reduced-motion` catch-all in `base.css` | S | Animations stop under the setting everywhere |
| 1.10 | Status and chart colours to the brand palette (decision D1): replace green/blue/teal/series tokens with red + neutrals; every status also carries an icon, text or pattern | M | No off-palette colour tokens; statuses and chart series distinguishable without colour |
| 1.9 | Dark theme pass (per D2): canvas, field borders, bordered red buttons; fix the ~111 `bg-white` with no dark pair and the 9 HR views with no dark styles | M | Dark contrast checked on its own, not inferred |

### Phase 2 — One component per job

| ID | Task | Size | Done when |
|---|---|---|---|
| 2.1 | **Button**: one `x-ui.button` with sizes sm/md/lg, tones primary/neutral/**danger**/ghost, and an **icon** size. `.page-action`, `.dk-btn`, `.dk-iconbtn` become thin aliases | L | No page needs `size-8!` or `text-status-critical!` |
| 2.2 | **Text input**: merge `x-ui.input` into `x-ui.form-input` (a `search` variant); `ap-inp` and `dk-scope` inputs reuse it | M | One input look app-wide |
| 2.3 | **Select**: remove native `x-ui.select` and raw `flux:select` (25 uses); one multi-select instead of three | M | Selects on one page look the same |
| 2.4 | **Chip and tag**: one status chip (radius 999, 14px) and one tag (13px) replacing enum-badge, status-pill, hold-chip, the three badges, `dk-pill`, `dk-tag`, `flux:badge` | L | Two components, documented tone list |
| 2.5 | **Dialogs**: everything on `x-ui.modal`; confirmations on `x-ui.confirm-modal` (replace 18 `wire:confirm` and inline in-modal confirms) | M | One dialog look and one confirm flow |
| 2.6 | **New: skeleton** (`x-ui.skeleton` row/card/panel) and a standard `wire:loading` recipe | M | Used by data-table, panels and the 13 pages with no loading feedback |
| 2.7 | **New: alert/callout** for page-level errors, warnings, info | S | Replaces ad-hoc banners |
| 2.8 | **New: tooltip** on every icon-only button (0 tooltips in HR today) | M | Hover and keyboard focus show the label |
| 2.9 | **New: action menu** — header shows at most 1 primary + 2 secondary; the rest go in a "More" menu | M | Component exists with a documented rule |
| 2.10 | **New: sticky save bar** replacing three hand-rolled copies; always paired with the unsaved-changes guard | S | One component; guard on every long form and large form modal |
| 2.11 | **Filter bar**: one pattern — search + up to 3 inline filters + "More filters" + active-filter chips with clear-all | M | Replaces the three patterns |
| 2.12 | **Tabs** (`relation-tabs` everywhere) and **pager** (one of `flux:pagination` / `funding.pager`) | S | One of each |
| 2.13 | Empty state: larger icon, title, one line of help, primary action; retire ~10 `dk-*-empty` variants | S | One empty state |
| 2.14 | Delete dead components: `page-header`, `capacity-bar`, `status-strip`; fold `section`→`panel`, `user-chip`→`employee-chip` | S | No zero-use components left |
| 2.15 | Table row actions: at most 2 visible icons + a menu; delete always inside the menu | M | Applied in `data-table` docs and the employees table |

| 2.16 | **Phone bottom bar** (decision D5): under 640px, 4 fixed shortcuts + "More" opening the full menu; safe-area padding; replaces the drawer trigger | M | Bottom bar on every signed-in page at 375px, EN and AR |

### Phase 3 — Four screen templates

Written once as short rules in `.ai/rules` (via `record-rule`) with a reference page each.

| Archetype | Structure | Reference page today |
|---|---|---|
| List | `x-desk.register` → filter bar → `data-table` (or card list) → empty state → pager → skeleton while loading | `hr/employees/⚡index`, `indicators/⚡index` |
| Detail | `detail-header` (breadcrumb naming the record, status chip, ≤3 actions + More) → summary tiles → `relation-tabs` → panels | `funding-agreements/⚡show`, `donor-reports/⚡show` |
| Form | `detail-header` → `form-section` groups → sticky save bar + unsaved guard; errors under the field | `hr/contracts/⚡form` |
| Settings | list + modal partials, confirm modals | `admin/evidence-types`, `hr/settings/*` |

### Phase 4 — Screen rollout (waves, worst first)

Each wave: apply templates and Phase 2 components, remove raw buttons/inputs and arbitrary values, split files over ~500 lines into partials, verify EN/AR × light/dark × 1366/375. Use the `review-page` skill per page so each is rebuilt around the user's real job, not only reskinned.

| Wave | Screens | Main work | Size |
|---|---|---|---|
| 4.1 | **Proposals** (4.8k lines) | Header from 13 actions to 1 primary + More; group ~25 modals; reduce 9 tabs | L |
| 4.2 | **HR recruitment** (41 files, 10.1k lines) | Overloaded headers (offers 7, interviews 5); applicants 11 columns/13 filters; `indicators/⚡rules` (1,069 lines, own tables and inline JS); one dialog style | L |
| 4.3 | **Annual plans** | Remove the `ap-*` system; raw inputs and inline styles to shared components | L |
| 4.4 | **Tasks, department-tasks** | 56 raw buttons, raw composers, 549-line detail page; add tabs | M |
| 4.5 | **Projects, programs, strategies** | `dk-story` headers to `detail-header`; hand-rolled tabs and tiles to shared ones | M |
| 4.6 | **Risks, milestones** | Split 717- and 700-line files; loading feedback; tabs on milestone detail | M |
| 4.7 | **Notifications, notification settings, security settings** | Bespoke pages onto `x-ui`; hand-rolled dialog out | M |
| 4.8 | **Contracts, leave, employees detail** | Contract header 8 buttons → menu; leave detail on panels; employees form gets the guard | M |
| 4.9 | **Finance: funding agreements, transfers, donors, donor reports** | One pager, one view switch; arbitrary values in overview/lanes/calendar | M |
| 4.10 | **M&E: reports, monthly reports, results, targets, indicator library** | Loading feedback; mostly light alignment | S |
| 4.11 | **Remaining HR** (attendance 13 columns, mail-lists, positions, news, statistics, checklists) and admin settings | Light alignment; table column pruning | M |

### Phase 5 — First impressions

| ID | Task | Size |
|---|---|---|
| 5.1 | **Login and auth**: one field style across the 4 auth pages (drop the 20-override field class and off-palette blue); add language switch, forgot-password link, busy state on submit | M |
| 5.2 | **Public careers**: use the shared form components; normal bordered inputs instead of underline-only; lighter section headers; labelled EN/AR switch; disable submit while sending; remove dead jQuery hooks | L |
| 5.3 | **Desk (home)**: reduce overlapping customisation ideas (pin, fold, quick access, ready layouts, drag, sheet, tabs) to a clear few; real first-run state; one undo toast instead of three copies; remove per-panel inline `<style>` | L |
| 5.4 | **Information architecture**: two Activity pages and two inboxes (core notifications vs `hr/inbox`) — merge or clearly separate | M |

### Phase 6 — Final verification

- Accessibility pass: keyboard-only walk of the four archetypes, contrast in both themes, touch targets ≥ 44px on phone.
- Arabic pass with real Arabic copy: body 17px/1.7, `<bdi>` around LTR runs, directional icons mirrored, Gantt direction confirmed.
- Phone pass at 375px and landscape; raw `<table>` views (20 files) either on `data-table` or scroll deliberately.
- Before/after screenshots against 0.11; guardrail counts (0.10) tightened to the new lower numbers.
- One opus reviewer over the whole diff per phase.

## 4. Order and dependencies

```
Phase 0 ──► Phase 1 ──► Phase 2 ──► Phase 3 ──► Phase 4 waves (parallel, max 3–4)
  (now)      (needs D1–D3, D6)                     └─► Phase 5 ──► Phase 6
```

- Phase 0 is independent and can start immediately.
- Phases 1–2 touch shared files, so they run **sequentially**, not in parallel.
- Phase 4 waves touch different folders and can run in parallel in separate worktrees.
- Every code change ships with a test (project rule); visual changes are checked in the browser.

## 5. How success is measured

| Metric | Today | Target |
|---|---|---|
| Text under 12px (CSS declarations / view classes) | 139 / ~300 | 0 / 0 |
| Distinct font sizes in CSS | 58 | ≤ 10 |
| Distinct radius values | 20+ | 4 (+ documented exceptions) |
| Button systems / input styles / chip styles | 5 / 5 / 8+ | 1 / 1 / 2 |
| Raw `<button>` in Tracking | 326 | < 30 |
| `!` override lines (HR + core) | 162 | < 20 |
| Max header actions on a detail page | 13 | 3 + More |
| Destructive actions without confirmation | 8 | 0 |
| Pages over 600 lines | 8+ | 0 |
| Interactive targets under 44px on touch | most | 0 |
