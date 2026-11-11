<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Only the NOT NULL columns without a database default (see
     * .ai/rules/seeders.md).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_balance_id' => LeaveBalance::factory(),
            'start_at' => now()->addWeek()->startOfDay(),
            'end_at' => now()->addWeek()->startOfDay(),
        ];
    }
}
