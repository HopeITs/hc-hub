<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Employee;
use Modules\Hr\Notifications\DigitalIdReportedLost;
use Modules\Hr\Notifications\NewEmployeeWelcome;

/*
 * employees.username is optional (decided 2026-10-30): any number of
 * employees may have none, a set one stays unique, and a blank one is stored
 * as NULL so nothing can be looked up by an empty username.
 */

test('many employees may have no username, but a set one stays unique', function () {
    Employee::factory()->count(2)->create(['username' => null]);
    Employee::factory()->create(['username' => 'lina.haddad']);

    expect(Employee::query()->whereNull('username')->count())->toBe(2)
        ->and(fn () => Employee::factory()->create(['username' => 'lina.haddad']))->toThrow(QueryException::class);
});

test('a blank username is stored as NULL', function () {
    $employee = Employee::factory()->create(['username' => '   ']);

    expect(DB::table('employees')->where('id', $employee->id)->value('username'))->toBeNull()
        ->and(Employee::factory()->create(['username' => ' sami ']))->username->toBe('sami');
});

test('notifications about an employee without a username carry no profile link', function (string $notification) {
    $employee = Employee::factory()->create(['username' => null]);

    expect((new $notification($employee))->toArray($employee)['url'])->toBeNull();
})->with([NewEmployeeWelcome::class, DigitalIdReportedLost::class]);
