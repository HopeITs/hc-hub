<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;

/**
 * @extends Factory<MailListMember>
 */
class MailListMemberFactory extends Factory
{
    /**
     * Only the NOT NULL columns without a database default (see .ai/rules/seeders.md).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mail_list_id' => MailList::factory(),
            'email' => fake()->unique()->lexify('person.?????').'@'.MailList::DOMAIN,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => MailListMember::PENDING]);
    }
}
