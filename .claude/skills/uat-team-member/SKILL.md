---
name: uat-team-member
description: Run the user-story test scenarios for the Team Member role against HC Tracking and report pass/fail per scenario. Use when asked to test, UAT or verify the Team Member role, or the ClickUp "Team member review" subtask.
---

# UAT: Team Member

Role name in code: `'Team Member'` (`userWithRole('Team Member')`), the default role for new HR accounts. Source: ClickUp subtask "Team member review" and `docs/user-story.ar.md` §8. Baseline: view everything except money and the activity log; nothing else. Note `.ai/rules/tests.md`: every role has `tasks.*`, so "no task access" needs a roleless user.

## How to run (same for every role)
1. Read `.ai/rules/index.md` and the rule files for the paths involved (tasks, desk, reports).
2. For each scenario below, `Grep` `tests/` for existing coverage. Run it: `php artisan test --compact <file> --filter=<name>`. Use `userWithRole()`, a fresh `Livewire::actingAs(...)->test(...)` per forbidden call.
3. A scenario with no test is **NOT COVERED**. List proposed gap tests and wait for approval before writing them (house style: `.ai/rules/tests.md`, `testing-best-practices` skill).
4. A scenario that fails because the app is wrong is a **DEFECT**: report it, do not bend the test. Do not change code unless asked.
5. **Never** click Livewire actions in the Browser pane on a dev port: it writes to the real dev DB. Browser use is read-only inspection.
6. Report a table: ID, scenario, result (PASS / FAIL / NOT COVERED), evidence. Do not post to ClickUp unless asked.

## Scenarios
- **TM-01** ✅ See assigned tasks, including report tasks, and complete them.
- **TM-02** ✅ Create, tick and delete personal To-do items; other users never see them.
- **TM-03** 🚫 Everything else is read-only: each create/edit/delete action is forbidden when forced through Livewire, and the buttons are hidden.
- **TM-04** 🚫 No money pages and no activity log (page and direct URL).
- **TM-05** ⚠ Desk panels without permission do not render; a new HR account (`User::forHrAccount()`) gets this role by default.
