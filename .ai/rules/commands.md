---
paths:
  - '{app/Actions/Fortify/**,app/Console/Commands/**,modules/Tracking/Console/Commands/**,config/fortify.php}'
---

# Commands

## Login uses users.email + users.password
SUPERSEDES "Login uses the HR password" (A2, 2026-11-11). AuthenticateUser checks `Hash::check()` against users.password and refuses an account whose linked employee is inactive or missing (User::hasLostAccess(); an account with no employee is a system account and may sign in). Password confirmation uses the same check (throttled: 5/min per user+IP, 10/15min per user). Login failures are capped at 30/15min per IP and 10/15min per email on top of Fortify's 5/min; 2FA and passkeys are unchanged. Self-service account deletion, email verification, password reset and Settings "update password" stay removed; profile name/email are read-only. `hub:import-hr` re-imports passwords from the old HR app until launch, so a password changed in the hub is overwritten by the next import. Registration stays disabled.
