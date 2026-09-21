# Contract Composer restructure — schedule, pay tiers & draft contracts

**Date:** 2026-09-15
**Scope:** `contracts`/`contract_types` schema, `ContractObserver`, `LeaveBalanceService`, the
contract composer (`ContractCreate`/`ContractEdit` + `DrivesContractComposer`), `EmployeeCreate`,
and the printed contract templates (screen + PDF).

---

## 1. Why

The current contract model was built for one shape: one contract type row (which bundled
`type`/`place` until the 2026-09-14 migration moved them onto `contracts`), one fixed work
schedule (`work_hours_from/to` + free-text `work_days`), one salary at one unit
(monthly/hourly/session). An audit of 16 real signed contract PDFs found this does not cover the
organization's actual variety:

1. Contracts with a work day **split into two periods**.
2. Contracts with **fully variable** hours ("varies by work requirements" / "coordinated with the
   direct manager").
3. Contracts with a **conditional, tiered** salary (e.g. $200 if 60h/month, $300 if 70h/month),
   with an unbounded number of tiers.
4. No way to create a **draft contract** (position + center only) from the employee-create screen
   that later upgrades into the same row as a full formal contract, without creating a second row.
5. `contract_types` has 6 rows that are redundant now that `type`/`place` live on `contracts` —
   they should collapse to the 3 real categories (volunteer / trial volunteer / specific voluntary
   service).
6. `LeaveBalanceService` grants annual leave to `service`-category contracts even though every
   real service contract examined has no leave clause at all — a bug to fix alongside the rest.

## 2. Decisions the user confirmed

| Decision | Outcome |
| --- | --- |
| Draft contract | A **4th value of the existing `type` column** (`Draft`), not a separate category |
| Flexible pay tiers | **Unbounded** rows via an "Add condition" repeater, not a fixed max |
| Draft exemptions | While `type = Draft`, the contract is fully exempt from `Position::canAssign()` capacity checks **and** from leave-balance generation |

## 3. Phased scope

Each phase ships and is verifiable on its own; a plan document exists per phase.

### Phase 0 — Database & core models
See `docs/superpowers/plans/2026-09-15-contract-composer-phase-0.md`.

- Consolidate `contract_types` from 6 rows to 3 (volunteer / trial volunteer / specific voluntary
  service), remapping every contract's `contract_type_id` and updating `config/contracts.php`'s
  `map_by_id` and `config/samples/sampleData.php`'s `ContractType` seed to match.
- Add `App\Enums\ContractType::Draft` (`= 4`).
- Add schedule columns (`schedule_mode`, `work_day_from/to`, `show_days_per_week`,
  `show_hours_per_week`, `flex_*`) and pay columns (`pay_mode`, `session_duration`) to `contracts`,
  plus a new `contract_pay_tiers` table (`contract_id`, `condition_en/ar`, `amount`, `sort_order`).
- Add `App\Enums\SessionDuration` (Hour / HourAndHalf / TwoHours).
- `Contract` model: fillable/casts for every new column + `payTiers(): HasMany`.
- New `App\Models\ContractPayTier` model (bilingual `condition` via `HasTranslation`).

### Phase 1 — Draft contract lifecycle — DONE

Commits `25a86cd6`, `0a9479b5`, `4da51037`. Verified via `composer test` (330 passed, the same 2
pre-existing unrelated failures as Phase 0) and a real browser walkthrough (login, fill personal
info, save, see the draft prompt, reserve a position, confirm the resulting `Contract` row and
that `exist_emp` was untouched) — not just PHPUnit.

- Employee-create screen (`EmployeeCreate.php` + new `DraftContractForm`): after saving personal
  info, the Profile tab shows an optional "Assign a Position" prompt instead of redirecting
  immediately. Reserving a position creates a `Contract` with `type = Draft`, the volunteer
  (`category === 'annual'`) `contract_type_id`, no schedule/pay, `is_active = true`. Position
  choices and their server-side validation both go through `Position::scopeInCity()`.
  Caught in browser testing and fixed: `saveInformation()` is also reachable from the Contact
  tab's own Save button (same Form object), but the prompt only lives in the Profile tab's view —
  the active tab is now forced back to `information` after any save.
- `ContractObserver::creating()`/`created()`/`updating()`/`updated()`: a draft skips the capacity
  check entirely and does not increment `exist_emp` on creation; the write where `type` changes
  *from* `Draft` to a real value applies the capacity check and the increment at that point,
  treated as the contract's true first assignment.
- `contracts.start_date`/`end_date`/`weekly_hours`/`currency` made nullable (new migration) — a
  draft has none of these yet and they were previously `NOT NULL` with no default.
- `LeaveBalanceService`: replaced the `contract_type_id`-based trial check with a category-based
  one (`$contract->contractType->category`), including in `calculateExtraLeave()`'s carry-over
  lookup. `type === Draft` and `category === 'service'` are now both fully exempt (not even
  annual leave, fixing a real pre-existing gap for service contracts); `category === 'trial'`
  keeps the existing behavior.

**Found via browser testing, not fixed (pre-existing, out of scope):** `PersonalInfoForm`'s
`nationality` field has no `required` marker in the UI and defaults to `null`, but is validated
with a bare `integer` rule (no `nullable`) — so saving an employee without explicitly picking a
nationality fails validation with "The nationality must be an integer," even though nothing in
the UI indicates it's mandatory. Worth a follow-up fix (either mark it required in the UI, or add
`nullable` to the rule) — unrelated to contracts, so left untouched here.

**Follow-up refinements from user review, commits `bc07c9e3` and `f63b92e1`:**
- `x-forms.select` themed to the brand primary color on focus instead of Tailwind's default
  indigo (it was the one form component left un-themed; `x-forms.input`/`select-input` already
  used primary).
- The single flat position dropdown was replaced with a cascading City → Program → Position
  picker using `x-forms.select2-input`, matching the rest of the app's dropdown style. City-scoped
  HR stays locked to the employee's own city; a nationwide role (`User::activeCityId() === null`
  — HR-SYR, super-admin, general-manager) gets an open city dropdown that includes Syria itself,
  and can reserve a position in a city other than the employee's own. `DraftContractForm`
  re-validates the chosen city against the acting user's own scope server-side either way.
  Caught and fixed in browser testing: `select2-input`'s `wire:ignore` wrapper means Livewire
  never updates its `<option>` list on an ordinary re-render, only the selected value (via Alpine
  `@entangle`) — the Program/Position wrapper divs now carry a `wire:key` tied to the value their
  options depend on, forcing a full remount (fresh options included) whenever that value changes.

### Phase 2 — Composer step 1 (contract type + assignment) — DONE

Commit `4c724b43`. Verified via `composer test` (352 passed, the same 2 pre-existing unrelated
failures) and a real browser walkthrough covering all three pieces, including a full submission
checked against the database directly (not just the redirect).

- 3 contract-type cards needed no code change — a direct consequence of the Phase 0 consolidation,
  since `contractTypeCards()` already reads whatever rows exist in `contract_types`.
- The `type` (full/part/freelance) radio is now conditional on the chosen card's category:
  Full/Part for volunteer and trial, Freelance only for service. `chooseType()` auto-corrects
  `type` when the category changes; `ContractForm::typeMatchesCategoryRule()` is the matching
  server-side check.
- Selecting an employee whose most-recently-created contract is `type = Draft` (the employee-create
  screen's position-reservation step) auto-fills position/program/center from it via a new
  renderless `ContractCreate::draftContractFor()` lookup, and `submit()` re-verifies the claimed
  draft id from scratch before calling `ContractForm::update()` on that row instead of `create()`
  — never `setContract()` + the shared `save()` path, since `submit()` already validates once and
  `save()` would re-validate redundantly (the comment in the original code already called this out).
  The Phase 1 `ContractObserver` upgrade-from-draft logic claims the position seat at exactly this
  point.
- Study Zone program (`slug === 'SZ'`) hides the Center field entirely and lands on "city-level"
  directly, relying on `Position::scopeInCity()`'s existing centerless-position handling.

**Found via browser testing, not a code bug:** `public/build/` was a stale Vite build (~5 days
old) that predated every JS change in this phase, so the composer's Alpine component failed to
initialize at all — `npm run build` fixed it locally. `public/build/` is gitignored, so a real
deploy must run its own build step to pick up `contract-composer.js`.

### Phase 3 — Schedule step — DONE

Commit `0984b38e`. Verified via `php artisan test` (360 passed, the same 2 pre-existing unrelated
failures) and a real browser walkthrough of both modes end to end, including a full submission
checked against the database directly, plus an Arabic/RTL pass.

Fixed (time range + **optional second time range for a split shift, e.g. 9:00-13:00 and
17:00-21:00** + day range + two computed, non-editable "N days/week" / "M hours/week" display
toggles) vs. flexible (5 independent checkboxes + one bilingual custom-text box) modes for
`resources/views/livewire/contract/composer/_schedule.blade.php`. The second shift's columns
(`work_hours_2_from`/`work_hours_2_to`, nullable — a contract has one only when
`work_hours_2_from` is set) were added to `contracts` in a follow-up migration after Phase 0
shipped; see `docs/superpowers/plans/2026-09-15-contract-composer-phase-0.md`.

- `weekly_hours` is computed client-side only — fixed mode from the day range × daily hours
  (both shifts combined), flexible mode straight from the "hours/week" box — and the server
  (`DrivesContractComposer::applyComposerPayload()`) trusts whatever `weeklyHours` the client
  sends, same as every other composer field. A contract created before this phase has no day
  range to compute from, so the JS seeds `initialWeeklyHours` from the contract's own stored
  figure once and falls back to it until someone actually edits the schedule — this stops an
  untouched renewal/edit screen from silently zeroing out historically-valid data.
- `work_day_from`/`work_day_to` are `nullable` + `required_with` each other server-side (not
  `required`) for the same reason: a pre-Phase-3 row has neither, and renewing it must not be
  blocked by fields that did not exist when it was created.

**Found via browser testing, not caught by PHPUnit:** the "custom description" checkbox in
flexible mode (`flexCustomOn`) was a derived getter/setter — `get` returned whether the two text
boxes already held text, `set` only handled the *uncheck* case (clearing them) and did nothing on
check. Checking the box therefore always sprang back to unchecked, permanently hiding the text
fields it was supposed to reveal: a genuine dead end for any flexible-schedule contract that
needed a custom description. PHPUnit's `->call('submit', $payload)` tests never touch the
checkbox at all (Livewire's `submit()` receives `flexCustomEn`/`flexCustomAr` directly), so this
only ever surfaced by actually clicking it in a browser. Fixed by giving `flexCustomOn` a real,
independent boolean rather than deriving it from the text fields — same shape as the plain
`showDaysPerWeek`/`showHoursPerWeek` toggles — with a `$watch` in `initialize()` that clears both
text fields on uncheck.

**Also found via browser testing:** the schedule summary box's "N h/week" text was a hardcoded
English literal (`x-text="weeklyHours + ' h/week'"`) shown even on the Arabic screen. Replaced
with a `general.composer.weekly-hours-value` translation (`:count h/week` / `:count
ساعة/أسبوعيًا`) formatted through the same `fill()` templating every other computed label already
uses.

### Phase 4 — Pay step — DONE

Commit `f384a310`. Verified via `php artisan test` (365 passed, the same 2 pre-existing unrelated
failures) and a real browser walkthrough of both modes end to end — fixed, fixed paid per session,
flexible with two pay tiers — each checked against the database directly, plus an Arabic/RTL pass.

Fixed (amount + currency + `SalaryPer` + `SessionDuration` when `Session`) vs. flexible
(`contract_pay_tiers` repeater) modes for `_pay.blade.php`, mirroring the schedule step's
`PayMode` fixed/flexible toggle exactly. Currency is shared by both modes — one flat figure or a
list of tiered ones, always in the same currency.

- `ContractForm::syncPayTiers()` replaces a contract's `contract_pay_tiers` rows wholesale on
  every save path (`save()`, `create()`, `update()`) — delete-then-recreate rather than diffing,
  since the repeater has no stable per-row identity worth preserving and the row count is always
  small.
- `salary`/`session_duration` are nulled and `salary_per` reset to `SalaryPer::Unknown` when
  `pay_mode` is Flexible, same null-the-inactive-mode's-fields pattern as the schedule step's
  fixed/flex fields — so a contract switched from flexible back to fixed does not keep stale
  tiers, and vice versa.
- The pre-Phase-4 print templates were not touched: they still read `$contract->salary` directly
  and degrade gracefully (DomPDF's own `$esc()` "—" fallback, no crash) for a flexible-pay
  contract. Rendering the tiered clauses is Phase 5's job, same as the schedule step's fixed/flex
  clauses.

### Phase 5 — Printed contract templates — DONE

Commit `3640d4ed`. Verified via `php artisan test` (376 passed, the same 2
pre-existing unrelated failures) plus real output: the on-screen contract
view for fixed, flexible, and tiered-pay contracts across all three
categories, and actual generated PDFs (`Barryvdh\DomPDF`) inspected via
`pdftotext` to confirm every figure survives the Arabic-shaping pass.

Data-driven `<x-contract.schedule-clause>` / `<x-contract.pay-clause>`
Blade components, shared between the on-screen contract view and the PDF
export — which turned out to be *two* independently hand-maintained
directory trees per category (`details/*/pages/page2.blade.php` for the
screen, `export/*/pages/page2.blade.php` for the PDF; the latter also
contained ~9 dead commented-out earlier drafts per file, left untouched),
six files total now reduced to thin wrappers around the same two
components instead of hand-diverged copies of the same prose.

The replaced wording was completely stale relative to the Phase 3/4
restructure: every category always printed a flat "$weeklyHours working
hours per week, $weeklyHours/5 hours per day" regardless of
`schedule_mode`, ignored the day range/second shift/flexible columns
entirely, and always said "monthly stipend" regardless of `salary_per` —
two of the three categories also hardcoded the currency to USD regardless
of what the contract actually stored.

- `<x-contract.schedule-clause>` branches on `schedule_mode`. Fixed states
  only whichever of hours/week, hours/day, days/week the `show_*` toggles
  have on, then an office-hours sentence with the day range (if
  `show_day_range`) and second shift (if `work_hours_2_from` is set) — the
  same structure `contract-composer.js`'s live preview already builds
  client-side (Phase 3's follow-up work), now the server-side source of
  truth for what actually prints. Flexible joins whichever `flex_*`
  fragments are set.
- `<x-contract.pay-clause>` branches on `pay_mode`. Fixed varies the
  wording by `salary_per` (monthly/hourly/per-session, with the session's
  own duration when relevant) instead of always saying "monthly", and
  reads the contract's own `currency` instead of hardcoding USD. Flexible
  lists every `pay_tiers` row. The SYP-equivalent-in-cash clause now only
  appears for a foreign-denominated contract (USD/EUR), not one already in
  SYP.

**Found only by generating an actual PDF, not by the on-screen render or
PHPUnit:** Arabic digit conversion (`ar_digits()`, PDF-only — the browser
never needs it) was first applied to *both* dual-language columns. On
screen this is invisible (the English column's `$digits()` call is a
no-op there), but in the actual DomPDF output every English-column number
printed as a blank glyph — the font/direction context that column renders
in has no glyphs for Arabic-Indic digits. Digit conversion is now
Arabic-column-only. Caught by rendering a real PDF via `Pdf::loadView(...)
->output()` in `tinker` and reading it back with `pdftotext`, which is
also how a second, smaller issue was caught: the flexible-pay tier
bullet's en dash (`–`) doesn't appear to be in the custom PDF font either
(rendered as a substitution glyph) — replaced with a plain hyphen.

Both components assemble each Arabic phrase as one raw string before a
single `glyphs()`/`smartGlyphs()` call — shaping "و" ("and") separately
from what follows it (e.g. splitting "ومن" into "و" + "من") prints it
disconnected, since ArPHP's shaper needs the full word in one call to pick
the correct joined letterform.

## 4. Verification (whole project)

- `php artisan migrate` on a dev copy first, then `php artisan migrate:rollback` to confirm `down()`
  works.
- Manual walk-through: add employee → create draft → create a real contract for the same employee
  → confirm auto-fill and in-place upgrade.
- One real contract per category × per schedule/pay mode combination, checked on screen and in the
  exported PDF.
- `composer test` stays green; update any test that assumed 6 `contract_types` rows or the old
  `LeaveBalanceService` trial check.
