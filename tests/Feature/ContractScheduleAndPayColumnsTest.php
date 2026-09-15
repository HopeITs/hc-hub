<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Contract;
use App\Models\ContractPayTier;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class ContractScheduleAndPayColumnsTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_the_new_schedule_and_pay_columns_are_mass_assignable_and_cast(): void
    {
        $contract = $this->makeContract([
            'schedule_mode' => 2,
            'work_day_from' => 0,
            'work_day_to' => 4,
            'show_days_per_week' => true,
            'show_hours_per_week' => false,
            'flex_days_per_week' => 5,
            'flex_hours_per_week' => 40,
            'flex_hours_per_month' => 60,
            'flex_varies_by_requirements' => true,
            'flex_coordinated_with_manager' => false,
            'flex_custom_en' => 'By arrangement',
            'flex_custom_ar' => 'حسب الاتفاق',
            'pay_mode' => 2,
            'session_duration' => 3,
        ]);

        $fresh = $contract->fresh();

        $this->assertSame(2, $fresh->schedule_mode);
        $this->assertTrue($fresh->show_days_per_week);
        $this->assertFalse($fresh->show_hours_per_week);
        $this->assertTrue($fresh->flex_varies_by_requirements);
        $this->assertFalse($fresh->flex_coordinated_with_manager);
        $this->assertSame('By arrangement', $fresh->flex_custom_en);
        $this->assertSame(3, $fresh->session_duration);
    }

    public function test_a_second_fixed_shift_is_mass_assignable(): void
    {
        $splitShift = $this->makeContract([
            'work_hours_from' => '09:00',
            'work_hours_to' => '13:00',
            'work_hours_2_from' => '17:00',
            'work_hours_2_to' => '21:00',
        ]);

        $fresh = $splitShift->fresh();

        $this->assertSame('17:00', $fresh->work_hours_2_from);
        $this->assertSame('21:00', $fresh->work_hours_2_to);
    }

    public function test_a_second_fixed_shift_is_optional(): void
    {
        $singleShift = $this->makeContract();

        $this->assertNull($singleShift->fresh()->work_hours_2_from);
        $this->assertNull($singleShift->fresh()->work_hours_2_to);
    }

    public function test_pay_tiers_relation_returns_tiers_ordered_by_sort_order(): void
    {
        $contract = $this->makeContract();

        $contract->payTiers()->create(['condition_en' => 'Second tier', 'condition_ar' => 'الشريحة الثانية', 'amount' => 300, 'sort_order' => 2]);
        $contract->payTiers()->create(['condition_en' => 'First tier', 'condition_ar' => 'الشريحة الأولى', 'amount' => 200, 'sort_order' => 1]);

        $ordered = $contract->fresh()->payTiers;

        $this->assertCount(2, $ordered);
        $this->assertInstanceOf(ContractPayTier::class, $ordered->first());
        $this->assertSame('First tier', $ordered->first()->condition_en);
        $this->assertSame('Second tier', $ordered->last()->condition_en);
    }

    public function test_condition_resolves_to_the_current_locale_like_contract_type_name_does(): void
    {
        $contract = $this->makeContract();
        $tier = $contract->payTiers()->create(['condition_en' => 'If 60h/month', 'condition_ar' => 'إذا 60 ساعة/شهر', 'amount' => 200, 'sort_order' => 1]);

        app()->setLocale('en');
        $this->assertSame('If 60h/month', $tier->fresh()->condition);

        app()->setLocale('ar');
        $this->assertSame('إذا 60 ساعة/شهر', $tier->fresh()->condition);
    }

    public function test_deleting_a_contract_cascades_to_its_pay_tiers(): void
    {
        $contract = $this->makeContract();
        $contract->payTiers()->create(['condition_en' => 'A', 'condition_ar' => 'أ', 'amount' => 100, 'sort_order' => 1]);

        $contract->delete();

        $this->assertDatabaseCount('contract_pay_tiers', 0);
    }

    private function makeContract(array $overrides = []): Contract
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
            'max_emp' => 2,
            'exist_emp' => 0,
            'is_active' => true,
        ]);

        $employee = $this->makeEmployee($city, 'schedule-pay-test-employee');
        $contractType = ContractType::query()->forceCreate(['id' => 1, 'name_en' => 'Volunteer Contract', 'name_ar' => 'عقد تطوّع']);

        return Contract::query()->create(array_merge([
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'contract_type_id' => $contractType->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'currency' => 2,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => true,
        ], $overrides));
    }
}
