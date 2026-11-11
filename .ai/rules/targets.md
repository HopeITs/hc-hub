---
paths:
  - 'modules/Tracking/resources/views/pages/targets/**'
  - 'modules/Tracking/Livewire/Concerns/Targets/**'
---

# Targets

## One headline sentence, three status buttons, one row per target (scenario rebuild 2026-11-06)
The page answers "are we reaching what we committed?". `_index-pulse` is one sentence that names its denominator ("9 of 57 targets have reached their committed value") and three buttons — Nothing approved yet, In progress, Achieved — that call `filterStatus()`; pressing the active one clears it. The counts are disjoint and add up to the total, and they ignore the status filter itself (they are read from `filteredTargets()`, not `matchingTargets()`). The stacked bar, the plans/proposals counters and the pending-hatch legend are gone.

Each row shows: indicator, period, one line "plan or proposal → project", one coloured status word, "40 of 200 households", a progress bar, **Record a report** (primary), **Details** and a ⋯ menu (edit, delete). The row's status word comes from the same rule as `PlanIndicator::achievementStatusSql()` (approved reports only). Delete stays visible but disabled, with the reason, once reports exist.

Everything secondary is behind **Details** (`toggleReports()` / `expandedTargetId`): the source link, program, donors and **their budgets** (still gated by `canViewAgreements()`), the project and city, report counts, drafts, baseline, and the report trail. Do not put money or donor links back on the row.

Search and Program stay visible; Committed in, Project, Period, Donor and Year are behind **More filters** (open by default only when one is active). The list is still grouped by result and paged by result (6 groups a page, so a result's targets never split); inside a group the order is nothing approved → in progress → achieved, then indicator. The "showing groups x–y of z" text counts groups, not targets.

The delete dialog names the indicator ("Delete the target for “Meals served”?"). `TargetDirectoryTest` parses the rendered HTML to assert the controls sit inside the Livewire root.
