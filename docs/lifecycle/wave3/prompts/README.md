# Wave 3 prompts

One prompt per item in `docs/lifecycle/wave3/README.md §2`. Run with `/lifecycle X-NN [slice]`; the command loads `docs/lifecycle/AGENT.md`, then `docs/lifecycle/wave3/AGENT.md`, `contracts.md`, `quality-bar.md`, and the prompt. Handoffs go to `docs/lifecycle/wave3/handoffs/`.

| Order | Prompt | Owner | Size | Needs | Contract change |
|---|---|---|---|---|---|
| 1 | [X-07](X-07.md) Desk matrix starts in January | Dev 2 | S | — | — |
| 2 | [X-08](X-08.md) Submitted report reads as done | Dev 2 | S | — | — |
| 3 | [X-10](X-10.md) Extension check | Dev 2 | S | — | — |
| 4 | [X-04](X-04.md) PR Manager approves | Dev 1 | S | **D-70** | §6, §8 |
| 5 | [X-05](X-05.md) PR adds centers, PR + MEAL manage sectors | Dev 1 | S | **D-72**, X-04 | §8 |
| 6 | [X-06](X-06.md) "My projects" task scope | Dev 2 | S | **D-73** | §12.2 |
| 7 | [X-09](X-09.md) Intermediary donor | Dev 1 + Dev 2 | M · 2 slices | **D-71** | §7 |
| 8 | [X-02](X-02.md) Project = program × governorate | Dev 1 | L · 3 slices | **D-69, D-69a, D-69b** | §2, §3, §6, §7 |
| 9 | [X-01](X-01.md) Naming per audience | Dev 2 | M | X-02 | glossary only |
| 10 | [X-11](X-11.md) User story + PDF | Dev 2 | S | all above | — |

Phase 1 (X-07, X-08, X-10) needs no decision: start now, and the three can run in parallel. X-06 and X-09 may run beside X-04/X-05 (different modules). X-02 runs alone: it touches projects, materialisation and monthly reports.

## How a prompt is built
Goal → Sources of truth (reading order) → Start state (checks before plan mode) → `[contract]` (verbatim text, with the D-number) → Rules → Build order (test first) → Quality bar → Definition of done → Stop and escalate → Do not.
