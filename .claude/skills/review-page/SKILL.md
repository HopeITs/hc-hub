---
name: review-page
description: Build, rebuild or fix any HC Tracking page around the user's real job (scenario) so it is simple, honest and plain-worded in English and Arabic. Encodes what worked when the Indicators page was rebuilt. Use when asked to simplify, redesign, fix or review a page's flow, or after /page-review produced a fix plan.
argument-hint: '"page name" "page-path"'
---

# HC Tracking: Scenario Workflow for One Page

Apply this to any page. It turns a page into the shortest path for the job a first-time NGO manager came to do. It was distilled from the Indicators rebuild (review, four fix phases, then a full simplification).

## 1. Start from the scenario, not the page
Write down before touching code:
- **Who** opens the page (Project & Fundraising Manager, MEAL Manager, or both) and **the one question** they ask ("which indicators need me?", "which reports are late?").
- **The 1–3 jobs** they finish there, and the click count today.
- **Five-second test:** what do they think the page is for, what do they do first, what confuses them.

The page must answer the one question at the top, show what needs a hand first, and put the main action one click away. Everything else is secondary.

## 2. Work in this order (stop and ask only where section 9 says)
1. **Review** with `/page-review` (click everything, EN and AR, dark and light, phone, keyboard). Evidence only; reproduce bugs twice.
2. **Phase 1, correctness:** wrong numbers, dead controls, stale or duplicated lists, access leaks.
3. **Phase 2, core job:** missing filters, missing feedback, broken Arabic or phone use, exports donors need.
4. **Phase 3, wording:** plain words, one term per idea.
5. **Phase 4, polish:** keyboard, labels, tap sizes.
6. **Simplify:** apply section 3 to the layout.

One reviewable commit per phase. Pull and merge before pushing. Never commit or push unless asked.

## 3. Layout principles (what the Indicators rebuild proved)
- **One question per page.** At most **three headline numbers**, and each is a button that filters the list; pressing it again clears the filter. No decorative charts, no five-chip rows.
- **One list, one row per record.** Row = name, where it belongs, **one status word with a colour**, how far along it is, the date it was last updated, **one primary action**, a **Details** button and a ⋯ menu (edit, delete). Do not offer Cards/Table/Board switches unless the owner insists.
- **Details live behind Details**, not on the row: targets, donors, reports, links to other records.
- **Search and one main filter stay visible.** Every other filter goes behind **More filters** (open by default only if one is active).
- **What needs a hand comes first:** sort by urgency, then name (stable).
- **Show a way out of every empty or no-result state** (Reset filters).
- **Records open as full pages, confirmations as centred dialogs** (settled rule). Dialogs bind a boolean with `wire:model`, never `:open`.

## 4. Honest numbers (a wrong number outranks any design issue)
- Say the denominator: "39 of 61", not "100%" of an unstated subset.
- A good status is only good while its data is fresh; otherwise show "old", not "on track".
- Counts on the tabs must be **disjoint** and match what a click shows.
- Cap or flag outliers before averaging; warn (never block) when a typed value is far from its target.
- Compare every headline figure with the rows below it and with its source page before shipping.
- Never expose money to roles without the permission; export only what the page already shows.

## 5. Plain words, English and Arabic
- Replace jargon with the user's word. Keep **one term per idea across the whole page** (no "Unrated" next to "Never measured").
- Status words: on track / needs attention / behind / waiting / old / no target set.
- Arabic uses the field's everyday word (e.g. **قياس** for a measurement, not **قراءة**). Check gender and grammar after bulk replacements.
- Button labels are verbs the user would say ("Log reading", "Details"); no internal names ("The chain").
- Distinguish "none yet" from "not enough" in empty text.
- Update `lang/en` and `lang/ar` together; never hard-code text.

## 6. Forms and dialogs
- Long selects and long text fields get **their own full-width row**; never put two long selects side by side. Segmented choices get a full row so labels are never clipped.
- Put the record's name in destructive dialogs ("“Meals” will be deleted…").
- Optional help text under a field is a better place for a warning than a toast.
- Double-click on save must create one record. Esc closes dialogs. Empty submit shows a message.
- A delete that is not allowed stays visible but disabled, with the reason.

## 7. RTL, dark and phone
- Wrap user-entered names in `<bdi>`; wrap number-plus-unit pairs ("88% of 85%") in `dir="ltr"` so the percent sign does not jump.
- Use logical properties (`ms-`, `ps-`, `text-start`) and flip arrows with `rtl:`.
- Dark mode uses the Graphite tokens only (see `.ai/rules/css.md`); never change a light class when restyling dark.
- Phone (375px): no sideways page scroll, tap targets at least 44px, list rows stack. Wide tables get a "scroll sideways" hint.
- Test the page in English, Arabic, light, dark, 375, 768 and 1440 before calling it done.

## 8. Accessibility
- Every icon-only control has a name; duplicate controls (a bar and its legend) are one tab stop.
- Disabled-with-reason items are focusable; every focus stop shows an outline; Tab order follows reading order.

## 9. Guardrails
- **Check `.ai/rules` first** (index.md, then the matching files). Settled rules are not bugs. **Changing a settled rule, who can see money, deleting or migrating real data, or touching other pages needs owner approval** with your recommended answer. Replacing a register's view switch is such a change.
- Do not add dependencies or base folders without approval.
- Test records are named `REVIEW-TEST …` and removed afterwards; never edit or delete real records. Do not type passwords; if not signed in, stop and ask.
- Use Livewire/Flux components already in the project; follow sibling pages' structure. The page file stays at or under 750 lines; split into `_index-*` partials.
- Keep partial markup **balanced**: one unclosed wrapper once pushed the controls outside the Livewire root so they did nothing. Add a Pest test that parses the rendered HTML and asserts the key controls sit inside the component root.

## 10. Tests and finish
- Every change gets a Pest feature test (see the `testing-best-practices` skill): behaviour, one failure mode, no extras. Update tests of removed UI instead of deleting coverage of kept behaviour; do not delete test files without approval.
- Run the affected tests, then `vendor/bin/pint --dirty --format agent`. Ask the owner to run the full suite.
- Verify in the browser, not only in tests, and share what you saw.
- After a design decision lands, update the matching `.ai/rules/<area>.md` so the next agent does not follow stale rules (edit the existing file; use `record-rule` only for new rules and only when asked).

## Definition of done for a page
1. A first-time manager names the page's purpose and first action in five seconds.
2. At most three headline numbers, each a working filter.
3. One list; one status word per row; one primary action per row.
4. Every number has a stated denominator and matches its source.
5. No jargon; one term per idea; English and Arabic both read naturally.
6. No dead control, no stale or duplicate list after any filter.
7. Forms: full-width long fields, named destructive dialogs, no clipped labels.
8. RTL, dark, 375px, keyboard checks pass.
9. Tests pass, Pint is clean, rules updated, work committed per phase.
