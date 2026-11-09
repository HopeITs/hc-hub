---
name: uat-meal
description: Run the user-story test scenarios for the MEAL role against HC Tracking and report pass/fail per scenario. Use when asked to test, UAT or verify the MEAL role, or the ClickUp "MEAL review" subtask.
---

# UAT: MEAL

Role name in code: `'MEAL'` (`userWithRole('MEAL')`). Source: ClickUp subtask "MEAL review" and `docs/user-story.ar.md` §5. MEAL holds indicators/results/targets writes, `reports.approve`, `mark-seen`, `reopen`, `aggregate`, and the MEAL-only permissions.

## How to run (same for every role)
1. Read `.ai/rules/index.md` and the rule files for the paths involved (indicators, results, targets, reports, risk-library, report-templates).
2. For each scenario below, `Grep` `tests/` for existing coverage. Run it: `php artisan test --compact <file> --filter=<name>`. Use `userWithRole()`, a fresh `Livewire::actingAs(...)->test(...)` per forbidden call.
3. A scenario with no test is **NOT COVERED**. List proposed gap tests and wait for approval before writing them (house style: `.ai/rules/tests.md`, `testing-best-practices` skill).
4. A scenario that fails because the app is wrong is a **DEFECT**: report it, do not bend the test. Do not change code unless asked.
5. **Never** click Livewire actions in the Browser pane on a dev port: it writes to the real dev DB. Browser use is read-only inspection (check Arabic labels against `lang/ar`).
6. Report a table: ID, scenario, result (PASS / FAIL / NOT COVERED), evidence. Do not post to ClickUp unless asked.

## Scenarios
- **ME-01** ✅ Build a logframe (impact → outcome → output → activity) and targets per center; risks can only be picked from the library.
- **ME-02** ✅ Confirm a report template: the project becomes `active` and monthly reports are generated.
- **ME-03** ✅ Validate a submitted monthly report; decide a reopen request.
- **ME-04** ⚠ Nothing validated → the donor report shows zero figures; a confirmed template with no lines is refused.
- **ME-05** ✅ Aggregate a monthly or half-yearly donor report from validated numbers only.
- **ME-06** 🚫 Cannot approve a proposal, approve a donor, record an agreement, or edit transfers.

## Known open question
The docs mention a "MEAL Manager" for reopen decisions; there is no such role in `config/access-control.php`. Report any reopen test that assumes one as a **story/config mismatch**.
