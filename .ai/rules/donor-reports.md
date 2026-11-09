---
paths:
  - '{database/migrations/**,app/Models/DonorReport.php,resources/views/pages/funding-agreements/**,resources/views/pages/donor-reports/**}'
---

# Donor Reports

## Donor reports: validated mirrors only, snapshot frozen
donor_reports (T19, migration 2026_10_19_122940) adds audience (donor|internal), period_type, period_start/end, snapshot json (schema_version 1), version, responsible_employee_id (HR id, no FK), notes; unique (donor_proposal_id, type, period_start, period_end, version). Figures come only from approved share mirrors (reports.monthly_report_id set) via DonorReportAggregator; ratios only through PlanIndicator::pooledRatio(). Saved pages, CSV and print read the snapshot, never re-query. A submitted report (and its files) is frozen: no edit, delete, regenerate or file change. Submission goes only through MarkDonorReportSubmitted (date ≤ today + a report_submission file for audience=donor). Finance renders only when the snapshot has it AND the viewer holds funding-agreements.view.

## Reporting duties is three status buttons over one list (scenario rebuild 2026-11-09)
reporting-duties/index leads with Late, To submit and Submitted buttons that call `setFocus()` (the `focus` URL property; pressing one again clears it). They are disjoint and add up to the page count badge; `focusCounts()` follows the search but not the pressed button, so a click shows exactly that many. The Open/All dropdown (`state`) is gone: with no button pressed the list shows every report, late first, then soonest due, then submitted (latest first). Search covers report title, donor and proposal name. An empty result offers Reset filters. The Table / Board / Calendar switch is unchanged (replacing it needs the owner's decision). Still no money anywhere on the page.
