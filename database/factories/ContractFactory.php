<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
        $endDate = (clone $startDate)->modify('+1 year');

        return [
            'employee_id' => Employee::factory(),
            'position_id' => Position::inRandomOrder()->first()->id ?? 1,
            'contract_type_id' => ContractType::inRandomOrder()->first()->id ?? 1,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'terminated_at' => null,
            'reason_of_termination' => null,
            'salary' => $this->faker->randomFloat(2, 500, 3000),
            'currency' => $this->faker->numberBetween(1, 3), // 1=SYP, 2=USD, 3=EUR
            'salary_per' => $this->faker->numberBetween(1, 3), // 1=Month,2=Hour,3=Session
            'work_days_en' => 'Monday,Tuesday,Wednesday,Thursday,Friday',
            'work_days_ar' => 'الاثنين,الثلاثاء,الأربعاء,الخميس,الجمعة',
            'work_hours_from' => '09:00:00',
            'work_hours_to' => '17:00:00',
            'second_shift_work_hours_from' => null,
            'second_shift_work_hours_to' => null,
            'weekly_hours' => 40,
            'daily_hours' => 8,
            'work_days_per_week' => 5,
            'shift_type' => 'standard',
            'is_split_shift' => false,
            'is_active' => true,
        ];
    }
}
