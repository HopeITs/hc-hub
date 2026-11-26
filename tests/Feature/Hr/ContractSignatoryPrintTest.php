<?php

use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Http\Controllers\Contracts\ContractLegalPrintController;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;

/*
| The first party's signatory on the printed legal contract comes from
| config('contracts.signatory'), never from the views.
*/

beforeEach(function () {
    $this->travelTo('2026-11-17 10:00:00');

    $city = City::factory()->create(['name_en' => 'Homs'])->id;
    $program = Program::factory()->unassigned()->create(['name' => 'Micro Project']);
    $position = Position::factory()->create(['program_id' => $program->id, 'city_id' => $city, 'center_id' => null, 'max_emp' => 5, 'exist_emp' => 0]);

    $this->render = function (int $typeId) use ($city, $position): string {
        ContractType::query()->find($typeId) ?? ContractType::factory()->create(['id' => $typeId]);

        $contract = Contract::factory()
            ->for(Employee::factory()->create(['city_id' => $city]))
            ->for($position)
            ->create([
                'contract_type_id' => $typeId, 'start_date' => '2026-11-01', 'end_date' => '2027-10-31',
                'weekly_hours' => 40, 'schedule_mode' => ScheduleMode::Fixed->value,
                'type' => ContractTypeEnum::FullTime->value, 'place' => ContractPlace::Onsite->value,
                'work_hours_from' => '08:00', 'work_hours_to' => '16:00',
                'pay_mode' => PayMode::Fixed->value, 'currency' => Currency::USD->value, 'salary' => 500, 'salary_per' => SalaryPer::Month->value,
            ]);

        return app(ContractLegalPrintController::class)($contract)->render();
    };
});

test('the signature block and the represented-by line print the configured signatory', function (int $typeId) {
    config()->set('contracts.signatory', [
        'name_en' => 'Test Signer Name',
        'name_ar' => 'اسم موقع تجريبي',
        'representative_en' => 'Mr. Test Rep',
        'representative_ar' => 'السيد ممثل تجريبي',
    ]);

    $html = ($this->render)($typeId);

    expect($html)->toContain('Test Signer Name')
        ->toContain('Mr. Test Rep');
})->with([1, 3, 5]);

test('an unset signatory still renders the contract with a blank name', function () {
    config()->set('contracts.signatory', ['name_en' => '', 'name_ar' => '', 'representative_en' => '', 'representative_ar' => '']);

    expect(($this->render)(1))->toContain('signature-party-first');
});
