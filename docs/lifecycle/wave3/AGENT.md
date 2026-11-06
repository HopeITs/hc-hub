# Wave 3 · session addendum to `docs/lifecycle/AGENT.md`

`/lifecycle X-NN [slice]` loads `docs/lifecycle/AGENT.md` first, then this file, then `contracts.md`, `quality-bar.md`, the prompt. Everything in `AGENT.md` and `wave2/AGENT.md` §4–§5 still applies; this file only adds or tightens.

## 1. Sources of truth for wave 3
1. `docs/lifecycle/contracts.md` (frozen, as before).
2. `docs/lifecycle/decisions.md` D-01…D-54, the rulings tables in `docs/lifecycle/wave2/README.md` (D-55…D-68), and the **signed** rows of `docs/lifecycle/wave3/README.md §1` (D-69…D-73). A D-number that is not marked signed there is **not** a source: a prompt that depends on it stops at Start state.
3. The task prompt `docs/lifecycle/wave3/prompts/X-NN.md`.
4. Upstream handoffs: `docs/lifecycle/handoffs/TNN.md`, `docs/lifecycle/wave2/handoffs/W-NN.md`, `docs/lifecycle/wave3/handoffs/X-NN.md`.
5. `CLAUDE.md` and the matching `.ai/rules` files.

## 2. Contract changes are allowed only where a prompt says so
Same rule as wave 2: the prompt's **`[contract]`** section is the only text you may add to `contracts.md`, in the same PR, as the first commit the developer reviews. Anything else is an escalation (`AGENT.md §6`).

## 3. Branches and ClickUp
- Branch from `main`: `wave3/X-NN-<slug>` (reuse it for a later slice).
- Put the ClickUp task URL in the PR description and the handoff header when the prompt has one. Do not change the ClickUp task yourself.

## 4. Tests on this machine
`php -d memory_limit=1536M vendor/bin/pest --compact <files>`, only the files the prompt names. The developer runs the suite in batches before merging.

## 5. Finish
`AGENT.md §9`, with the handoff written to `docs/lifecycle/wave3/handoffs/X-NN.md` from `docs/lifecycle/wave3/handoffs/_TEMPLATE.md`.
