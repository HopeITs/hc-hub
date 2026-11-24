<?php

use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Relative;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| HR-014 and HR-020 of the HR app (081-Employees): a record id comes from the
| browser and is only ever looked up among the edited employee's own rows, and
| an export runs only for someone holding its own permission, however the
| call reaches the page.
*/

beforeEach(function () {
    HrAccess::flush();

    $this->city = City::factory()->create();
    $this->hr = User::factory()->role('HR-'.strtoupper($this->city->slug))->create();
    $this->hr->givePermissionTo(Permission::findOrCreate('employees.update', 'web'));
    $this->hr = $this->hr->fresh();
    $this->edited = Employee::factory()->create(['city_id' => $this->city->id, 'username' => 'edited.person']);
});

test('a relative of another employee is never deleted through the profile', function () {
    $other = Employee::factory()->create(['city_id' => City::factory()->create()->id]);
    $foreign = Relative::query()->forceCreate(['employee_id' => $other->id, 'name' => 'Not yours', 'phone' => '0100', 'relation' => 1]);

    Livewire::actingAs($this->hr)->test('hr::employees.show', ['employee' => $this->edited])
        ->call('confirmRecordDelete', 'relative', $foreign->id)
        ->call('deleteRecord')
        ->assertNotFound();

    expect(Relative::query()->find($foreign->id))->not->toBeNull();
});

test('the edited employees own relative is still removed', function () {
    $own = Relative::query()->forceCreate(['employee_id' => $this->edited->id, 'name' => 'Sibling', 'phone' => '0100', 'relation' => 1]);

    Livewire::actingAs($this->hr)->test('hr::employees.show', ['employee' => $this->edited])
        ->call('confirmRecordDelete', 'relative', $own->id)
        ->call('deleteRecord');

    expect(Relative::query()->find($own->id))->toBeNull();
});

test('the leave balances export needs its own permission, not only the page', function () {
    $viewer = User::factory()->role('HR-'.strtoupper($this->city->slug))->create();
    $viewer->givePermissionTo(Permission::findOrCreate('leaves.manage-balances', 'web'));

    Livewire::actingAs($viewer->fresh())->test('hr::leave.balances')
        ->call('export')
        ->assertForbidden();

    $viewer->givePermissionTo(Permission::findOrCreate('leaves.export', 'web'));

    Livewire::actingAs($viewer->fresh())->test('hr::leave.balances')
        ->call('export')
        ->assertOk();
});
