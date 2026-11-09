---
name: uat-financial
description: Run the user-story test scenarios for the Financial role against HC Tracking and report pass/fail per scenario. Use when asked to test, UAT or verify the Financial role, or the ClickUp "Financial review" subtask.
---

# UAT: Financial

Role name in code: `'Financial'` (`userWithRole('Financial')`). Source: ClickUp subtask "Financial review" and `docs/user-story.ar.md` §4. Only Financial (and General Manager/Super Admin) sees the money pages; tests that write must seed `RolesAndPermissionsSeeder` (see `.ai/rules/tests.md`).

## How to run (same for every role)
1. Read `.ai/rules/index.md` and the rule files for the paths involved (transfers, funding-agreements, proposals, donor-reports).
2. For each scenario below, `Grep` `tests/` for existing coverage. Run it: `php artisan test --compact <file> --filter=<name>`. Use `userWithRole()`, a fresh `Livewire::actingAs(...)->test(...)` per forbidden call.
3. A scenario with no test is **NOT COVERED**. List proposed gap tests and wait for approval before writing them (house style: `.ai/rules/tests.md`, `testing-best-practices` skill).
4. A scenario that fails because the app is wrong is a **DEFECT**: report it, do not bend the test. Do not change code unless asked.
5. **Never** click Livewire actions in the Browser pane on a dev port: it writes to the real dev DB. Browser use is read-only inspection.
6. Report a table: ID, scenario, result (PASS / FAIL / NOT COVERED), evidence. Do not post to ClickUp unless asked.

## Scenarios
- **FI-01** ✅ Enter the budget and currency (`requested_budget`) on a `proposal draft`; the move to review is blocked without it.
- **FI-02** ✅ Record an agreement, then fill a transfer: transfer ≤ arrival ≤ receive dates, received amount, `fees`, exchange rate.
- **FI-03** ⚠ Dates in the wrong order are refused; re-recording an agreement creates nothing.
- **FI-04** ✅ The finance section of the donor report is visible to Finance only.
- **FI-05** 🚫 Cannot approve a proposal or a report, or edit the logframe or targets.
- **FI-06** ⚠ Reviewers: the baseline grants `proposals.manage-reviewers`, so test that it works; flag the mismatch with the ClickUp wording for the owner.
- **FI-07** ✅ Finance department tasks are visible only to the Finance department (`TaskVisibilityScope`).
