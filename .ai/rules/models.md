---
paths:
  - 'app/Models/**'
  - 'modules/*/Models/**'
  - modules/Tracking/Models/Account.php
  - modules/Core/Models/Program.php
  - modules/Core/Models/User.php
  - modules/Tracking/Models/Task.php
---

# Models

## plan_indicators sources are AnnualPlan and Proposal, via a morph map
`plan_indicators.source_type`/`source_id` is a morphTo relation with exactly two valid sources: AnnualPlan and Proposal. A target is committed either in an annual operational plan or in a donor grant proposal.

`source_type` is VARCHAR(50), so register short morph keys rather than storing class names:

    Relation::enforceMorphMap([
        'annual_plan' => AnnualPlan::class,
        'proposal' => Proposal::class,
    ]);

Put this in a service provider's boot(). Storing 'Modules\\Tracking\\Models\\AnnualPlan' would work today but leaves no headroom in the column and couples stored data to the namespace.

No foreign key backs source_id — see the migrations rule about orphans for the cleanup obligation.

## Model file shape: strict_types, attributes, casts() and CarbonImmutable
Every class in app/Models carries `declare(strict_types=1);`. Pint's laravel preset does not add or strip it, so keep it by hand on new models.

Mass assignment and hidden fields use the Laravel 13 class attributes `#[Fillable([...])]` / `#[Hidden([...])]`, not `protected $fillable`. Casts go in `protected function casts(): array`. `users.role` is deliberately NOT fillable — adding it opens privilege escalation through registration.

`AppServiceProvider` calls `Date::use(CarbonImmutable::class)`, so every `date` / `datetime` cast returns `CarbonImmutable`, never `Illuminate\Support\Carbon`. Type `@property` docblocks accordingly.

`DonorProposal` and `TransfersTracking` are the only models needing `protected $table` (donor_proposal, transfers_tracking); everything else infers.

## is_active decides whether an account is usable, not closed_at
`accounts.is_active` is the single source of truth for whether an account can receive a transfer. `closed_at` only records when it was closed and must never be read as a usability check.

Call sites go through `isUsable()`, the `#[Scope] active()` query scope, or `close()` — never the raw columns, so the two can never be interpreted apart. `close()` writes both together so a closed account is never left looking active.

The model carries `protected $attributes = ['is_active' => true]` mirroring the column default. Without it `Account::create([...])->isUsable()` throws a TypeError, because the factory deliberately omits the column and the attribute is therefore unhydrated until the model is refreshed. The column default is still proven independently by a raw-insert test in tests/Feature/AccountTest.php.

Accounts are never deleted — transfers_tracking.account_id restricts on delete. Close them instead.

## One database: HR rows are ordinary tables with real foreign keys
SUPERSEDES "HR database is read-only; tracking links to it by id" (A2, 2026-11-11). There is no `hr` connection: employees, cities, centers, departments, positions and contracts are Modules\Hr / Core models in the hub database, loaded by `hub:import-hr` until launch. Tracking columns holding their ids have real FKs (restrict for history such as projects.user_id, milestones.responsible_user_id, task_assignees/assignments/proposal_reviewers/meal_coverages.employee_id, supervisions, projects/proposal_locations.city_id; null on delete for optional links such as programs.manager_id, proposal_locations/donor_reports.responsible_employee_id, projects.center_id/host_center_id, tasks.department_id). Use relations, joins and whereHas() (Employee::whereNameLike(), whereHas('employee'|'manager'|'user')); no in-memory id matching. Employee photos still come from the `hr` filesystem disk through hr_media rows.

## Programs are Core rows; tracking may create one from a proposal
SUPERSEDES "Programs mirror HR programs" (A2, 2026-11-11). Program::syncFromHr and the SyncProgramsFromHr middleware are gone; hub:import-hr upserts HR's columns by id and keeps strategy_id/manager_id. A proposal location marked new creates its Core program (programs.create) or center (centers.create) through CreateLocationPlaces; the program-request bridge (ProgramRequest, program_requests) was removed. HR screens only read programs and centers.

## Mirror DB column defaults in User's $attributes for strict mode
`User::factory()->create()` without an explicit role never puts `role` in the INSERT payload (it's only set via the `role()` factory state, or `forceFill` in real app code). Under `Model::shouldBeStrict()`, the resulting in-memory model then throws `MissingAttributeException` the moment anything reads `$user->role` (e.g. a Policy). Fixed by adding `'role' => 'Officer'` to `User::$attributes` alongside the existing `'locale' => null` — mirroring the DB column default so a user that hasn't round-tripped through the database still reports it. Apply the same pattern to any other User (or other model) column that has a DB default but isn't always present in factory-created instances.

## Multi-assignee tasks (task_assignees), duplicate removed, task-updates removed entirely
Three changes landed together on 2026-09-18/21, all by user request:

**Multi-assignee**: `tasks.assigned_to` (single HR employee id) is gone. A task's assignees now live in `task_assignees` (migration `2026_09_18_163104_create_task_assignees_table`, backfilled from the old column then dropped it in the same migration) — `task_id` FK cascades, `employee_id` is a restricting FK to employees. New model `TaskAssignee` (`task()` belongsTo, `employee()` belongsTo Employee). `Task::taskAssignees(): HasMany`. `Employee::assignedTasks()` is a `hasManyThrough(Task::class, TaskAssignee::class, ...)`.

Every place that used to read/write `assigned_to` needed updating: `tasks/⚡index.blade.php`'s drawer (checkbox multi-select, not a `<select>`), `tasks/⚡show.blade.php` (subtask assignee inheritance copies ALL of the parent's `taskAssignees` rows), `calendar/⚡index.blade.php` (its own separate task create/edit form — same checkbox multi-select), the dashboard's "my work" query in `app/Livewire/Concerns/Dashboard/ReadsDashboardWork.php` (`whereHas('taskAssignees', ...)` instead of `where('assigned_to', ...)`), `proposals/⚡show.blade.php`'s related-tasks panel. `TaskFactory` no longer sets `assigned_to`; instead its `configure()`'s `afterCreating` hook auto-creates one `TaskAssignee` row if none exists yet, so `Task::factory()->create()` still yields a task with exactly one assignee by default (tests relying on a single deterministic assignee call `$task->taskAssignees()->delete()` first, then add their own).

Sort-by-assignee (`orderByAssigneeName` in `modules/Tracking/Livewire/Concerns/Tasks/FiltersTaskDirectory.php`, used by `tasks/⚡index.blade.php`) now orders by each task's alphabetically-first assignee name, computed at the TASK level (not employee level) since a task can map to several employees — resolves via a `TaskAssignee::whereIn('task_id', ...)` query plus a `CASE id WHEN ... END` on `tasks.id`, not `assigned_to`.

**Duplicate action removed**: `duplicateTask()` and every "Duplicate" button/icon in the task list and board are gone. `tasks.duplicate`/`duplicate_title`/`task_duplicated` lang keys removed.

**task_updates removed entirely**: `App\Models\TaskUpdate`, its factory, and the `task_updates` table (migration `2026_09_18_163332_drop_task_updates_table`) are gone — not deprecated, deleted. `Task::updates()` and `User::taskUpdates()` relations removed. The "Updates" panel + `addUpdate()`/`comment` on `tasks/⚡show.blade.php` are gone. The dashboard's recent-activity feed (`activity()` in `app/Livewire/Concerns/Dashboard/ReadsDashboardWork.php`) no longer has a task-update source — just proposal-status-notes and report-submissions now. `DeliverySeeder` creates a `TaskAssignee` instead of a `TaskUpdate` per seeded task. If a future "task comments" feature is wanted, it needs to be built fresh — nothing here should be un-deleted or repurposed.

## Tracked models record activity through TracksActivity + RecordsActivity
Any model activity is recorded against implements App\Notifications\TracksActivity and uses App\Concerns\RecordsActivity (child rows like assignees/files use RecordsParentActivity and record on their parent). activityEvents() lists meaningful fields → event suffix; other field changes log as "{type}.updated" without notifying. Its morph key must be in AppServiceProvider::configureMorphMap() and MorphMapTest. Read attributes via activityAttribute() (strict mode), never lazy-load relations in activity hooks.
