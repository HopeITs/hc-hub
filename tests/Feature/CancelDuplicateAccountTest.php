<?php

use App\Models\Role;
use App\Models\UserTodo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/*
| Roadmap 1.7: one login per employee. users.employee_id is unique, and
| hub:cancel-duplicate-account cancels the spare login a person chose, keeping
| the union of both logins' roles on the other one.
*/

/**
 * Two logins of one employee, as they were before the unique index existed.
 *
 * @return array{keep: User, cancel: User}
 */
function twoLoginsOfOneEmployee(): array
{
    allowTwoLoginsPerEmployee();
    $employee = Employee::factory()->create();
    $keep = User::factory()->create(['employee_id' => $employee->id]);
    $cancel = User::factory()->create(['employee_id' => $employee->id]);

    return ['keep' => $keep, 'cancel' => $cancel];
}

test('the spare login is cancelled and the kept one holds both logins roles', function () {
    ['keep' => $keep, 'cancel' => $cancel] = twoLoginsOfOneEmployee();
    $keep->assignRole(Role::query()->create(['name' => 'super-admin', 'guard_name' => 'web']));
    $cancel->assignRole(Role::query()->create(['name' => 'employee', 'guard_name' => 'web']));

    expect(Artisan::call('hub:cancel-duplicate-account', ['cancel' => $cancel->id, '--keep' => $keep->id, '--force' => true]))->toBe(0);

    expect(User::query()->find($cancel->id))->toBeNull()
        ->and($keep->refresh()->roles->pluck('name')->sort()->values()->all())->toBe(['employee', 'super-admin'])
        ->and(DB::table('model_has_roles')->where('model_id', $cancel->id)->count())->toBe(0);
});

test('without --force nothing changes', function () {
    ['keep' => $keep, 'cancel' => $cancel] = twoLoginsOfOneEmployee();

    expect(Artisan::call('hub:cancel-duplicate-account', ['cancel' => $cancel->id, '--keep' => $keep->id]))->toBe(0)
        ->and(Artisan::output())->toContain('Dry run');

    expect(User::query()->whereKey([$keep->id, $cancel->id])->count())->toBe(2);
});

test('a spare login that still carries rows is not cancelled', function () {
    ['keep' => $keep, 'cancel' => $cancel] = twoLoginsOfOneEmployee();
    UserTodo::factory()->for($cancel)->create();

    expect(Artisan::call('hub:cancel-duplicate-account', ['cancel' => $cancel->id, '--keep' => $keep->id, '--force' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('user_todos.user_id: 1');

    expect(User::query()->find($cancel->id))->not->toBeNull();
});

test('logins of two different employees are refused', function () {
    $first = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $second = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);

    expect(Artisan::call('hub:cancel-duplicate-account', ['cancel' => $second->id, '--keep' => $first->id, '--force' => true]))->toBe(1);

    expect(User::query()->whereKey([$first->id, $second->id])->count())->toBe(2);
});

test('the database refuses a second login for the same employee', function () {
    $employee = Employee::factory()->create();
    User::factory()->create(['employee_id' => $employee->id]);

    expect(fn () => User::factory()->create(['employee_id' => $employee->id]))
        ->toThrow(UniqueConstraintViolationException::class);
});
