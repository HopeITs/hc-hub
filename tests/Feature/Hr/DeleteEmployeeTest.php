<?php

use Modules\Core\Models\User;
use Modules\Hr\Actions\Employees\DeleteEmployee;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Spatie\Permission\Models\Permission;

/*
| Deleting an employee cascades away their contracts, relatives, journeys and
| notes, and a login left with no employee becomes an always-active system
| account. So only a record with neither a contract nor a login is deleted;
| anyone else is deactivated instead.
*/

beforeEach(function () {
    $this->actingAs(User::factory()->create()->givePermissionTo(Permission::findOrCreate('employees.delete'))->refresh());
});

test('an employee with a contract is kept with the contract', function () {
    $employee = Employee::factory()->create();
    $contract = Contract::factory()->for($employee)->create();

    expect(app(DeleteEmployee::class)->handle($employee))->toBeFalse()
        ->and(Employee::query()->whereKey($employee->id)->exists())->toBeTrue()
        ->and(Contract::query()->whereKey($contract->id)->exists())->toBeTrue();
});

test('an employee with a login is kept and the login stays linked to them', function () {
    $employee = Employee::factory()->create();
    $login = User::factory()->create(['employee_id' => $employee->id]);

    expect(app(DeleteEmployee::class)->handle($employee))->toBeFalse()
        ->and(Employee::query()->whereKey($employee->id)->exists())->toBeTrue()
        ->and($login->fresh()->employee_id)->toBe($employee->id);
});

test('an employee with neither a contract nor a login is deleted', function () {
    $employee = Employee::factory()->create();

    expect(app(DeleteEmployee::class)->handle($employee))->toBeTrue()
        ->and(Employee::query()->whereKey($employee->id)->exists())->toBeFalse();
});
