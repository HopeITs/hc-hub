<?php

use Illuminate\Support\Once;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentHiringConversionService;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Support\Recruitment\OnboardingChecklist;

/*
| After a hire, the convert page lists what is done and what is left: the
| employee, the contract, any older contract still running, a login account,
| the ID card and the welcome message. A transfer to another program warns
| that the old printed card stops working.
*/

beforeEach(function () {
    $this->employee = Employee::factory()->create(['city_id' => City::factory()->create(['code' => '7'])->id]);
    $this->programA = Program::factory()->create(['code' => '11']);
    $this->programB = Program::factory()->create(['code' => '12']);

    $this->contractIn = fn (Program $program, string $start): Contract => Contract::factory()->for($this->employee)
        ->for(Position::factory()->create(['program_id' => $program->id]))
        ->create(['is_active' => true, 'start_date' => $start, 'end_date' => now()->addYear()]);

    $this->stepsFor = function (Contract $contract, string $mode): array {
        Once::flush();

        $conversion = (new RecruitmentHiringConversion)->forceFill([
            'status' => RecruitmentHiringConversion::STATUS_CONVERTED,
            'employee_id' => $this->employee->id,
            'contract_id' => $contract->id,
            'employee_payload_json' => ['mode' => $mode],
        ])->setRelation('employee', $this->employee->fresh());

        return collect(app(OnboardingChecklist::class)->for($conversion, null))->keyBy('key')->all();
    };
});

test('a new hire without an account is left the account and the welcome message', function () {
    $contract = ($this->contractIn)($this->programA, now()->toDateString());

    $steps = ($this->stepsFor)($contract, RecruitmentHiringConversionService::MODE_CREATE);

    expect(array_keys($steps))->toBe(['employee', 'contract', 'account', 'card', 'welcome'])
        ->and($steps['contract']['done'])->toBeTrue()
        ->and($steps['account']['done'])->toBeFalse()
        ->and($steps['card']['done'])->toBeTrue();

    User::factory()->create(['employee_id' => $this->employee->id]);

    expect(($this->stepsFor)($contract, RecruitmentHiringConversionService::MODE_CREATE)['account']['done'])->toBeTrue();
});

test('a transfer to another program lists the old contract and warns about the printed card', function () {
    $old = ($this->contractIn)($this->programA, now()->subYear()->toDateString());
    $new = ($this->contractIn)($this->programB, now()->toDateString());

    $steps = ($this->stepsFor)($new, RecruitmentHiringConversionService::MODE_LINK);

    expect(array_keys($steps))->toBe(['employee_existing', 'contract', 'previous', 'account', 'card', 'announce'])
        ->and($steps['previous']['url'])->toBe(route('hr.contracts.show', ['contract' => $old->id]))
        ->and($steps['card']['done'])->toBeFalse()
        ->and($steps['card']['note'])->toContain($this->programA->name)->toContain($this->programB->name);
});
