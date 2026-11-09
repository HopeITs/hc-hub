---
name: uat-media-team
description: Run the user-story test scenarios for the Media Team role against HC Tracking and report pass/fail per scenario. Use when asked to test, UAT or verify the Media Team role, or the ClickUp "Media team review" subtask.
---

# UAT: Media Team

Role name in code: `'Media Team'` (`userWithRole('Media Team')`). Source: ClickUp subtask "Media team review" and `docs/user-story.ar.md` §7. Baseline: view everything except money, plus tasks create/update and reports create/update.

## How to run (same for every role)
1. Read `.ai/rules/index.md` and the rule files for the paths involved (tasks, milestones, proposals).
2. For each scenario below, `Grep` `tests/` for existing coverage. Run it: `php artisan test --compact <file> --filter=<name>`. Use `userWithRole()`, a fresh `Livewire::actingAs(...)->test(...)` per forbidden call. Test direct URLs with `$this->actingAs($u)->get(route(...))`.
3. A scenario with no test is **NOT COVERED**. List proposed gap tests and wait for approval before writing them (house style: `.ai/rules/tests.md`, `testing-best-practices` skill).
4. A scenario that fails because the app is wrong is a **DEFECT**: report it, do not bend the test. Do not change code unless asked.
5. **Never** click Livewire actions in the Browser pane on a dev port: it writes to the real dev DB. Browser use is read-only inspection.
6. Report a table: ID, scenario, result (PASS / FAIL / NOT COVERED), evidence. Do not post to ClickUp unless asked.

## Scenarios
- **MD-01** ✅ See department tasks linked to proposals and milestones; do the task, upload a file, close it.
- **MD-02** 🚫 No money anywhere: proposals, agreements, transfers, accounts are hidden or forbidden, including by direct URL.
- **MD-03** 🚫 Cannot create projects, edit the logframe, approve reports, or use AI drafting (Media Team has no `ai.draft`).
- **MD-04** ⚠ Other departments' tasks are invisible, except the user's own and tasks on projects they answer for.
