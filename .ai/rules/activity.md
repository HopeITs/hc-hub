---
paths:
  - 'resources/views/pages/activity/**'
---

# Activity log

## Two headline buttons, search plus Area up front, the rest behind More filters (2026-11-06)
The log answers "what changed, and who did it?". Today and Last 7 days are buttons that set the date range (pressing the active one clears it). Their counts come from `summary()`, which applies the Area, Person and search filters and ignores only the dates, so a click always shows exactly that many entries (`ActivityLogHeadlineTest`). Search and Area stay visible; Person, From and To are behind **More filters**, open by default only when one is set.

"Module" is shown to users as **Area** (Arabic المجال) and the program column as **Program**; the `module` property, URL parameter and column name are unchanged. The sentence on each row already names the person, so there is no separate Person column. Day groups, the Time/Area sort and the per-day totals are kept as built.
