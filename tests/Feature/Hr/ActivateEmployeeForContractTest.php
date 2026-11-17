<?php

use Modules\Core\Models\User;
use Modules\Hr\Actions\Contracts\ActivateEmployeeForContract;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/*
| Characterization tests for account activation (roadmap 2.1, D13): a real,
| active contract activates its employee and gives them a login named after
| their username. A taken work email, a draft contract or a draft employee
| create nothing.
*/

test('an active contract activates its employee and creates their login', function () {
    $employee = Employee::factory()->create(['is_active' => false, 'username' => 'Rana.Haddad']);
    $contract = Contract::factory()->for($employee)->create(['type' => ContractTypeEnum::FullTime->value, 'is_active' => true]);

    expect(app(ActivateEmployeeForContract::class)->handle($contract))->toBe(ActivateEmployeeForContract::ACCOUNT_CREATED);

    $employee->refresh();
    $user = User::query()->where('employee_id', $employee->id)->sole();

    expect($employee->is_active)->toBeTrue()
        ->and($employee->digital_id_token)->not->toBeNull()
        ->and($user->email)->toBe('rana.haddad@hcsyria.org');
});

test('running it again for the same employee creates no second login', function () {
    $employee = Employee::factory()->create(['is_active' => false, 'username' => 'sami']);
    $contract = Contract::factory()->for($employee)->create(['type' => ContractTypeEnum::FullTime->value, 'is_active' => true]);

    app(ActivateEmployeeForContract::class)->handle($contract);

    expect(app(ActivateEmployeeForContract::class)->handle($contract))->toBe(ActivateEmployeeForContract::NONE)
        ->and(User::query()->where('employee_id', $employee->id)->count())->toBe(1);
});

test('a work email already in use creates no login but still activates the employee', function () {
    User::factory()->create(['email' => 'sami@hcsyria.org']);
    $employee = Employee::factory()->create(['is_active' => false, 'username' => 'sami']);
    $contract = Contract::factory()->for($employee)->create(['type' => ContractTypeEnum::FullTime->value, 'is_active' => true]);

    expect(app(ActivateEmployeeForContract::class)->handle($contract))->toBe(ActivateEmployeeForContract::EMAIL_TAKEN)
        ->and(User::query()->where('employee_id', $employee->id)->exists())->toBeFalse()
        ->and($employee->fresh()->is_active)->toBeTrue();
});

test('a draft contract or an inactive contract changes nothing', function (array $contractState) {
    $employee = Employee::factory()->create(['is_active' => false, 'username' => 'lina']);
    $contract = Contract::factory()->for($employee)->create($contractState);

    expect(app(ActivateEmployeeForContract::class)->handle($contract))->toBe(ActivateEmployeeForContract::NONE)
        ->and($employee->fresh()->is_active)->toBeFalse()
        ->and(User::query()->where('employee_id', $employee->id)->exists())->toBeFalse();
})->with([
    'draft contract' => [['type' => ContractTypeEnum::Draft->value, 'is_active' => true]],
    'inactive contract' => [['type' => ContractTypeEnum::FullTime->value, 'is_active' => false]],
]);

test('an employee whose record is still a draft is not activated', function () {
    $employee = Employee::factory()->create(['is_active' => false, 'username' => 'omar']);
    $employee->forceFill(['record_status' => Employee::RECORD_DRAFT])->save();
    $contract = Contract::factory()->for($employee)->create(['type' => ContractTypeEnum::FullTime->value, 'is_active' => true]);

    expect(app(ActivateEmployeeForContract::class)->handle($contract))->toBe(ActivateEmployeeForContract::NONE)
        ->and($employee->fresh()->is_active)->toBeFalse();
});
