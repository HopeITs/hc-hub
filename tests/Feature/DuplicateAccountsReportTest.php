<?php

use App\Models\UserTodo;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/*
| hub:duplicate-accounts lists employees linked to more than one login and
| what each login carries, so a person can pick the one to cancel. It reads
| only.
*/

test('it lists both logins of an employee with the rows that point at each', function () {
    allowTwoLoginsPerEmployee();
    $employee = Employee::factory()->create();
    $kept = User::factory()->create(['employee_id' => $employee->id, 'email' => 'kept@hc.test']);
    User::factory()->create(['employee_id' => $employee->id, 'email' => 'spare@hc.test']);
    UserTodo::factory()->for($kept)->create();

    expect(Artisan::call('hub:duplicate-accounts'))->toBe(0);
    $rows = collect(explode("\n", Artisan::output()));

    expect($rows->first(fn (string $row): bool => str_contains($row, "Employee #{$employee->id} has 2 logins")))->not->toBeNull()
        ->and($rows->first(fn (string $row): bool => str_contains($row, 'kept@hc.test')))->toContain('user_todos.user_id: 1')
        ->and($rows->first(fn (string $row): bool => str_contains($row, 'spare@hc.test')))->toContain('none');

    expect(User::query()->where('employee_id', $employee->id)->count())->toBe(2)
        ->and(UserTodo::query()->where('user_id', $kept->id)->count())->toBe(1);
});

test('it says so when every employee has at most one login', function () {
    User::factory()->create(['employee_id' => Employee::factory()->create()->id]);

    expect(Artisan::call('hub:duplicate-accounts'))->toBe(0)
        ->and(Artisan::output())->toContain('No employee is linked to more than one login');
});
