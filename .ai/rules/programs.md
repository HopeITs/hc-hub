---
paths:
  - 'resources/views/pages/programs/**'
---

# Programs

## The directory is one list with three status buttons (scenario rebuild 2026-11-06)
programs/⚡index.blade.php is no longer a card grid. `_index-focus-bar` is three buttons — Needs attention, Not set up yet, On track — that call `setFocus()` (the `focus` URL property); pressing the active one clears it. `_index-list` is one row per program: name, strategy · manager, one coloured status word with the reasons (projects overdue, live risks, not funded yet), project and annual-plan counts, **Open program**, **Assign** when the program is not fully linked (only with `programs.update`) and a ⋯ menu to edit the link. The strategy filter and search stay visible; the manager filter is behind **More filters**.

`allPrograms()` loads every program matching the search and filters once, with its counts, and sets `status` (`attention` = a late project, a live risk or a project with no proposal; `setup` = no strategy, no manager or no projects; otherwise `ontrack`, in that order so the three are disjoint), `health` (the dot colour: critical / warn / good / none) and `urgency` (late + risky projects). It sorts attention → setup → on track, then by late-project count, then by name (stable). `focusCounts()` and `programs()` read that same list, so a tab count always equals the rows a click shows. "Load more" still pages the list eight at a time, four per click.

The funding signal ("not funded yet") is only counted for users with `proposals.view`; for everyone else a program with no late or risky projects reads On track. Money never appears on this page.

Programs still mirror HR (see .ai/rules/models.md): only the strategy and manager links are editable, and the program name is never edited here. `ProgramManagementTest` parses the rendered HTML to check the tabs and list sit inside the Livewire root.
