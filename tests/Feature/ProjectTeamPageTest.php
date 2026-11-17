<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;
use Spatie\Permission\Models\Permission;

/*
| The project page's Team section (roadmap 2.4): the project's manager adds
| and ends members; a regular member sees the team and changes nothing.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');

    $this->ownerEmployee = Employee::factory()->create(['first_name_en' => 'Rana', 'last_name_en' => 'Haddad']);
    $this->owner = User::factory()->create(['employee_id' => $this->ownerEmployee->id]);
    $this->owner->givePermissionTo(Permission::findOrCreate('projects.view', 'web'));
    $this->owner = $this->owner->fresh();

    $this->project = Project::factory()->create(['owner_employee_id' => $this->ownerEmployee->id]);

    $this->memberEmployee = Employee::factory()->create(['first_name_en' => 'Sami', 'last_name_en' => 'Nassar']);
    $this->membership = ProjectMember::factory()->create(['project_id' => $this->project->id, 'employee_id' => $this->memberEmployee->id, 'role' => 'member', 'starts_on' => '2026-10-01']);

    $this->regular = User::factory()->create(['employee_id' => $this->memberEmployee->id]);
    $this->regular->givePermissionTo(Permission::findOrCreate('projects.view', 'web'));
    $this->regular = $this->regular->fresh();
});

test('the project page has a Team section listing the current team', function () {
    $this->actingAs($this->regular)
        ->get(route('projects.show', ['project' => $this->project, 'view' => 'team']))
        ->assertOk()
        ->assertSee('data-section="team"', false)
        ->assertSee('data-test="project-team"', false)
        ->assertSee('Rana Haddad')
        ->assertSee('Sami Nassar');
});

test('the project manager adds a member from the Team section', function () {
    $newcomer = Employee::factory()->create();

    Livewire::actingAs($this->owner)->test('projects.team', ['projectId' => $this->project->id])
        ->assertSeeHtml('data-test="team-add-button"')
        ->call('openAddModal')
        ->set('memberForm.employee_id', (string) $newcomer->id)
        ->set('memberForm.role', ProjectMemberRole::Trainer->value)
        ->set('memberForm.starts_on', '2026-11-01')
        ->call('addMember')
        ->assertHasNoErrors()
        ->assertSet('showAddModal', false);

    $row = ProjectMember::query()->where('employee_id', $newcomer->id)->sole();

    expect($row->role)->toBe(ProjectMemberRole::Trainer)
        ->and($row->starts_on->toDateString())->toBe('2026-11-01');
});

test('adding someone already on the team in that role shows the error on the person field', function () {
    Livewire::actingAs($this->owner)->test('projects.team', ['projectId' => $this->project->id])
        ->call('openAddModal')
        ->set('memberForm.employee_id', (string) $this->memberEmployee->id)
        ->set('memberForm.role', 'member')
        ->call('addMember')
        ->assertHasErrors(['memberForm.employee_id']);
});

test('the project manager ends a membership and the person moves to earlier members', function () {
    Livewire::actingAs($this->owner)->test('projects.team', ['projectId' => $this->project->id])
        ->call('askEnd', $this->membership->id)
        ->assertSet('showEndModal', true)
        ->set('endsOn', '2026-11-15')
        ->call('endMember')
        ->assertHasNoErrors()
        ->assertSeeHtml('data-test="team-past"');

    expect($this->membership->fresh()->ends_on->toDateString())->toBe('2026-11-15');
});

test('a regular member sees the team read-only and cannot change it', function () {
    $component = fn () => Livewire::actingAs($this->regular)->test('projects.team', ['projectId' => $this->project->id]);

    $component()
        ->assertSee('Sami Nassar')
        ->assertDontSeeHtml('data-test="team-add-button"')
        ->assertDontSeeHtml('data-test="team-end-'.$this->membership->id.'"')
        ->assertSeeHtml('data-test="team-read-only"');

    $component()->call('openAddModal')->assertForbidden();
    $component()->call('askEnd', $this->membership->id)->assertForbidden();

    expect($this->membership->fresh()->ends_on)->toBeNull();
});

test('someone who cannot see projects cannot open the team', function () {
    Livewire::actingAs(User::factory()->create())->test('projects.team', ['projectId' => $this->project->id])
        ->assertForbidden();
});
