<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
| One employee page (roadmap 3.5): the profile is where a person's name leads
| from the other modules, it carries the equipment HR handed them, and on
| their own profile it points to their sign-in settings.
*/

beforeEach(function () {
    HrAccess::flush();
    Employee::forgetPickerOptions();
});

test('a linked employee chip opens the active employee\'s profile', function () {
    $employee = Employee::factory()->create(['username' => 'lina.saleh', 'is_active' => true]);

    $this->blade('<x-ui.employee-chip :employee="$employee" compact link />', ['employee' => $employee->fresh()])
        ->assertSee('href="'.route('hr.employees.show', ['employee' => 'lina.saleh']).'"', false)
        ->assertSee('data-employee-link="'.$employee->id.'"', false);
});

test('a chip stays plain text for a former employee, and wherever the link was not asked for', function () {
    $former = Employee::factory()->create(['username' => 'old.hand', 'is_active' => false]);
    $active = Employee::factory()->create(['username' => 'still.here', 'is_active' => true]);

    $this->blade('<x-ui.employee-chip :employee="$employee" compact link />', ['employee' => $former->fresh()])
        ->assertDontSee('data-employee-link', false);

    $this->blade('<x-ui.employee-chip :employee="$employee" compact />', ['employee' => $active->fresh()])
        ->assertDontSee('data-employee-link', false);
});

test('a person\'s own profile points to their sign-in settings, a colleague\'s does not', function () {
    $employee = Employee::factory()->create(['username' => 'own.page', 'is_active' => true]);
    $self = User::factory()->create(['employee_id' => $employee->id])->fresh();
    $colleague = User::factory()->create(['employee_id' => Employee::factory()->create()->id])->fresh();

    $this->actingAs($self)->get(route('hr.employees.me'))
        ->assertOk()
        ->assertSee('data-test="own-security"', false)
        ->assertSee('href="'.route('security.edit').'"', false);

    $this->actingAs($colleague)->get(route('hr.employees.show', ['employee' => 'own.page']))
        ->assertOk()
        ->assertDontSee('data-test="own-security"', false);
});

test('the Equipment tab is offered to whoever may edit the employee, and to nobody else', function () {
    $employee = Employee::factory()->create(['username' => 'has.items', 'is_active' => true]);
    $self = User::factory()->create(['employee_id' => $employee->id])->fresh();
    $editor = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $editor->assignRole(Role::findOrCreate('HR-SYR', 'web'));
    $editor->givePermissionTo(Permission::findOrCreate('employees.update', 'web'));
    $editor = $editor->fresh();
    HrAccess::flush();

    $asEditor = Livewire::actingAs($editor)->withQueryParams(['tab' => 'equipment'])->test('hr::employees.show', ['employee' => $employee]);
    $asSelf = Livewire::actingAs($self)->withQueryParams(['tab' => 'equipment'])->test('hr::employees.show');

    expect($asEditor->instance()->visibleTabs())->toContain('equipment')
        ->and($asSelf->instance()->visibleTabs())->not->toContain('equipment');

    $asEditor->assertSet('tab', 'equipment')->assertSee(__('hr_checklists.employee.checklist_title'));
    $asSelf->assertSet('tab', 'overview');
});
