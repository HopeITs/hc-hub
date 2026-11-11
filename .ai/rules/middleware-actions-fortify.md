---
paths:
  - '{modules/Core/Models/User.php,app/Console/Commands/**,modules/Tracking/Console/Commands/**,app/Http/Middleware/**,modules/Tracking/Http/Middleware/**,app/Actions/Fortify/**}'
---

# Middleware Actions Fortify

## Access follows the linked employee's is_active
SUPERSEDES "HR user sync: identity is hr_user_id" (A2, 2026-11-11). users.id is the identity; there is no HR account link. EnsureAccountIsActive (web group) signs out a user whose account is gone, whose linked employee is inactive/missing (User::hasLostAccess()), or whose password hash changed since login (cached 60s, fails open); passkey login checks the same via Passkeys::authorizeLoginUsing. User::delete() runs in a transaction so Spatie's role detach rolls back when a restricting FK keeps the user. Filter on access with User::withActiveAccount()/withoutActiveAccount(); employees are in the same database, so use whereHas('employee') freely.
