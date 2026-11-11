---
paths:
  - 'modules/Tracking/resources/views/pages/indicator-library/**'
  - 'modules/Tracking/Livewire/Concerns/IndicatorLibrary/**'
---

# Indicator library

## Three status buttons, search and one list (scenario pass 2026-11-09)
The library is a catalogue MEAL keeps and proposals pick from, so the one question is "what can people pick, and what is nobody using?". `_index-focus-bar` has three buttons — In use (active, at least one indicator uses it), Not used yet (active, none does) and Switched off — that call `setFocus()`; pressing the active one clears it. `statusOf()` in `ListsLibraryEntries` is the single source of the word; `focusCounts()` reads `matchingEntries()` (search and every filter except the buttons), so the three counts are disjoint, add up to the matching total and equal what a click shows.

Search and Program stay visible; Level, Measure, Collected by and English name sit behind **More filters** (open only while one is active). The old Status select is gone: the buttons replace it. Each row has one status word, **Edit** (for holders of `indicator-library.update`), **Details** (expands the definition and sources) and a ⋯ menu with Switch off / Switch on and Delete; Delete stays visible but disabled with the reason when an indicator uses the entry. Both confirmation dialogs name the entry.

English says "Switch off" / "Switched off" (Arabic إيقاف / متوقف), the same as the risk library; the actions, `is_active` column and `confirmDeactivate`/`reactivateEntry` methods keep their names. Entries stay grouped under their program heading and in framework order; the page does not re-sort by status.
