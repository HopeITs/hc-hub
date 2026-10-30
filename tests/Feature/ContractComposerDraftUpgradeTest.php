<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Livewire\Contract\ContractCreate;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Services\LeaveBalanceService;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class ContractComposerDraftUpgradeTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_draft_contract_for_finds_the_employees_latest_draft(): void
    {
        $fixtures = $this->makeFixtures();
        $draft = $this->makeDraft($fixtures);

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('draftContractFor', $fixtures['employee']->id)
            ->assertReturned([
                'draftContractId' => $draft->id,
                'positionId' => $fixtures['position']->id,
                'programId' => $fixtures['program']->id,
                'centerId' => $fixtures['center']->id,
                'cityId' => $fixtures['city']->id,
            ]);
    }

    public function test_draft_contract_for_returns_null_when_the_latest_contract_is_not_a_draft(): void
    {
        $fixtures = $this->makeFixtures();
        $this->makeRealContract($fixtures);

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('draftContractFor', $fixtures['employee']->id)
            ->assertReturned(null);
    }

    public function test_draft_contract_for_returns_null_for_an_employee_outside_the_users_city(): void
    {
        $fixtures = $this->makeFixtures();
        $otherCity = $this->makeCity('HMS', '002');
        $outsideEmployee = $this->makeEmployee($otherCity, 'outside-city-draft-employee');

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('draftContractFor', $outsideEmployee->id)
            ->assertReturned(null);
    }

    public function test_submitting_with_a_valid_draft_id_upgrades_the_same_row(): void
    {
        $fixtures = $this->makeFixtures();
        $draft = $this->makeDraft($fixtures);
        $this->ignoreLeaveBalances();

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('submit', $this->fullPayload($fixtures, $draft->id))
            ->assertHasNoErrors();

        $this->assertSame(1, Contract::query()->count());

        $draft->refresh();
        $this->assertSame(ContractTypeEnum::FullTime, (int) $draft->type);
        $this->assertNotNull($draft->start_date);
        $this->assertSame(40, $draft->weekly_hours);
    }

    public function test_upgrading_a_draft_claims_the_position_seat(): void
    {
        $fixtures = $this->makeFixtures();
        $draft = $this->makeDraft($fixtures);
        $this->ignoreLeaveBalances();

        $this->assertSame(0, $fixtures['position']->fresh()->exist_emp);

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('submit', $this->fullPayload($fixtures, $draft->id))
            ->assertHasNoErrors();

        $this->assertSame(1, $fixtures['position']->fresh()->exist_emp);
    }

    public function test_a_forged_draft_id_for_a_different_employee_is_ignored_and_a_new_contract_is_created(): void
    {
        $fixtures = $this->makeFixtures();
        $otherEmployee = $this->makeEmployee($fixtures['city'], 'other-employee-draft-forge');
        $someoneElsesDraft = Contract::query()->forceCreate([
            'employee_id' => $otherEmployee->id,
            'position_id' => $fixtures['position']->id,
            'contract_type_id' => $fixtures['annualType']->id,
            'type' => ContractTypeEnum::Draft,
            'is_active' => true,
        ]);
        $this->ignoreLeaveBalances();

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('submit', $this->fullPayload($fixtures, $someoneElsesDraft->id))
            ->assertHasNoErrors();

        // The forged id belongs to a different employee, so it must be
        // ignored: a brand-new contract is inserted, and the other
        // employee's draft is left completely untouched.
        $this->assertSame(2, Contract::query()->count());
        $this->assertSame(ContractTypeEnum::Draft, (int) $someoneElsesDraft->fresh()->type);
    }

    private function fullPayload(array $fixtures, ?int $draftContractId): array
    {
        return [
            'cityId' => $fixtures['city']->id,
            'employeeId' => $fixtures['employee']->id,
            'programId' => $fixtures['program']->id,
            'centerId' => $fixtures['center']->id,
            'positionId' => $fixtures['position']->id,
            'draftContractId' => $draftContractId,
            'contractTypeId' => $fixtures['annualType']->id,
            'type' => ContractTypeEnum::FullTime,
            'place' => 1,
            'startDate' => now()->addMonth()->startOfMonth()->toDateString(),
            'endDate' => now()->endOfYear()->toDateString(),
            'workHoursFrom' => '09:00',
            'workHoursTo' => '17:00',
            'weeklyHours' => 40,
            'currency' => 1,
            'salary' => 100,
            'salaryPer' => 1,
        ];
    }

    private function ignoreLeaveBalances(): void
    {
        $this->mock(LeaveBalanceService::class)->shouldReceive('create')->andReturnNull();
    }

    private function makeDraft(array $fixtures): Contract
    {
        return Contract::query()->forceCreate([
            'employee_id' => $fixtures['employee']->id,
            'position_id' => $fixtures['position']->id,
            'contract_type_id' => $fixtures['annualType']->id,
            'type' => ContractTypeEnum::Draft,
            'is_active' => true,
        ]);
    }

    private function makeRealContract(array $fixtures): Contract
    {
        return Contract::query()->forceCreate([
            'employee_id' => $fixtures['employee']->id,
            'position_id' => $fixtures['position']->id,
            'contract_type_id' => $fixtures['annualType']->id,
            'type' => ContractTypeEnum::FullTime,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'weekly_hours' => 40,
            'currency' => 1,
            'salary_per' => 1,
            'is_active' => true,
        ]);
    }

    private function makeFixtures(): array
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

        $employee = $this->makeEmployee($city, 'draft-upgrade-test-employee');

        $annualType = ContractType::query()->forceCreate(['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);

        $hr = $this->makeUser($city, 'HR-ALP', ['hr.contracts.create']);

        return compact('city', 'center', 'department', 'jobLevel', 'program', 'position', 'employee', 'annualType', 'hr');
    }
}
