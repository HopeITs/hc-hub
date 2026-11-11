---
paths:
  - '{app/Actions/Fortify/**,app/Console/Commands/**,modules/Tracking/Console/Commands/**,config/auth.php,database/seeders/**}'
---

# Commands Seeders

## Accounts are users rows; roles are granted explicitly
SUPERSEDES "every HR account is a tracking user" (A2, 2026-11-11). There is no first-login account creation and no hr:sync-users. Accounts come from `hub:import-hr` (upserted by HR id) or `php artisan user:create --email= [--password=] --role=` (an existing email gets the role via User::grantAccess(); a new email needs --password and gets no employee) or Admin > Users (picks an existing account without a role). CoreDataSeeder creates a few accounts only into an empty users table.
