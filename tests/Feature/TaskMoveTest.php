<?php

use App\Actions\Tasks\MoveTask;
use App\Models\Milestone;
use App\Models\Program;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

/*
| "Move to" on the task page: a top-level task, with its subtasks, moves to
| the program itself, one of its projects or one of their milestones — never
| outside its program.
*/

beforeEach(function () {
    $this->program = Program::factory()->create();
    $this->project = Project::factory()->create(['program_id' => $this->program->id, 'name' => 'Wells Project']);
    $this->milestone = Milestone::factory()->create(['project_id' => $this->project->id, 'name' => 'Pumps installed']);
    $this->manager = userWithRole('General Manager', insertHrEmployee());
    $this->task = Task::factory()->create(['source_type' => 'project', 'source_id' => $this->project->id]);
    $this->subtask = Task::factory()->create(['source_type' => 'project', 'source_id' => $this->project->id, 'parent_task_id' => $this->task->id]);
});

test('the task page lists every place in the program and marks the current one', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::tasks.show', ['task' => $this->task])
        ->assertSeeHtml('data-test="task-move-button"')
        ->call('openMoveModal')
        ->assertSet('showMoveModal', true)
        ->assertSet('moveTarget', 'project:'.$this->project->id)
        ->assertSeeHtml('data-move-target="program"')
        ->assertSeeHtml('data-move-target="milestone:'.$this->milestone->id.'"')
        ->assertSee(['Wells Project', 'Pumps installed', 'Current']);
});

test('a task moves with its subtasks to a milestone of its program', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::tasks.show', ['task' => $this->task])
        ->call('openMoveModal')
        ->set('moveTarget', 'milestone:'.$this->milestone->id)
        ->call('moveTask')
        ->assertSet('showMoveModal', false);

    expect($this->task->fresh()->only(['source_type', 'source_id']))->toBe(['source_type' => 'milestone', 'source_id' => $this->milestone->id])
        ->and($this->subtask->fresh()->only(['source_type', 'source_id']))->toBe(['source_type' => 'milestone', 'source_id' => $this->milestone->id]);
});

test('a task never moves outside its program', function () {
    $foreignMilestone = Milestone::factory()->create();

    Livewire::actingAs($this->manager)
        ->test('pages::tasks.show', ['task' => $this->task])
        ->call('openMoveModal')
        ->set('moveTarget', 'milestone:'.$foreignMilestone->id)
        ->call('moveTask')
        ->assertSet('showMoveModal', true);

    expect($this->task->fresh()->only(['source_type', 'source_id']))->toBe(['source_type' => 'project', 'source_id' => $this->project->id]);
});

test('a subtask offers no move: it follows its parent', function () {
    Livewire::actingAs($this->manager)
        ->test('pages::tasks.show', ['task' => $this->subtask])
        ->assertDontSeeHtml('data-test="task-move-button"')
        ->set('moveTarget', 'program')
        ->call('moveTask');

    expect($this->subtask->fresh()->source_type)->toBe('project');
});

test('someone who cannot edit the task cannot move it', function () {
    $this->actingAs(User::factory()->create());

    expect(fn () => app(MoveTask::class)->handle($this->task, 'program'))->toThrow(AuthorizationException::class)
        ->and($this->task->fresh()->source_type)->toBe('project');
});
