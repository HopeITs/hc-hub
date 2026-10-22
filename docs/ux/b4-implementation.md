# B4 · Compact tables, preserved context and presets (implementation notes)

Status: **implemented, uncommitted on branch `b4`; formal review pending; not marked done.**
Design source: [table-contract.md](table-contract.md) (B4-D). Tables covered: Employees, Contracts.

## What changed
- `app/Livewire/Concerns/RemembersTableState.php`: session state keyed by user + active role + city scope; URL wins; page clamp; invalid filters dropped with a notice; Clear all (keeps columns/per-page); filter chips; phone card hook `cardView()`.
- Vendor table view: chips, Clear all, notice, cards below `md`.
- `EmployeeTable` + `EmployeeDrawer`: compact columns, `?view=active|inactive` (Inactive HR/GM only), row click opens a read-only drawer, named row actions and More menu, phone cards, export hint.
- `ContractsTable` + `ContractDrawer`: compact columns, `?view=` Active / Ending soon (30 d) / Terminated / Draft reservations (non-Active HR/GM only), drawer without any pay figure, phone cards, export hint.
- Excel export of contracts now follows the picker's columns; `ContractExport::styles()` return type fixed (it was a fatal error).
- `config/access-control.php`: drawer and table actions classified.
- Tests: `RemembersTableStateTest`, `EmployeeTableB4Test`, `ContractsTableB4Test`; four existing tests adjusted (page clamp, preset, chip echo, drawer instead of navigation).

## Acceptance evidence
| AC | Evidence |
|---|---|
| B4-01 | Drawers are separate components; tests assert search/filters/sort/page unchanged after open/close. |
| B4-02 | Tests: role/city switch restores nothing; out-of-scope filter dropped; forged preset 403; cross-city ids 404. |
| B4-03 | Phone cards tested for rendering; **browser result: see evidence in `docs/ux/evidence/b4/` (pending at time of writing)**. |

Full suite at the time of writing: 867 passed, 0 failed.

## Not done / limits
- **Personal saved views (B4D-Q5, §5.1) are deferred:** they need the `saved_table_views` migration, which requires separate authorization.
- Preset is URL-only (not remembered in session) for Contracts; Employees likewise.
- Free-text column searches have no allowed-value check; phone cards offer global search only.
- Leave tables (All leaves, My leaves, Balances) are not converted; outside the B4 inspect scope.
- Excel export of employees has no server-side permission check in the component (pre-existing, unchanged).
- Browser keyboard/focus/RTL checks: see evidence folder.
- No migration, deploy or data change was made.
