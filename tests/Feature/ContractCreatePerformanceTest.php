<?php

namespace Tests\Feature;

use App\Livewire\Contract\ContractCreate;
use App\Livewire\Contract\ContractEdit;
use App\Models\Center;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class ContractCreatePerformanceTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_city_options_are_compact_scoped_and_query_bounded(): void
    {
        $fixtures = $this->makeFixtures();
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $component = Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class);

        $queries = [];

        $component
            ->call('locationOptions', $fixtures['city']->id)
            ->assertReturned(function (array $options) use ($fixtures): bool {
                $employeeIds = collect($options['employees'])->pluck('value');
                $positionIds = collect($options['positions'])->pluck('value');

                return $employeeIds->contains($fixtures['employee']->id)
                    && ! $employeeIds->contains($fixtures['remoteEmployee']->id)
                    && $positionIds->contains($fixtures['position']->id)
                    && $positionIds->contains($fixtures['centerScopedPosition']->id)
                    && collect($options['programs'])->pluck('value')->contains($fixtures['program']->id)
                    && collect($options['centers'])->pluck('value')->contains($fixtures['center']->id);
            });

        $this->assertLessThanOrEqual(12, count($queries), 'The renderless city option request exceeded its query budget.');

        $employeeQuery = collect($queries)->first(fn (string $sql): bool => str_contains($sql, 'employees'));

        $this->assertNotNull($employeeQuery);
        $this->assertStringNotContainsString('select *', strtolower($employeeQuery));
    }

    public function test_identity_selection_is_synchronized_and_validated_in_one_action(): void
    {
        $fixtures = $this->makeFixtures();

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('advanceIdentity', [
                'cityId' => (string) $fixtures['city']->id,
                'employeeId' => (string) $fixtures['employee']->id,
                'programId' => (string) $fixtures['program']->id,
                'centerId' => (string) $fixtures['center']->id,
                'positionId' => (string) $fixtures['position']->id,
                'contractTypeId' => (string) $fixtures['trialType']->id,
            ])
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->assertSet('maxStep', 2)
            ->assertSet('city_id', $fixtures['city']->id)
            ->assertSet('program_id', $fixtures['program']->id)
            ->assertSet('center_id', $fixtures['center']->id)
            ->assertSet('form.employee_id', $fixtures['employee']->id)
            ->assertSet('form.position_id', $fixtures['position']->id)
            ->assertSet('form.contract_type_id', $fixtures['trialType']->id)
            ->assertSet('form.start_date', Carbon::now()->addMonthNoOverflow()->startOfMonth()->toDateString())
            ->assertSet(
                'form.end_date',
                Carbon::now()->addMonthNoOverflow()->startOfMonth()->addMonthsNoOverflow(3)->toDateString(),
            );
    }

    public function test_tampered_city_and_center_selections_are_still_rejected_server_side(): void
    {
        $fixtures = $this->makeFixtures();

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('locationOptions', $fixtures['remoteCity']->id)
            ->assertStatus(403);

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('advanceIdentity', [
                'cityId' => $fixtures['city']->id,
                'employeeId' => $fixtures['employee']->id,
                'programId' => $fixtures['program']->id,
                'centerId' => $fixtures['otherCenter']->id,
                'positionId' => $fixtures['position']->id,
                'contractTypeId' => $fixtures['trialType']->id,
            ])
            ->assertSet('step', 1)
            ->assertHasErrors('form.position_id');
    }

    public function test_edit_uses_the_same_fast_options_without_changing_existing_dates(): void
    {
        $fixtures = $this->makeFixtures();
        $startDate = now()->subMonth()->startOfMonth()->toDateString();
        $endDate = now()->addMonths(5)->endOfMonth()->toDateString();

        $contract = Contract::query()->forceCreate([
            'employee_id' => $fixtures['employee']->id,
            'position_id' => $fixtures['position']->id,
            'contract_type_id' => $fixtures['trialType']->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'currency' => 1,
            'salary' => 100,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => true,
        ]);

        $fixtures['employee']->update(['is_active' => false]);

        $component = Livewire::actingAs($fixtures['hr'])
            ->test(ContractEdit::class, ['contract' => $contract])
            ->call('locationOptions', $fixtures['city']->id)
            ->assertReturned(fn (array $options): bool => collect($options['employees'])
                ->pluck('value')
                ->contains($fixtures['employee']->id));

        $component
            ->call('advanceIdentity', [
                'cityId' => $fixtures['city']->id,
                'employeeId' => $fixtures['employee']->id,
                'programId' => $fixtures['program']->id,
                'centerId' => $fixtures['center']->id,
                'positionId' => $fixtures['position']->id,
                'contractTypeId' => $fixtures['trialType']->id,
            ])
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->assertSet('form.start_date', $startDate)
            ->assertSet('form.end_date', $endDate);
    }

    public function test_the_optimized_identity_flow_still_creates_the_complete_contract(): void
    {
        $fixtures = $this->makeFixtures();

        Notification::fake();
        $this->mock(LeaveBalanceService::class)
            ->shouldReceive('create')
            ->once()
            ->withArgs(fn (Contract $contract): bool => $contract->employee_id === $fixtures['employee']->id);

        Livewire::actingAs($fixtures['hr'])
            ->test(ContractCreate::class)
            ->call('advanceIdentity', [
                'cityId' => $fixtures['city']->id,
                'employeeId' => $fixtures['employee']->id,
                'programId' => $fixtures['program']->id,
                'centerId' => $fixtures['center']->id,
                'positionId' => $fixtures['position']->id,
                'contractTypeId' => $fixtures['trialType']->id,
            ])
            ->set('form.salary', 500)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'employee_id' => $fixtures['employee']->id,
            'position_id' => $fixtures['position']->id,
            'contract_type_id' => $fixtures['trialType']->id,
            'salary' => 500,
            'is_active' => true,
        ]);
    }

    private function makeFixtures(): array
    {
        $city = $this->makeCity('ALP', '001');
        $remoteCity = $this->makeCity('HMS', '002');
        $center = $this->makeCenter($city, 'Hope Center');
        $otherCenter = $this->makeCenter($city, 'Other Center');

        $department = Department::query()->forceCreate(['name' => 'Programs', 'code' => 'PRG']);
        $jobLevel = JobLevel::query()->forceCreate([
            'name_en' => 'Officer',
            'name_ar' => 'مسؤول',
            'grade' => 2,
        ]);
        $program = Program::query()->forceCreate([
            'name' => 'Core Program',
            'slug' => 'core-program',
            'code' => 'CORE',
            'is_active' => true,
        ]);
        $trialType = ContractType::query()->forceCreate([
            'id' => 3,
            'name_en' => 'Trial',
            'name_ar' => 'تجريبي',
        ]);

        $position = $this->makePosition(
            $center,
            $city->id,
            $department,
            $jobLevel,
            $program,
            'Program Officer',
        );

        // Position::inCity() intentionally includes this row through its center,
        // even though the legacy direct city_id points elsewhere.
        $centerScopedPosition = $this->makePosition(
            $center,
            $remoteCity->id,
            $department,
            $jobLevel,
            $program,
            'Center Scoped Officer',
        );

        $employee = $this->makeEmployee($city, 'local-contract-employee');
        $remoteEmployee = $this->makeEmployee($remoteCity, 'remote-contract-employee');
        $hr = $this->makeUser($city, 'HR-ALP', ['contracts.create', 'contracts.update']);

        return compact(
            'city',
            'remoteCity',
            'center',
            'otherCenter',
            'program',
            'trialType',
            'position',
            'centerScopedPosition',
            'employee',
            'remoteEmployee',
            'hr',
        );
    }

    private function makeCenter($city, string $name): Center
    {
        return Center::query()->create([
            'name_en' => $name,
            'name_ar' => $name,
            'city_id' => $city->id,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);
    }

    private function makePosition(
        Center $center,
        int $cityId,
        Department $department,
        JobLevel $jobLevel,
        Program $program,
        string $title,
    ): Position {
        return Position::query()->forceCreate([
            'center_id' => $center->id,
            'city_id' => $cityId,
            'department_id' => $department->id,
            'job_level_id' => $jobLevel->id,
            'program_id' => $program->id,
            'title_en' => $title,
            'title_ar' => $title,
            'max_emp' => 2,
            'exist_emp' => 0,
            'is_active' => true,
        ]);
    }
}
