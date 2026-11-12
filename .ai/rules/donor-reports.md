---
paths:
  - '{database/migrations/**,modules/Tracking/Models/DonorReport.php,modules/Tracking/resources/views/pages/funding-agreements/**,modules/Tracking/resources/views/pages/donor-reports/**}'
---

# Donor Reports

## Donor reports: validated mirrors only, snapshot frozen
donor_reports (T19, migration 2026_10_19_122940) adds audience (donor|internal), period_type, period_start/end, snapshot json (schema_version 1), version, responsible_employee_id (HR id, no FK), notes; unique (donor_proposal_id, type, period_start, period_end, version). Figures come only from approved share mirrors (reports.monthly_report_id set) via DonorReportAggregator; ratios only through PlanIndicator::pooledRatio(). Saved pages, CSV and print read the snapshot, never re-query. A submitted report (and its files) is frozen: no edit, delete, regenerate or file change. Submission goes only through MarkDonorReportSubmitted (date ≤ today + a report_submission file for audience=donor). Finance renders only when the snapshot has it AND the viewer holds funding-agreements.view.

## Reporting duties is three status buttons over one list (scenario rebuild 2026-11-09)
reporting-duties/index leads with Late, To submit and Submitted buttons that call `setFocus()` (the `focus` URL property; pressing one again clears it). They are disjoint and add up to the page count badge; `focusCounts()` follows the search but not the pressed button, so a click shows exactly that many. The Open/All dropdown (`state`) is gone: with no button pressed the list shows every report, late first, then soonest due, then submitted (latest first). Search covers report title, donor and proposal name. An empty result offers Reset filters. The Table / Board / Calendar switch is unchanged (replacing it needs the owner's decision). Still no money anywhere on the page.

## Donor reports list, report page and builder: page review fixes (2026-11-06)
- The report page and the builder lead back to `donor-reports.index` (breadcrumb "Donor reports" and BackUrl fallback), never to the builder.
- The report page names its donor and proposal under the title (`data-report-owner`), from the agreement, not only from a composed snapshot.
- A grant without its actual start or end date (`Proposal::lacksGrantDates()`: `actual_date_begin` or `effectiveDateEnd()` null) is said as such: `DonorReportPeriods::window()` throws `grant_has_no_dates`, and the builder shows it as soon as the proposal is picked. Owner decision (2026-11-06): do NOT fall back to the planned dates; the builder keeps refusing with this message until the grant has real start and end dates. The message links to `proposals.show?grant_dates=1` (Set grant dates, see proposals.md) for someone who may update the proposal, and says who can for everyone else; Preview stays disabled meanwhile. The builder form is `novalidate` so the server names each missing field.
- List search reads proposal, donor and report title; an empty filter result offers Reset filters; a due report shows its days left (`funding_agreements.due_in_days` / `count_days_overdue`, as the agreement page); the pager shows only with more than one page; `reports.aggregate` holders get a "New donor report" button.
- Who delivers a report is set on the report page through `AssignDonorReportResponsible` (policy `assign`: funding-agreements.update, or reports.aggregate with `view` on the report's proposal, not submitted; active HR employee only); the change is logged via `activityEmployeeFields`. A compiler who only holds an assigned duty (cannot view the proposal) cannot reassign, matching the builder, which hides the responsible picker from them.
