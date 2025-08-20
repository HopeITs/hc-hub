<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        return [
            'name' => [
                'en' => $this->faker->name,
                'ar' => 'اسم ' . $this->faker->unique()->numberBetween(1, 1000),
            ],
            'photo' => null, // or you can use $this->faker->imageUrl()
            'actual_job' => $this->faker->jobTitle,
            'years_of_service' => $this->faker->randomFloat(1, 0, 8),
            'direct_manager_id' => null, // you can link later
            'tech_manager_id' => null,   // you can link later
            'national_number' => $this->faker->unique()->numerify('###########'),
            'dob' => $this->faker->date('Y-m-d', '-20 years'),
            'civil_registry_office' => $this->faker->city,
            'civil_registry_number' => strtoupper($this->faker->bothify('###??')),
            'place_of_registration' => $this->faker->city,
            'gender' => $this->faker->randomElement([0, 1, 2]),
            'father_name' => [
                'en' => $this->faker->firstNameMale,
                'ar' => 'اسمأب' . $this->faker->unique()->numberBetween(1, 1000),
            ],
            'mother_name' => [
                'en' => $this->faker->firstNameFemale,
                'ar' => 'اسمأم' . $this->faker->unique()->numberBetween(1, 1000),
            ],
            'nationality' => 1,
            'marital_status' => $this->faker->randomElement([0, 1, 2]),
            'children' => $this->faker->numberBetween(0, 5),
            'work_phone' => $this->faker->numerify('09########'),
            'personal_phone' => $this->faker->numerify('09########'),
            'personal_email' => $this->faker->unique()->safeEmail,
            'address' => [
                'en' => $this->faker->city,
                'ar' => 'مدينة' . $this->faker->unique()->numberBetween(1, 1000),
            ],
            'skills' => implode(', ', $this->faker->words(3)),
            'interests' => implode(', ', $this->faker->words(3)),
            'is_active' => $this->faker->boolean(90),
            'is_board_member' => $this->faker->boolean(10),
            'board_member_started_at' => null,
            'board_member_end_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
