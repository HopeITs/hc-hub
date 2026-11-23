# Inventory · contracts (frozen)

Names here are final. A session that needs a different name, column or signature stops and escalates (`AGENT.md §6` of
the lifecycle rules). The task that creates each item is in brackets.

## 1. Enums — `Modules\Inventory\Enums` [I03]
TitleCase cases, lower-case stored values, labels under `enums.inventory.<enum>.<value>` in `lang/{en,ar}/enums.php`.

| Enum | Cases → stored value | Notes |
|---|---|---|
| `AssetStatus` | InUse `in_use` · InStock `in_stock` · Broken `broken` · Missing `missing` · Disposed `disposed` · Sold `sold` | `isTerminal()` true for Disposed, Sold. `isAssignable()` true for InStock only. |
| `AssetCondition` | New `new` · Good `good` · Fair `fair` · Poor `poor` | |
| `CategoryGroup` | It `it` · Network `network` · Security `security` · Media `media` · Accessory `accessory` · Other `other` | |
| `HolderType` | Employee `employee` · Center `center` · Department `department` · Store `store` | |
| `TransferStatus` | Draft `draft` · InTransit `in_transit` · Received `received` · Cancelled `cancelled` | |
| `DisposalType` | Sold `sold` · Disposed `disposed` · Lost `lost` | |
| `DisposalStatus` | Pending `pending` · Approved `approved` · Rejected `rejected` | |
| `AuditResult` | Found `found` · Missing `missing` · Damaged `damaged` | |
| `SimStatus` | Active `active` · Hybrid `hybrid` · Suspended `suspended` · Cancelled `cancelled` | |

Sheet value → enum (used by the importer, one definition in `Support\Import\ValueMap`): In Use→InUse · Not Used→InStock ·
Trashed→Broken · Missed→Missing · Disposed→Disposed · Sold→Sold · فعال→Active · فعال-هجين→Hybrid · ايقاف مؤقت→Suspended · ملغى→Cancelled.
Matching is case-insensitive and trims spaces.

## 2. Tables
All tables: `id`, timestamps, utf8mb4_unicode_ci, explicit index and FK names (`inv_<table>_<cols>_idx|uq|fk`), working `down()`.
FKs to `cities`, `centers`, `programs`, `employees`, `departments`, `users` are `restrictOnDelete` unless noted.

```
inventory_categories [I03]
  name_en, name_ar, slug (uq), group (CategoryGroup), requires_serial bool, has_specs bool, is_active bool, sort

inventory_assets [I04]                                   soft deletes
  asset_tag (uq, 32)            category_id fk           brand?, model?
  serial_number? (uq)           status (AssetStatus, idx) condition (AssetCondition)
  city_id fk (idx)              center_id? fk            program_id? fk
  holder_type (HolderType)      employee_id? fk (idx)    department_id? fk      holder_label? (import only)
  specs json?  {cpu,gpu,ram,hdd,ssd}
  purchased_at? date            entered_at? date         invoice_number?        purchase_price? decimal(12,2)   currency? (3)
  notes? text                   last_verified_at?        last_verified_by? fk users
  legacy_source? (tab name)     legacy_row? int          (uq together, nullable — import idempotency)

inventory_custodies [I07]
  asset_id fk (idx)             holder_type              employee_id? / center_id? / department_id?
  handed_by fk users            handed_at                condition_out (AssetCondition)
  acknowledged_at?              acknowledged_by? fk users
  returned_at?                  received_by? fk users    condition_in? (AssetCondition)
  notes?                        checklist_id? fk checklists (nullOnDelete)  [I09]
  open_marker tinyint null      -- 1 while returned_at is null, null after; unique(asset_id, open_marker)

inventory_transfers [I12]
  reference (uq, TR-YYYY-0001)  from_city_id, from_center_id?, to_city_id, to_center_id?
  status (TransferStatus)       sent_by?, sent_at?, received_by?, received_at?, notes?
inventory_transfer_items [I12]
  transfer_id fk (cascade)      asset_id fk              received_at?           note?        uq(transfer_id, asset_id)

inventory_disposals [I13]
  asset_id fk                   type (DisposalType)      status (DisposalStatus) reason text
  price? decimal(12,2)          currency?                buyer_employee_id?     buyer_name?
  requested_by fk users         decided_by? fk users     decided_at?            decision_note?

inventory_audits [I14]
  city_id fk, center_id? fk     started_by, started_at   closed_by?, closed_at? notes?
inventory_audit_items [I14]
  audit_id fk (cascade)         asset_id fk              result? (AuditResult)  note?  checked_by?  checked_at?
  uq(audit_id, asset_id)

inventory_sim_plans [I15]
  operator, name, price? decimal(10,2), data?, sms?, minutes?, is_active        uq(operator, name)
inventory_sim_lines [I15]
  number (uq, digits only)      operator                 account_name?          status (SimStatus)
  credit_limit?, balance? decimal(10,2)                  plan_id? fk
  city_id? fk, center_id? fk    employee_id? fk          holder_label?          notes?

inventory_licenses [I16]
  product                       type?                    license_key (text, `encrypted` cast, hidden)
  expires_at? date (idx)        program_id? fk, city_id? fk
  employee_id? fk               asset_id? fk inventory_assets (nullOnDelete)    is_needed bool      notes?
```

## 3. Models — `Modules\Inventory\Models`
`Category`, `Asset`, `Custody`, `Transfer`, `TransferItem`, `Disposal`, `Audit`, `AuditItem`, `SimPlan`, `SimLine`, `License`.
- `Asset`: uses `RecordsActivity`; relations `category, city, center, program, employee, department, custodies,
  openCustody (hasOne, returned_at null), transferItems, disposals`. Scopes: `visibleTo(User $user)`,
  `search(string $term)` (tag, serial, brand, model), `heldBy(Employee $employee)`.
- `Asset::visibleTo`: `inventory.view-all` → all · `inventory.view-city` → assets in the user's cities via `AccessScope`
  · `inventory.view-own` → assets whose open custody is the user's employee · otherwise none.
- Core bridge (provider, `resolveRelationUsing`): `Employee::inventoryAssets()`, `Employee::inventoryCustodies()`.

## 4. Permissions — group `inventory` in `config/access-control.php` [I02]
`inventory.view-own` · `inventory.view-city` · `inventory.view-all` · `inventory.manage` (create/edit assets, categories)
· `inventory.assign` (assign/return/acknowledge for a center) · `inventory.transfer` · `inventory.dispose` (request)
· `inventory.approve-disposal` · `inventory.audit` · `inventory.sims.manage` · `inventory.licenses.manage` · `inventory.import`.

Policies (`Modules\Inventory\Policies`): `AssetPolicy` — `viewAny, view, create, update, assign, return, transfer,
requestDisposal, report`; `CustodyPolicy` — `acknowledge`; `TransferPolicy`, `DisposalPolicy` (`approve`),
`AuditPolicy`, `SimLinePolicy`, `LicensePolicy` (`viewKey`). Every city-scoped ability also checks the record's city
against `AccessScope`.

## 5. Actions — `Modules\Inventory\Actions` (the only writers of status / holder / city)
```php
Assets\CreateAsset::handle(array $data): Asset                                    // [I04] generates the tag
Assets\UpdateAsset::handle(Asset $asset, array $data): Asset                      // [I04] never status/holder/city
Assets\ReportAssetCondition::handle(Asset $asset, AssetStatus $to, ?string $note): Asset   // [I07] Broken|Missing|InStock
Custody\AssignAsset::handle(Asset $asset, HolderType $type, ?int $holderId, AssetCondition $conditionOut,
                            ?string $notes = null, ?int $checklistId = null): Custody       // [I07]
Custody\AcknowledgeCustody::handle(Custody $custody): Custody                     // [I07]
Custody\ReturnAsset::handle(Custody $custody, AssetCondition $conditionIn, ?string $notes = null,
                            bool $markBroken = false): Custody                              // [I07]
Transfers\CreateTransfer::handle(array $data, array $assetIds): Transfer          // [I12]
Transfers\SendTransfer::handle(Transfer $transfer): Transfer                      // [I12]
Transfers\ReceiveTransferItem::handle(Transfer $transfer, int $assetId, ?string $note = null): TransferItem  // [I12]
Transfers\CloseTransfer::handle(Transfer $transfer): Transfer                     // [I12] unreceived items → Missing
Transfers\CancelTransfer::handle(Transfer $transfer): Transfer                    // [I12] Draft only
Disposals\RequestDisposal::handle(Asset $asset, DisposalType $type, array $data): Disposal   // [I13]
Disposals\DecideDisposal::handle(Disposal $disposal, bool $approve, ?string $note = null): Disposal // [I13]
Audits\StartAudit::handle(City $city, ?Center $center): Audit                     // [I14] snapshots expected assets
Audits\RecordAuditItem::handle(Audit $audit, int $assetId, AuditResult $result, ?string $note = null): AuditItem // [I14]
Audits\CloseAudit::handle(Audit $audit): Audit                                    // [I14]
```
Each state-changing Action: `Gate::authorize` first, then one `DB::transaction` that takes `lockForUpdate()` on the
asset row (or transfer / disposal / audit row), checks the expected current status, writes, and dispatches
notifications after commit. A failed guard throws `Modules\Inventory\Exceptions\InvalidAssetState` (extends `DomainException`),
which pages turn into a translated validation error.

`Modules\Inventory\Support\AssetTagGenerator::next(City $city, ?Program $program): string` — `{CITY}-{PROGRAM}-{0001}`,
sequence per city+program, allocated under a lock so two concurrent creates never collide (unique index is the last guard).

## 6. Invariants (each has a named test)
1. An asset has at most one open custody (`open_marker` unique index + Action guard).
2. `status = in_use` ⇒ exactly one open custody; `in_stock` ⇒ no open custody, `holder_type = store`, holder ids null.
   A `broken` / `missing` asset may still have an open custody (the holder stays accountable until it is returned or disposed).
3. Only `InStock` assets can be assigned, put on a transfer, or have a disposal requested (`Broken`/`Missing` may also be disposed).
4. An asset on a transfer `InTransit` cannot be assigned, returned, audited as found elsewhere, or added to another transfer.
5. `city_id` / `center_id` change only in `ReceiveTransferItem`.
6. `Sold` / `Disposed` are terminal and set only by `DecideDisposal(approve: true)`. The requester cannot decide their own request.
7. `asset_tag` never changes.

## 7. Routes — prefix `/inventory`, middleware as Hr (`web`, `auth`, 2FA)
| Name | URI | Ability | Task |
|---|---|---|---|
| `inventory.overview` | `/inventory` | `inventory.view-own` (content differs by scope) | I01 / I18 |
| `inventory.assets.index` | `/inventory/assets` | `viewAny` Asset | I05 |
| `inventory.assets.create` · `.show` · `.edit` | `/inventory/assets/create` · `/{asset}` · `/{asset}/edit` | policy | I06 |
| `inventory.mine` | `/inventory/mine` | `inventory.view-own` | I08 |
| `inventory.transfers.index` · `.show` | `/inventory/transfers` · `/{transfer}` | `inventory.transfer` | I12 |
| `inventory.disposals.index` | `/inventory/disposals` | `inventory.dispose` or `inventory.approve-disposal` | I13 |
| `inventory.audits.index` · `.show` | `/inventory/audits` · `/{audit}` | `inventory.audit` | I14 |
| `inventory.sims.index` | `/inventory/sims` | `inventory.sims.manage` | I15 |
| `inventory.licenses.index` | `/inventory/licenses` | `inventory.licenses.manage` | I16 |

## 8. Notifications and alerts [I08, I12, I13, I18]
Category `inventory` in `config/notifications.php`. Events: custody assigned (to the holder), disposal requested
(to approvers), disposal decided (to requester), transfer sent (to destination-city IT).
Alert scanners: `HandoverUnacknowledged` (> 7 days), `TransferStuck` (InTransit > 14 days), `LicenseExpiring` (30 / 7 days).

## 9. Commands
`inventory:import {tab} {csv} {--dry-run} {--report=}` [I10] · `inventory:import-sims {csv} {--dry-run}` [I15]
· `inventory:import-licenses {csv} {--dry-run}` [I16] · `inventory:migrate-employee-items {--dry-run}` [I11].
All are idempotent and print a summary table (created / updated / skipped / unmatched).
