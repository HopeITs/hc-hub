# Inventory · decisions

Defaults chosen with the plan on 2026-11-20. A decision changes only by an edit to this file approved by the developer.

| # | Decision | Consequence for the code |
|---|---|---|
| D1 | New module `modules/Inventory` (`Modules\Inventory`), wired exactly like `modules/Hr`. | Own provider in `bootstrap/providers.php`, own migrations folder, `inventory::` namespaces, one routes file per area, registered with `ModuleRegistry`. |
| D2 | Inventory owns the asset; HR checklists stay. | `checklists.asset_id` nullable FK (I09). Delivering a checklist line picks an asset and calls `AssignAsset`. Offboarding "equipment returned" reads open custodies. `employee_items` is migrated (I11) and then frozen. |
| D3 | No passwords in the hub (v1). | The Cameras credential columns and the GMAILS tab are never imported. DVR/NVR/camera devices are imported as plain assets. |
| D4 | Every asset has a tag `{CITY}-{PROGRAM}-{0001}`. | `AssetTagGenerator`; tag unique; serial unique when present, nullable otherwise. An asset without a program uses `GEN`. A tag never changes after creation, even when the asset moves city. |
| D5 | Import from CSV by Artisan command. | No spreadsheet library. One CSV per sheet tab, `--dry-run` first. |
| D6 | Reuse hub infrastructure. | `cities`, `centers`, `programs`, `employees`, `departments`, `AccessScope`, `RecordsActivity`, `<x-notes.record>`, encrypted `Media`, `CsvExport`, print Blade views, `AppNotification` + scanners. |
| D7 | Status follows custody. | `InUse` ⇒ exactly one open custody; `InStock` ⇒ none. A device reported broken or missing while held keeps its open custody. Returning sets `InStock` (or `Broken` when returned `Poor` and flagged). `Broken`/`Missing` are set by `ReportAssetCondition`; `Sold`/`Disposed` only by `ApproveDisposal`; they are terminal. |
| D8 | A holder is an employee, a center, a department or a store. | `holder_type` + exactly one of `employee_id` / `center_id` / `department_id`; `store` means "in the IT store of `city_id`" and has no open custody. |
| D9 | Acknowledgement is a click, not a signature. | `acknowledged_at` set by the holding employee only. Center/department custodies are acknowledged by a user with `inventory.assign` in that city. Unacknowledged after 7 days raises an alert (I18). |

## Open questions (do not decide in a session — escalate)
- Who approves disposals: General Manager only, or also the finance manager? (default in I02: GM role only)
- Should an employee see the purchase price of what they hold? (default: no — price is `inventory.manage` only)
- Depreciation / accounting value: out of scope for v1.
