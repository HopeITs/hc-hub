<?php

namespace Database\Factories;

use App\Enums\NoteVisibility;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * A private personal note by a new user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'body' => fake()->sentence(),
            'visibility' => NoteVisibility::Private,
        ];
    }

    /**
     * A note about a record, visible to whoever can view it.
     */
    public function about(Model $subject, NoteVisibility $visibility = NoteVisibility::Context): static
    {
        return $this->state([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'visibility' => $visibility,
        ]);
    }
}
