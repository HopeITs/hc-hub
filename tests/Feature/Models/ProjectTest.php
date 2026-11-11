<?php

use App\Models\HrCity;
use App\Models\HrEmployee;
use Modules\Core\Models\Program;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Risk;

test('a project belongs to an hr city, a program and an hr employee', function () {
    $city = HrCity::find(insertHrCity());
    $program = Program::factory()->create();
    $employeeId = insertHrEmployee();

    $project = Project::factory()->create([
        'city_id' => $city->id,
        'program_id' => $program->id,
        'user_id' => $employeeId,
    ]);

    expect($project->city)->toBeInstanceOf(HrCity::class)
        ->and($project->city->id)->toBe($city->id)
        ->and($project->program->id)->toBe($program->id)
        ->and($project->user)->toBeInstanceOf(HrEmployee::class)
        ->and($project->user->id)->toBe($employeeId);
});

test('a project attaches to proposals through the project_proposal pivot', function () {
    $project = Project::factory()->create();
    $proposal = Proposal::factory()->create();

    $project->proposals()->attach($proposal);

    expect($project->proposals()->sole()->id)->toBe($proposal->id)
        ->and($proposal->projects()->sole()->id)->toBe($project->id);

    $this->assertDatabaseHas('project_proposal', [
        'project_id' => $project->id,
        'proposal_id' => $proposal->id,
    ]);
});

test('a project keeps timestamps on its proposal links', function () {
    $project = Project::factory()->create();

    $project->proposals()->attach(Proposal::factory()->create());

    expect($project->proposals()->sole()->pivot->created_at)->not->toBeNull();
});

test('a project has its risks, milestones and targets', function () {
    $project = Project::factory()->create();

    Risk::factory(2)->create(['project_id' => $project->id]);
    Milestone::factory()->create(['project_id' => $project->id]);
    PlanIndicator::factory()->create(['project_id' => $project->id]);

    expect($project->risks)->toHaveCount(2)
        ->and($project->milestones)->toHaveCount(1)
        ->and($project->planIndicators)->toHaveCount(1);
});
