<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    /**
     * A saved row holding the defaults.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }

    /**
     * Quiet from 22:00 to 07:00 (the period wraps midnight).
     */
    public function quietAtNight(): static
    {
        return $this->state(['quiet_hours' => true, 'quiet_start' => '22:00', 'quiet_end' => '07:00']);
    }
}
