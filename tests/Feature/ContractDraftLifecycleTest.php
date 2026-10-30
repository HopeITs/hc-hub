<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class ContractDraftLifecycleTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_a_draft_can_be_created_on_a_position_that_is_already_at_capacity(): void
    {
        $position = $this->makePosition(maxEmp: 1, existEmp: 1);
        $employee = $this->makeEmployee($position['city'], 'draft-over-capacity');

        $draft = $this->makeDraft($employee->id, $position['position']->id);

        $this->assertNotNull($draft->id);
    }

    public function test_creating_a_draft_does_not_increment_position_headcount(): void
    {
        $position = $this->makePosition(maxEmp: 5, existEmp: 0);
        $employee = $this->makeEmployee($position['city'], 'draft-no-inc');

        $this->makeDraft($employee->id, $position['position']->id);

        $this->assertSame(0, $position['position']->fresh()->exist_emp);
    }

    public function test_upgrading_a_draft_to_a_real_type_claims_the_seat(): void
    {
        $position = $this->makePosition(maxEmp: 5, existEmp: 0);
        $employee = $this->makeEmployee($position['city'], 'draft-upgrade');
        $contractType = $this->makeContractType(1);

        $draft = $this->makeDraft($employee->id, $position['position']->id);

        $draft->update([
            'type' => ContractTypeEnum::FullTime,
            'contract_type_id' => $contractType->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'weekly_hours' => 40,
            'currency' => 1,
            'salary' => 100,
            'salary_per' => 1,
        ]);

        $this->assertSame(1, $position['position']->fresh()->exist_emp);
    }

    public function test_upgrading_a_draft_is_blocked_when_the_position_is_now_full(): void
    {
        $position = $this->makePosition(maxEmp: 1, existEmp: 1);
        $employee = $this->makeEmployee($position['city'], 'draft-upgrade-blocked');
        $contractType = $this->makeContractType(1);

        $draft = $this->makeDraft($employee->id, $position['position']->id);

        $this->expectException(ValidationException::class);

        $draft->update([
            'type' => ContractTypeEnum::FullTime,
            'contract_type_id' => $contractType->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'weekly_hours' => 40,
            'currency' => 1,
            'salary' => 100,
            'salary_per' => 1,
        ]);
    }

    public function test_updating_a_draft_that_stays_a_draft_does_not_touch_headcount(): void
    {
        $position = $this->makePosition(maxEmp: 1, existEmp: 1);
        $employee = $this->makeEmployee($position['city'], 'draft-stays-draft');

        $draft = $this->makeDraft($employee->id, $position['position']->id);
        $draft->update(['weekly_hours' => 10]);

        $this->assertSame(1, $position['position']->fresh()->exist_emp);
    }

    private function makeDraft(int $employeeId, int $positionId): Contract
    {
        $contractType = $this->makeContractType(1);

        return Contract::create([
            'employee_id' => $employeeId,
            'position_id' => $positionId,
            'contract_type_id' => $contractType->id,
            'type' => ContractTypeEnum::Draft,
            'is_active' => true,
        ]);
    }

    private function makeContractType(int $id): ContractType
    {
        return ContractType::query()->find($id)
            ?? ContractType::query()->forceCreate(['id' => $id, 'name_en' => 'Type '.$id, 'name_ar' => 'نوع '.$id]);
    }

    /** @return array{city: \App\Models\City, position: Position} */
    private function makePosition(int $maxEmp, int $existEmp): array
    {
        static $counter = 0;
        $counter++;

        $city = $this->makeCity('ALP'.$counter, '00'.$counter);

        $center = Center::query()->create([
            'name_en' => 'Hope Center '.$counter,
            'name_ar' => 'مركز الرجاء '.$counter,
            'city_id' => $city->id,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);

        $department = Department::query()->forceCreate(['name' => 'Programs '.$counter, 'code' => 'PRG'.$counter]);
        $jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Officer '.$counter, 'name_ar' => 'مسؤول '.$counter, 'grade' => 2]);
        $program = Program::query()->forceCreate(['name' => 'Core Program '.$counter, 'slug' => 'core-program-'.$counter, 'code' => 'CORE'.$counter, 'is_active' => true]);

        $position = Position::query()->forceCreate([
            'center_id' => $center->id,
            'city_id' => $city->id,
            'department_id' => $department->id,
            'job_level_id' => $jobLevel->id,
            'program_id' => $program->id,
            'title_en' => 'Program Officer '.$counter,
            'title_ar' => 'مسؤول برنامج '.$counter,
            'max_emp' => $maxEmp,
            'exist_emp' => $existEmp,
            'is_active' => true,
        ]);

        return ['city' => $city, 'position' => $position];
    }
}
