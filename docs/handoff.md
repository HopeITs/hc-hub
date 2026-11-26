# Handoff — HC Hub phase 7 + UI/UX (2026-11-26)

## Done (all on `main` at `04d6e129`, nothing pushed)
- 7.10b–e merged (`e0ddf06e`); A1 HR merge kept main's contract weekdays and leave alternatives plus the branch's extras.
- port-daily: was already merged on `main` (`0aeb232a`) by another session. The A2 merge added only test updates (6 stale tests moved to the embedded proposal page or to place-scoped MEAL coverage), the `_framework-matrix` indicator fix (`@if`/`@else` around `x-ui.button`), and `forms.css` `.form-section-review` → `--color-brand-text`. Merged as `15eb7f97`.
- 7.11 Entitlements: hc-hr's contract→entitlements sync was already ported (`modules/Hr/Support/Entitlements`). Added `tests/Feature/Hr/ContractEntitlementSyncTest.php` (8 cases via SaveContract, amendment approval and TerminateContract). Merged as `04d6e129`.
- Proposals: "Set grant dates" is back in the proposal head's More actions menu (`bbd49a59`). Checked in the browser; the dialog opens.
- UI ratchet: the proposal and annual-plan pages are back under the UiDebt and DesignTokens guardrails (`c8e465eb`, `25963cc1`).

## Remaining
- Full suite not run for this batch: `php -d memory_limit=3G vendor/bin/pest --compact` (~90–100 min, run it in the background).
- ClickUp is not updated for the batch (the MCP daily limit of 100 calls was hit on 2026-11-26). Update: UI 0.9, 2.1, 2.14, 2.6, 2.7; 7.10 merged; port-daily merged; 7.11 done (task `123jdbau5j3`).
- Remind the owner to set the `CONTRACT_SIGNATORY_*` values in the production `.env`.

## Open issues
- `DesignTokensTest` "readable text never uses the faint grey" fails on `main`: `placeholder:text-ink-muted!` in `x-ui.input`. It comes from UI 2.2 (`1dc87477` / `4d48c1e3`, the `/ui` session), not this batch.
- Known failures that were already there: CriticalPositionsTest and PickerScopesTest (`$criticalMark` not found); EmployeeFormParityTest draft contract fails only in the full run.
- 7.11 small differences from hc-hr: EntitlementHooks skips updates on rows loaded without `type`, `is_active` or `position_id`; `PositionSeats::holdsSeat` treats a null `is_active` as no seat. Bulk query-builder writes skip the hooks (`entitlements:backfill` covers them).
- Annual-plans page not checked visually (no plans in the local DB).
- Isolated test DBs: `hc_hub_a1_test`, `hc_hub_a2_test`, `hc_hub_e7_test`. Worktrees `agent-a171731e357d703c9` and `agent-ae66b132ddc2a4275` are merged and safe to remove (each has an untracked copy of vendor).

## Next command
Run the full suite in the background, compare its failures with the known list above, then update ClickUp once for the batch.
