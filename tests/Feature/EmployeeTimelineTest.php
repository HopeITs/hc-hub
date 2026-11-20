<?php

use App\Models\Activity;
use App\Support\Modules\Contracts\TimelineSource;
use App\Support\Modules\ModuleRegistry;
use App\Support\Timeline\EmployeeTimeline;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/*
| Roadmap 4.7 (D6): an employee's timeline is built from the lasting records
| (contracts, project memberships, equipment, journeys) through each module's
| TimelineSource, so it stands when the activity log has been pruned.
| activities.employee_id says whom a log entry is about.
*/

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->self = User::factory()->create(['employee_id' => $this->employee->id])->fresh();

    $this->contract = Contract::factory()->for($this->employee)->create(['start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'terminated_at' => null]);
    $this->project = Project::factory()->create(['name' => 'Winter shelter']);
    ProjectMember::factory()->endedOn('2025-09-30')->create(['project_id' => $this->project->id, 'employee_id' => $this->employee->id, 'starts_on' => '2025-03-01']);

    $itemId = DB::table('checklist_items')->insertGetId(['name' => 'Laptop', 'order' => 1, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('checklists')->insert([
        'item_id' => $itemId, 'employee_id' => $this->employee->id, 'delivered_id' => $this->employee->id, 'brand' => 'Lenovo', 'delivered_at' => '2025-01-05 09:00:00',
        'returned_at' => '2025-12-30 15:00:00', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('onboarding_journeys')->insert([
        'employee_id' => $this->employee->id, 'contract_id' => $this->contract->id, 'started_at' => '2025-01-01 08:00:00', 'orientation_due_on' => '2025-01-15',
        'completed_at' => '2025-01-20 08:00:00', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->timeline = fn (User $viewer) => Livewire::actingAs($viewer)->test('employees.timeline', ['employeeId' => $this->employee->id]);
    $this->sources = fn (User $viewer): array => array_values(array_unique(array_column(app(EmployeeTimeline::class)->entries($viewer, $this->employee->id), 'source')));
});

test('the timeline shows contracts, memberships, equipment and journeys, newest first', function () {
    ($this->timeline)($this->self)
        ->assertSeeInOrder([
            __('timeline.contracts.ended'),
            __('timeline.equipment.returned'),
            __('timeline.projects.left'),
            __('timeline.projects.joined'),
            __('timeline.journeys.onboarding_completed'),
            __('timeline.equipment.received'),
            __('timeline.journeys.onboarding_started'),
        ])
        ->assertSee(__('timeline.contracts.started'))
        ->assertSee('Winter shelter')
        ->assertSee('Laptop Lenovo');
});

test('the timeline stands when the activity log has been emptied', function () {
    DB::table('activities')->delete();

    expect(($this->sources)($this->self))->toEqualCanonicalizing(['contracts', 'projects', 'equipment', 'journeys']);

    ($this->timeline)($this->self)->assertSee(__('timeline.contracts.started'))->assertSee(__('timeline.equipment.received'));
});

test('a membership whose start was never recorded is listed without a date', function () {
    ProjectMember::factory()->create(['project_id' => Project::factory()->create(['name' => 'Inferred one'])->id, 'employee_id' => $this->employee->id, 'starts_on' => null]);

    $entries = app(EmployeeTimeline::class)->entries($this->self, $this->employee->id);

    expect(end($entries)['date'])->toBeNull()
        ->and(end($entries)['detail'])->toContain('Inferred one');

    ($this->timeline)($this->self)->assertSee(__('timeline.undated'));
});

test('a colleague with no right to the records sees projects only, and a stranger to both sees no timeline', function () {
    $colleague = userWithRole('Team Member', Employee::factory()->create()->id);
    $stranger = User::factory()->create(['employee_id' => Employee::factory()->create()->id])->fresh();

    expect(($this->sources)($colleague))->toBe(['projects'])
        ->and(($this->sources)($stranger))->toBe([]);

    ($this->timeline)($stranger)->assertForbidden();
});

test('the profile offers the timeline tab to the employee', function () {
    Livewire::actingAs($this->self)->withQueryParams(['tab' => 'timeline'])->test('hr::employees.show')
        ->assertSet('tab', 'timeline')
        ->assertSee('data-test="employee-timeline"', false);
});

test('an activity about an employee carries their id, and a reader of the log sees it on the timeline', function () {
    $this->employee->update(['first_name_en' => 'Renamed']);
    $activity = Activity::query()->where('subject_type', 'employee')->where('subject_id', $this->employee->id)->latest('id')->firstOrFail();

    expect($activity->employee_id)->toBe($this->employee->id)
        ->and(($this->sources)($this->self))->not->toContain('activity')
        ->and(($this->sources)(userWithRole('Super Admin')))->toContain('activity');
});

test('an activity that names no employee, or one that does not exist, is about no one', function () {
    $project = Project::factory()->create();

    $plain = $project->recordActivity('project.updated');
    $unknown = $project->recordActivity('project.member_added', properties: ['employee_id' => 999999]);
    $named = $project->recordActivity('project.member_added', properties: ['employee_id' => $this->employee->id]);

    expect($plain->employee_id)->toBeNull()
        ->and($unknown->employee_id)->toBeNull()
        ->and($named->employee_id)->toBe($this->employee->id);
});

test('the migration fills employee_id from what each row already says, and rolls back', function () {
    $migration = 'database/migrations/2026_11_20_100300_add_employee_id_to_activities_table.php';
    $this->employee->update(['last_name_en' => 'Changed']);
    Project::factory()->create()->recordActivity('project.member_added', properties: ['employee_id' => $this->employee->id]);
    $about = DB::table('activities')->whereNotNull('employee_id')->count();

    Artisan::call('migrate:rollback', ['--path' => $migration]);
    expect(Schema::hasColumn('activities', 'employee_id'))->toBeFalse();

    Artisan::call('migrate', ['--path' => $migration]);

    expect($about)->toBeGreaterThan(1)
        ->and(DB::table('activities')->whereNotNull('employee_id')->count())->toBe($about);
});

test('every registered source reads a lasting table, not the activity log, except the log\'s own', function () {
    $keys = array_map(fn (TimelineSource $source): string => $source->key(), app(ModuleRegistry::class)->timelineSources());

    expect($keys)->toEqualCanonicalizing(['activity', 'contracts', 'equipment', 'journeys', 'projects']);
});
