<?php

namespace Database\Factories;

use App\Enums\AiRequestStatus;
use App\Models\AiRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRequest>
 */
class AiRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feature' => 'monthly-report-narrative',
            'provider' => 'ollama',
            'model' => 'qwen3:8b',
            'status' => AiRequestStatus::Succeeded,
            'input_tokens' => fake()->numberBetween(500, 4000),
            'output_tokens' => fake()->numberBetween(100, 800),
            'duration_ms' => fake()->numberBetween(2000, 40000),
        ];
    }

    /**
     * A call the provider did not answer.
     */
    public function failed(): static
    {
        return $this->state([
            'status' => AiRequestStatus::Failed,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'error' => 'Connection refused',
        ]);
    }
}
