<?php

namespace Database\Factories;

use Database\Factories\Concerns\CreatesHrRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TaskAssignee;

/**
 * @extends Factory<TaskAssignee>
 */
class TaskAssigneeFactory extends Factory
{
    use CreatesHrRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'employee_id' => fn (): int => $this->createHrEmployee(),
        ];
    }
}
