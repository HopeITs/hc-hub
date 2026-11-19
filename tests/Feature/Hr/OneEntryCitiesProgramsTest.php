<?php

use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Spatie\Permission\Models\Permission;

/*
| One way in to programs and to cities (roadmap 3.6): HR's copy of the
| programs list leads to the hub's list, a program page leads to HR's
| staffing view of it, and the two cities pages lead to each other for
| whoever may open both.
*/

test('HR\'s programs list leads to the one programs list, under its old name', function () {
    $user = User::factory()->role('Super Admin')->create();

    expect(route('hr.settings.programs.index', absolute: false))->toBe('/hr/programs');

    $this->actingAs($user)->get('/hr/programs')->assertRedirect('/programs')->assertStatus(301);
});

test('a program page leads to its positions and headcount', function () {
    $user = User::factory()->role('Super Admin')->create(['employee_id' => Employee::factory()->create()->id])->fresh();
    $program = Program::factory()->create();

    $this->actingAs($user)->get(route('programs.show', $program))
        ->assertOk()
        ->assertSee('data-test="program-staffing-link"', false)
        ->assertSee('href="'.route('hr.settings.programs.show', $program).'"', false);

    $this->actingAs($user)->get(route('hr.settings.programs.show', $program))->assertOk();
});

test('the cities page leads to where cities are edited only for someone who may open it', function () {
    $viewer = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $viewer->givePermissionTo(Permission::findOrCreate('cities.view', 'web'));

    $this->actingAs($viewer->fresh())->get(route('admin.cities.index'))
        ->assertOk()
        ->assertDontSee('data-test="cities-manage-link"', false);

    $viewer->givePermissionTo(Permission::findOrCreate('project-basics.view', 'web'));

    $this->actingAs($viewer->fresh())->get(route('admin.cities.index'))
        ->assertOk()
        ->assertSee('data-test="cities-manage-link"', false);
});

test('HR\'s cities tab leads to the coverage page only for someone who may open it', function () {
    $hr = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $hr->givePermissionTo(Permission::findOrCreate('project-basics.view', 'web'));

    $this->actingAs($hr->fresh())->get(route('hr.settings.index'))
        ->assertOk()
        ->assertDontSee('data-test="hr-settings-cities-coverage"', false);

    $hr->givePermissionTo(Permission::findOrCreate('cities.view', 'web'));

    $this->actingAs($hr->fresh())->get(route('hr.settings.index'))
        ->assertOk()
        ->assertSee('data-test="hr-settings-cities-coverage"', false);
});
