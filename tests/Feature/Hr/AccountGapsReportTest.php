<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/*
| hub:account-gaps lists employees with no login, classified against the rules
| ActivateEmployeeForContract follows when it makes a login, and logins with
| no employee. It reads only.
*/

/**
 * The output line of one employee (or user) id in the table.
 */
function accountGapRow(string $output, int $id): ?string
{
    return collect(explode("\n", $output))
        ->first(fn (string $row): bool => preg_match('/^\|\s*'.$id.'\s*\|/', $row) === 1);
}

test('an employee whose only contract is a draft is classed as draft', function () {
    $employee = Employee::factory()->create(['username' => 'drafty']);
    Contract::factory()->for($employee)->create(['type' => ContractType::Draft->value]);

    expect(Artisan::call('hub:account-gaps'))->toBe(0);
    $output = Artisan::output();

    expect(accountGapRow($output, $employee->id))->toContain('| draft ')->toContain('no real contract');
});

test('a draft employee record is classed as draft', function () {
    $employee = Employee::factory()->create(['username' => 'drafty', 'record_status' => Employee::RECORD_DRAFT]);
    Contract::factory()->for($employee)->create();

    Artisan::call('hub:account-gaps');
    $output = Artisan::output();

    expect(accountGapRow($output, $employee->id))->toContain('| draft ')->toContain('draft record');
});

test('an employee with an active contract and no username is a silent failure', function () {
    $employee = Employee::factory()->create(['username' => '  ']);
    Contract::factory()->for($employee)->create();

    Artisan::call('hub:account-gaps');
    $output = Artisan::output();

    expect(accountGapRow($output, $employee->id))->toContain('| username empty ');
});

test('an employee whose work email another login holds is a silent failure', function () {
    $employee = Employee::factory()->create(['username' => 'Taken']);
    Contract::factory()->for($employee)->create();
    User::factory()->create(['email' => 'taken@hcsyria.org']);

    Artisan::call('hub:account-gaps');
    $output = Artisan::output();

    expect(accountGapRow($output, $employee->id))->toContain('| email taken ');
});

test('a former employee whose real contract ended is classed by username, not as a draft', function () {
    $employee = Employee::factory()->inactive()->create(['username' => '']);
    Contract::factory()->for($employee)->create(['is_active' => false]);

    Artisan::call('hub:account-gaps');
    $output = Artisan::output();

    expect(accountGapRow($output, $employee->id))->toContain('| username empty ')->toContain('contract since ended');
});

test('an employee activation would have given a login is not explained, and a bad username is flagged', function () {
    $clean = Employee::factory()->create(['username' => 'clean']);
    Contract::factory()->for($clean)->create();
    $spaced = Employee::factory()->create(['username' => 'two words']);
    Contract::factory()->for($spaced)->create();

    Artisan::call('hub:account-gaps');
    $output = Artisan::output();

    expect(accountGapRow($output, $clean->id))->toContain('| not explained ')->not->toContain('invalid username')
        ->and(accountGapRow($output, $spaced->id))->toContain('| not explained ')->toContain('invalid username')
        ->and($output)->toMatch('/invalid username\s+1\s+\('.$spaced->id.'\)/');
});

test('an employee with a login is not listed, and a login with no employee is', function () {
    $linked = User::factory()->create();
    Contract::factory()->for($linked->employee)->create();
    $system = User::factory()->withoutEmployee()->create(['email' => 'robot@hc.test']);

    Artisan::call('hub:account-gaps');
    $output = Artisan::output();

    expect($output)->toContain('Every employee has a login.')
        ->and(accountGapRow($output, $system->id))->toContain('robot@hc.test')
        ->and(User::query()->count())->toBe(2);
});
