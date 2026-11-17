<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;
use Spatie\Permission\Models\Permission;

/*
| The "Projects" tab on an employee's profile (roadmap 2.7): which projects
| the person is on now and which they were on before. Tracking adds it
| through the module registry; HR's page never names a project.
*/

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->self = User::factory()->create(['employee_id' => $this->employee->id])->fresh();

    $this->currentProject = Project::factory()->create(['name' => 'Winter shelter repairs']);
    $this->pastProject = Project::factory()->create(['name' => 'Summer school']);

    ProjectMember::factory()->create(['project_id' => $this->currentProject->id, 'employee_id' => $this->employee->id, 'role' => ProjectMemberRole::Trainer->value, 'starts_on' => '2026-09-01']);
    ProjectMember::factory()->endedOn('2026-08-31')->create(['project_id' => $this->pastProject->id, 'employee_id' => $this->employee->id, 'role' => ProjectMemberRole::Member->value, 'starts_on' => '2026-06-01']);
});

test('the employee sees a Projects tab on their own profile', function () {
    Livewire::actingAs($this->self)->withQueryParams(['tab' => 'projects'])->test('hr::employees.show')
        ->assertSet('tab', 'projects')
        ->assertSee(__('projects.employee_projects.tab'));

    $this->actingAs($this->self)->get(route('hr.employees.me', ['tab' => 'projects']))
        ->assertOk()
        ->assertSee('data-test="employee-projects"', false)
        ->assertSee('Winter shelter repairs');
});

test('the tab lists the projects the person is on now and the ones they were on before', function () {
    Livewire::actingAs($this->self)->test('projects.employee-projects', ['employeeId' => $this->employee->id])
        ->assertSeeHtml('data-test="employee-projects-current"')
        ->assertSeeHtml('data-test="employee-projects-past"')
        ->assertSeeInOrder(['Winter shelter repairs', ProjectMemberRole::Trainer->label(), 'Summer school', ProjectMemberRole::Member->label()]);
});

test('someone who can see projects sees a colleague\'s Projects tab', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('projects.view', 'web'));

    Livewire::actingAs($viewer->fresh())->test('projects.employee-projects', ['employeeId' => $this->employee->id])
        ->assertSee('Winter shelter repairs');
});

test('a colleague who cannot see projects gets no Projects tab', function () {
    $colleague = User::factory()->create(['employee_id' => Employee::factory()->create()->id])->fresh();

    Livewire::actingAs($colleague)->withQueryParams(['tab' => 'projects'])->test('hr::employees.show', ['employee' => $this->employee])
        ->assertSet('tab', 'overview')
        ->assertDontSee('Winter shelter repairs');

    Livewire::actingAs($colleague)->test('projects.employee-projects', ['employeeId' => $this->employee->id])
        ->assertForbidden();
});
