---
paths:
  - 'modules/Tracking/resources/views/pages/programs/**'
---

# Programs

## Programs are Core rows; money stays off the directory
Programs are Core rows (see .ai/rules/models.md): only the strategy and manager links are editable here, and the program name is never edited on this page (a new program comes from a proposal location). The "no proposal" (not funded yet) signal is offered only to users with `proposals.view`; money never appears on the directory page.

## The directory kept the program-page redesign (merge decision 2026-11-06)
When main was merged into scenarios-workflow, both branches had redesigned programs/⚡index.blade.php (main: one list with three status buttons and `_index-focus-bar` / `_index-list`; this branch: the program-page redesign). The user chose this branch's page; main's two partials and its tests for them were dropped. Do not reintroduce them without asking.
