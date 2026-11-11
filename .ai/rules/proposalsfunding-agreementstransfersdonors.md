---
paths:
  - '{modules/Tracking/Models/Proposal.php,modules/Tracking/Models/DonorProposal.php,modules/Tracking/resources/views/pages/{proposals,funding-agreements,transfers,donors}/**}'
---

# Proposalsfunding Agreementstransfersdonors

## Internal and external project numbers
The internal number lives on proposals (`internal_project_number`): required, unique, and case-insensitive through the utf8mb4_unicode_ci collation. `TEMP-` is reserved for migration placeholders; check it with `Proposal::isPlaceholderNumber`, never a hand-written prefix test. The external number lives on agreements (`donor_proposal.external_project_number`): optional, not unique, empty stored as null. Always display a number through `x-ui.project-number` (LTR-isolated, placeholder chip), never inline.
