<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\User;

test('a new task starts as todo at medium priority with no progress', function () {
    $user = User::factory()->create();

    $task = DB::table('tasks')->find(insertTask(insertProposal(), $user->employee_id, $user->id));

    expect($task->status)->toBe('todo')
        ->and($task->priority)->toBe('medium')
        ->and((int) $task->progress_percentage)->toBe(0)
        ->and($task->source_type)->toBe('proposal')
        ->and($task->description)->toBeNull()
        ->and($task->start_date)->toBeNull()
        ->and($task->due_date)->toBeNull()
        ->and($task->completed_date)->toBeNull();
});

test('deleting a milestone leaves tasks sourced from it orphaned', function () {
    // source_type/source_id carry no foreign key (same as plan_indicators'
    // own polymorphic source), so unlike the old milestone_id column this
    // no longer cascades or nulls out — the task keeps pointing at the
    // deleted id, and Task::sourceLabel() falls back to "No source" for it.
    $user = User::factory()->create();
    $projectId = insertProject(insertCity(), insertProgram(insertStrategy(), $user->employee_id), $user->employee_id);
    $milestoneId = insertMilestone($projectId, $user->employee_id);
    $taskId = insertTask(insertProposal(), $user->employee_id, $user->id, $milestoneId);

    DB::table('milestones')->where('id', $milestoneId)->delete();

    $this->assertDatabaseHas('tasks', ['id' => $taskId, 'source_type' => 'milestone', 'source_id' => $milestoneId]);
});

test('deleting a proposal leaves tasks sourced from it in place', function () {
    // Same reasoning as the milestone case above: no foreign key means no
    // cascade delete anymore.
    $user = User::factory()->create();
    $proposalId = insertProposal();
    $taskId = insertTask($proposalId, $user->employee_id, $user->id);

    DB::table('proposals')->where('id', $proposalId)->delete();

    $this->assertDatabaseHas('tasks', ['id' => $taskId, 'source_type' => 'proposal', 'source_id' => $proposalId]);
});

test('a task assignment stores its hr employee id with a foreign key to employees', function () {
    $foreignKeyColumns = collect(Schema::getForeignKeys('task_assignees'))->pluck('columns')->flatten();

    expect($foreignKeyColumns)->toContain('employee_id');
});

test('a user is not deletable while a task they created still exists', function () {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    insertTask(insertProposal(), $assignee->id, $author->id);

    expect(fn () => DB::table('users')->where('id', $author->id)->delete())
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('users', ['id' => $author->id]);
});
