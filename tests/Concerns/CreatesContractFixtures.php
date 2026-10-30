<?php

namespace Tests\Concerns;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\Program;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;

/**
 * The minimum org chart a contract needs to exist: a city, a center, a
 * department, a job level, a program, and a position to sit on.
 *
 * ContractFactory falls back to `Position::inRandomOrder()->first()->id ?? 1`,
 * which inserts a foreign key to a row that does not exist on an empty test
 * database. Anything creating contracts directly needs this first.
 */
trait CreatesContractFixtures
{
    use CreatesHrFixtures;

    protected function makePosition(City $city, array $overrides = []): Position
    {
        $center = Center::query()->create([
            'name_en' => 'Hope Center '.$city->slug,
            'name_ar' => 'مركز الرجاء',
            'city_id' => $city->id,
            'is_active' => true,
            'weekends' => 'friday',
            'open_at' => '08:00:00',
            'close_at' => '16:00:00',
        ]);

        $department = Department::query()->firstOrCreate(['code' => 'PRG'], ['name' => 'Programs']);
        $jobLevel = JobLevel::query()->firstOrCreate(['grade' => 2], ['name_en' => 'Officer', 'name_ar' => 'مسؤول']);
        $program = Program::query()->firstOrCreate(['code' => 'CORE'], [
            'name' => 'Core Program', 'slug' => 'core-program', 'is_active' => true,
        ]);

        return Position::query()->forceCreate(array_merge([
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
        ], $overrides));
    }

    protected function makeContractFor(Employee $employee, array $overrides = []): Contract
    {
        $city = $employee->city;
        $position = $this->makePosition($city);
        $contractType = ContractType::query()->firstOrCreate(
            ['name_en' => 'Volunteer Contract'],
            ['name_ar' => 'عقد تطوّع'],
        );

        return Contract::query()->create(array_merge([
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'contract_type_id' => $contractType->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'currency' => 2,
            'salary_per' => 1,
            'salary' => 1500,
            'weekly_hours' => 40,
            'is_active' => true,
        ], $overrides));
    }
}
