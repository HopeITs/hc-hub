<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Tasks;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Enums\TaskStatus;
use Modules\Tracking\Models\Task;

/**
 * Raise a task straight against one record (a program, proposal, a
 * proposal's location row, department or employee) and assign it, in one
 * transaction.
 *
 * Y-12: `proposal_location` is the source new code writes; `project` is kept
 * readable until Y-18 but is no longer raised against.
 */
class CreateTask
{
    /** The morph map keys a task can be raised against. */
    public const SOURCE_TYPES = ['program', 'project', 'proposal', 'proposal_location', 'department', 'employee'];

    /**
     * @param  string  $sourceType  the morph map key: 'program', 'proposal', 'proposal_location', 'department' or 'employee' (and legacy 'project')
     * @param  array{newTaskTitle: string, newTaskDescription?: string|null, newTaskStatus: string, newTaskPriority: string, newTaskDueDate: string, newTaskAssignedTo: array<int, int|string>, newTaskEvidenceRequired?: bool}  $validated  keyed by the add-task form's property names
     */
    public function handle(string $sourceType, int $sourceId, array $validated, int $createdBy): Task
    {
        Gate::authorize('create', Task::class);

        $modelClass = Relation::getMorphedModel($sourceType);

        if (! in_array($sourceType, self::SOURCE_TYPES, true) || $modelClass === null || ! $modelClass::query()->whereKey($sourceId)->exists()) {
            throw ValidationException::withMessages(['newTaskTarget' => __('tasks.the_selected_source_is_no_longer_available')]);
        }

        $statusCase = TaskStatus::from($validated['newTaskStatus']);
        $employeeIds = collect($validated['newTaskAssignedTo'])->map(fn ($id): int => (int) $id)->unique()->values();

        return DB::transaction(function () use ($sourceType, $sourceId, $validated, $statusCase, $employeeIds, $createdBy): Task {
            $task = Task::query()->create([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'title' => $validated['newTaskTitle'],
                'description' => trim((string) ($validated['newTaskDescription'] ?? '')) === '' ? null : trim((string) $validated['newTaskDescription']),
                'status' => $statusCase,
                'priority' => $validated['newTaskPriority'],
                'due_date' => $validated['newTaskDueDate'] === '' ? null : $validated['newTaskDueDate'],
                'evidence_required' => (bool) ($validated['newTaskEvidenceRequired'] ?? false),
                'progress_percentage' => $statusCase === TaskStatus::Done ? 100 : 0,
                'completed_date' => $statusCase === TaskStatus::Done ? CarbonImmutable::today() : null,
                'created_by' => $createdBy,
            ]);

            foreach ($employeeIds as $employeeId) {
                $task->taskAssignees()->create(['employee_id' => $employeeId]);
            }

            return $task;
        });
    }
}
