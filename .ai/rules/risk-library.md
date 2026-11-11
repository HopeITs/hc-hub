---
paths:
  - 'modules/Tracking/resources/views/pages/admin/*risk-library*'
---

# Risk library

## Three status buttons over one ordered list (scenario pass 2026-11-09)
The library is an ordered list: the order MEAL sets is the order the logframe picker offers, so the page never re-sorts by urgency. `_risk-library-focus-bar` has three buttons — In use (active and picked on at least one proposal risk), Not used yet (active, never picked) and Switched off — that call `setFocus()`; the counts come from `focusCounts()`, are disjoint, add up to the total and read under the search. Each row carries one status word and its position in the **whole** library (`$positions`), not its place in the filtered list.

While a search or button narrows the list, the up/down arrows are disabled with a tooltip, because a move is a move in the whole library; the arrows keep their `data-test="move-entry-up|down"` hooks and the refocus-after-move script. Edit is the row's main action; Switch off / Switch on and Delete are in the ⋯ menu, and the two confirmation dialogs name the entry ("Switch off “…”?", "Delete “…”?"). Search covers both titles and the category.

"Deactivate" is "Switch off" and "Inactive" is "Switched off" in English (Arabic إيقاف / متوقف); the action classes, routes and `is_active` column keep their names. A used entry still cannot be deleted: its delete dialog offers "Switch off instead". `RiskLibraryHeadlineTest` parses the rendered HTML to keep the buttons and rows inside the Livewire root.
