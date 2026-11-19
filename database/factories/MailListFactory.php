<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\MailList;

/**
 * @extends Factory<MailList>
 */
class MailListFactory extends Factory
{
    /**
     * Only the NOT NULL columns without a database default (see .ai/rules/seeders.md).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lexify('list-?????');

        return [
            'name' => ucfirst($name),
            'address' => $name.'@'.MailList::DOMAIN,
        ];
    }
}
