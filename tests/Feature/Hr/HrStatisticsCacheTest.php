<?php

use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Desk\HrStatistics;

/*
| HR figures are remembered ten minutes, but saving or deleting a contract or
| an employee shows in the next read (HrStatistics::flush through
| HrStatisticsObserver). A write that fires no model events waits out the ten
| minutes.
*/

beforeEach(function () {
    $this->employeeId = insertHrEmployee();
    $this->contractId = insertHrContract($this->employeeId, insertHrPosition(insertHrDepartment()));
    Contract::query()->whereKey($this->contractId)->update(['end_date' => now()->addYear()]);
});

test('the figures are remembered while nothing is saved through the models', function () {
    $statistics = app(HrStatistics::class);

    expect($statistics->totals(null)['active'])->toBe(1);

    DB::table('employees')->where('id', $this->employeeId)->update(['is_active' => false]);

    expect($statistics->totals(null)['active'])->toBe(1);
});

test('editing an employee or a contract changes the figures at once', function () {
    $statistics = app(HrStatistics::class);

    expect($statistics->totals(null))->toMatchArray(['employees' => 1, 'active' => 1, 'contracts' => 1]);

    Employee::query()->findOrFail($this->employeeId)->update(['is_active' => false]);

    expect($statistics->totals(null)['active'])->toBe(0);

    Contract::query()->findOrFail($this->contractId)->update(['is_active' => false]);

    expect($statistics->totals(null)['contracts'])->toBe(0);

    Employee::query()->findOrFail(insertHrEmployee(firstNameEn: 'Lina'))->delete();
    insertHrEmployee(firstNameEn: 'Omar');

    expect($statistics->totals(null)['employees'])->toBe(2);
});
