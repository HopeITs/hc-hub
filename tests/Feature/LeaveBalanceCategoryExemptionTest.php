<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\AnnualLeaveRule;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\Position;
use Modules\Hr\Services\LeaveBalanceService;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class LeaveBalanceCategoryExemptionTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_a_draft_contract_gets_no_leave_balance_at_all(): void
    {
        $contract = $this->makeContract(type: ContractTypeEnum::Draft, contractTypeId: 1); // 'annual' category

        app(LeaveBalanceService::class)->create($contract);

        $this->assertSame(0, LeaveBalance::where('contract_id', $contract->id)->count());
    }

    public function test_a_service_category_contract_gets_no_leave_balance_not_even_annual(): void
    {
        AnnualLeaveRule::create(['days' => 12, 'day_per_month' => 1, 'years_of_volunteering' => 0]);

        $contract = $this->makeContract(type: ContractTypeEnum::FullTime, contractTypeId: 5); // 'service' category

        app(LeaveBalanceService::class)->create($contract);

        $this->assertSame(0, LeaveBalance::where('contract_id', $contract->id)->count());
    }

    public function test_update_on_a_draft_contract_is_a_safe_no_op(): void
    {
        $contract = $this->makeContract(type: ContractTypeEnum::Draft, contractTypeId: 1);

        app(LeaveBalanceService::class)->update($contract);

        $this->assertSame(0, LeaveBalance::where('contract_id', $contract->id)->count());
    }

    public function test_update_on_a_service_category_contract_is_a_safe_no_op(): void
    {
        $contract = $this->makeContract(type: ContractTypeEnum::FullTime, contractTypeId: 5);

        app(LeaveBalanceService::class)->update($contract);

        $this->assertSame(0, LeaveBalance::where('contract_id', $contract->id)->count());
    }

    private function makeContract(int $type, int $contractTypeId): Contract
    {
        $city = $this->makeCity('ALP', '001');

        $center = Center::query()->create([
            'name_en' => 'Hope Center',
            'name_ar' => 'مركز الرجاء',
            'city_id' => $city->id,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);

        $department = Department::query()->forceCreate(['name' => 'Programs', 'code' => 'PRG']);
        $jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Officer', 'name_ar' => 'مسؤول', 'grade' => 2]);
        $program = Program::query()->forceCreate(['name' => 'Core Program', 'slug' => 'core-program', 'code' => 'CORE', 'is_active' => true]);

        $position = Position::query()->forceCreate([
            'center_id' => $center->id,
            'city_id' => $city->id,
            'department_id' => $department->id,
            'job_level_id' => $jobLevel->id,
            'program_id' => $program->id,
            'title_en' => 'Program Officer',
            'title_ar' => 'مسؤول برنامج',
            'max_emp' => 5,
            'exist_emp' => 0,
            'is_active' => true,
        ]);

        $employee = $this->makeEmployee($city, 'leave-exempt-test');

        $contractType = ContractType::query()->find($contractTypeId)
            ?? ContractType::query()->forceCreate(['id' => $contractTypeId, 'name_en' => 'Type '.$contractTypeId, 'name_ar' => 'نوع '.$contractTypeId]);

        return Contract::create([
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'contract_type_id' => $contractType->id,
            'type' => $type,
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'weekly_hours' => 40,
            'currency' => 1,
            'salary_per' => 1,
        ]);
    }
}
