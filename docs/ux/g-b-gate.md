# G-B: Core task completion and data recovery gate

Task G-B in [ux-prompts-v3.md](ux-prompts-v3.md) (R-02, R-03, R-04, R-05, R-13, R-16, R-17, R-20).
Integrated SHA: **`ccb3575b`** (`origin/main`: B1, B2, B3, B4, B5, B6, M1 on top of G-A). The working tree differed only by `docs/ux/approval-source-contract.md` (C3-D, documentation). Date: 2026-10-23.
Prerequisites: B1, B2, B3, B4, B5, B6, M1, all on `main`. No product code, schema or data was changed by G-B.

**Gate result: PASS WITH CONDITIONS.** All four core tasks completed with the expected permissions in English and Arabic, on desktop and phone. No input was lost, no request, contract or account was duplicated, and no accounting or permission boundary was broken. The conditions are the open items in §5; none is waived silently.

**Independence:** this gate was run by the developer who implemented B2 and B5. The QA review (§7) should treat those areas with that in mind.

## 1. Full suite (AC-G-B-03)

`vendor/bin/phpunit` on PHP 8.5.11, SQLite `:memory:`, at the SHA above.

| Run | Tests | Assertions | Failures | Errors |
|---|---|---|---|---|
| G-A (`8141fdc7`) | 759 | 4304 | 0 | 0 |
| **G-B (`ccb3575b`)** | **930** | **5125** | **0** | **0** |

## 2. Environment

- **Data:** a fresh synthetic SQLite copy built from the SHA (X1 builder + X2 session states), `C:/laragon/tmp/hc-hr-gb`, with a clean snapshot; launch entry "hc-hr G-B (synthetic SQLite copy)", port 8400.
- **Accounts:** synthetic only (`*@example.test`). No `.env` change; the operational database was not touched.
- **Browser:** the built-in Chromium pane.
  - Desktop runs at the pane's size, 1238×742.
  - Phone runs emulated at 375×667. At 375×812 the emulated page is scaled to fit the pane and clicks miss, so 667 was used.
- **Two independent sessions** were obtained with two host names (`127.0.0.1` and `localhost`), which keep separate cookies.
- **Input:** real clicks and key presses, with three exceptions:
  - date fields that already had a value were set directly, because key presses went into the wrong segment;
  - sign-in was set up by script; it is not part of any task;
  - the role switch was invoked through the switcher's own action (§4).
- **This is a moderator walkthrough, not participant sessions:** no times, SEQ or SUS are reported. The X2 tasks' outcome codes and end-state checks are used as the pass criteria.

## 3. The four X2 tasks (AC-G-B-01)

End states were checked in the session database against [x2/task-scripts.md](x2/task-scripts.md) v1.1.

| Task | Run | Persona | Outcome | End-state evidence |
|---|---|---|---|---|
| A · Request leave | EN, desktop | staff ALP | **S** | Request 70: annual, day, 31 Oct, reason, pending. **Double-clicking the confirmation's Save made one request.** |
| A · Request leave | AR, phone | staff ALP | **S** | Request 71 (3 Nov). Saving without a reason was refused («حقل السبب مطلوب.») and the type and dates were kept. |
| B · Find an employee | EN, desktop | HR ALP | **S** | "Mira Najjar" → one row, ICDL Trainer, `synth.040@example.test` (= manifest); profile shows the same email. |
| B · Find an employee | AR, phone | HR HMS | **S** | «كريم عيسى» → one card, «مدبرة مركز 95», `synth.194@example.test` (= manifest); search chip and Clear all; no page overflow. |
| C · Review a request | AR, phone | manager ALP | **S** | Request 64 rejected by the manager (employee 34), reason stored, balance `used` unchanged. Rejecting without a reason was refused. |
| C · Review a request | EN, desktop | manager HMS | **S** | Request 67 rejected by the manager (195) with the exact reason; the other pending requests untouched; done by keyboard (§4). |
| D · Create a contract | EN, desktop | HR ALP | **S** | Contract 317: position 2, Volunteer Contract, full time, on site, 6 Nov – 31 Dec, Sun–Thu 09:00–17:00, fixed per month; one seat; one account created (B5). |
| D · Create a contract | AR, desktop | HR HMS | **S** | Contract 318, same checks on position 53; one seat; one account. |
| D′ · Reservation upgrade | AR, phone | HR ALP | **S** | The composer found reservation 315 and filled the position (banner shown); **315 was upgraded in place** (same id, now full time, from 8 Nov); seats 2/2; total contracts 318 (316 + 2 new); one account. |

**Coverage:** every task in both languages and on both device classes. Not every language × device × task combination was run (9 runs including D′, of 16 combinations). Not run: A AR desktop, A EN phone, B AR desktop, B EN phone, C AR desktop, C EN phone, D EN phone.

**Permissions observed:**
- HR saw the B5 Incomplete / Awaiting contract views.
- The manager's pending list held only their reports' requests.
- The HMS HR role (two cities) chose the city through the employee.
- The employee role (after the switch) lost *All Leaves*.
- The leave dialogs of a staff persona's own requests needed no other permission.
- Pay visibility and the staff persona's full menu were **not** re-checked here; they rely on the suite (`SalaryVisibilityTest`, `DirectoryAccessPolicyTest`, A2 tests). A *Termination Reasons* link on the staff page is by design (G-B-13).

## 4. Recovery and integrity checks (AC-G-B-02)

| Check | How | Result |
|---|---|---|
| Duplicate submit, leave | Double-click on the confirmation's Save (Task A EN) | **Pass**: 1 request |
| Duplicate submit + **server failure**, contract | Composer complete → **server stopped** → Create → server restarted → **double-click** Create (Task D EN) | **Pass on data**: every entry kept while the server was down; after restart, 1 contract, 1 seat, 1 account, no other contract changed. **Defect G-B-2** (no feedback while down). |
| Reservation not duplicated | D′ | **Pass**: upgraded in place |
| **Two sessions, same request** | HR (session 2) opened the decision for request 64; the manager (session 1) rejected it; HR then chose Accept and saved | **Pass on data**: still rejected by the manager, balance unchanged. **Defect G-B-1** (misleading message). |
| **Role switch on a stale page** | Synthetic manager given a second role (`employee`) in the session copy; tab A kept a decision open for request 65; tab B switched the active role; tab A then saved Accept | **Pass**: refused («محظور»), request 65 still pending with no approver; tab B's menu lost *All Leaves*. Role restored afterwards. |
| Validation keeps input | Leave without reason (AR phone); rejection without reason (AR phone) | **Pass** on data; **Defect G-B-3** (phone: the message is off-screen) |
| Table state | Employees: search → profile → Back | **Pass**: the search is in the URL (`?globalSearch=…`) and the box, and the result is kept |
| Unsaved changes, leave dialog | Cancel/Escape on an untouched dialog | **Pass**: closed without a question (B2/A5-M) |
| Keyboard | Task A fields (select2, dates, reason) by keys; confirmation by Tab/Enter; Task C by Tab/Enter | **Pass with defect G-B-7** (Enter in the decision's reason field does not submit) |
| Phone layout | All phone runs | **Pass**: no page-level horizontal scroll (`scrollWidth = innerWidth`); M1 sticky action bars present. Leave tables scroll sideways *inside* their container (G-B-4). |

Data integrity summary: no duplicate request, contract, account or decision; balances changed only by accepted leave (none in this run); no write from a stale session or a lost role.

## 5. Open items and disposition (AC-G-B-03)

| # | Item | Severity | Disposition | Owner |
|---|---|---|---|---|
| G-B-1 | A stale leave decision (another person already decided) shows the full-page "Forbidden — you don't have permission" error instead of "already rejected by …". Data is safe. | Medium | **Fixed** (§9). Opening or saving a decision on a request that is no longer pending shows its details and "already rejected by … on …", the `already_handled` outcome of [approval-source-contract.md](approval-source-contract.md) §4.1. C3's inbox still maps its own refusals the same way. | Rafi |
| G-B-2 | When the server cannot be reached, *Create contract* (and Livewire actions generally) give **no feedback**: no toast, no error, the button returns to normal. Entries are kept and a retry is safe (B2 token). | Medium | **Fixed** (§9): one error toast with *Try again*, which repeats the failed actions ([feedback-contract.md](feedback-contract.md) §2.3). | Rafi |
| G-B-3 | Phone: after a failed save in a long dialog, the inline error can be below the visible area; focus is not moved and the summary stays hidden (it counts an off-screen message as "shown"). | Medium | **Fixed** (§9): after a failed save the first error is scrolled into view and focused; an inline message counts as "shown" only when it is inside the visible area. | Rafi |
| G-B-4 | Leave tables (All leaves, My leaves, Balances) are sideways tables on phones; the status column is off-screen. Already listed as not done in B4 and M1. | Medium | **Carried**: convert to cards (B4-D §6) in a follow-up before G-C. | B4/M1 owners |
| G-B-5 | Tapping a decided leave row opens the **editor**, not a read-only view (B4D-Q2 was applied to employees and contracts only). | Low | Carried with G-B-4. | B4 owner |
| G-B-6 | The leave **edit** dialog opened by the manager showed the *Employee* field empty (red border). Seen once, on request of Nour Halabi (2 Oct). Not investigated further here. | Low, to confirm | **Reproduce and decide** before C3 reuses the dialog. | Leave owner |
| G-B-7 | Keyboard: pressing Enter in the decision dialog's reason field does nothing (no `<form>`); Tab to *Save* works. | Low | Fix in C3 (decision UI) or A5 follow-up. | Frontend |
| G-B-8 | Composer: choosing the contract type after entering the start date resets the start date to the type's default. | Low | Composer follow-up: keep a user-entered date. | Contracts owner |
| G-B-9 | HR (city) can still decide leave requests; DEC-05 revision says only the direct manager decides (A3 §4, C3-D G1). | Policy gap, known | **C3** implements DEC-05 rev. | C3 |
| G-B-10 | Auto-renew is on by default and the composer does not ask (X2 note). | Info | HR question for D4. | HR product owner |
| G-B-11 | Not covered here: screen reader, real phone and on-screen keyboard, 200 % zoom; 7 of 16 task × language × device combinations; participant timings. | Limitation | Carried to X2-R / G-C; G-A item 2 still open. | QA lead |
| G-B-12 | Test environment only: `/en/...` redirects to https on the `php -S` copy; viewport emulation taller than the pane misplaces clicks; one-step typing does not fill date inputs. | Info | No product action. | — |

| G-B-13 | A *Termination Reasons* link (`/termination-reasons`) appeared in a staff persona's page. **Checked: by design.** The route requires `project-basics.view`, and `config/access-control.php` grants that read permission to the `employee` and `manager` baselines. Writes on those reference tables are a separate matter (G-A item 4). | Info | None for G-B. HR may want to confirm that staff should see reference data. | HR product owner |

**Release-blocking items:** none. No item loses data, duplicates a write, or exposes data across roles or cities.

## 6. Limits of this gate

- Synthetic data, one browser engine, emulated phone, moderator (not participant) runs.
- Run by the B2/B5 implementer.
- The B5 onboarding screens were not re-walked here; they rely on B5's own browser evidence ([b5-implementation.md](b5-implementation.md) §4) and the suite.

## 7. Acceptance criteria

| AC | Status | Evidence |
|---|---|---|
| **AC-G-B-01** The four tasks complete in EN/AR on desktop and phone with expected permissions. | **Met, with coverage limits** | §3: 9 successful runs; each task in both languages and both device classes; 7 combinations not run (§3, G-B-11) |
| **AC-G-B-02** Recovery tests preserve input and accounting; requests/accounts are not duplicated. | **Met** | §4: double-submit (leave, contract), server failure + retry, two sessions, role switch, validation; database checks after each |
| **AC-G-B-03** The milestone result cites one integrated SHA and measurable evidence. | **Met** | `ccb3575b`; §1 suite 930/930; §3–§4 record ids and database states |

## 8. Handoff

C0 and C3-D may proceed. C3 takes G-B-7, G-B-9 and must not reuse the leave edit dialog before G-B-6 is checked. G-B-1, G-B-2 and G-B-3 are fixed (§9). G-B-4/5 should be closed before G-C. **Formal review (QA engineer + technical lead): pending.**

## 9. Follow-up: G-B-1 and G-B-2 fixed

Date: 2026-10-23. On `hr-ux-by-rafi` after `8872f852`. Checked on a separate synthetic copy (`C:/laragon/tmp/hc-hr-gb12`, launch entry "hc-hr G-B-1/2 fixes", port 8401), so the gate's own database above is unchanged.

**G-B-1: already decided.**
- **Change:** `LeaveRequestForm::submitDecision()` checks before validation, and again under the row lock, whether the request is still pending. If it is not, and the actor may still see it, it raises `LeaveDecisionAlreadyMade`, so nothing is written and no notification is sent. `AllLeaveRequestModule` then closes the decision, opens the request's details and shows an info toast from `LeaveRequestNextStep::alreadyDecided()`. `openDecision()` does the same for a row or link that is out of date. Anyone who may no longer see the request still gets the policy's 403, which says nothing about it.
- **The same rule covers a repeat of your own decision** (double click, or *Try again* after a lost response). It says "Your decision to accept this request was already saved on …" instead of Forbidden.
- **Browser, AR, two sessions:**
  1. HR ALP (`localhost`) opened the decision for request 66.
  2. The manager ALP (`127.0.0.1`) rejected it with a reason.
  3. HR then chose Accept and saved.
  4. HR saw the details dialog (status «مرفوض», the manager's reason) and the toast «سبق أن رُفض هذا الطلب من قِبل جورج خوري بتاريخ 23-10-2026, 16:49. لم يُحفظ قرارك.»
  5. The database still showed rejected by employee 34, balance `used` 0, and no new notification.
- **Tests** (`LeaveRequestWorkflowTest`): someone else decided first; a stale decision is reported before validation; opening an already-decided decision shows its details; out-of-scope access is still forbidden with no toast; a repeat of your own decision. The last one replaces the old test that expected 403.

**G-B-2: lost connection.**
- **Change:** `feedback.js` listens for Livewire action failures (fetch threw) instead of the request hook. A failure the user caused shows one error toast with *Try again* («إعادة المحاولة»), which sends the same actions again. The toast store gained a browser-only `action` button next to *Undo*. Details: [feedback-contract.md](feedback-contract.md) §2.3.
- **Browser, AR, HR ALP, request 65.** The connection was cut inside the page (Livewire's `fetch` rejected, as when the network drops), because stopping the server would also have ended the browser session.
  1. Accept, then Save twice while offline: **one** toast with *Try again*. The choice was kept. Both `submitDecision` calls and a background `updateCount` poll failed; the poll was not reported.
  2. Back online, *Try again*: exactly **one** `submitDecision` was sent, followed by the success toast and the dialog closing.
  3. Database: request 65 accepted once by employee 1, balance 62 `used` 0 → 24 h (3 days × 8 h), one new notification.
- **Phone 375 px:** the toast is 343 px wide, with no sideways page scroll.
- **Not covered:** *Try again* is not covered by an automated test (the suite has no JS runner). A real network drop on a phone has not been tried.

**G-B-3: fixed (2026-10-26).**
- **Change** (`resources/js/feedback.js`):
  - The error summary counts an inline message as already shown only if it is **inside the visible area**: the window and every scrolling container around it, such as a dialog's body on a phone (`isInView`). This is re-checked after a save, a click or a key press, not on scroll, so the list does not jump while the user scrolls.
  - After a failed save the user caused, the first error's field is scrolled to the middle and focused; if the field is on another tab or in a closed section, the summary is focused instead. This was started in `8872f852`.
  - The scroll is now **instant**: the smooth scroll did not move the dialog's scrolling container at all, so the field stayed below the screen.
- **Browser** (copy `hc-hr-gb12`, staff ALP, Arabic, leave request, reason left empty and scrolled out of view, Submit pressed with Enter):

  | Size | Reason field before | After the failed save |
  |---|---|---|
  | Phone 375×667, first failure | top 682 px (off screen) | focus on the reason, top 516 px (on screen), error visible |
  | Phone 375×667, second failure | top 682 px | same; one focus move |
  | Desktop 1200×700 | top 823 px (off screen) | focus on the reason, error visible, summary hidden (nothing duplicated) |

  An earlier phone run, before the instant scroll, showed the summary correctly listing the off-screen error but left the field below the screen.
- **Tests:** none added; the suite has no JS runner. Full suite: 934 tests, 1 failure in `ContractsTableB4Test::test_no_amount_in_the_rendered_html_or_payload_under_any_column_choice`, which passes 3 of 3 on its own. It is a flaky check (it looks for the digits `8731` in HTML that also holds random component ids and checksums), not related to this change; to be raised with the B4 owner.
