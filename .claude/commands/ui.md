---
description: Run one or more tasks of the UI/UX plan (ClickUp list "HC-Hub UI/UX")
argument-hint: <task id>... | next    e.g. 2.3  |  4.4 4.6 4.9  |  next
---
You are the orchestrator for UI/UX task(s) `$ARGUMENTS`. You delegate, check lightly, commit and report. You do not implement.

- PROMPTS = `docs/superpowers/plans/2026-11-20-ui-ux-prompts.md` (one block per task)
- PLAN = `docs/superpowers/plans/2026-11-20-ui-ux-improvement.md`

This command replaces the session-orchestrator start steps 3–5: do not look for or write a `docs/phase-*.md`, do not show a task table.

## Token rules (hold for the whole session)

- Never read PROMPTS or PLAN whole, and never read a task block yourself. The agent reads its own block.
- Never paste full files or full test output. Do not re-run an agent's tests.
- Open a full diff only for `resources/css/**`, `resources/views/components/ui/**`, or a risk the agent listed. Otherwise `git diff --stat`.

## 1. Pick the task(s)

Run these three commands, nothing more:

```
grep -nE "^### " docs/superpowers/plans/2026-11-20-ui-ux-prompts.md
git log --oneline -E --grep="\(UI [0-9]"
git status -s
```

If the third prints any tracked change (` M`, `M `, `A `…), someone may be mid-task in this checkout: list the files, ask the user whether that work is the task you were about to start, and stop until they answer. Untracked files (`??`) alone do not block.

The first gives each block's line and model (`### 2.4 — opus`; no model named = sonnet). Tasks 3.1–3.4 share the block `### 3.1–3.4` (opus). Tasks 4.1–4.11 share the block `### 4.x`: opus for 4.1–4.3, sonnet for 4.4–4.11.

The second lists finished tasks: the ids inside `(UI …)` in each subject. Phases 0 and 1 are finished except 0.11.

- A requested id that is already finished: say so in one line and skip it.
- `next` = the first unfinished id in this order: 2.1 → 2.16, 3.1 → 3.4, 4.1 → 4.11, 5.1 → 5.4, 0.11, 6.1, 6.2.
- Unknown id: print the output of the first command and stop.

## 2. Order rules — stop and say why if one is broken

- Phase 2 and Phase 3 share files: **one task at a time**, never in parallel.
- Phase 4 starts only when 3.1–3.4 are finished. Waves may run in parallel, at most 3.
- Phase 5 after Phase 4. Phase 6 last.
- Known dependencies: 2.8, 2.9 need 2.1 · 2.11 needs 2.2 and 2.3 · 2.15 needs 2.9.

## 3. Delegate — one agent per task

Use `isolation: worktree` and the model from step 1. Send exactly this, with `<id>` and `<line>` filled in:

```
Task <id> of the HC Hub UI/UX plan.
Read CLAUDE.md. Then in docs/superpowers/plans/2026-11-20-ui-ux-prompts.md read ONLY the "Common rules"
section and the block that starts at line <line> (stop at the next "###"). In
docs/superpowers/plans/2026-11-20-ui-ux-improvement.md read ONLY the table row of task <id>.
Follow the common rules and the block exactly. Work on task <id> only.
Before reporting: npm run build if any CSS, JS or Blade class changed; run the block's "Run" line and
php artisan test --compact --filter=UiDebt; vendor/bin/pint --dirty --format agent.
If UiDebt counts went down, lower the ceilings in tests/Unit/UiDebtTest.php to the new numbers.
Do NOT: commit, update ClickUp, edit files outside the block's scope.
Report (max 10 lines): files changed, test result (pass/fail counts), UiDebt before/after, open risks.
```

Exceptions:

- 0.11 and 6.2 need a signed-in browser: run them yourself in this session, no agent, no code changes.
- 5.4 and 6.1 are read-only: no worktree, no commit. For 6.1 add the phase number and its base commit to the prompt.
- 5.3: the agent stops with a proposal. Show it to the user and wait; removing a Desk feature needs their yes.
- 2.16: the agent proposes 4 shortcuts. Show them to the user before you commit.

If an agent fails the same task twice, retry once on opus. If that fails, stop and report.

## 4. Check, commit, close

1. Re-run the `git log` command from step 1. If the task's id is there now, another session finished it while the agent ran: do not merge or commit, tell the user where the agent's copy is, and skip to step 6. Otherwise `git diff --stat` of the agent's branch. Unclear report → one follow-up message to that agent.
2. Merge the worktree branch into the current branch.
3. Visual task: open one changed page on `http://hc-hub.test` at 1366 and 375 wide. Read the page as text first; take at most one screenshot per width. Say plainly which of EN/AR and light/dark you did not check. Never change the user's language or theme setting.
4. One commit per task: `<feat|fix|refactor>(ui): <what changed> (UI <id>)`. The `(UI <id>)` tag is what step 1 reads, so never leave it out. Do not push.
5. ClickUp, once for the whole run: find each task with a search for its id and name in list `1200200000016148`, set it to `complete`, add a one-line comment with the commit hash. When every subtask of a phase is complete, complete the phase task too. No ClickUp tools in this session → tell the user which tasks to close by hand.
6. Reply in the user's language, max 6 lines: done, failed, not verified, next id.
