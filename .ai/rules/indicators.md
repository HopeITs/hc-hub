---
paths:
  - 'modules/Tracking/resources/views/pages/indicators/**'
---

# Indicators

## The indicator directory loads once, then filters, rates and pages in memory
indicators/⚡index.blade.php reads every indicator up front with the whole chain — result (+program, +`parentRecursive` for lineage), project, `progress` (+recorder), `planIndicators` (+project, the morphed `source` with the proposal's donors, and the `reports` rows themselves) — then resolves the filters, the RAG rating, staleness, each row's status word, the three status-tab counts and pagination in PHP afterwards. Flat at 20 queries however it is filtered or paged; `IndicatorDirectoryTest` asserts that, so a dropped `with()` fails the suite rather than quietly going N+1.

`reports` are loaded as rows, not `withCount`, because the page shows what was actually reported (`sum(reports.value)`) against what was committed (`plan_indicators.target`) — the question the funding side asks. Counts alone cannot answer it. A report still `draft` past its `planned_date` counts as overdue; it is shown inside "Details" (and by the legacy `reporting` focus), no longer as a top-level chip.

Lineage is walked over the eager-loaded `parentRecursive` via `lineageOf()`, never `HasTree::ancestors()` — `ancestors()` reads `parent`, a different relation key, so it would fire a query per level per row.

Because the filters run in memory, the paginator is hand-built (`new LengthAwarePaginator(...)` over `filteredRows`), not `->paginate()`. `WithPagination` is still used for `getPage()`/`resetPage()`.

Achievement is rated against the calendar year (`now()->startOfYear()`/`endOfYear()`), the same window results/⚡index.blade.php uses, so a percentage means the same thing on both pages. The RAG rating compares achievement against that window's elapsed share, so keep using the same figure.

Any write must call `forgetIndicators()`, which unsets every cached computed — clearing only `pagedRows` re-renders stale headline figures in the same request.

All four dialogs (indicator form, delete, reading, chain inspector) are bound with `wire:model` on a boolean, never `:open`; see .ai/rules/pages.md for why.

## One list, three status tabs, one plain word per row (user decision 2026-11-06)
The page was rebuilt for first-time users: no Cards/Table switch (the `view` property is gone), no rating bar, no chip row, no commitments block. `_index-focus-bar` is three buttons — Needs attention, Waiting for a reading, On track — that set `focus` to `attention` / `waiting` / `ontrack`; pressing the active one clears it. `_index-list` is one row per indicator: name, program, one status word, a progress bar ("88% of 85%"), last measurement date, **Log reading**, **Details**, and a ⋯ menu for edit/delete. Donor, result and measure filters sit behind **More filters**; search and program stay visible. This supersedes the register Table/Board/Calendar switch for this page only.

Each row carries `status`, set by `statusFor()` in BuildsIndicatorRows: no readings → `unmeasured`; red → `late`; amber → `watch`; stale → `stale`; green → `ontrack`; otherwise `notarget`. **A green rating only counts as on track while its last measurement is within the indicator's own frequency**, otherwise a green stale indicator would sit under "On track" while also needing a reading. The three tab counts are disjoint (`attention` = watch + late, `waiting` = unmeasured + stale, `ontrack`), and the list is sorted late → watch → stale → unmeasured → notarget → ontrack, then by name (stable sort in `filteredRows()`). The legacy focus values (`offtrack`, `stale`, `reporting`, `unmeasured`, `untargeted`) and `setRag()` still work server-side but have no UI.

The headline average caps each indicator at 100% before averaging, so one over-target indicator cannot drag the mean up. A reading at least double its target shows a non-blocking warning in the reading dialog.

Arabic says **قياس** (measurement), never **قراءة** (reading); English still says "reading". Keep the status words in lang/*/indicators.php (`status_*`, `tab_*`) in step with `statusFor()`. The partials must stay balanced: an unclosed wrapper once pushed the chips, cards and pager outside the Livewire root, so they were dead and filters left a stale list — `IndicatorDirectoryTest` parses the rendered HTML to catch it.
