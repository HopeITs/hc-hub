# Wave 3 · User-story corrections (5 Nov 2026)

Status: **draft plan, not approved**. Written from the user-story review of 5 Nov 2026 and a read of `main` (`daf12490`). `contracts.md` and `decisions.md` stay the sources of truth: every item marked **[contract]** needs its D-number signed before code. Run items like wave 2 (`/lifecycle X-NN`), one branch each (`wave3/X-NN-slug`), test first, a handoff in `wave3/handoffs/`.

Size: S ≤ ½ day · M 1–2 days · L 3–5 days.

---

## 0. What the review said, and what the code does today

| # | Review note | Today on `main` |
|---|---|---|
| 1 | The journey starts at a **Proposal** (concept note or draft). There is no "create the project first" step. | The proposal form already offers a starting stage (`ManagesProposalForm::$startingStage`: concept note, or proposal draft with a skip reason). Projects are created on approval from the location rows (W-34a, `MaterialiseProposalLocations`). Only the user story and the "Add center" entry make it look project-first. |
| 2 | The **General Manager does not approve** a proposal; the Projects & Fundraising Manager does. | `ProposalPolicy::approve()` = `proposals.update`, held by Financial, PR Manager and GM (`config/access-control.php`). |
| 3 | **PR adds centers/programs**; Managers do not. | `projects.create` = PR Manager, GM, Financial. The `Public Relations` role does not have it. |
| 4 | **Sectors** are managed by MEAL **and PR**. | `sectors.manage` = MEAL, GM. |
| 5 | A **project manager** sees the tasks of their projects, not only a department manager. | `User::canSeeDepartmentTasks()` = administrator or HR job level "Department Manager – Grade 5" (W-14, D-65). |
| 6 | Monthly reports: the year starts in **January**. | Desk card `components/desk/panels/⚡matrix.blade.php:58-62` shows a rolling 12 months ending this month (Dec → Nov). |
| 7 | Fill-report error. | Already fixed. Nothing to do. |
| 8 | A filled report reads **done**, and waits in **To validate**. | Submit exists (`SubmitMonthlyReport`); the "To validate" tab exists (`_index-validate.blade.php`). To check: the wording the manager sees after submit, and that the report reaches the right MEAL queue. |
| 9 | An **extension** keeps the same proposal and number, so it stays the same project. | W-08 `ExtendProposal` does this: same row, `extended_date_end`, projects widened. User story only. |
| 10 | A new proposal asks: **is there an intermediary donor? yes / no**. The proposal's donor is always the primary donor. | No such field. `proposals.donor_id` is the only donor. |
| 11 | **What a project is.** For PR, a proposal **is** the project, with one final report. For the organisation, a project = **program × governorate**; centers are its sites, so one governorate can hold several centers of one program in a single project. A donor's money can cover parts of several such projects. The monthly-report owner is set per governorate + program. | One project per (`center_id ?? city_id`, `program_id`) (D-30a, W-01). `MaterialiseProposalLocations` creates **one project per center**. Monthly reports are per project, so today two centers of one program in Aleppo are two projects and two reports. |

---

## 1. Decisions to sign first (Dev 1 → management)

| # | Question | Recommended answer | Blocks |
|---|---|---|---|
| D-69 | **Project = program × governorate.** | A project is keyed on (`city_id`, `program_id`). Centers become a list on the project (`project_centers` pivot, HR ids). `host_center_id` stays for "followed up by". One open project per (`city_id`, `program_id`). The monthly report stays **one per project per month**, so ADISC1 and ADISC2 in Aleppo report together. | X-02 |
| D-69a | Inside that one report, are figures entered **per center** or once for the project? | Once for the project. Add a per-center split later only if MEAL asks. | X-02 |
| D-69b | Existing projects that become duplicates (same city + program, different centers). | Merge them into the oldest: move centers, proposals, targets, tasks and template lines; months that clash need a person to decide. A dry-run command lists them first. | X-02 |
| D-70 | **Approval** belongs to the PR Manager only. | New permission `proposals.approve`, PR Manager only. GM, Financial and Super Admin keep the view. `ProposalPolicy::approve()` reads it. Financial loses the `projects.create` it was given for approval (W-34a) unless X-05 needs it. | X-04 |
| D-71 | **Intermediary donor.** | `proposals.has_intermediary` (bool) + `proposals.intermediary_donor_id` (nullable FK `donors`). `donor_id` is always the primary donor. Agreements, transfers and donor reports stay on the primary donor; the intermediary is shown and filterable. | X-09 |
| D-72 | Who manages sectors, and who adds centers? | `sectors.manage` += Public Relations Manager **and** Public Relations. `projects.create` += Public Relations. | X-05 |
| D-73 | Task scope for project managers. | A "My projects" scope on the Tasks panel and the department-tasks page: tasks on projects the user owns (`projects.user_id` = their employee). Shown to project owners and department managers. | X-06 |

---

## 2. Work items

### Phase 1 · Small fixes (no or tiny contract change) · Team B

| # | Item | Where | Size |
|---|---|---|---|
| X-07 | **Desk matrix starts in January.** Show Jan → Dec of the selected year (default: this year), with ‹ › to change year. Late reports from an earlier year still show as the row marker. Update the matrix test. | `components/desk/panels/⚡matrix.blade.php:58-62` and `:113`, `:162` | S |
| X-08 | **Submitted reads as done.** After Submit, the manager's list and report header say "Sent · waiting for review"; the row counts as done in "My reports" and the Reporting duties panel; it appears in **To validate** for the responsible MEAL officer, or for the MEAL Manager slot. Check with the demo report (`/monthly-reports/demo-micro-aleppo-2026-10`) first: this may be wording only. | `pages/monthly-reports/_index-mine.blade.php`, `_show-header.blade.php`, `lang/*/monthly_reports.php` | S |
| X-10 | **Extension check.** Confirm in the browser that "Extend" on an active proposal keeps the same number and the same projects, and shows "Extended to …". Story text only. | `proposals/_show-actions.blade.php` | S |

### Phase 2 · Roles · Team A

| # | Item | Depends on | Size |
|---|---|---|---|
| X-04 | **PR Manager approves.** Add `proposals.approve` (PR Manager), point `ProposalPolicy::approve()` at it, and remove approve from GM through `General Manager`'s exclusion list. `permissions:sync` only adds, so a one-off migration revokes approve from GM and Financial. Hide the Approve button for everyone else. Tests: GM, Financial and PR are refused; the PR Manager approves. | D-70 | S |
| X-05 | **PR adds centers; PR + MEAL manage sectors.** Baselines in `config/access-control.php`, then `permissions:sync`. Update `contracts.md §8`. | D-72 | S |
| X-06 | **"My projects" task scope.** `User::ownedProjectIds()` already exists. Add the scope next to `department` in the Tasks panel and on `department-tasks`; `canSeeDepartmentTasks()` becomes "department manager **or** owns at least 1 open project". | D-73 | S |

### Phase 3 · Proposal form · Team A

| # | Item | Depends on | Size |
|---|---|---|---|
| X-09 | **Intermediary donor.** After the donor: "Is there an intermediary donor? Yes / No". On Yes, a donor picker, which uses the same pending-donor rule as W-04. Show it on the proposal header and in the register filters. | D-71 | M |

### Phase 4 · Project = program × governorate [contract] · Team A (Dev 1)

| # | Item | Depends on | Size |
|---|---|---|---|
| X-02 | **One project per (city, program), centers as a list.** (1) `project_centers` pivot plus a backfill from `projects.center_id`; (2) `Project::openProjectFor()` keyed on (`city_id`, `program_id`); (3) `MaterialiseProposalLocations` creates or reuses **one project per row** (city + program) and attaches the row's centers, no longer one per center; (4) the "Add center" form adds a center to the (city, program) project or creates it; (5) a merge command `projects:merge-by-city-program {--dry-run}` for D-69b; (6) badges and filters show the centers list. Guard tests (`ProjectDraftLifecycleTest`, open-project rule, W-34a materialisation) are updated first. | D-69, D-69a, D-69b | L |
| X-01 | **Naming per audience.** PR pages (Proposals, Funding agreements, Donor reports) call a proposal "Project"; M&E pages keep "Project" for program × governorate. Lang keys only, no route or table renames. Update the glossary. | X-02 | M |

### Phase 5 · Docs

| # | Item | Size |
|---|---|---|
| X-11 | Update `docs/user-story.ar.md` and the PDF: the journey starts at the Proposal; PR Manager approves; PR adds centers; PR + MEAL manage sectors; project-owner task scope; the January year; the intermediary donor; project = program × governorate. | S |

---

## 3. Order

```
Sign D-69…D-73
  ├─ Phase 1 (X-07, X-08, X-10)        no decision needed, start now
  ├─ Phase 2 (X-04 → X-05 → X-06)      after D-70, D-72, D-73
  ├─ Phase 3 (X-09)                    after D-71
  ├─ Phase 4 (X-02 → X-01)             after D-69 a/b; the biggest risk, test on a DB copy
  └─ X-11 last
```

Risks: X-02 touches the open-project rule, materialisation and monthly-report uniqueness. Run the merge dry-run on a copy of production before signing D-69b.
