---
paths:
  - '{modules/Hr/Models/Concerns/PresentsEmployee.php,app/Support/EmployeeProfile.php,app/Support/Concerns/RemembersTableLookup.php}'
---

# Models Concerns

## Current position = active contract; never read contract pay
An employee's current position comes from their active contract (contracts.is_active = 1); with several active, the latest start_date wins (log a warning). Read it through App\Support\EmployeeProfile, not a new query. Every HR query lists its columns: never select contracts.salary, contracts.currency, the contract type, or `*` (no ->with('position') on Contract, which loads contracts.*). HR and tracking tables share one database, so joins and whereHas() are fine.
