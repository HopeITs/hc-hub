<?php

use App\Models\Activity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Actions\ProjectMembers\AddProjectMember;
use Modules\Tracking\Actions\ProjectMembers\EndProjectMember;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Enums\ProjectMemberSource;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;
use Spatie\Permission\Models\Permission;

/*
| A project's team (roadmap 2.2, D4): members are employees with a role and
| dates. The project's manager adds and ends them; ending fills ends_on and
| never deletes the row. The Owner row follows projects.owner_employee_id.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');

    $this->ownerEmployee = Employee::factory()->create();
    $this->owner = User::factory()->create(['employee_id' => $this->ownerEmployee->id]);
    $this->owner->givePermissionTo(Permission::findOrCreate('projects.view', 'web'));
    $this->owner = $this->owner->fresh();

    $this->project = Project::factory()->create(['owner_employee_id' => $this->ownerEmployee->id]);
    $this->colleague = Employee::factory()->create();

    $this->actingAs($this->owner);
});

test('the project owner adds a member with a role and a known start date', function () {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Coordinator->value, '2026-09-01');

    $row = DB::table('project_members')->find($member->id);

    expect((int) $row->employee_id)->toBe($this->colleague->id)
        ->and($row->role)->toBe('coordinator')
        ->and($row->starts_on)->toBe('2026-09-01')
        ->and($row->ends_on)->toBeNull()
        ->and($row->source)->toBe('manual')
        ->and((int) $row->added_by)->toBe($this->owner->id)
        ->and(Activity::query()->where('event', 'project.member_added')->where('subject_id', $this->project->id)->exists())->toBeTrue();
});

test('a member added without a start date keeps it unknown', function () {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value);

    expect(DB::table('project_members')->where('id', $member->id)->value('starts_on'))->toBeNull();
});

test('ending a membership fills ends_on and ended_by and keeps the row', function () {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value, '2026-09-01');
    $rowsBefore = ProjectMember::query()->count();

    expect(app(EndProjectMember::class)->handle($member, '2026-11-10'))->toBeTrue();

    $row = DB::table('project_members')->find($member->id);

    expect($row)->not->toBeNull()
        ->and($row->ends_on)->toBe('2026-11-10')
        ->and((int) $row->ended_by)->toBe($this->owner->id)
        ->and(ProjectMember::query()->count())->toBe($rowsBefore)
        ->and(Activity::query()->where('event', 'project.member_ended')->exists())->toBeTrue();
});

test('ending an ended membership changes nothing', function () {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value);
    app(EndProjectMember::class)->handle($member, '2026-11-10');

    expect(app(EndProjectMember::class)->handle($member->fresh(), '2026-11-17'))->toBeFalse()
        ->and(DB::table('project_members')->where('id', $member->id)->value('ends_on'))->toBe('2026-11-10');
});

test('an end date before the start or in the future is refused', function (string $endsOn) {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value, '2026-09-01');

    expect(fn () => app(EndProjectMember::class)->handle($member, $endsOn))->toThrow(ValidationException::class);

    expect(DB::table('project_members')->where('id', $member->id)->value('ends_on'))->toBeNull();
})->with(['before the start' => '2026-08-31', 'in the future' => '2026-11-18']);

test('the same person cannot hold the same role twice at once, but may again after it ended', function () {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value);

    expect(fn () => app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value))
        ->toThrow(ValidationException::class);

    app(EndProjectMember::class)->handle($member, '2026-11-17');
    app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value, '2026-11-17');

    expect(ProjectMember::query()->where('employee_id', $this->colleague->id)->count())->toBe(2)
        ->and(ProjectMember::query()->where('employee_id', $this->colleague->id)->current()->count())->toBe(1);
});

test('the database itself refuses a second open row for the same person and role', function () {
    $row = ['project_id' => $this->project->id, 'employee_id' => $this->colleague->id, 'role' => 'member', 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()];
    DB::table('project_members')->insert($row);

    expect(fn () => DB::table('project_members')->insert($row))->toThrow(QueryException::class);
});

test('the database refuses an end before the start', function () {
    expect(fn () => DB::table('project_members')->insert([
        'project_id' => $this->project->id, 'employee_id' => $this->colleague->id, 'role' => 'member', 'source' => 'manual',
        'starts_on' => '2026-09-01', 'ends_on' => '2026-08-01', 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('an inactive employee or the Owner role cannot be added by hand', function (Closure $employee, string $role) {
    expect(fn () => app(AddProjectMember::class)->handle($this->project, $employee()->id, $role))
        ->toThrow(ValidationException::class);
})->with([
    'inactive employee' => [fn () => Employee::factory()->inactive()->create(), ProjectMemberRole::Member->value],
    'owner role' => [fn () => Employee::factory()->create(), ProjectMemberRole::Owner->value],
]);

test('a regular team member can see the team but cannot add or end members', function () {
    $member = app(AddProjectMember::class)->handle($this->project, $this->colleague->id, ProjectMemberRole::Member->value);

    $regular = User::factory()->create(['employee_id' => $this->colleague->id]);
    $regular->givePermissionTo(Permission::findOrCreate('projects.view', 'web'));
    $regular = $regular->fresh();
    $this->actingAs($regular);

    expect($regular->can('viewAny', [ProjectMember::class, $this->project]))->toBeTrue()
        ->and($regular->can('manage', [ProjectMember::class, $this->project]))->toBeFalse();

    expect(fn () => app(AddProjectMember::class)->handle($this->project, Employee::factory()->create()->id, ProjectMemberRole::Member->value))
        ->toThrow(AuthorizationException::class);
    expect(fn () => app(EndProjectMember::class)->handle($member))
        ->toThrow(AuthorizationException::class);

    expect(DB::table('project_members')->where('id', $member->id)->value('ends_on'))->toBeNull();
});

test('a holder of projects.update manages any project team', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(Permission::findOrCreate('projects.view', 'web'), Permission::findOrCreate('projects.update', 'web'));

    expect($manager->fresh()->can('manage', [ProjectMember::class, $this->project]))->toBeTrue();
});

test('a new project starts with its owner on the team from today', function () {
    $owner = ProjectMember::query()->where('project_id', $this->project->id)->sole();

    expect($owner->employee_id)->toBe($this->ownerEmployee->id)
        ->and($owner->role)->toBe(ProjectMemberRole::Owner)
        ->and($owner->starts_on->toDateString())->toBe('2026-11-17')
        ->and($owner->source)->toBe(ProjectMemberSource::Manual);
});

test('a new owner ends the previous owner row today and opens their own', function () {
    $this->travelTo('2026-11-20 09:00:00');
    $newOwner = Employee::factory()->create();

    $this->project->update(['owner_employee_id' => $newOwner->id]);

    $rows = ProjectMember::query()->where('project_id', $this->project->id)->where('role', 'owner')->orderBy('id')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->employee_id)->toBe($this->ownerEmployee->id)
        ->and($rows[0]->ends_on->toDateString())->toBe('2026-11-20')
        ->and($rows[1]->employee_id)->toBe($newOwner->id)
        ->and($rows[1]->ends_on)->toBeNull();
});

test('the owner row is not ended by hand', function () {
    $owner = ProjectMember::query()->where('project_id', $this->project->id)->sole();

    expect(fn () => app(EndProjectMember::class)->handle($owner))->toThrow(ValidationException::class);

    expect($owner->fresh()->ends_on)->toBeNull();
});
