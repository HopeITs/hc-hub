# D3 (part B) · In-app feedback to the IT team

Task: D3 in [ux-prompts-v3.md](ux-prompts-v3.md) (R-14), steps 3–4 and AC-D3-03 · Date: 2026-10-27 · Branch `main` (uncommitted)
Decision: [DEC-08](decisions.md): read by the development (IT) team only · text only, no screenshots · deleted after one year.
Part A (contextual help, tours) is a separate delivery and is not covered here.
Status: **built and tested; review pending. One new table (migration file only; not run on the operational MySQL).**

## 1. What people see

- **User menu → «إرسال ملاحظة إلى فريق تقنية المعلومات» / Send feedback to the IT team.** This opens a dialog over the current page. Nothing navigates or reloads, so unsaved work on the page stays where it was.
- Before anything is sent, the dialog shows **what goes with the note**:
  - the page (its route name, e.g. `leaves.index`);
  - the language;
  - the active role;
  - the sender (your account, so IT can follow up).

  It also states the rules: only the typed text is sent, never page or form contents, files or screenshots. Personal data and pay figures must not be included. Notes are kept for one year and read only by the IT team.
- Optional type (Problem / Idea / Question), and a text of 3 to 2000 characters with a counter. There is no file input.
- **Send** closes the dialog and shows a success toast (`WithFeedback`). A validation error, the rate limit, or a role switched in another tab keeps the dialog open with the text intact.
- **Cancel, Escape and ×** follow A5-M. A clean dialog closes at once. A dirty one asks «Discard your changes?» with Keep editing / Discard changes. The backdrop is ignored.
- **Settings → «ملاحظات المستخدمين» / User feedback** (`/{locale}/feedback`) appears only for a reader. It lists notes newest first, 25 per page, with a type filter. Each note shows its date, sender name (or «Removed account»), text and the three context fields. It is read-only, and notes leave only when they expire.

## 2. Decisions and assumptions

| # | Decision | Why |
|---|---|---|
| D-1 | **Who counts as "the IT team".** There is no IT role. `User::canReadFeedback()` is true when **the active role is `super-admin`**, the developers' system account (`config('feedback.reader_roles')`). It is also true for an account holding a **direct** `feedback.view` permission. | DEC-08 says IT only. super-admin is already the developers' role (see `canViewSalary()`). **Assumption, needs product sign-off.** |
| D-2 | **A role's `feedback.view` never counts.** It is checked by name, not through `hasActivePermission()`. The permission is deliberately left out of `access-control.groups`. | `hasActivePermission()` says yes to every `administrator_roles` entry, **HR-SYR included**. Also, saving an administrator role in Access management syncs *every* permission in the table to it. Through either path HR-SYR would have read IT-only data. Outside the catalog, `Role::created` never auto-grants it to HR-* roles. The access-management screen does not list it either, so a grant is a deliberate act (tinker / SQL on a named account). |
| D-3 | **Author deleted → `user_id` set null** (`nullOnDelete`). The note stays until its one-year expiry and shows «Removed account». | The text is product feedback, not the user's record. Keeping it anonymous loses nothing IT needs, and DEC-08's one-year limit still applies. |
| D-4 | **Page capture.** The layout passes `request()->route()?->getName()` to the component on the server at render. It is stored in a `#[Locked]` property and re-checked by `InAppFeedbackRecorder::safeRouteName()`: it must match `[A-Za-z0-9._-]`, be at most 150 characters, satisfy `Route::has()`, and not be a Livewire endpoint. Anything else is stored as null. | A route name says which screen without the URL. A URL can carry record ids and search terms (`?search=…`). `wire:navigate` replaces the body, so the component remounts on each page and the name stays current. |
| D-5 | **Language and role are derived at submit**, on the server. Language comes from `app()->getLocale()` of the request, limited to the supported locales. Role comes from `User::myRole()`, stored trimmed as the Spatie name, e.g. `HR-ALP`, at most 64 characters. Nothing role-like is read from the browser. | The update route is locale-prefixed, so the language is the page's own. The role is the one acting now, not another assigned role. |
| D-6 | **Stale role.** The disclosure records the role it showed (`#[Locked] disclosedRole`). If the active role has changed by submit time, nothing is stored. The new role is shown with «Your active role changed…», and the text is kept. | The user must have seen the context that is stored. |
| D-7 | **Rate limit:** 5 sends per user per hour (`RateLimiter`, key per user id, `config/feedback.php`). Over the limit: field error + error toast; the text is kept. | It bounds abuse and accidental repeats. Other users are unaffected. |
| D-8 | **Text only.** The component has no `WithFileUploads`, so Livewire has no signed upload URL for it and `_startUpload` is refused. The table has no file column. Nothing goes to any disk, public or private. | AC-D3-03 and DEC-08. An image validator is not a document sanitiser. |
| D-9 | **Minimal record.** The columns are exactly `id, user_id, category, message, route_name, locale, role_name, created_at`. There is no IP, user agent, URL/query, record or city id, form contents or `updated_at`. The model is fully guarded, and only the recorder sets fields. | D3 §7 Data integrity. |
| D-10 | **Retention:** `InAppFeedback` is `MassPrunable` for `created_at` older than `config('feedback.retention_days')` (365). `routes/console.php` schedules `model:prune --model=App\Models\InAppFeedback` daily at 03:20. | DEC-08. This requires the scheduler (`schedule:run` cron), which already runs the other daily jobs. |

**The old Google Forms link has been replaced (2026-10-27).** The header icon «إرسال مشكلة أو ملاحظة / Send problem or feedback» now opens the in-app dialog (`data-feedback-open`). The duplicate phone-menu entry was removed because the account menu already has the dialog item. No feedback path leaves the server any more. `PhoneShellTest` asserts this.

## 3. Files

| Area | Files |
|---|---|
| Schema | `database/migrations/2026_10_27_120000_create_in_app_feedback_table.php` (table + `feedback.view` permission row) |
| Model / write | `app/Models/InAppFeedback.php`, `app/Services/InAppFeedbackRecorder.php`, `config/feedback.php` |
| Access | `User::canReadFeedback()`, Gate `feedback.read` (`AppServiceProvider`). Route `feedback.index` uses `can:feedback.read` (`routes/web.php`). `config/access-control.php` `livewire_exempt` entries are commented: form = every signed-in role, self-scoped write; list = in-method Gate. Settings menu item in `app/Support/Navigation.php`. |
| UI | `app/Livewire/Feedback/FeedbackForm.php` + `resources/views/livewire/feedback/feedback-form.blade.php` (mounted in `components/layouts/app.blade.php`); `FeedbackIndex.php` + `feedback-index.blade.php`. The menu item is in `resources/views/livewire/layout/header.blade.php`. |
| Text | `resources/lang/{en,ar}/feedback.php` (Arabic plurals for counts and minutes) |
| Retention | `routes/console.php` |
| Tests | `tests/Feature/InAppFeedbackTest.php` (17). `NavigationAreasTest` expects `feedback.index` in super-admin's menu, and HR-SYR's list is unchanged. |

## 4. AC-D3-03 evidence: "stores only disclosed approved context; unsupported attachment types are not accepted"

| Check | Test (`InAppFeedbackTest`) |
|---|---|
| Exact column list; stored values = text, type, `dashboard`, `ar`, `HR-ALP` derived on the server | `test_it_stores_only_the_disclosed_fields_with_server_derived_context` |
| Disclosure shows page, language, role and the four rules; no file input | `test_the_disclosure_lists_page_language_role_and_the_rules_before_sending` |
| Browser cannot change `pageRoute` / `disclosedRole` (locked) | `test_the_client_cannot_change_the_page_or_disclosed_role` |
| Extra fields (`role_name`, `user_id`, `salary`) from the browser are not stored | `test_an_extra_field_from_the_browser_is_not_stored` |
| Active role, not another assigned role (inactive super-admin) | `test_the_stored_role_is_the_active_one_not_another_assigned_role` |
| URL / query string / unknown name → route stored as null | `test_a_page_route_that_is_not_a_route_name_is_dropped` |
| Empty, 2001 characters and unknown type are rejected | `test_validation_rejects_empty_too_long_and_unknown_category` |
| 6th send in an hour is refused; text kept; other user unaffected | `test_submissions_are_rate_limited_per_user` |
| Role switched after opening → nothing stored until re-confirmed | `test_a_role_switch_after_opening_is_shown_before_anything_is_sent` |
| Guest `open` / `submit` → 403 | `test_guests_cannot_open_or_submit` |
| No `WithFileUploads`; `_startUpload` refused | `test_no_attachment_can_be_sent` |
| A5-M: clean close; dirty → confirm; keep; discard clears; no redirect | `test_dirty_close_asks_and_keeps_text_until_discard` |
| HR-SYR (even with a role grant of `feedback.view`), HR-ALP, general-manager, manager and employee → 403 on the page and the component | `test_hr_staff_and_managers_cannot_read_feedback` |
| Reader switched to a non-reader role → next Livewire update 403 | `test_a_reader_whose_role_is_switched_is_refused_on_the_next_update` |
| super-admin and a direct-grant account read newest first; filter works | `test_super_admin_and_a_directly_granted_account_read_newest_first` |
| Older than 365 days pruned, newer kept; `model:prune` scheduled | `test_feedback_older_than_one_year_is_pruned_and_the_prune_is_scheduled` |
| Author deleted → note kept, `user_id` null | `test_deleting_the_author_keeps_the_note_without_the_link` |

**Checks:**
- **Full suite: 1034 tests, 6117 assertions, 0 failures.** This is the shared checkout with part A in progress; the C4 baseline was 1004 tests with 0 failures.
- `InAppFeedbackTest` (17) and `NavigationAreasTest` were re-run after the last view edit: 25 passed.
- Pint was run on the changed PHP files; `npm run build` passed.

### Browser (synthetic SQLite copy `C:/laragon/tmp/hc-hr-d3`, launch entry «hc-hr D3 feedback», port 8403)

The copy was cloned from the G-C synthetic database. Only this migration was run on it, and `feedback.view` was granted directly to the synthetic `hr.alp@example.test` to show the reader path. The operational MySQL (`.env`) was **not** migrated or touched.

| Screenshot (`docs/ux/evidence/d3/`) | Shows |
|---|---|
| `feedback-en-1440-disclosure.jpg` | EN 1440×900: dialog over the dashboard; disclosure `dashboard · English · HR · ALP · your account` + rules |
| `feedback-en-1440-dirty-cancel.jpg` | Text typed → Cancel → «Discard your changes?» (Keep editing / Discard changes). Keep editing kept the text. |
| `feedback-en-1440-sent-toast.jpg` | Sent: success toast, still on the dashboard. Stored row: `route_name=dashboard, locale=en, role_name=HR-ALP`, nothing else. |
| `feedback-ar-375-user-menu.jpg` | AR 375×812: RTL user menu with the new entry. Taken before the Google Forms entry was removed (see §2). |
| `feedback-ar-375-disclosure.jpg` | AR phone sheet: `leaves.index · العربية · الموارد البشرية · ALP`, rules, Send/Cancel pinned |
| `feedback-ar-375-dirty-escape.jpg` | Escape on a dirty dialog → «تجاهل التغييرات؟» |
| `feedback-ar-375-sent-toast.jpg` | Sent: toast, still on My leaves |
| `feedback-en-1440-it-list.jpg`, `feedback-ar-375-it-list.jpg` | Reader list, newest first, with mixed-script notes |

**Found by the browser check:** a note in the other script rendered with its punctuation on the wrong end. Fixed with `dir="auto"` on the note text and on the textarea. The list screenshots are taken after the fix.

**Not checked in the browser:** EN 375×812, AR 1440×900, 200% zoom, a screen reader, and a 403 for a non-reader in the browser (covered by tests). No super-admin persona exists in the synthetic data.

## 5. Limits and follow-ups

- **IT mapping (D-1/D-2) is an assumption.** If the product owner wants a named IT account rather than super-admin, remove `super-admin` from `feedback.reader_roles` and grant `feedback.view` directly to that account.
- **No in-app grant UI** for `feedback.view`, on purpose (D-2). Grant it with tinker: `User::find($id)->givePermissionTo('feedback.view')`.
- **Free text can still contain personal data** that the user typed despite the warning. There is no automatic scrubbing. Readers are IT only, and rows expire after a year.
- No reply/close workflow and no manual delete, as the task allows.

## 6. Migration and recovery

- **Deploy:** `php artisan migrate` creates `in_app_feedback` and the `feedback.view` permission row. Make sure the scheduler runs so `model:prune` enforces retention.
- **Rollback:** `php artisan migrate:rollback --step=1` (or drop the table and delete the permission row). The data is disposable by policy, so there is nothing to recover. Remove the layout mount and menu item, or the dialog will fail on a missing table.
- No existing table or record is changed.
