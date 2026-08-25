# Contract Create — "The Composer" redesign

**Date:** 2026-08-25
**Scope:** `/{locale}/contracts/create` only. `ContractEdit` and the contract Details/print
views are explicitly out of scope and must remain functionally untouched.

---

## 1. Why

`ContractCreate` is a three-step wizard whose steps carry 6 / 11 / 3 fields. The audit
that preceded this spec found five defects that are structural, not cosmetic:

1. **The cascade lies about what is required.** `Programs` and `Centers` render without a
   required marker, but `contract-location-picker.js::availablePositions()` returns an empty
   array unless a program is selected, and returns only positions whose `center_id` is null
   unless a center is selected. `Positions` carries the required star and can silently stay
   unfillable forever. The "Note: Choose a City First" banner exists to apologise for this.
2. **The layout contradicts the data.** The dependency graph is a tree
   (`city → employee`, `city → program → center → position`) rendered as a two-column zigzag:
   City, Programs, Employees, Centers, Positions, Contract Type.
3. **Contract type is decided last but governs everything.** `ContractForm::updatedContractTypeId()`
   silently rewrites `start_date` and `end_date` (types 3 and 4 are trial → 3 months; all others
   → end of the current year). The user never sees this happen.
4. **Create asks about termination.** `terminated_at`, `termination_reason_id` and
   `reason_of_termination` sit on a form for a contract that does not yet exist.
5. **No confirmation anywhere.** `submit()` persists immediately. There is no review, no
   position-capacity visibility before selection, and no echo of the salary that was typed.

## 2. Goals

- One page, no steps, no navigation cost.
- The dependency tree is visible in the layout.
- Every constraint (capacity, date ceiling, prerequisites) is stated *before* it is violated.
- The finished form is its own review.
- Visual language unchanged: white cards on `--surface-base`, brand red `#c2151c` reserved for
  the active state, the required marker, and the single primary action.

## 3. Non-goals

- No database migrations.
- No changes to `ContractEdit`, its view, `Partials/*.blade.php`, or `contract-location-picker.js`.
- No "Create & add another" and no carry-over between contracts (explicitly rejected).
- No live print-document preview.
- No change to who may create a contract or to any authorization rule.

## 4. Approved decisions

| Decision | Outcome |
| --- | --- |
| Flow shape | Single-page composer, four sections |
| Reach | Create screen only; Edit untouched |
| Termination fields | **Removed** from create; remain fully available in Edit |
| Dropdowns | New create-only Alpine combobox; Edit keeps Select2 |
| Duplicate active contract | **Warn, do not block** |
| Save & add another | **Not built** |
| Schema | Unchanged |

---

## 5. Screen architecture

```
breadcrumbs (existing livewire:layout.breadcrumbs — unchanged)
page title: "New contract" + subtitle "<City> · sections unlock as you go"

+--------------------------------+------------------+
| 1  Assignment                  |  Contract Card   |
| 2  Contract                    |  (sticky)        |
| 3  Schedule                    |                  |
| 4  Pay                         |                  |
| -- action bar --               |                  |
+--------------------------------+------------------+
```

- Left column `flex-1`, right rail `w-[272px]`, `sticky top-6`.
- Below `lg`, the rail collapses to a sticky bottom bar showing employee · position · capacity
  plus the primary button; tapping it expands the full card as a sheet.
- Action bar is a card at the foot of the left column: progress text on the start side,
  `Cancel` (secondary, → `contracts.index`) and `Create contract` (primary red) on the end side.

### Section states

| State | Appearance | Behaviour |
| --- | --- | --- |
| **Locked** | `#fafafb` card, grey numeral, muted title, prerequisite sentence on the end side | Not focusable, not clickable |
| **Open** | White card, `border-primary`, red numeral, fields visible | Fields editable |
| **Done** | White card collapsed to one row: red-tinted check, title, summary values, `Edit` chip | Click anywhere reopens |

Transition rules:

- A section unlocks the instant its prerequisites are satisfied. There is no per-section
  confirm button.
- A section collapses to **Done** when it is valid **and** the user opens or focuses a different
  section. It never collapses under the cursor.
- Opening a section collapses the previously open one (accordion), so the page stays short.
- An invalid section shows its error count in the header and refuses to collapse.

Prerequisites:

| Section | Unlocks when |
| --- | --- |
| 1 Assignment | Always |
| 2 Contract | Assignment valid (employee + position chosen) |
| 3 Schedule | Contract valid (type + both dates) |
| 4 Pay | Schedule valid |

Locked sections state their prerequisite in words, e.g. *"finish Assignment to set the salary"*.
This replaces the `note-text1` / `note-text2` banner, which is deleted.

---

## 6. Section 1 — Assignment

City sits alone at full width because it is the root of both branches. Beneath it, two labelled
lanes read top-to-bottom in dependency order:

```
City   [ Aleppo - locked to your role ]
---------------------------------------------------
WHO                    |  WHERE
Employee *  [ v ]      |  Program  *  [ v ]
                       |  Center      [ v ]
                       |  Position *  [ v ]
                       |  [####.] 4 of 5 filled - 1 seat left
```

**City.** `getCities()` already applies `->when(activeCityId(), whereKey(...))`, so any
`HR-ALP`-style role receives exactly one row. When `$cities->count() === 1`, render a locked
chip with the city name instead of a control and preselect it. Only a role with no active city
(super-admin) gets a real picker.

**Employee.** Combobox with avatar (`getFirstMediaUrl('avatar')`, falling back to
`public/images/default-avatar.png`) and localized full name. When the chosen employee already
has an active contract, show a red-tinted note under the field naming the end date of that
contract and stating that creating this one will not close it. **Non-blocking.**

**Program.** Gains the required marker, because it functionally is required.

**Center.** The empty value currently means "only positions with no center", which is invisible.
Once a program is chosen the placeholder is replaced by an explicit first option
**"City-level (no center)"** carrying value `''`, so both modes are visible choices. Semantics
of `availablePositions()` are unchanged.

**Position.** Every option renders `"<title> — <exist_emp> of <max_emp>"`. Options where
`exist_emp >= max_emp` render greyed, labelled *Full*, and are not selectable. After selection a
segmented capacity meter and a seats-remaining line sit under the field, turning red at the last
seat. This converts `positionCapacityRule()` from a post-hoc error into pre-selection information;
the server rule stays exactly as it is.

---

## 7. Section 2 — Contract

**Contract type** becomes a responsive 3-column card grid, one card per `ContractType` row
(six rows today, so two rows of three; the grid must not assume that count). Each card shows:

- the localized name;
- two micro-badges from the columns already stored but never displayed — `type`
  (`FullTime` / `PartTime` / `Freelance`) and `place` (`Onsite` / `Online`);
- the date consequence in brand red: `→ 3 months` for ids 3 and 4, `→ runs to 31 Dec` otherwise.

Selecting a card calls the existing `ContractForm::updatedContractTypeId()`. The date fields
below visibly highlight for ~600 ms so the rewrite is observed rather than discovered.

**Start date / End date** sit side by side with a live duration pill between them
(`formatDuration` helper). The one-year ceiling enforced by `validateTerms()`
(`before_or_equal:start+1y`) is printed as a hint under the end date, not surfaced as an error.

---

## 8. Section 3 — Schedule

**Work days.** Seven toggles, Sunday first, each showing the English short name and the Arabic
name. Selected days fill brand red. On change the component serializes the selection into the
**existing** `work_days_en` and `work_days_ar` string columns using each locale's day names
joined by that locale's separator: `", "` for English, `"، "` for Arabic. Edit continues to read
them as free text; no migration, no format negotiation. An `enter manually` disclosure reveals
the two raw text inputs; once the user edits either one by hand the strip stops overwriting them
for the rest of the session, and the typed text is what gets saved.

**Start time / End time** reuse `x-forms.time-picker` as today.

**Hours per week** is computed as `selected_days × (end_time − start_time)`, rounded to the
nearest whole hour, displayed with a `5 days × 8 h` derivation chip and an `override` link that
converts it back into a plain editable number. Once overridden it stops recomputing until the
user clears the override.

**Attending notes (EN / AR)** move behind an `+ Add attending notes` disclosure — optional,
rarely used, and currently responsible for two full-width textareas of noise.

**Removed:** `terminated_at`, `termination_reason_id`, `reason_of_termination`. They are
`nullable` in `validateTerms()` and default to `null` in `ContractForm`, so `buildPayload()`
writes nulls and nothing downstream changes. `TerminationReason` is no longer queried in
`ContractCreate::render()`.

---

## 9. Section 4 — Pay

**Salary + currency** stay one joined control (`0 Unknown / 1 SYP / 2 USD / 3 EUR`). Below it,
a live echo formats the amount in the active locale — using `ar_digits()` under Arabic — and
annualises it when `salary_per` is Month. This is the highest-cost typo on the screen and
currently has no confirmation.

**Paid per** keeps the three-card radio group for `SalaryPer` (1 Month / 2 Hour / 3 Session)
but replaces the emoji with line icons from the existing `x-icons.*` set. The emoji stay
in the old partial, which Edit continues to use.

---

## 10. Contract Card (right rail)

Starts as a ghost outline with every label present and em-dashes for values, then fills in as
sections complete:

| Row | Source |
| --- | --- |
| Avatar · name · city · program | `Employee` + selection |
| Position | selected position title |
| Center | selected center, or "City-level" |
| Capacity | `exist_emp / max_emp`, red at full |
| Type | selected `ContractType` name |
| Period | start → end plus duration |
| Hours | computed weekly hours |
| Salary | formatted amount + period |
| **Annual leave** | `LeaveBalanceService::previewAnnual()` |

The leave row surfaces something that is currently completely invisible: `submit()` calls
`balanceForm->store($contract->id)` after saving and the user is never told what entitlement was
created. If `previewAnnual()` cannot be fed cleanly by an unsaved `Contract`, this single row is
omitted and nothing else changes.

---

## 11. Draft persistence

The selection graph and form values are written to `sessionStorage` under a key namespaced by
the authenticated user id, debounced at 400 ms. On mount, if a draft exists, a dismissible bar
offers *"Restore your unsaved contract?"*. The draft is cleared on successful save and on
explicit dismissal. `sessionStorage` rather than `localStorage` so it dies with the tab and
leaves nothing behind on a shared HR machine.

## 12. Validation model

- **Client:** per-field on blur, for speed of feedback only. Section headers show an error count.
- **Server:** unchanged authority. `submit()` continues to call `validateIdentity()`,
  `validateTerms()`, `validatePay()` and `validateCityConsistency()` before `form->create()`.
- **Work days become required on create only.** Both `form.work_days_en` and `form.work_days_ar`
  are required, since the day strip always writes the pair together. This rule is declared in
  `ContractCreate`, *not* in `ContractForm::validateTerms()`, because that method is shared with
  Edit and existing contracts have null work days. Adding it to the shared method would break
  editing historical contracts.

## 13. Backend changes

All additive. No migration.

1. `ResolvesContractLocationOptions::locationOptions()`
   - positions payload gains `maxEmp` and `existEmp`;
   - employees payload gains `avatar` (URL string) and `activeContractEndsAt` (the `end_date` of
     the employee's current active contract as a date string, or `null` when there is none — its
     presence is itself the duplicate-contract signal).
   Existing keys keep their exact shape, so `contract-location-picker.js` — still used by Edit —
   ignores the additions harmlessly. `Position::scopeInCity()` remains the only city filter.
2. `LeaveBalanceService::previewAnnual(Contract $unsaved): ?float` — a read-only sibling of
   `create()` reusing the same private calculators. Entitlement logic stays in this class only.
3. `ContractCreate` — rewritten. `step`, `maxStep`, `jumpTo()`, `validatorsByStep`, `next()`,
   `prev()` and `advanceIdentity()` are removed. `render()` no longer loads `TerminationReason`.
   `submit()`, `notifyIfFirstContract()` and the `NewEmployeeWelcome` broadcast are unchanged.

## 14. File inventory

**New**
- `resources/views/livewire/contract/create/_assignment.blade.php`
- `resources/views/livewire/contract/create/_contract.blade.php`
- `resources/views/livewire/contract/create/_schedule.blade.php`
- `resources/views/livewire/contract/create/_pay.blade.php`
- `resources/views/livewire/contract/create/_contract-card.blade.php`
- `resources/views/livewire/contract/create/_action-bar.blade.php`
- `resources/views/components/forms/combobox.blade.php`
- `resources/js/contract-composer.js`

**Rewritten**
- `app/Livewire/Contract/ContractCreate.php`
- `resources/views/livewire/contract/contract-create.blade.php`

**Modified, additively**
- `app/Livewire/Contract/Concerns/ResolvesContractLocationOptions.php`
- `app/Services/LeaveBalanceService.php`
- `lang/en/general.php`, `lang/ar/general.php`
- `resources/js/app.js` (register the new Alpine component)

**Untouched**
- `resources/views/livewire/contract/Partials/{identity,terms,pay}-fields.blade.php`
- `resources/js/contract-location-picker.js`
- `app/Livewire/Contract/ContractEdit.php` and its view
- `app/Livewire/Forms/ContractForm.php`

## 15. RTL, i18n, accessibility

- Logical properties throughout (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start`/`end`); no `ml`/`mr`.
- Every string goes through `lang/{en,ar}/general.php`. No literal English in Blade.
- Day strip, capacity meter and duration pill all mirror under `dir="rtl"`.
- Combobox implements `role="combobox"` with `aria-expanded`, `aria-controls`,
  `aria-activedescendant`, a `role="listbox"` popup, `aria-disabled` on full positions, and
  full keyboard support: type to filter, Up/Down to move, Enter to select, Esc to close, Tab to
  commit and leave.
- Section headers are `<button>` elements with `aria-expanded`; locked ones are `disabled` with
  the prerequisite as their accessible name.
- Every state is distinguishable without colour: check glyph for Done, lock glyph for Locked,
  the word *Full* on exhausted positions.

## 16. Testing

Feature tests (`php artisan test`, sqlite in-memory):

1. Creating a contract through the composer persists the same payload the wizard produced.
2. A user with an `HR-*` role cannot create a contract in another city
   (`validateCityConsistency()` still rejects).
3. A position at `exist_emp === max_emp` is rejected server-side even if the client submits it.
4. A position whose program or center does not match the selection is rejected.
5. Work days are required on create and still optional on edit.
6. Creating a first contract still queues `NewEmployeeWelcome` to the employee's city.
7. `LeaveBalanceService::create()` still runs and produces the same balances as before.

`./vendor/bin/pint` must pass on every touched PHP file.

## 17. Acceptance

The redesign is done when: the four sections lock, unlock and collapse as specified; a
city-scoped user never sees a city dropdown; a full position cannot be picked from the list; the
contract type card states its own date consequence before it is clicked; weekly hours computes
itself; the Contract Card fills in live; Edit behaves exactly as it does today; and all tests
above pass.

---

## 18. Built — deviations from this spec

Recorded during implementation. Everything else was built as written above.

1. **Avatars use `Employee::$avatar_url`, not `getFirstMediaUrl('avatar')`.** The model
   documents that every view must use the accessor, because `getFirstMediaUrl()` returns a
   directly fetchable public path that bypasses `EmployeePolicy::view`. The accessor routes
   through `EmployeeAvatarController::internal` instead. Section 6 said the wrong thing.

2. **Center became an explicit filter with an "All centers" default.** The spec kept the old
   semantics (blank centre = centre-less positions only) and merely relabelled the blank
   option. That still hides most positions behind an unmarked control. The centre selector now
   offers *All centers* (default, every position in the program), *City-level (no center)* when
   such positions exist, and each real centre. Position rows carry their centre name so the
   unfiltered list stays readable. Server compatibility is unchanged: `center_id` is only a
   filter — contracts never store it, and `validateCityConsistency()` only checks a centre when
   one was actually chosen.

3. **The leave preview is a third renderless action, not a local calculation.** Computing it in
   JavaScript would have required shipping `AnnualLeaveRule` per employee and duplicating the
   entitlement maths. `leavePreview()` is debounced at 500 ms, fires only once employee, dates
   and weekly hours are all present, and is city-scoped so it cannot leak figures for employees
   outside the caller's scope. Network cost per contract is therefore up to three requests, not
   two.

4. **Two extra Blade components.** `components/contract/section.blade.php` holds the shared
   card chrome (locked / open / done states) rather than repeating it four times, and
   `components/contract/continue.blade.php` renders a quiet "Continue" link that appears once a
   section is valid. The link is a navigation shortcut, not a confirm button — the section is
   already valid and already held in state when it appears, and clicking any other section
   header does the same thing.

5. **`config/access-control.php` was updated.** `LivewireMutationCoverageTest` requires every
   public Livewire action to be classified. `leavePreview` is mapped to `contracts.create`;
   `save` and `advanceIdentity` were removed from the create map, and the create screen's
   exempt list was emptied, because the wizard methods behind those entries no longer exist.

6. **The `locationOptions` query budget moved from 12 to 14.** Two additional constant-time
   queries: one eager-load of the avatar media collection, one lookup of active contracts for
   the duplicate warning. Neither is an N+1. The assertion in
   `ContractCreatePerformanceTest` carries this reasoning inline.
