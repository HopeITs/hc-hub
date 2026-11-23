# Inventory module · prompt pack

Plan: [`docs/superpowers/plans/2026-11-20-inventory-module.md`](../superpowers/plans/2026-11-20-inventory-module.md).
ClickUp: list **HC-Hub Inventory** (folder Projects › Hub). One ClickUp subtask per prompt.

Run a task with `/inventory INN [slice]`. The command loads `docs/lifecycle/AGENT.md`, then
[`AGENT.md`](AGENT.md) (addendum), [`contracts.md`](contracts.md), `docs/lifecycle/quality-bar.md`, the prompt,
the decisions it lists ([`decisions.md`](decisions.md)) and the upstream handoffs. Handoffs go to `handoffs/INN.md`.

| Order | Prompt | Phase | Model | Group | Needs | Size |
|---|---|---|---|---|---|---|
| 1 | [I01](prompts/I01.md) Module scaffold | P0 | sonnet | A | — | S |
| 2 | [I02](prompts/I02.md) Permissions and role baselines | P0 | sonnet | A | I01 | S |
| 3 | [I03](prompts/I03.md) Enums, categories, seeder | P0 | sonnet | A | I01 | S |
| 4 | [I04](prompts/I04.md) Asset model, policy, tag generator | P1 | opus | B | I02, I03 | M |
| 5 | [I05](prompts/I05.md) Assets index page | P1 | sonnet | C | I04 | M |
| 6 | [I06](prompts/I06.md) Asset create / edit / show | P1 | sonnet | C | I05 | M · 2 slices |
| 7 | [I07](prompts/I07.md) Custody actions and invariants | P2 | opus | D | I04 | M |
| 8 | [I08](prompts/I08.md) My equipment + confirm receipt | P2 | sonnet | E | I07 | S |
| 9 | [I09](prompts/I09.md) HR bridge | P2 | sonnet | E | I07, I08 | M · 2 slices |
| 10 | [I10](prompts/I10.md) Import command | P3 | opus | F | I07 | L · 2 slices |
| 11 | [I11](prompts/I11.md) Import runs + `employee_items` migration | P3 | sonnet | F | I10, I09 | M |
| 12 | [I12](prompts/I12.md) Transfers | P4 | sonnet | G | I07 | M · 2 slices |
| 13 | [I13](prompts/I13.md) Disposals and sales | P4 | sonnet | G | I07 | M |
| 14 | [I14](prompts/I14.md) Stock-take audits | P4 | sonnet | G | I07 | M |
| 15 | [I15](prompts/I15.md) SIM plans and lines | P5 | sonnet | H | I04 | M |
| 16 | [I16](prompts/I16.md) Software licences | P5 | sonnet | H | I04 | M |
| 17 | [I17](prompts/I17.md) Hub integration (desk, work items, timeline, search) | P6 | sonnet | I | I08, I12, I13 | M |
| 18 | [I18](prompts/I18.md) Overview page and alerts | P6 | sonnet | I | I16, I17 | M |
| 19 | [I19](prompts/I19.md) Whole-module review | P7 | opus (reviewer) | — | I01…I18 | M |
| 20 | [I20](prompts/I20.md) UAT, user guide, sheet read-only | P7 | human + sonnet | — | I19 | S |

**Parallel rules.** Tasks in one group touch the same files: one agent, in order. Groups may run in parallel
worktrees when neither lists the other under "Needs": C ∥ D after I04 · E ∥ F(I10) after I07 · G ∥ H after I07.
I12, I13, I14 all add buttons to the asset show page: run them in one agent or one after another, never in parallel.

**Gates.** A phase is closed by: full suite (in batches, by the developer), one commit per task, ClickUp moved by the
developer. P3 does not start on production before IT has reviewed the dry-run report. I19 runs before any production release.

## How a prompt is built
Goal (the outcome) → Sources of truth (reading order) → Start state (checks before plan mode) → Rules (server-side,
one definition per rule) → Build order (test file first) → Quality bar additions → Definition of done (named test
files, pasted output) → Stop and escalate → Do not.
