---
paths:
  - '{modules/Tracking/Actions/Proposals/**,modules/Tracking/resources/views/pages/proposals/**,modules/Tracking/resources/views/pages/projects/**}'
---

# Projects

## Renewal = new proposal on the same project; no project chain
A renewal never creates a project (D-30). It is a new `concept note` proposal on the same project(s), linked through `proposals.previous_proposal_id` and started with `CloneProposalForRenewal` (which writes through `SaveProposal`). There is no `projects.previous_project_id` and no renewal-project action. Never copy status, actual dates, files, agreement, reports, reviewers, deliveries or targets. Copy the framework only through `CopyResultTree`, never a second tree-copy routine. Last year's achievement is a reference read via `PlanIndicator::previousYearReference()`.

## Templates to confirm is two status buttons over one list (scenario rebuild 2026-11-09)
report-templates/index leads with New templates (pending) and Lines added buttons that call `setFocus()` (the `focus` URL property; pressing one again clears it). They are disjoint, follow the search and the city filter but not the pressed button, and add up to the count badge. Search covers project, place and proposal names; an empty result offers Reset filters. The page now renders in `layouts::desk` inside `x-desk.register`; each row has one text "Review" button that opens the project's `?tab=template`. Read-only, no money.
