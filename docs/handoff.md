# Handoff — HC Hub unification (2026-11-23)

## Done
- Phase 5 (roadmap `docs/hub-unification/03-roadmap.md`, rows 5.1–5.4) is finished, reviewed, merged into `main` and pushed (`000b6226`).
- Phase 6 item 6.1, first step: the full suite was run on the pre-phase-5 commit `797f1907` and compared with `main`.
  - Baseline: 7097 tests, 30 failed + 9 errors. `main`: 7111 tests, 30 failed + 9 errors.
  - The 39 broken tests are the same names on both sides. Phase 5 broke nothing; the +14 tests are the Sample module tests.
  - PHPStan level 7 (same app code as `main`): 956 errors — `modules/Hr` 716, `modules/Tracking` 138, `app` 86, `database` 16.

## Remaining
- 6.1: fix the 39 broken tests and the 956 PHPStan errors (nothing fixed yet; task table was proposed to the owner).
- 6.2–6.7 not started. 6.7 (dropping old tables/columns) needs explicit approval per table and a backup.
- Branch `hub/phase-5` still exists locally (merged, safe to delete).

## Open issues
- `php artisan test --compact` on the full suite runs out of memory; use `php -d memory_limit=3G vendor/bin/pest --compact` (~90–100 min).
- `tests/TestCase.php` refuses any DB whose name does not end in `_test`; an isolated run needs `DB_DATABASE=<name>_test` in the environment.
- Left in place by the 6.1 run: worktree `.claude/worktrees/p6-baseline` (detached at `797f1907`) and MySQL database `hc_hub_p6base_test`. Both are safe to remove.
- Logs: `$TEMP/phase5-suite.log` (main), `$TEMP/phase6-baseline-suite.log` (baseline), `$TEMP/phase6-phpstan-full.log`.
- Owner decisions waiting: accept or reject the proposed D9 change (`decisions.md` is unchanged); 8 open questions in the assets plan.
- Inventory docs disagree on `ApproveDisposal` vs `DecideDisposal` (belongs to the Inventory session).
- Untracked files of other sessions were left alone: `.claude/commands/inventory.md`, `docs/inventory/`, `docs/superpowers/plans/2026-11-20-*.md`.

## Next command
After the owner approves the 6.1 task table: start with the Hr-merge fallout group (policies, authorization coverage, module boundaries), one agent per group, each in its own worktree with its own `*_test` database.
