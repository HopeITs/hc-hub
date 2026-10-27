# G-C: Navigation, inbox and role dashboards gate

Task G-C in [ux-prompts-v3.md](ux-prompts-v3.md) (R-06, R-07, R-08, R-09, R-10, R-12). Version 1.0, 2026-10-27.
Integrated SHA: **`aa983edc`** (`main`: C1, C2, C3, C5, C4 on top of G-B). No product code, schema or data was changed by G-C; the only repository changes are this file, `evidence/g-c/` and one `.claude/launch.json` entry.
Prerequisites: C1–C5 notes exist and every test they name exists ([evidence/g-c/source-audit.md](evidence/g-c/source-audit.md) §1). **None of C1–C5 (nor C3-D, C5-D, G-B) has its formal review recorded**; G-C does not stand in for those reviews.

**Gate result: NEEDS CORRECTION.** Navigation, search, counts across surfaces and privacy held in every run. Two decision-authority defects (G-C-1, G-C-2) must be fixed or explicitly decided before Phase C is accepted; neither writes bad data on its own, but G-C-1 leaves real requests with no possible decider and G-C-2 allows self-approval.

## 1. Evidence

| Source | What | Where |
|---|---|---|
| Full suite | `vendor/bin/phpunit`, PHP 8.5, SQLite `:memory:`, at `aa983edc`: **1004 tests, 5638 assertions, 0 failures, 0 errors** (G-B: 930/930) | run 2026-10-27 |
| Source audit | Prerequisites, every count source, privacy, server checks, bilingual search (read only) | [evidence/g-c/source-audit.md](evidence/g-c/source-audit.md) |
| Browser walkthrough | Moderator runs on a fresh synthetic SQLite copy (`C:/laragon/tmp/hc-hr-gc`, launch entry "hc-hr G-C (synthetic SQLite copy)", port 8402), built-in Chromium, desktop pane size and 375×667 | [evidence/g-c/browser-walkthrough.md](evidence/g-c/browser-walkthrough.md) |

Accounts and data are synthetic (`*@example.test`). The operational database and `.env` were not touched.

**Independence:** the audit and the walkthrough were run by AI agents in the same workspace that implemented C1–C5, not by an independent QA team; the review (§6) should treat them as a first pass.

## 2. Task success and findability against X2 (AC-G-C-01)

**This is a moderator walkthrough, not participant sessions.** The X2 baseline ([usability-baseline.md](usability-baseline.md)) and the C0 user-test table hold **0 participant observations**, so no time, SEQ, SUS or before/after improvement is reported. Outcome codes and end states follow [x2/task-scripts.md](x2/task-scripts.md).

| Task | Run | Persona | Outcome | Entry point found by | End state |
|---|---|---|---|---|---|
| A · Request leave | EN desktop | staff ALP | **S** | dashboard / menu | request 70, pending |
| A · Request leave | AR phone | staff HMS | **S** | dashboard / menu | request 71, pending (see G-C-1) |
| B · Find an employee | EN desktop | HR ALP | **S** | header search (C2) | correct single result |
| B · Find an employee | AR phone | HR HMS | **S** | header search (C2) | correct single result |
| C · Review a request | EN desktop | manager ALP | **S** | "For you now" (C4) → inbox (C3) | request 64 rejected with reason |
| C · Review a request | AR desktop | manager HMS | **S** | "For you now" → inbox | request 67 rejected with reason |
| D · Create a contract | EN desktop | HR ALP | entry only | New → Contract (C2) | composer not completed; **no outcome code** |

C0 scenarios T1–T8: every entry point was reached from navigation, the dashboard or search **without typing a URL, in 0–2 clicks**. T9 not run (no admin persona).

**Not covered:** Task D end state (incl. G-B-10 auto-renew box, reservation upgrade); A EN phone, A AR desktop, B EN phone, B AR desktop, C EN phone, C AR phone; HR-HMS in English; general manager and super-admin personas; Ctrl+K; screen reader, real phone, on-screen keyboard, 200 % zoom; MySQL. These are limitations, not passes, and carry to X2-R.

## 3. Consistency and privacy matrix (AC-G-C-02)

| Check | Result |
|---|---|
| Menu badge = inbox tabs = dashboard "For you now" (all personas walked) | **Consistent.** All read the same `WorkInbox` rows and policies (audit §2); browser numbers matched in every run, including 5 → 8 for HR-ALP once both direct managers were put on leave today (on-behalf state). |
| Calendar "away today" = Upcoming leaves card | **Consistent** (3 → 4 HR-ALP, 1 HR-HMS); both use `TeamCoverage`. |
| Contracts ending / awaiting contract / incomplete records vs. their tables | **Consistent** (audit §2). |
| Notification bell vs. inbox | Different by design (unread notices, not pending work); see G-C-12. |
| Role switch, HR with two cities, super-admin | Recomputed per request; nothing cached across requests. |
| Calendar privacy | No reason, note, balance or pay; colleagues see accepted leave only and "On leave" for sensitive types; disallowed scopes fall back to an allowed view. |
| Inbox, dashboard, search privacy | No reason, pay or national id; search selects only `DirectoryAccess::COLUMNS`; New → Contract also needs `canViewSalary()`; photos via `avatar_url`. |
| Direct URL / Livewire calls to another city's record or a non-report | **Refused** in every probe (403 / "not allowed"). |
| Two sessions, same request (65) | HR decided on behalf first; the manager's later reject was refused with "already accepted by …"; **one write**. |
| Role switch on a stale inbox page (66) | Refused; request stayed pending. |
| Bilingual search | Arabic name in EN UI, English name in AR UI, «ساره» → «سارة» all found. C2 never promised Arabic-Indic digits. |

**No private calendar/profile exposure was observed.** Count contradictions were observed only in the edge cases listed as G-C-1, G-C-5–G-C-8 and G-C-11 below; the main surfaces agree.

## 4. Findings and disposition (AC-G-C-03)

Ids: `F` = source audit, `D` = browser walkthrough.

| # | Finding | Severity | Disposition |
|---|---|---|---|
| G-C-1 (F1, D-01) | **A pending request can have no possible decider.** If the direct-manager position is held by someone who cannot decide (only `employee` role, no account, switched role, HR role of another city) and is not on leave, HR may not stand in (`LeaveDecisionAuthority::basisFor`, lines 44–58). Every inbox shows 0 while All leaves → Pending shows it; the employee is told "waiting for the HR team". Seen in the browser on request 71; the synthetic copy has ≥ 10 such requests across cities. | **High** | **Held for policy decision (DEC-05 / C3-Q1)**, then correction: should HR stand in when the holder cannot decide, or should the manager role be granted? Until then the requests stay visible in All leaves (no data loss). Owner: HR product owner, then C3 owner. |
| G-C-2 (F2) | **HR can decide its own leave** "on behalf" of its own manager on leave, or as "no manager" at the top of the tree; nothing compares requester and decider. | **High** | **Fixed (2026-10-27).** `LeaveDecisionAuthority::basisFor()` returns no basis when the requester is the user, on every basis; the policy, the decision form and the inbox all go through it. Tests `test_gc2_hr_cannot_decide_its_own_request_as_no_manager` and `…_on_behalf_of_its_absent_manager` fail without the change; another HR person in scope still stands in. |
| G-C-3 (F3) | Inbox "Seen by HR" / "Object" skip the `leaves.review` permission that the old table action requires; an objection returns days to the balance. | Medium | **Needs correction**: same permission check as the table action. |
| G-C-4 (D-02, D-04) | After HR decides on behalf (or any older HR decision), HR's review row says "The manager accepted/rejected". The employee's view is correct. | Medium | **Needs correction** (wording from the stored basis). |
| G-C-5 (D-03) | Pending requests that already started are labelled "Starts soon" and counted in "⚠ N start within two days". | Medium | **Needs correction**. |
| G-C-6 (F4) | Checklist items of former employees are dropped from the inbox/home but still counted by the Take back / Hand out counters and the pending-delivers card. | Medium | **Needs correction** or a recorded decision that leavers' returns stay outside the inbox. |
| G-C-7 (F5) | Each inbox source is capped at 100 rows, and for leave the cap is applied before the "may I decide" filter: HR-SYR / super-admin can see "100" or miss a decidable request. | Medium | **Needs correction** (count separately or filter before limiting). |
| G-C-8 (F6) | "Team contracts" still follows the technical-manager line; leaves, calendar and inbox use direct reports. | Medium | **Held for policy decision** (DEC-06 team definition applies to contracts?). |
| G-C-9 (F14) | Statistics "soon-ending contracts" has no city scope and disagrees with the home figure. Predates C1–C5. | Medium | **Needs correction** (city scope). |
| G-C-10 (F7, F8) | Home "off this week" covers today+6 but links to the Sun–Sat week; "who's off" includes pending while the upcoming card is accepted-only. | Low | F8 **accepted** (C5-D Q2); F7 needs correction. |
| G-C-11 (F9, F10, D-05) | "For you now" omits checklist rows for employee/manager while the badge counts them; a former employee's pending leave shows 0 in the inbox but 1 in Team leaves; badge stayed 3 vs 2 after an "already handled" refusal until the next refresh. | Low | Needs correction (F9, F10); D-05 accepted until next poll. |
| G-C-12 (D-06, D-07, D-08) | Manager's own notice says "your manager …"; the bell keeps counting handled requests; Arabic comma in English text. | Low | Needs correction. |
| G-C-13 (F11, F12, F13, F15) | Calendar "my reports" from positions not active role; on-behalf overlap warning may list another city's pending leave (names only); HR role with a non-existent city treated as nationwide; never-employed volunteer labelled "(former)". | Low | F12, F13 need correction (scope); F11, F15 low. |
| G-C-14 (D-09, D-10) | Leave-type list reopens after keyboard selection; Centers page 500 for a manager on SQLite (`09:00` without seconds). | Low | D-09 needs correction; D-10 check on MySQL before acting. |
| G-C-15 | Notes out of date: C5 §5 (upcoming card replaced by C4), C1 (interim inbox count replaced by C3; C1 §6 puts the team calendar in *My team*, the menu has it in *My space*). C3 needs `php artisan migrate` on deploy. | Info | Update notes. |
| G-C-16 | Built assets were older than `resources/` (re-ran `npm run build`); older launch entries point at a removed `php-8.5.11` path. | Info | Environment only. |

**Accepted as delivered:** C1 navigation, C2 bilingual search and quick actions, C5 calendar privacy, the shared count source across badge / inbox / dashboard, and concurrency/stale-role handling.
**Release-blocking for Phase C:** G-C-1 (after its decision). G-C-2 is fixed.

## 5. Acceptance criteria

| AC | Status | Evidence |
|---|---|---|
| **AC-G-C-01** Findability and task success are reported against X2 without invented improvements. | **Met, with coverage limits** | §2: 6 successful task runs, entry found for every task and C0 T1–T8; no participant data, so no improvement claimed; uncovered combinations listed. |
| **AC-G-C-02** No contradictory actionable counts or private calendar/profile exposure in the defined matrix. | **Not met** | §3: privacy held everywhere and the main surfaces agree, but G-C-1 (0 in every inbox vs. pending in All leaves), G-C-5, G-C-7 and G-C-11 are contradictory actionable counts. |
| **AC-G-C-03** The gate states whether changes are accepted, need correction or are held for policy decisions. | **Met** | §4 dispositions. |

## 6. Handoff

- **C3 owner:** G-C-3, G-C-4, G-C-7; G-C-1 after the decision. **C4/C5 owner:** G-C-5, G-C-6, G-C-10, G-C-11, G-C-13. Then re-run the §3 matrix on the corrected SHA.
- **HR product owner:** G-C-1 (who decides when the manager-position holder cannot), G-C-8 (team definition for contracts), G-C-6 if leavers' returns should stay out of the inbox.
- **D4** may start its design work; it must not build on the current on-behalf rule until G-C-1 is decided. **X1-I** may note the inbox being computed 2–3 times per page (audit F16). **X2-R** inherits §2's uncovered combinations and the need for real participants. **G-R** must cite the corrected SHA, not `aa983edc`.
- **Formal review (QA engineer + technical lead): pending.**
