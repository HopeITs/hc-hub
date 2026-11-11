---
paths:
  - '{modules/Core/Models/User.php,modules/Core/Models/Program.php,database/factories/{UserFactory,ProgramFactory}.php}'
---

# Concerns Factories

## One database: users and programs are real rows
SUPERSEDES "No copies of HR data" (A2, 2026-11-11). users holds email and password (both NOT NULL, email unique) and employee_id (FK to employees, null on delete); hr_user_id is gone. programs holds name/slug/code/dates. User::name always reads the directory (users LEFT JOIN employees: English name, else email) via User::directory() (RemembersLookup, reloaded after 60s or on an unknown id); User::email and Program::name/slug read the column when loaded and fall back to the remembered lookup, so `user:id` / `program:id` eager loads stay strict-mode safe. Search/sort with User::whereNameOrEmailLike()/orderByName() and Program::whereNameLike()/orderByName()/options(). UserFactory gives each user its own Employee (withoutEmployee() for a system account); ProgramFactory writes plain programs rows.
