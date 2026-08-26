# Contract Renewal — Design

**Date:** 2026-08-26
**Status:** Approved

## Problem

When a contract nears its end date, HR must create the follow-on contract by hand: open
`/contracts/create`, re-select the city, employee, program, center, position and contract
type, then re-enter every term and pay field to match the expiring contract. Every field is
a chance to introduce a discrepancy, and the common case — "same job, same pay, new dates" —
costs the same effort as an entirely new hire.

## Goal

A **Renew** button on a contract that opens a modal offering two paths:

1. **Renew as is** — create a new contract identical to the source except for the dates.
2. **Renew with edit** — open the normal contract-creation wizard, fully prefilled from the
   source with the new dates, so every field can be adjusted before saving.

Both paths produce a brand-new contract row. The source contract is never modified.

## Current behaviour (as of this spec)

- Creation is a 3-step wizard, `App\Livewire\Contract\ContractCreate` (identity/org → terms →
  pay), backed by the `App\Livewire\Forms\ContractForm` Livewire form object.
- `ContractForm::fillFromModel()` already hydrates the form from an existing contract; it is
  used by `ContractEdit`.
- On submit, `ContractCreate` validates all three sections, calls `ContractForm::create()`,
  then `LeaveBalanceForm::store()` to generate leave balances.
- `LeaveBalanceService::calculateExtraLeave()` locates the contract that immediately precedes
  the new one by `end_date` and rolls its unused annual leave forward. A renewal inherits this
  for free — no renewal-specific leave code is needed.
- `ContractCreate::notifyIfFirstContract()` only broadcasts when the employee has exactly one
  contract, so a renewal never triggers the "new employee" notification.
- The contracts table actions column (`ContractsTable::columns()`) renders view / edit / delete.
- `ContractForm::validateTerms()` enforces `end_date <= start_date + 1 year`.

## Constraint: position capacity

`Position::exist_emp` is defined as *the number of contracts on that position with
`is_active = true`* — the definition `positions:fix-counts` enforces, and the one
`deactivate:contract` maintains (it flips expired contracts to `is_active = false` and
decrements the counter).

`ContractObserver::creating()` rejects any create when `exist_emp >= max_emp`. Because a
renewal is issued *before* the source contract expires, the source still counts, so **every
renewal on a full position — `max_emp = 1`, the normal case — would fail** without explicit
handling.

Known pre-existing gap, out of scope here: `deactivate:contract` is not registered in
`routes/console.php` or `bootstrap/app.php`, so it only runs when invoked by hand.

## Decisions

| Question | Decision |
| --- | --- |
| New dates | The modal presents two editable date fields, prefilled with `start = source end_date + 1 day` and `end = start + source duration`, clamped to `start + 1 year`. |
| Button placement | Contracts table actions column, and the contract details page header. |
| Button visibility | Contracts that are `is_active` and not terminated, regardless of end date. |
| Source contract on renewal | Untouched. It expires naturally on its own end date. |
| Capacity | Bypassed only when the renewal targets the *same position* as its source. |

## Architecture

### 1. `App\Livewire\Contract\ContractRenewModal`

One standalone Livewire component, mounted once in `contracts-module.blade.php` and once in
`contract-details.blade.php`. Both Renew buttons dispatch `open-renew-modal` with a contract
id; a single modal instance serves both entry points.

Renders with the existing `<x-modal.dialog>` component:

- Read-only summary: employee name, position title, contract type, current period.
- Two date inputs (`start_date`, `end_date`), prefilled as described above.
- Buttons: **Renew as is**, **Renew with edit**, **Cancel**.

The contract id arrives from the browser and is therefore untrusted. The component
re-authorizes on open **and** again on save:

- `contracts.create` active permission, and
- `Gate::allows('view', $contract)` — the `ContractPolicy` city scope, and
- `$contract->is_active === true && $contract->terminated_at === null`.

Failing any of these aborts 403 rather than silently no-oping.

### 2. `ContractForm::fillFromRenewal()`

```php
public ?int $renewal_source_id = null;

public function fillFromRenewal(Contract $source, string $start, string $end): void
{
    $this->fillFromModel($source);
    $this->contract = null;              // force create(), not update()
    $this->renewal_source_id = $source->id;
    $this->is_active = true;
    $this->terminated_at = null;         // a renewal is never born terminated
    $this->termination_reason_id = null;
    $this->reason_of_termination = null;
    $this->start_date = $start;
    $this->end_date = $end;
}
```

Setting `contract = null` is what makes the shared `save()` / `create()` path insert instead
of update. Clearing the termination trio prevents a previously-terminated-then-reactivated
source from carrying a stale termination onto its successor.

### 3. Default renewal window

`Contract::renewalWindow(): array` returns `[$start, $end]`:

- `$start = end_date->addDay()`
- `$end = $start + (end_date - start_date)`, i.e. the same duration
- `$end` clamped to `$start->addYear()` so the default can never violate `validateTerms()`

Used by the modal for its prefill and by `ContractCreate` as the fallback when the renew route
is hit without date query parameters.

### 4. Capacity bypass — three touch points

The bypass applies if and only if `renewal_source_id` is set **and** the target `position_id`
equals the source contract's `position_id`. Renewing into a *different* position is ordinary
new headcount and is capacity-checked normally.

1. **`ContractForm::positionCapacityRule()`** — return early in that case.
2. **`ContractObserver::creating()`** — same check. The flag reaches the observer through a
   *declared* typed property `public ?int $renewalSourceId = null;` on the `Contract` model.
   It must be declared, not dynamic: a dynamic property would land in Eloquent's `$attributes`
   and produce an "unknown column" error on insert. `ContractForm::create()` therefore changes
   from `Contract::create($payload)` to `new Contract($payload)` → assign flag → `save()`.
3. **`ContractCreate::updatedFormPositionId()`** — suppress the live inline capacity error the
   same way `ContractEdit::updatedFormPositionId()` already does for an unchanged position.

`ContractObserver::created()` still calls `Position::inc()`. `exist_emp` therefore reads one
above `max_emp` for the overlap window between issuing the renewal and the source expiring,
and returns to the correct value when `deactivate:contract` processes the expired source. This
keeps the `exist_emp == count(is_active contracts)` invariant intact throughout — nothing that
consumes positions breaks, because the org chart and statistics read
`Position::activeContracts()`, which filters on `Contract::scopeActiveNow()`
(`is_active` AND `start_date <= today <= end_date`), and the two contracts never overlap in
that window.

### 5. "Renew as is" save path

Inside `ContractRenewModal::renewAsIs()`:

1. Re-authorize.
2. `fillFromRenewal($source, $start, $end)`.
3. `validateIdentity()`, `validateTerms()`, `validatePay()` — the same three validators the
   create wizard runs, so a renewal can never write data a manual create would have rejected.
4. `ContractForm::create()`.
5. `LeaveBalanceForm::store($contract->id)` — identical to `ContractCreate::submit()`, giving
   the renewal its leave balances and the annual-leave carry-over.
6. Success toast, redirect to `contracts.show` for the new contract.

Validation failures render back into the modal rather than closing it.

### 6. "Renew with edit"

```php
Route::get('/{contract}/renew', ContractCreate::class)
    ->middleware('active_permission:contracts.create')
    ->name('renew');
```

Registered inside the existing `contracts` prefix group in
`routes/employees-management.php`, *before* the catch-all `/{contract}` show route. The extra
path segment means there is no routing ambiguity either way, but ordering it first keeps the
file's specific-before-general convention.

`ContractCreate::mount(?Contract $contract = null)`:

- No argument → current behaviour, unchanged (`form->setDefaults()`).
- With a source contract → authorize (`view` policy, active + not terminated, else 403), read
  `?start=` and `?end=` from the query string and **re-validate them server-side**, falling
  back to `Contract::renewalWindow()` when absent or invalid; call `fillFromRenewal()`; call
  `initLocationContext()`; set `maxStep = 3` so all three tabs render enabled.

The wizard then behaves exactly like create — three steps, every field editable, saved as a
new contract by the existing `submit()`. Only the page heading changes, to name the contract
being renewed.

Query parameters are used rather than session flash because they survive a refresh and make
the renewal window bookmarkable. The values are two dates — no personal data enters the URL.

### 7. Supporting refactors

Both are required by the above, not opportunistic:

- **`initLocationContext()` moves from `ContractEdit` into the
  `ResolvesContractLocationOptions` trait.** It is currently a private method on
  `ContractEdit`; `ContractCreate` now needs byte-identical logic. The trait already owns the
  city/program/center/position option graph, so this is where it belongs.
- **`ContractForm::updatedContractTypeId()` gains an optional anchor date.** Today, changing
  the contract type overwrites `start_date` with the start of next month and recomputes
  `end_date`. In renew mode that silently discards the renewal dates and can back-date the new
  contract before its predecessor ends. With an anchor, renew mode recomputes the duration
  from the renewal start instead. Create mode passes no anchor and is unchanged.

## Authorization summary

| Surface | Guard |
| --- | --- |
| Renew button (table + details) | `contracts.create` permission, `Gate::allows('view', $contract)`, active, not terminated |
| `ContractRenewModal::open()` | all of the above, server-side |
| `ContractRenewModal::renewAsIs()` | all of the above, re-checked at save |
| `GET /contracts/{contract}/renew` | `active_permission:contracts.create` middleware + all of the above in `mount()` |

## Testing

Feature tests, PHPUnit (`tests/Feature/ContractRenewalTest.php`), following the existing
`tests/Concerns/CreatesHrFixtures` conventions:

1. Renew-as-is creates a new contract whose every field matches the source except
   `start_date`, `end_date` and `id`.
2. Renew-as-is succeeds on a full position (`max_emp = 1`, `exist_emp = 1`).
3. A **non-renewal** create on a full position still fails — the bypass does not leak.
4. Renew-as-is generates leave balances for the new contract.
5. Renew-as-is leaves the source contract completely unmodified.
6. Renew-with-edit prefills every field from the source and saves as a new contract.
7. Switching to a *different* full position during renew-with-edit is rejected.
8. A user whose active city differs from the contract's city gets 403 on both paths.
9. A terminated contract cannot be renewed by either path.
10. `end_date > start_date + 1 year` is rejected.
11. `Contract::renewalWindow()` returns day-after-end plus the source duration, clamped at one
    year.

## Out of scope

- **No `renewed_from_contract_id` column.** Nothing in the feature needs the link persisted:
  leave carry-over already locates the predecessor by date, and the capacity flag is
  transient. Add it later if an audit trail is wanted.
- **Not scheduling `deactivate:contract`.** A real pre-existing gap, but unrelated to this
  feature and better handled on its own.

## Translations

New `general.php` keys in both `resources/lang/en` and `resources/lang/ar`: `renew`,
`renew-contract`, `renew-as-is`, `renew-with-edit`, `renewing-contract-for`, `current-period`,
`new-period`.
