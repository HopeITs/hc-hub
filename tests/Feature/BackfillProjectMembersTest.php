<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;
use Modules\Tracking\Models\Task;

/*
| The first fill of project teams (roadmap 2.3): every project's owner joins
| as Owner and the people seen on its milestones and tasks as Members, all
| `source = inferred` with no dates. A dry run writes nothing; a second run
| adds nothing; every run leaves a CSV report.
*/

beforeEach(function () {
    Storage::fake('local');

    $this->owner = Employee::factory()->create();
    $this->project = Project::factory()->create(['owner_employee_id' => $this->owner->id]);

    $this->responsible = Employee::factory()->create();
    $milestone = Milestone::factory()->create(['project_id' => $this->project->id, 'responsible_employee_id' => $this->responsible->id]);

    $this->assignee = Employee::factory()->create();
    $task = Task::factory()->create(['source_type' => 'milestone', 'source_id' => $milestone->id]);
    $task->taskAssignees()->delete();
    $task->taskAssignees()->create(['employee_id' => $this->assignee->id]);

    // Projects saved before the team existed have no rows yet.
    DB::table('project_members')->delete();
});

test('a dry run writes nothing and still reports what it would add', function () {
    $this->artisan('projects:backfill-members')->assertSuccessful();

    expect(ProjectMember::query()->count())->toBe(0);

    $report = Storage::disk('local')->get(Storage::disk('local')->files('reports')[0]);

    expect($report)->toContain('project owner')
        ->and($report)->toContain('to add');
});

test('applying adds the owner and the people seen on the project, inferred and undated', function () {
    $this->artisan('projects:backfill-members --apply')->assertSuccessful();

    $rows = ProjectMember::query()->where('project_id', $this->project->id)->get()->keyBy('employee_id');

    expect($rows)->toHaveCount(3)
        ->and($rows[$this->owner->id]->role->value)->toBe('owner')
        ->and($rows[$this->responsible->id]->role->value)->toBe('member')
        ->and($rows[$this->assignee->id]->role->value)->toBe('member')
        ->and($rows->every(fn (ProjectMember $row): bool => $row->source->value === 'inferred' && $row->starts_on === null && $row->ends_on === null))->toBeTrue();
});

test('every project ends up with its owner on the team', function () {
    Project::factory()->count(2)->create();
    DB::table('project_members')->delete();

    $this->artisan('projects:backfill-members --apply')->assertSuccessful();

    foreach (Project::query()->get(['id', 'owner_employee_id']) as $project) {
        expect(ProjectMember::query()->where('project_id', $project->id)->where('employee_id', $project->owner_employee_id)->where('role', 'owner')->current()->exists())->toBeTrue();
    }
});

test('a second run adds nothing, and an owner already on the team is kept as is', function () {
    ProjectMember::factory()->create(['project_id' => $this->project->id, 'employee_id' => $this->owner->id, 'role' => 'owner', 'starts_on' => '2026-01-05']);

    $this->artisan('projects:backfill-members --apply')->assertSuccessful();
    $countAfterFirst = ProjectMember::query()->count();
    $this->artisan('projects:backfill-members --apply')->assertSuccessful();

    expect(ProjectMember::query()->count())->toBe($countAfterFirst)
        ->and(ProjectMember::query()->where('employee_id', $this->owner->id)->sole()->starts_on->toDateString())->toBe('2026-01-05');
});

test('an inactive employee or a closed project is flagged for review, and the row stays open', function () {
    $this->assignee->forceFill(['is_active' => false])->save();
    $this->project->forceFill(['status' => ProjectStatus::Closed])->saveQuietly();

    $this->artisan('projects:backfill-members --apply')->assertSuccessful();

    $report = Storage::disk('local')->get(Storage::disk('local')->files('reports')[0]);

    expect($report)->toContain('employee is not active')
        ->and($report)->toContain('project is closed')
        ->and(ProjectMember::query()->where('employee_id', $this->assignee->id)->sole()->ends_on)->toBeNull();
});
