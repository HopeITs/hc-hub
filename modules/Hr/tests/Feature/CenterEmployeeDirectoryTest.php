<?php

namespace Modules\Hr\Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Hr\Livewire\ProgramManagement\Center\CenterDetails;
use Modules\Hr\Livewire\ProgramManagement\Center\CenterEmployees;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class CenterEmployeeDirectoryTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_center_total_and_directory_count_each_employee_once_while_preserving_assignments(): void
    {
        $fixtures = $this->makeDirectoryFixtures();
        $hr = $this->makeUser(
            $fixtures['city'],
            'HR-ALP',
            ['hr.centers.view', 'hr.employees.view', 'hr.positions.view'],
        );

        $parent = Livewire::actingAs($hr)
            ->test(CenterDetails::class, ['center' => $fixtures['center']])
            ->assertViewHas('totalEmployees', 2)
            ->assertViewHas('totalAssignments', 3)
            ->assertSee(__('general.center-employees'))
            ->assertSee(__('general.active-assignments'));

        $directory = Livewire::actingAs($hr)
            ->test(CenterEmployees::class, ['centerId' => $fixtures['center']->id])
            ->assertViewHas('totalEmployees', 2)
            ->assertViewHas('employees', function ($employees) use ($fixtures): bool {
                return $employees->total() === 2
                    && $employees->pluck('id')->sort()->values()->all() === collect([
                        $fixtures['multiAssignmentEmployee']->id,
                        $fixtures['singleAssignmentEmployee']->id,
                    ])->sort()->values()->all();
            });

        $this->assertSame(
            $parent->viewData('totalEmployees'),
            $directory->viewData('employees')->total(),
        );

        $multiAssignmentEmployee = $directory->viewData('employees')
            ->firstWhere('id', $fixtures['multiAssignmentEmployee']->id);

        $this->assertCount(2, $multiAssignmentEmployee->allContracts);
    }

    public function test_directory_excludes_expired_inactive_and_other_center_assignments(): void
    {
        $fixtures = $this->makeDirectoryFixtures();
        $hr = $this->makeUser($fixtures['city'], 'HR-ALP', ['hr.centers.view', 'hr.employees.view']);

        Livewire::actingAs($hr)
            ->test(CenterEmployees::class, ['centerId' => $fixtures['center']->id])
            ->assertViewHas('employees', function ($employees) use ($fixtures): bool {
                $ids = $employees->pluck('id');

                return ! $ids->contains($fixtures['expiredEmployee']->id)
                    && ! $ids->contains($fixtures['inactiveContractEmployee']->id)
                    && ! $ids->contains($fixtures['otherCenterEmployee']->id);
            });
    }

    public function test_directory_searches_localized_names_positions_and_programs(): void
    {
        $fixtures = $this->makeDirectoryFixtures();
        $hr = $this->makeUser($fixtures['city'], 'HR-ALP', ['hr.centers.view', 'hr.employees.view']);

        $component = Livewire::actingAs($hr)
            ->test(CenterEmployees::class, ['centerId' => $fixtures['center']->id]);

        $component->set('search', 'ليلى')
            ->assertViewHas('employees', fn ($employees): bool => $employees->total() === 1
                && $employees->first()->is($fixtures['singleAssignmentEmployee']));

        $component->set('search', 'Coordinator')
            ->assertViewHas('employees', fn ($employees): bool => $employees->total() === 1
                && $employees->first()->is($fixtures['multiAssignmentEmployee']));

        $component->set('search', 'Outreach')
            ->assertViewHas('employees', fn ($employees): bool => $employees->total() === 1
                && $employees->first()->is($fixtures['multiAssignmentEmployee']));

    }

    public function test_employee_directory_requires_both_center_and_employee_access(): void
    {
        $fixtures = $this->makeDirectoryFixtures();

        $centerOnlyUser = $this->makeUser($fixtures['city'], 'manager', ['hr.centers.view']);
        Livewire::actingAs($centerOnlyUser)
            ->test(CenterEmployees::class, ['centerId' => $fixtures['center']->id])
            ->assertStatus(403);

        $otherCity = $this->makeCity('HMS', '002');
        $remoteUser = $this->makeUser($otherCity, 'HR-HMS', ['hr.centers.view', 'hr.employees.view']);
        Livewire::actingAs($remoteUser)
            ->test(CenterEmployees::class, ['centerId' => $fixtures['center']->id])
            ->assertStatus(403);
    }

    private function makeDirectoryFixtures(): array
    {
        $city = $this->makeCity('ALP', '001');
        $center = $this->makeCenter($city, 'Hope Center');
        $otherCenter = $this->makeCenter($city, 'Other Center');

        $department = Department::query()->forceCreate(['name' => 'Programs', 'code' => 'PRG']);
        $jobLevel = JobLevel::query()->forceCreate([
            'name_en' => 'Officer',
            'name_ar' => 'مسؤول',
            'grade' => 2,
        ]);
        $coreProgram = Program::query()->forceCreate([
            'name' => 'Core Program',
            'slug' => 'core-program',
            'code' => 'CORE',
            'is_active' => true,
        ]);
        $outreachProgram = Program::query()->forceCreate([
            'name' => 'Outreach Program',
            'slug' => 'outreach-program',
            'code' => 'OUT',
            'is_active' => true,
        ]);
        $annualType = ContractType::query()->forceCreate([
            'name_en' => 'Full Annual',
            'name_ar' => 'سنوي كامل',
        ]);
        $serviceType = ContractType::query()->forceCreate([
            'name_en' => 'Service Agreement',
            'name_ar' => 'اتفاق خدمة',
        ]);

        $officerPosition = $this->makePosition(
            $center,
            $city->id,
            $department,
            $jobLevel,
            $coreProgram,
            'Program Officer',
        );
        $coordinatorPosition = $this->makePosition(
            $center,
            $city->id,
            $department,
            $jobLevel,
            $outreachProgram,
            'Outreach Coordinator',
        );
        $otherCenterPosition = $this->makePosition(
            $otherCenter,
            $city->id,
            $department,
            $jobLevel,
            $coreProgram,
            'Remote Officer',
        );

        $multiAssignmentEmployee = $this->makeEmployee($city, 'youssef.multi', [
            'first_name_en' => 'Youssef',
            'first_name_ar' => 'يوسف',
            'last_name_en' => 'Kharbout',
            'last_name_ar' => 'خربوط',
        ]);
        $singleAssignmentEmployee = $this->makeEmployee($city, 'layla.single', [
            'first_name_en' => 'Layla',
            'first_name_ar' => 'ليلى',
            'last_name_en' => 'Hope',
            'last_name_ar' => 'رجاء',
        ]);
        $expiredEmployee = $this->makeEmployee($city, 'expired.employee');
        $inactiveContractEmployee = $this->makeEmployee($city, 'inactive.contract');
        $otherCenterEmployee = $this->makeEmployee($city, 'other.center');

        $this->makeContract($multiAssignmentEmployee, $officerPosition, $annualType);
        $this->makeContract($multiAssignmentEmployee, $coordinatorPosition, $annualType);
        $this->makeContract($singleAssignmentEmployee, $officerPosition, $serviceType);
        $this->makeContract($expiredEmployee, $officerPosition, $annualType, [
            'start_date' => now()->subYears(2),
            'end_date' => now()->subYear(),
        ]);
        $this->makeContract($inactiveContractEmployee, $officerPosition, $annualType, [
            'is_active' => false,
        ]);
        $this->makeContract($otherCenterEmployee, $otherCenterPosition, $annualType);

        return compact(
            'city',
            'center',
            'multiAssignmentEmployee',
            'singleAssignmentEmployee',
            'expiredEmployee',
            'inactiveContractEmployee',
            'otherCenterEmployee',
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
            'is_active' => true,
        ]);
    }

    private function makeContract(
        Employee $employee,
        Position $position,
        ContractType $contractType,
        array $attributes = [],
    ): Contract {
        return Contract::query()->forceCreate(array_merge([
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'contract_type_id' => $contractType->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'currency' => 2,
            'weekly_hours' => 40,
            'is_active' => true,
        ], $attributes));
    }
}
