<?php

use Illuminate\Support\Once;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\DigitalId;

/*
| The ID card shows one of the employee's running contracts: the highest job
| level first, then a fixed contract before a trial, then the newest.
*/

beforeEach(function () {
    foreach ([1, 2] as $id) {
        ContractType::query()->find($id) ?? ContractType::factory()->create(['id' => $id]);
    }

    config(['contracts.map_by_id' => [1 => 'annual', 2 => 'trial']]);

    $this->employee = Employee::factory()->create();
    $this->running = fn (?int $grade, int $typeId, string $start): Contract => Contract::factory()->for($this->employee)
        ->for(Position::factory()->create(['job_level_id' => JobLevel::factory()->create(['grade' => $grade])->id]))
        ->create(['is_active' => true, 'contract_type_id' => $typeId, 'start_date' => $start, 'end_date' => now()->addYear()]);
    $this->card = function (): ?int {
        Once::flush();

        return DigitalId::cardContract($this->employee)?->id;
    };
});

test('the higher job level wins over a newer contract', function () {
    $senior = ($this->running)(2, 1, now()->subYear()->toDateString());
    ($this->running)(5, 1, now()->subMonth()->toDateString());
    ($this->running)(null, 1, now()->subWeek()->toDateString());

    expect(($this->card)())->toBe($senior->id);
});

test('on the same level a fixed contract comes before a trial, then the newest', function () {
    $fixed = ($this->running)(3, 1, now()->subYear()->toDateString());
    ($this->running)(3, 2, now()->subWeek()->toDateString());

    expect(($this->card)())->toBe($fixed->id);

    $newer = ($this->running)(3, 1, now()->subMonth()->toDateString());

    expect(($this->card)())->toBe($newer->id);
});
