<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\MessageTemplate;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    /**
     * Only the NOT NULL columns without a database default (see .ai/rules/seeders.md).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(2, true)),
            'channel' => MessageTemplate::EMAIL,
            'subject' => fake()->sentence(4),
            'body' => 'Dear {first_name}, '.fake()->sentence(),
        ];
    }

    public function whatsapp(): static
    {
        return $this->state(['channel' => MessageTemplate::WHATSAPP, 'subject' => null]);
    }
}
