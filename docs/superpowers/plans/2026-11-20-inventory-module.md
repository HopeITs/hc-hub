# Inventory & Equipment Custody module — implementation plan

Date: 2026-11-20 · Source: Google Sheet "Hope Electronics NEW" · Roadmap link: `docs/hub-unification/03-roadmap.md` phase 5.3

**Execution pack:** [`docs/inventory/README.md`](../../inventory/README.md) — one prompt per task (`I01`…`I20`), run with
`/inventory INN`. Shared rules: [`AGENT.md`](../../inventory/AGENT.md) · [`contracts.md`](../../inventory/contracts.md) ·
[`decisions.md`](../../inventory/decisions.md). Where the pack and this plan differ, the pack wins.
**ClickUp:** list [HC-Hub Inventory](https://app.clickup.com/90151272596/v/l/li/1200200000016134) (Projects › Hub).
Task ids: 0.1–0.3 = I01–I03 · 1.1–1.3 = I04–I06 · 2.1–2.3 = I07–I09 · 3.1–3.2 = I10–I11 · 4.1–4.3 = I12–I14 ·
5.1–5.2 = I15–I16 · 6.1–6.2 = I17–I18 · 7.1–7.2 = I19–I20.

## 1. What the sheet holds today

| Tab | Rows | Becomes in the hub |
| --- | --- | --- |
| Aleppo / Damascus / Homs / Tartus / Latakia | 996 / 170 / 2 / 39 / 277 | Assets (one register, filtered by city) |
| Communication | 242 | Assets, category group "Media" (camera bodies, lenses, lights, audio) |
| Extra Laptops | 27 | Sale records (price, buyer) on existing assets |
| Study Zone Laptops | 31 | Custody history + inter-city transfers |
| Numbers | 274 | SIM lines (operator, status, credit limit, plan, holder) |
| Kasper | 62 | Software licences (type, key, expiry, holder) |
| DropBoxes | — | Lookup lists: categories, statuses, centers, SIM plans, brands |
| Cameras, GMAILS | 89 / 15 | **Credentials — not imported** (see decision D3) |

Problems the module must fix: each tab has different columns; status and item names are typed inconsistently
("In Use"/"in Use", "Hub"/"HUB", "Security Camera"/"Security camera"); the holder is free text ("CENTER", "IT Store",
"Media Team", a person's name); history lives in an "Old Users" cell; ~120 items have no serial number; the same laptop
appears in up to three tabs; nobody signs for what they receive.

## 2. Decisions (defaults chosen — change before phase 0 if you disagree)

- **D1 — New module `modules/Inventory`** (`Modules\Inventory`), wired exactly like `modules/Hr`: own provider in
  `bootstrap/providers.php`, own migrations folder, `inventory::` Livewire namespace, one routes file per area,
  registered with `ModuleRegistry` (destinations, desk panel, timeline, search, work items, schedule).
- **D2 — Inventory owns the asset; HR checklists stay.** `checklists` gets a nullable `asset_id`. Onboarding still uses
  the checklist, but delivering a line picks a real asset and opens a custody record. Offboarding's "equipment returned"
  step reads open custody records. `employee_items` is migrated into assets and then frozen.
- **D3 — No passwords in the hub (v1).** DVR/NVR devices are imported as assets; their passwords, e-mail passwords and
  the GMAILS tab move to a password manager. A hub vault would need per-reveal auditing and key management — out of scope.
- **D4 — Every asset gets a tag** `{CITY}-{PROGRAM}-{0001}` (the sheet already improvises `LAT-HC010`). Serial number
  is unique when present, optional otherwise.
- **D5 — Import from CSV, not xlsx.** The hub has no spreadsheet library and dependencies need approval; each tab is
  exported to CSV and read by an Artisan command.
- **D6 — Reuse, do not rebuild:** `cities`, `centers`, `programs`, `employees`, `departments`, `AccessScope`,
  `RecordsActivity`, `<x-notes.record>`, encrypted `Media` (invoices, photos), `CsvExport`, print Blade views,
  `AppNotification` + scanners.

## 3. Data model

```
inventory_categories   name_en, name_ar, group(it|network|security|media|accessory|other),
                       requires_serial, has_specs, is_active
inventory_assets       asset_tag*, category_id, brand, model, serial_number(unique, nullable),
                       status, condition, city_id, center_id, program_id,
                       holder_type(employee|center|department|store), employee_id?, department_id?,
                       specs(json: cpu, gpu, ram, hdd, ssd), purchased_at, entered_at, invoice_number,
                       purchase_price?, notes, last_verified_at, last_verified_by
inventory_custodies    asset_id, holder_type, employee_id?, department_id?, center_id?,
                       handed_by, handed_at, condition_out, acknowledged_at,
                       returned_at, received_by, condition_in, notes          (full history, replaces "Old Users")
inventory_transfers    reference, from_city/center, to_city/center, status(draft|in_transit|received|cancelled),
                       sent_by, sent_at, received_by, received_at
inventory_transfer_items  transfer_id, asset_id, received(bool), note
inventory_disposals    asset_id, type(sold|disposed|lost), reason, price?, buyer_employee_id?, buyer_name?,
                       requested_by, approved_by, approved_at
inventory_audits       city_id, center_id?, started_by, started_at, closed_at
inventory_audit_items  audit_id, asset_id, result(found|missing|damaged), note
inventory_sim_plans    operator, name, price, data, sms, minutes
inventory_sim_lines    number*, operator, account_name, status(active|hybrid|suspended|cancelled),
                       credit_limit, balance, plan_id, city_id, center_id, employee_id?, holder_label?, notes
inventory_licenses     product, type, key(encrypted), expires_at, program_id, city_id,
                       employee_id?, asset_id?, is_needed
```

Enums (TitleCase): `AssetStatus` InUse · InStock · Broken · Missing · Disposed · Sold (sheet: In Use · Not Used ·
Trashed · Missed · Disposed · Sold) — `AssetCondition` New · Good · Fair · Poor — `HolderType`, `TransferStatus`,
`DisposalType`, `SimStatus`.

Invariants enforced in Actions, covered by tests: an asset has at most one open custody; `assets.status/holder/city`
are only changed through Actions (assign, return, transfer, dispose) so the register and the history never disagree;
Sold/Disposed/Missing assets cannot be assigned or transferred; disposal needs approval before the status changes.

## 4. Permissions (group `inventory` in `config/access-control.php`)

`view-own` (every employee: my equipment) · `view-city` · `view-all` · `manage` · `assign` · `transfer` · `dispose`
(request) · `approve-disposal` · `audit` · `sims.manage` · `licenses.manage` · `import`.
City IT officers are scoped by `AccessScope` to their city; licence keys are visible only with `licenses.manage`.

## 5. Screens

1. **Assets** — data-table with filters (city, center, program, category, status, holder), search by tag/serial/model, CSV export.
2. **Asset page** — details, specs, current holder, custody timeline, transfers, notes, files; actions: assign, return, transfer, report broken/missing, request disposal.
3. **My equipment** (every employee) — what I hold, "confirm receipt" button, report a problem.
4. **Employee profile → Equipment tab** (HR) — current and past custody; printable handover / clearance form.
5. **Transfers** — create shipment, mark sent, receive item-by-item at destination.
6. **Stock-take** — per center checklist; missing items are flagged, verified items get `last_verified_at`.
7. **Disposals & sales** — request → management approval → done; sale price and buyer.
8. **SIM lines** and **Licences** — registers with expiry / status filters.
9. **Overview** — stat tiles: in use / in stock / broken / missing per city, unconfirmed handovers, licences expiring.

All pages bilingual (en/ar, RTL), built from existing `x-ui.*` components, following `.ai/rules`.

## 6. Phases and tasks

| ID | Task | Model | Group | Done when |
| --- | --- | --- | --- | --- |
| **P0** | **Foundation** | | | |
| 0.1 | Module scaffold: provider, routes loader, view + Livewire namespaces, lang files, nav destination | sonnet | A | `/inventory` renders an empty page for a permitted user, 403 otherwise |
| 0.2 | Permission group + role baselines + `permissions:sync` | sonnet | A | catalog test passes |
| 0.3 | Enums, categories table + seeder from the DropBoxes list | sonnet | A | seeder idempotent |
| **P1** | **Asset register** | | | |
| 1.1 | `assets` migration, model, factory, policy, tag generator, activity logging | opus | B | policy + scope tests pass |
| 1.2 | Index page (filters, search, CSV export) | sonnet | C | feature tests for filter + scope |
| 1.3 | Create / edit / show pages, notes, files | sonnet | C | CRUD tests pass |
| **P2** | **Custody** | | | |
| 2.1 | `custodies` + Assign / Acknowledge / Return actions with invariants | opus | D | invariant tests pass |
| 2.2 | "My equipment" page + confirm receipt + notification | sonnet | E | employee sees only own items |
| 2.3 | HR bridge: `checklists.asset_id`, profile Equipment tab, offboarding step, print form | sonnet | E | onboarding delivery opens a custody |
| **P3** | **Import** | | | |
| 3.1 | `inventory:import {tab} {csv} --dry-run`: per-tab column map, value normalisation, duplicate-serial merge, holder → employee matching, unmatched report | opus | F | dry-run report on real CSVs reviewed by IT |
| 3.2 | Run on staging, fix data, run on production; migrate `employee_items` | sonnet | F | counts match the sheet |
| **P4** | **Movements** | | | |
| 4.1 | Transfers (send / receive) | sonnet | G | asset city changes only on receipt |
| 4.2 | Disposals & sales with approval | sonnet | G | status changes only after approval |
| 4.3 | Stock-take audits | sonnet | G | missing items flagged |
| **P5** | **SIM lines & licences** | | | |
| 5.1 | SIM plans + lines register + import | sonnet | H | 274 lines imported |
| 5.2 | Licences with encrypted key + expiry scanner + import | sonnet | H | key hidden without permission |
| **P6** | **Hub integration** | | | |
| 6.1 | Desk panel, work items (unconfirmed handover, incoming transfer, pending approval), timeline, search | sonnet | I | contracts registered, tests pass |
| 6.2 | Overview page + alerts (licence expiry, handover unconfirmed > 7 days, transfer stuck) | sonnet | I | scanners tested |
| **P7** | **Close** | | | |
| 7.1 | Whole-module review (security, N+1, RTL) | opus reviewer | — | findings fixed |
| 7.2 | UAT with IT team, user-guide section, sheet set to read-only | — | — | IT signs off |

Tasks in the same group touch the same files and run in one agent; groups inside a phase run in parallel worktrees.
Each phase ends with the full test suite, one commit per task, and a short report.

## 7. Risks

- **Data quality on import** — handled by dry-run reports and an "unmatched holders" list IT resolves before the real run.
- **Two sources of truth during rollout** — the sheet stays the master until 3.2 is signed off, then becomes read-only.
- **The sheet is public by link and holds plain-text passwords** — restrict sharing and rotate those passwords now, independent of this project.
- **Tests run on MySQL (`hc_hub_test`)** — parallel agents need separate test databases (`--parallel` tokens) to avoid collisions.
