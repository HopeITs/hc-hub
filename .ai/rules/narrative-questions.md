---
paths:
  - 'modules/Tracking/resources/views/pages/admin/*narrative-questions*'
---

# Narrative questions

## Three status buttons over one ordered list (scenario pass 2026-11-09)
The bank is ordered within each frequency: the order MEAL sets is the order monthly reports ask. `_narrative-questions-focus-bar` has three buttons — Monthly (on), Quarter end (on) and Switched off — that call `setFocus()`; the counts come from `focusCounts()`, are disjoint, add up to the total and read under the search. The old frequency dropdown is gone. Rows stay grouped under a Monthly and a Quarter end heading; each row carries one status word (On / Switched off) and its position in the **whole** frequency (`$positions`), not in the narrowed list.

While a search or button narrows the list the up/down arrows are disabled with a tooltip, because a move is a move in the whole frequency; they keep their `data-test="move-narrative-question-up|down"` hooks and the refocus-after-move script. Edit is the row's main action; Switch off / Switch on and Delete are in the ⋯ menu, and both confirmation dialogs name the question. Search covers both languages.

"Deactivate" is "Switch off" and "Inactive" is "Switched off" in English (Arabic إيقاف / متوقف, تشغيل for switch on); actions, routes and the `is_active` column keep their names. A used question still cannot be deleted: its delete dialog offers "Switch off instead". Tests in NarrativeQuestionsTest parse the rendered HTML to keep the buttons and rows inside the Livewire root.
