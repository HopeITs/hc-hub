<?php

use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Models\AnnualPlan;

beforeEach(function () {
    $this->program = Program::factory()->create();
    $this->plan = AnnualPlan::factory()->create(['program_id' => $this->program->id]);
    $this->plans = fn (User $user) => Livewire::actingAs($user)->test('tracking::annual-plans.index');
});

test('a Team Member cannot edit a program; Managers can', function () {
    Livewire::actingAs(userWithRole('Team Member'))->test('tracking::programs.show', ['program' => $this->program])
        ->call('openLinkModal')
        ->assertForbidden();

    Livewire::actingAs(userWithRole('Managers'))->test('tracking::programs.show', ['program' => $this->program])
        ->call('openLinkModal')
        ->assertOk();
});

test('the plan\'s own manager and the program manager may edit the plan', function () {
    $planManagerEmployee = insertHrEmployee();
    $this->plan->update(['manager_id' => $planManagerEmployee]);
    ($this->plans)(userWithRole('Team Member', $planManagerEmployee))->call('openEditModal', $this->plan->id)->assertOk();

    $programManagerEmployee = insertHrEmployee();
    $this->program->update(['manager_id' => $programManagerEmployee]);
    ($this->plans)(userWithRole('Team Member', $programManagerEmployee))->call('openEditModal', $this->plan->id)->assertOk();

    ($this->plans)(userWithRole('Team Member', insertHrEmployee()))->call('openEditModal', $this->plan->id)->assertForbidden();
});

test('a plan manager\'s edit keeps the plan\'s program and manager', function () {
    $planManagerEmployee = insertHrEmployee();
    $this->plan->update(['manager_id' => $planManagerEmployee]);
    $otherProgram = Program::factory()->create();

    ($this->plans)(userWithRole('Team Member', $planManagerEmployee))
        ->call('openEditModal', $this->plan->id)
        ->set('planProgramId', (string) $otherProgram->id)
        ->set('planManagerId', (string) insertHrEmployee())
        ->call('savePlan')
        ->assertHasNoErrors();

    expect($this->plan->fresh())
        ->program_id->toBe($this->program->id)
        ->manager_id->toBe($planManagerEmployee);
});

test('MEAL cannot create an annual plan', function () {
    ($this->plans)(userWithRole('MEAL'))->call('openCreateModal')->assertForbidden();
});

test('only annual-plans.delete may delete', function () {
    $programManagerEmployee = insertHrEmployee();
    $this->program->update(['manager_id' => $programManagerEmployee]);

    ($this->plans)(userWithRole('Team Member', $programManagerEmployee))->call('confirmDelete', $this->plan->id)->assertForbidden();
    ($this->plans)(userWithRole('Managers'))->call('confirmDelete', $this->plan->id)->call('deletePlan')->assertOk();

    expect(AnnualPlan::query()->find($this->plan->id))->toBeNull();
});
