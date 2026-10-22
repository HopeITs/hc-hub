# A5-M: Protect modal input and close without refreshing the page

Task A5-M (R-03, R-18, R-19) in [ux-prompts-v3.md](ux-prompts-v3.md). Prerequisite B0: `WithFeedback::confirm()` and the shared confirm dialog.
Status: **My Leaves (new and edit) implemented, awaiting review. A5-M is NOT done**: the other editable consumers are not migrated yet (§3) and no browser or screen-reader evidence exists (§5).

## 1. Shared contract (what was built)

| Piece | Where | Behaviour |
|---|---|---|
| `close-action` prop | `components/modal.blade.php` (passes through `x-modal.dialog`) | Name of a Livewire method that decides whether the dialog may close. Set → the **backdrop is ignored** and Escape, the header close button and Cancel all call `close()`, which calls that method. Unset → every route still closes locally (read-only dialogs unchanged). |
| `close()` | modal Alpine scope | The one close route. Cancel buttons use `x-on:click="close()"`, never `show = false` or a link. |
| `confirm(... cancelLabel:)` | `WithFeedback`, `feedback.js`, `confirm-dialog` | Lets the dismiss button read "Keep editing". Initial focus is already on that button; Escape or a backdrop click on the confirmation answers "no" and returns to the form with all values. |
| `LeaveRequestForm::markClean()` / `isDirty()` | `app/Livewire/Forms` | `baseline` (`#[Locked]`, in memory, per form) is set when the form opens or loads. Dirty = normalized current entries ≠ baseline, so changed-then-restored is clean and `09:00:00` equals `09:00`. Nothing is written to browser storage. |
| `requestClose()` / `discardChanges()` | `MyLeaveRequestModule` | Clean → close and reset. Dirty → one confirmation: **Discard your changes? / Your changes have not been saved. / Keep editing / Discard changes** (AR: تجاهل التغييرات؟ / لم يتم حفظ تغييراتك. / متابعة التعديل / تجاهل التغييرات). Only `discardChanges` clears the form. Neither writes an HR record or navigates. |
| Labels | `leaves.modal.*` EN/AR | New: **Request leave / طلب إجازة** (entry button and title), **Submit request / إرسال الطلب**. Existing: **Edit leave request / تعديل طلب الإجازة**, **Save changes / حفظ التغييرات**. The approval flow is untouched. |

Cancel already closed locally (earlier work); this change makes it go through the guard. Page, filters, scroll and the opener's focus are untouched, since nothing navigates and the existing `show` watcher still returns focus.

## 2. Behaviour on save

- Save still goes through `confirmSave` then `processSave`, with the per-form submission token (a retried or timed-out save returns the request it already created). `finishSave` closes and resets the form.
- A close attempt during an in-flight save is queued by Livewire behind it. If the save succeeds the dialog is already closed and `requestClose()` does nothing; if it fails the entries and errors are kept and the guard then applies.
- The leave form has no file input, so no attachment state exists to restore.

## 3. Consumer inventory

| Consumer | Kind | State |
|---|---|---|
| `employee/leave/index` (new/edit request) | editable | **Done** |
| `employee/leave/index` (details) | read-only | Unchanged, normal dismissal |
| `employee/leave/form`, `employee/leave/all`, `employee/leave/balance` | editable | **Not migrated** |
| `access-management/index` | editable | **Not migrated** |
| `basics/*-module` (department, city, contract-type, leave-type, job-level, termination-reason, course-category, annual-leave-rule, checklist-item) | editable | **Not migrated** |
| `program-management/{centers,positions,default-deliverables}-module` | editable | **Not migrated** |
| `badges/badges-module` | mixed | **Not classified** |
| `contract/contract-renew-modal` | editable | **Not migrated** |

Each remaining form needs its own `markClean()/isDirty()/requestClose()/discardChanges()` and `close-action`. The mechanism is generic; the per-form baseline is the work. Until then those dialogs keep today's behaviour, including backdrop dismissal.

## 4. Tests

`LeaveSelectorStateTest` (new tests): pristine close without a prompt and no write; dirty close keeps dialog and entries and raises exactly the Keep/Discard confirmation; changed-then-restored is clean; discard clears and a reopened request starts clean; edit guarded and reopens with saved values with the record unchanged; the dialog is wired to the guard and the backdrop no longer closes; EN/AR labels for new and edit.

**These tests were written but not run.** The session had PHP 8.3 and the project needs 8.5; only `php -l` was run. Run `php artisan test --filter=LeaveSelectorStateTest` and `composer test` on PHP 8.5 first.

## 5. Open before A5-M can close

- AC-A5-M-01/02/05/07: real browser checks on synthetic data (backdrop click, Cancel with no document reload or `leaves.index` request, focus return, filters/page/scroll kept), EN/LTR and AR/RTL, desktop and phone with keyboard open, plus a screen-reader pass.
- AC-A5-M-06: the ambiguous-timeout case relies on the existing submission token; confirm it in the browser.
- Migrate or explicitly waive the consumers in §3. Coordinate the dirty-state API with B2 so navigation and modal guards do not both prompt.
