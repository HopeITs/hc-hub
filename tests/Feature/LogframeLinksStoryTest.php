<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Result;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->role('MEAL')->create();
    $this->program = Program::factory()->create();
    $this->proposal = Proposal::factory()->create();
    $this->project = Project::factory()->create(['program_id' => $this->program->id]);
    DB::table('project_proposal')->insert(['project_id' => $this->project->id, 'proposal_id' => $this->proposal->id, 'target_beneficiaries' => 10]);
});

test('the empty targets checklist switches the proposal to the logframe tab in place', function () {
    Livewire::actingAs($this->user)->test('tracking::proposals.show', ['proposal' => $this->proposal, 'embedded' => true])
        ->set('tab', 'targets')
        ->assertSeeHtml('data-test="open-logframe-tab"')
        ->assertDontSeeHtml('tab=logframe')
        ->set('tab', 'logframe')
        ->assertSet('tab', 'logframe');
});

test('the results framework links a proposal group straight to its Story block', function () {
    Result::factory()->create(['type' => ResultType::Impact, 'program_id' => $this->program->id, 'proposal_id' => $this->proposal->id]);

    Livewire::actingAs($this->user)->test('tracking::results.index')
        ->assertSeeHtml('href="'.e(route('projects.show', ['project' => $this->project, 'proposal' => $this->proposal->id, 'ptab' => 'logframe']).'#proposal').'"')
        ->assertDontSeeHtml(route('proposals.show', $this->proposal));
});
