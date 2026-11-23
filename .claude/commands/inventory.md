---
description: Run one task (or slice) of the Inventory module plan
argument-hint: INN [slice]
---
You are starting inventory task `$ARGUMENTS`. The first word is the task id (e.g. I04); the optional second word is the slice number (default 1).

1. Read `docs/lifecycle/AGENT.md`, then `docs/inventory/AGENT.md`, and follow both for the whole session. Where they differ, `docs/inventory/AGENT.md` wins.
2. Read `docs/inventory/contracts.md`, then `docs/lifecycle/quality-bar.md`.
3. Read the prompt `docs/inventory/prompts/<task id>.md`. If it has slices, work **only** on the requested slice.
4. Read only the decisions the prompt lists (`docs/inventory/decisions.md`) and every upstream handoff it lists (`docs/inventory/handoffs/`).
5. Read `.ai/rules/index.md` and every rule file whose glob matches a path the prompt touches.
6. Run the prompt's **Start state** checks. If any fails, report it and stop.
7. Enter plan mode and present the plan (`docs/lifecycle/AGENT.md §3`). Do not edit files before approval.

If the prompt file does not exist, list `docs/inventory/prompts/` and stop.
