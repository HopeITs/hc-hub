# HC Hub UI/UX — Agent prompts

One prompt per task in `2026-11-20-ui-ux-improvement.md` (called **the plan** below). Copy a block as-is into an agent. Every prompt assumes the agent reads **Common rules** first.

## Common rules (every agent reads this first)

```
Read CLAUDE.md, then .ai/rules/index.md and every rule file whose glob covers a path in your scope.
Read docs/superpowers/plans/2026-11-20-ui-ux-improvement.md for context (your task id is in it).
Design standard: C:/Users/Rit/.claude/skills/hc-ui-ux/references/hope-center-sizes.md,
hope-center-brand.md and rtl-arabic.md. For Blade/Flux/Tailwind work, load the matching project skill
(fluxui-development, tailwindcss-development, livewire-development). Before writing tests, read the
testing-best-practices skill.

Owner decisions (final, do not reopen):
 D1 Colour: brand palette only — red #BE1F24 + greys #414042 #6D6E71 #939598 #BCBEC0 #E6E7E8 + white.
    No green/blue/teal. A status always carries an icon, text or pattern, never colour alone.
 D2 Dark mode: red stays #BE1F24, used for fills/buttons only (white text on it). Small red text
    becomes white/light grey with an icon.
 D3 One grey ramp: the five brand greys above.
 D4 Desk (dk-*) keeps its look but reads the shared tokens and reuses shared components.
 D5 Phone (<640px): bottom bar with 4 shortcuts + More.
 D6 Controls: 44px on forms, primary actions and all touch; 38px compact only for desktop toolbars
    and table rows. Inputs 46px with 16px text.

Rules:
 - Every code change ships with a Pest test (php artisan make:test --pest Name). Run only the
   affected tests: php artisan test --compact --filter=<name>
 - After PHP edits run: vendor/bin/pint --dirty --format agent
 - Logical CSS properties only (ms-/me-/ps-/pe-, start/end). All visible text through __() in EN and AR.
 - No new dependencies. No new base folders. No documentation files.
 - Do NOT: commit, update ClickUp, edit files outside your scope, change the user's account settings.
 - If something outside scope must change, stop and list it under "open risks".

Report (max 10 lines): files changed, test result (pass/fail counts), open risks.
```

Run order: Phase 0 tasks are independent (max 3–4 in parallel, each in its own worktree). Phases 1 → 2 → 3 run **one task at a time** (shared files). Phase 4 waves run in parallel, max 3–4. Then 5, then 6.

---

## Phase 0 — Quick fixes (parallel)

### 0.1 — sonnet
```
Task 0.1 — recruitment breadcrumbs are not clickable. See the plan §3 Phase 0.
Read "Common rules" in docs/superpowers/plans/2026-11-20-ui-ux-prompts.md first.
Scope: resources/views/components/ui/detail-header.blade.php; the 13 recruitment headers under
modules/Hr/resources/views/pages/recruitment/** only if the component fix alone is not enough.
Problem: the component reads the crumb key `url` (line ~17) but 13 headers pass `href`, so crumbs
render as plain text. Accept both keys in the component. Also make the "›" separator mirror in RTL.
Done when: crumbs are links on all 13 recruitment headers; a test covers `url` and `href`.
Run: php artisan test --compact --filter=DetailHeader
```

### 0.2 — sonnet
```
Task 0.2 — flux:badge colours do not render. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: resources/css/app.css (line ~45, the `@source not` exclusion); the 13 `flux:badge` uses in modules/Hr/resources/views/**.
First confirm the cause in the built CSS — do not assume. Then choose the smaller fix: replace the
13 uses with x-ui.enum-badge (preferred, and keeps decision D1: no off-palette colours), or include the stub.
Done when: every former flux:badge shows a visible, on-palette state in light and dark; test covers one page.
Run: php artisan test --compact --filter=<the page test you touch>
```

### 0.3 — sonnet
```
Task 0.3 — employee avatars are taller than the table row and overlap. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: modules/Hr/resources/views/pages/employees/⚡index.blade.php and the avatar/employee-chip
component it uses under resources/views/components/ui/.
Done when: the avatar fits inside the row at 1366px and inside the stacked card at 375px, EN and AR;
checked in the browser at http://hc-hub.test/hr/employees if a signed-in session is available,
otherwise say "not verified in browser".
Run: php artisan test --compact --filter=Employees
```

### 0.4 — sonnet
```
Task 0.4 — a Desk card links to /hr/employees/{id}/show and returned 404 for id 502. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: investigation first (routes, the Desk card view under resources/views/components/desk/** or
resources/views/pages/desk/**, the employee show page and its authorization). Use `php artisan route:list --path=hr/employees`.
Find the cause: wrong URL shape, missing/soft-deleted record, or a permission returning 404.
Done when: cause stated in the report; the link uses the named route and is hidden or disabled when
the target cannot be opened; a test covers the fixed case.
Run: php artisan test --compact --filter=<your test>
```

### 0.5 — sonnet
```
Task 0.5 — eight destructive actions run with no confirmation. See the plan §3 Phase 0.
Read "Common rules" first.
Scope (Tracking pages under modules/Tracking/resources/views/pages/): projects/_show-funding (~line 164),
annual-plans/_update-numbers (~37), annual-plans/_plan-review (~34), annual-plans/_logframe-risks-drawer (~39),
strategic-plans/⚡show (~380), tasks/_show-properties; plus news/⚡index (~299) and the HR desk notes
page (~122) — locate the last two with Grep.
Use the existing x-ui.confirm-modal the way sibling pages do; do not invent a new dialog.
Done when: each of the 8 asks before deleting, text in EN and AR; tests prove the record survives
until confirmed for each.
Run: php artisan test --compact --filter=<each test you add>
```

### 0.6 — sonnet
```
Task 0.6 — seven applicant filters show only "All" with no name. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: modules/Hr/resources/views/pages/recruitment/applicants/** (list page and its filter partial).
Done when: every filter says what it filters when empty (visible label or "All <thing>"), EN and AR,
and has an accessible name; test asserts the labels render.
Run: php artisan test --compact --filter=Applicants
```

### 0.7 — haiku
```
Task 0.7 — the collapse button in group-header has no aria-label. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: resources/views/components/ui/group-header.blade.php (lines ~12–16); lang files for the string.
Done when: the button has a translated aria-label (EN + AR) and aria-expanded; test asserts it.
Run: php artisan test --compact --filter=GroupHeader
```

### 0.8 — haiku
```
Task 0.8 — the Arabic comma '، ' is hardcoded as a list separator and shows in English. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: the 4 annual-plan partials under modules/Tracking/resources/views/pages/annual-plans/** that
contain '، ' (find with Grep).
Done when: the separator follows the locale (', ' in EN, '، ' in AR) through one shared helper or
translation key; test covers both locales.
Run: php artisan test --compact --filter=AnnualPlan
```

### 0.9 — sonnet
```
Task 0.9 — a person's name is hardcoded in the legal contract print views. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: modules/Hr/resources/views/pages/print/contract-legal/** (4 files) — locate exact path with Glob.
Find where signatory/organisation data already lives (settings, config, model) and read the name from there.
If no such source exists, do NOT create a table or setting: stop and report it as an open risk.
Done when: no literal person name in the 4 files; test renders a contract with the name from data.
Run: php artisan test --compact --filter=Contract
```

### 0.10 — sonnet
```
Task 0.10 — add a ratchet guardrail test for UI debt. See the plan §3 Phase 0 and §5.
Read "Common rules" first.
Scope: one new test under tests/Feature (follow sibling structure and .ai/rules/tests.md).
The test scans resources/css/**, resources/views/** and modules/*/resources/views/** and counts:
font sizes under 12px in CSS; arbitrary `text-[...]` classes; raw <button> tags; `!`-suffixed
utility overrides; raw hex colours in views. Store today's counts as ceilings in the test; fail
only when a count goes UP, and print the new lower number when it goes down.
Done when: test passes today and fails if one offending line is added (prove it once, then revert).
Run: php artisan test --compact --filter=UiDebt
```

### 0.11 — haiku (needs a signed-in browser session; run from the main session)
```
Task 0.11 — baseline screenshots. See the plan §3 Phase 0.
Read "Common rules" first.
Scope: no code changes. Save PNGs outside the repo, in a folder the orchestrator names.
Screens (20): login, desk home, projects list + one detail, proposals list + one detail, tasks,
indicators, annual plans, reports, funding agreements list + one detail, donors,
hr employees list + one detail, hr leaves, hr contracts form, hr recruitment applicants,
hr settings, notifications.
Each at 1366x800 and 375x812. Do NOT change the user's language or theme settings — capture the
current ones and report which combinations (EN/AR, light/dark) were not captured.
Done when: files named <screen>-<width>-<lang>-<theme>.png exist; list of gaps in the report.
```

---

## Phase 1 — Foundation tokens (sequential, one agent at a time, sonnet unless noted)

Shared scope for all Phase 1 tasks: `resources/css/tokens.css`, `typography.css`, `base.css`, `forms.css`, `flux-overrides.css`, `desk*.css`, `annual-plan.css`. Views only where a task says so. After each task run `npm run build` and the 0.10 guardrail test.

### 1.1
```
Task 1.1 — type scale tokens. See the plan §3 Phase 1.
Read "Common rules" first.
Scope: resources/css/tokens.css, resources/css/typography.css.
Add to @theme: --text-h1 clamp(1.625rem,4vw,2.125rem), --text-h2 1.6875rem, --text-h3 1.375rem,
--text-h4 1.125rem, --text-body 1rem, --text-table 0.9375rem, --text-small 0.875rem, --text-th 0.8125rem,
--text-caption 0.75rem, with the line heights and weights in hope-center-sizes.md §1.
Set html[lang="ar"] { --text-body: 1.0625rem } with line-height 1.7. Body and .page-title use the tokens.
Done when: utilities text-h1…text-caption exist in the build; Arabic body computes to 17px/1.7.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.2
```
Task 1.2 — raise the 12px text floor. See the plan §3 Phase 1. Depends on 1.1.
Read "Common rules" first.
Scope: all files in resources/css/. No view edits.
Replace every font-size under 12px (139 declarations) with a token: captions 12px; .form-label,
.type-label and table headers 13px/700 (drop the uppercase+tracking on 11px labels).
Done when: zero CSS font sizes under 12px; guardrail ceiling lowered to 0 for that count.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.3
```
Task 1.3 — shape tokens. See the plan §3 Phase 1.
Read "Common rules" first.
Scope: resources/css/.
Add --radius-inset 0.25rem, --radius-control 0.5rem, --radius-surface 0.75rem, --radius-round 9999px
(alias --radius-panel to surface) and --shadow-e1/e2/e3 tinted rgb(65 64 66) per hope-center-sizes.md §3;
point --shadow-hairline/card/card-hover/overlay at them. Replace the 20+ literal radius values in CSS
by element type (control / surface / round / inset). List any exception you keep and why.
Done when: only the four radius tokens (+ listed exceptions) remain in CSS.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.4
```
Task 1.4 — control heights. See the plan §3 Phase 1 and decision D6.
Read "Common rules" first.
Scope: resources/css/tokens.css, forms.css (.form-box ~39–45), flux-overrides.css (.page-action ~834),
desk.css (.dk-btn ~105, ~469).
Add --control-h 2.75rem, --control-h-compact 2.375rem, --field-h 2.875rem, --border-control 1.5px.
Buttons read --control-h (compact variant for desktop toolbars/table rows only); inputs/selects read
--field-h with 16px text and 1.5px border. Under `@media (pointer: coarse)` compact becomes 44px.
Flux buttons/inputs, x-ui and dk-* must all read the same tokens.
Done when: computed heights match on one Flux, one x-ui and one dk control; input text is 16px.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.5
```
Task 1.5 — one global focus ring. See the plan §3 Phase 1.
Read "Common rules" first.
Scope: resources/css/.
Add a global :focus-visible rule: 2px solid ink outline, 2px offset. Inputs: ink border + 3px ring
at 12% ink. Never brand red. Remove the 30+ `outline: none` and their weak replacements, and the red
modal ring. In dark mode the ring uses the dark ink colour.
Done when: Tab through one list, one form and one modal shows a visible ring on every stop, both themes.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.6 — opus (wide blast radius)
```
Task 1.6 — one grey ramp, one semantic layer. See the plan §3 Phase 1 and decision D3.
Read "Common rules" first.
Scope: resources/css/tokens.css first; other CSS only to remove literals.
Canonical: ink #414042, ink-muted #6D6E71, ink-faint #939598, line-strong #BCBEC0, line #E6E7E8.
Re-point --color-secondary-*, --color-desk-ink*, --color-desk-line*, --color-ink*, --color-line*,
paper/tint/sunk, ground/board and the remapped red-50…300 and zinc scale to this ramp as ALIASES
(keep the old names working — do not rename usages in views in this task). Retire #58595b, #dcddde,
#232427 and the other off-ramp greys. Give the dark theme an equivalent five-step ramp.
Done when: no off-ramp grey literal in tokens.css (light); old token names still resolve; list every
place where the visible shift is more than subtle.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.7
```
Task 1.7 — muted text contrast. See the plan §3 Phase 1. Depends on 1.6.
Read "Common rules" first.
Scope: resources/css/ and views that set faint text colour directly (find with Grep: ink-faint,
desk-faint, secondary-light, text-zinc-400).
#939598 is 3.0:1 on white: allowed for icons, borders and disabled states only. Any readable text
(labels, meta, hints, placeholders) uses #6D6E71 or darker. Check dark mode separately.
Done when: no body/meta text below 4.5:1 in either theme; list of the files changed.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.8 — haiku
```
Task 1.8 — global reduced-motion rule. See the plan §3 Phase 1.
Read "Common rules" first.
Scope: resources/css/base.css.
Add one `@media (prefers-reduced-motion: reduce)` catch-all that removes animations, transitions and
smooth scrolling.
Done when: rule present and built.
Run: npm run build
```

### 1.9
```
Task 1.9 — dark theme pass. See the plan §3 Phase 1 and decision D2. Depends on 1.6.
Read "Common rules" first.
Scope: the .dark block in resources/css/tokens.css; the ~111 `bg-white` with no dark pair and the 9
HR views with no dark styles (find with Grep).
Set dark --color-brand, --color-brand-text, --color-brand-ink, --color-status-critical back to #BE1F24;
remove #d4343a, #e04046, #ff5a60. Red in dark mode is fills/buttons with white text only. Every place
that used red as small text on a dark surface becomes light ink + an icon.
Done when: no brightened red anywhere; every changed view readable in dark (≥4.5:1), checked on its
own, not inferred from light.
Run: npm run build; php artisan test --compact --filter=UiDebt
```

### 1.10 — opus
```
Task 1.10 — status and chart colours to the brand palette. See the plan §3 Phase 1 and decision D1.
Read "Common rules" first, and hope-center-brand.md (status colours and chart rules).
Scope: resources/css/tokens.css (status-*, funding*, series-1..6); components that draw status or
series (resources/views/components/ui/enum-badge, status-pill, gantt and chart components; the
Enums under app/Enums and modules/Tracking/Enums that return a colour/tone).
Replace green/blue/teal/amber and the six series colours with red + the five greys. Because colour
can no longer separate states, give every status an icon and its text label, and give chart series a
second channel (pattern, dash style, direct labels). Critical/overdue is the only state that uses red.
Done when: no off-palette colour token remains; each status is distinguishable in a greyscale
screenshot; tests cover the enum → icon/tone mapping.
Run: npm run build; php artisan test --compact --filter=Enum
```

---

## Phase 2 — One component per job (sequential, sonnet unless noted)

Shared scope: `resources/views/components/ui/**`, `resources/css/`, plus call sites named in each task. Each task: build/extend the one component, keep old names as thin aliases, migrate call sites, add a component test.

### 2.1 — opus
```
Task 2.1 — one button. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: resources/views/components/ui/button*.blade.php; resources/css (page-action, dk-btn, dk-iconbtn);
call sites that use `size-8!`, `text-status-critical!` or raw <button> with button styling.
One x-ui.button: sizes sm(38)/md(44)/lg(52), tones primary/neutral/danger/ghost, an icon-only size
(44x44 hit area, aria-label required), loading state, renders <a> when href is given.
.page-action, .dk-btn, .dk-iconbtn become aliases of the same tokens.
Done when: no page needs a `!` override to style a button; component test covers each tone/size.
Run: php artisan test --compact --filter=Button
```

### 2.2
```
Task 2.2 — one text input. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: x-ui.input, x-ui.form-input, ap-inp (annual-plan.css), dk-scope inputs and their call sites.
Merge into x-ui.form-input with a `search` variant: visible label, hint, error under the field,
46px / 16px text / 1.5px border, 2px border when invalid. x-ui.input becomes an alias.
Done when: one input look app-wide; component test covers label, hint, error, search.
Run: php artisan test --compact --filter=FormInput
```

### 2.3
```
Task 2.3 — one select, one multi-select. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: x-ui.select, x-ui.multi-select-popover and the other two multi-select variants, the 25 raw
flux:select uses.
Done when: one single-select and one multi-select component, same height/border as 2.2; all 25 raw
uses migrated; component tests.
Run: php artisan test --compact --filter=Select
```

### 2.4 — opus
```
Task 2.4 — chip and tag. See the plan §3 Phase 2. Depends on 1.10.
Read "Common rules" first.
Scope: enum-badge, status-pill, hold-chip, the three badge components, dk-pill, dk-tag, flux:badge uses.
Two components: status chip (radius 999, 14px, icon + label) and tag (13px). Tones limited to the
brand palette per D1; document the tone list in the component docblock. Old names alias to these.
Done when: two components; every old variant renders through them; component tests per tone.
Run: php artisan test --compact --filter=Chip
```

### 2.5
```
Task 2.5 — one dialog, one confirm. See the plan §3 Phase 2.
Read "Common rules" first and .ai/rules/js-views-components-ui.md.
Scope: x-ui.modal, x-ui.confirm-modal, the 18 `wire:confirm` uses, hand-rolled dialogs and
in-modal inline confirms.
Done when: every dialog uses x-ui.modal; every confirmation uses x-ui.confirm-modal; no wire:confirm
left; tests for two representative flows.
Run: php artisan test --compact --filter=<flows you touch>
```

### 2.6
```
Task 2.6 — skeleton and loading recipe. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: new x-ui.skeleton (row / card / panel variants); x-ui.data-table and x-ui.panel; the 13 pages
with no loading feedback (listed in the Tracking and HR audit notes in the plan §1 R6 — locate with
Grep for pages lacking wire:loading).
Standard recipe: wire:loading.delay shows the skeleton, aria-busy on the region, no layout shift.
Done when: data-table and panel show a skeleton while loading; the 13 pages use the recipe; test for the component.
Run: php artisan test --compact --filter=Skeleton
```

### 2.7 — haiku
```
Task 2.7 — alert/callout component. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: new x-ui.alert (tones info / warning / error, on-palette, icon + title + text + optional action,
role="alert" for error); replace ad-hoc banners found with Grep.
Done when: component + test; listed banners migrated.
Run: php artisan test --compact --filter=Alert
```

### 2.8
```
Task 2.8 — tooltip on icon-only buttons. See the plan §3 Phase 2. Depends on 2.1.
Read "Common rules" first.
Scope: x-ui.button icon size; a small x-ui.tooltip.
Every icon-only button shows its aria-label as a tooltip on hover AND keyboard focus; respects RTL
placement and reduced motion.
Done when: tooltips appear on HR and Tracking icon buttons without per-page code; component test.
Run: php artisan test --compact --filter=Tooltip
```

### 2.9
```
Task 2.9 — action menu. See the plan §3 Phase 2. Depends on 2.1.
Read "Common rules" first.
Scope: new x-ui.action-menu; x-ui.detail-header actions slot.
Rule: a header shows at most 1 primary + 2 secondary buttons; everything else goes into a "More"
menu; destructive items last, in the danger tone. Keyboard: arrows, Esc, focus returns to the trigger.
Done when: component + test + the rule recorded with the record-rule tool (glob resources/views/pages/**).
Run: php artisan test --compact --filter=ActionMenu
```

### 2.10 — haiku
```
Task 2.10 — sticky save bar + unsaved-changes guard. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: new x-ui.save-bar replacing the three hand-rolled copies (find with Grep for sticky bottom
bars in forms); the existing unsaved-changes guard JS.
Done when: one component, always paired with the guard; three copies removed; test.
Run: php artisan test --compact --filter=SaveBar
```

### 2.11
```
Task 2.11 — one filter bar. See the plan §3 Phase 2. Depends on 2.2, 2.3.
Read "Common rules" first and .ai/rules/app-livewire-concerns.md.
Scope: the three existing filter patterns (x-desk.register filters, header-filter-select, the
applicants filter partial) and a new x-ui.filter-bar.
Pattern: search + up to 3 inline filters + "More filters" + active-filter chips with clear-all.
Done when: component + test; employees and indicators lists use it as the reference.
Run: php artisan test --compact --filter=FilterBar
```

### 2.12 — haiku
```
Task 2.12 — one tabs component, one pager. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: x-ui.relation-tabs and the two other tab styles; flux:pagination vs funding.pager.
Done when: relation-tabs used everywhere tabs appear; one pager kept (state which and why); tests pass.
Run: php artisan test --compact --filter=<pages you touch>
```

### 2.13 — haiku
```
Task 2.13 — one empty state. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: x-ui.empty-state; the ~10 dk-*-empty variants in desk*.css and their uses.
Larger icon, title, one line of help, one primary action.
Done when: dk-*-empty variants removed; test for the component.
Run: php artisan test --compact --filter=EmptyState
```

### 2.14 — haiku
```
Task 2.14 — remove dead components. See the plan §3 Phase 2.
Read "Common rules" first.
Scope: resources/views/components/ui/{page-header,capacity-bar,status-strip}.blade.php; section → panel;
user-chip → employee-chip.
Confirm zero uses with Grep (Blade tags AND dynamic component names) before deleting each file.
Done when: files removed, aliases folded, full related tests pass. Do not delete any test file.
Run: php artisan test --compact --filter=Component
```

### 2.15
```
Task 2.15 — table row actions. See the plan §3 Phase 2. Depends on 2.9.
Read "Common rules" first.
Scope: x-ui.data-table; modules/Hr/resources/views/pages/employees/⚡index.blade.php as the reference.
Rule: at most 2 visible icon actions + a menu; delete always inside the menu.
Done when: employees table follows it; rule recorded with record-rule; test.
Run: php artisan test --compact --filter=Employees
```

### 2.16
```
Task 2.16 — phone bottom bar. See the plan §3 Phase 2 and decision D5.
Read "Common rules" first and .ai/rules/app.md, components-navigation.md, navigation.md.
Scope: resources/views/layouts/app/sidebar.blade.php, resources/views/components/navigation/**,
the desk layout, resources/css.
Under 640px: fixed bottom bar, 4 shortcuts + "More" (opens the existing full menu), 44px targets,
labels under icons, current page marked, env(safe-area-inset-bottom) padding, page content padded so
nothing hides behind it. Propose the 4 shortcuts from the most-used nav items and list them in the
report for the owner to confirm. Sidebar unchanged at ≥640px.
Done when: bar shows on every signed-in page at 375px in EN and AR; test asserts it renders.
Run: php artisan test --compact --filter=Navigation
```

---

## Phase 3 — Screen templates (sequential, opus)

### 3.1–3.4 (one agent per template, same shape)
```
Task 3.<n> — <List | Detail | Form | Settings> template. See the plan §3 Phase 3.
Read "Common rules" first.
Scope: the reference page only —
  3.1 List:     modules/Hr/resources/views/pages/employees/⚡index.blade.php
  3.2 Detail:   modules/Tracking/resources/views/pages/funding-agreements/⚡show.blade.php
  3.3 Form:     modules/Hr/resources/views/pages/contracts/⚡form.blade.php
  3.4 Settings: modules/Tracking/resources/views/pages/admin/ (evidence-types page)
Rebuild the reference page with Phase 2 components only, following the structure in the plan's
Phase 3 table. Then record the template as a short rule with the record-rule tool (glob covering
that page type), pointing at the reference page.
Done when: reference page has no raw <button>/<input>, no arbitrary text-[..], no `!` overrides;
verified at 1366 and 375, EN and AR, light and dark; existing page tests pass plus one new assertion.
Run: php artisan test --compact --filter=<reference page test>
```

---

## Phase 4 — Rollout waves (parallel, max 3–4, each in its own worktree)

### 4.x (one agent per wave)
```
Task 4.<n> — roll out the templates to <wave>. See the plan §3 Phase 4, row 4.<n>.
Read "Common rules" first, the template rules recorded in Phase 3, and use the review-page skill
for each page so it is rebuilt around the user's real job, not only reskinned.
Scope: only the folders of this wave —
  4.1  modules/Tracking/resources/views/pages/proposals/**                      (opus)
  4.2  modules/Hr/resources/views/pages/recruitment/**                          (opus)
  4.3  modules/Tracking/resources/views/pages/annual-plans/** + annual-plan.css (opus)
  4.4  modules/Tracking/resources/views/pages/{tasks,department-tasks}/**       (sonnet)
  4.5  modules/Tracking/resources/views/pages/{projects,programs,strategies}/** (sonnet)
  4.6  modules/Tracking/resources/views/pages/{risks,milestones}/**             (sonnet)
  4.7  resources/views/pages/notifications/** and the settings pages for notifications and security (sonnet)
  4.8  modules/Hr/resources/views/pages/{contracts,leaves,employees}/**         (sonnet)
  4.9  modules/Tracking/resources/views/pages/{funding-agreements,transfers,donors,donor-reports}/** (sonnet)
  4.10 modules/Tracking/resources/views/pages/{reports,monthly-reports,results,targets,indicator-library}/** (sonnet)
  4.11 remaining modules/Hr pages (attendance, mail-lists, positions, news, statistics, checklists) and admin settings (sonnet)
Work: apply the matching template and Phase 2 components; header ≤ 1 primary + 2 secondary + More;
remove raw buttons/inputs, arbitrary values, `!` overrides and per-page <style>; split any file over
~500 lines into partials; add loading and empty states; keep all behaviour and permissions unchanged.
Do not edit shared components — if one is missing something, report it.
Done when: guardrail counts for these folders went down (give before/after); every page checked at
1366 and 375, EN and AR, light and dark; existing tests for these pages pass.
Run: php artisan test --compact --filter=<wave area>
```

---

## Phase 5 — First impressions

### 5.1 — sonnet
```
Task 5.1 — login and auth pages. See the plan §3 Phase 5.
Read "Common rules" first and .ai/rules/auth.md, fortify.md, components.md. Load the fortify-development skill.
Scope: resources/views/pages/auth/** (4 pages), resources/views/layouts/auth/simple.blade.php.
One field style (x-ui.form-input) — remove the 20-override field class and the off-palette blue at
login.blade.php:12; add language switch, forgot-password link, busy state on submit.
No change to authentication logic.
Done when: all 4 pages share the field and button; auth tests still pass.
Run: php artisan test --compact --filter=Auth
```

### 5.2 — opus
```
Task 5.2 — public careers pages. See the plan §3 Phase 5.
Read "Common rules" first.
Scope: the public careers/apply views (locate with `php artisan route:list --path=careers`).
Shared form components; bordered inputs instead of underline-only; lighter section headers; a
labelled EN/AR switch; submit disabled while sending; remove dead jQuery hooks.
Done when: form usable at 375px with the keyboard only; application submit tests pass.
Run: php artisan test --compact --filter=Career
```

### 5.3 — opus
```
Task 5.3 — Desk home. See the plan §3 Phase 5.
Read "Common rules" first and .ai/rules/desk.md.
Scope: resources/views/pages/desk/**, resources/views/components/desk/**, app/Support/Desk/**, resources/js/desk-*.js.
First write a short proposal (in the report, not a file) of which customisation features to keep
out of: pin, fold, quick access, ready layouts, drag, sheet, tabs — and STOP for owner approval
before removing any feature. Safe to do now: real first-run state, one undo toast instead of three
copies, remove per-panel inline <style>.
Done when: safe items done with tests; proposal delivered.
Run: php artisan test --compact --filter=Desk
```

### 5.4 — opus (read-only)
```
Task 5.4 — duplicated Activity pages and inboxes. See the plan §3 Phase 5.
Read "Common rules" first.
Scope: READ-ONLY. The two Activity pages and the two inboxes (core notifications vs hr/inbox).
Report what each shows, who uses it, the overlap, and a recommendation: merge or keep with clear
names. No code changes.
Done when: recommendation delivered (max 20 lines).
```

---

## Phase 6 — Final verification

### 6.1 — opus reviewer (read-only, once per finished phase)
```
Task 6.1 — review the diff of Phase <n>.
Read "Common rules" first.
Scope: READ-ONLY. `git diff <phase base>..HEAD`.
Check against hope-center-sizes.md, hope-center-brand.md, rtl-arabic.md and decisions D1–D6:
off-palette colour, text under 12px, controls under spec, physical CSS properties, untranslated
strings, missing focus ring, missing confirmation, behaviour or permission changes, missing tests.
Report: findings ranked by severity with file:line. No fixes.
```

### 6.2 — sonnet (needs a signed-in browser session)
```
Task 6.2 — final passes. See the plan §3 Phase 6.
Read "Common rules" first.
Scope: no code changes; findings only.
Keyboard-only walk of one List, Detail, Form and Settings page; contrast in both themes; touch
targets ≥44px at 375px; Arabic pass with real copy (17px/1.7, <bdi> around LTR runs, mirrored
directional icons, Gantt direction); retake the 0.11 screenshots for before/after.
Done when: pass/fail per check with the page and element for each failure.
```
