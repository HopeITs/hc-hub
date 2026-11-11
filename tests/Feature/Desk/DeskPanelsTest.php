<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ArStatus;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Risk;
use Modules\Tracking\Models\TransfersTracking;

beforeEach(function () {
    $this->travelTo('2026-09-15 09:00:00');
});

test('every built panel renders for a super admin with data', function (string $panel) {
    $agreement = DonorProposal::factory()->create();
    TransfersTracking::factory()->create(['donor_proposal_id' => $agreement->id, 'ar_status' => ArStatus::Pending, 'scheduled_date' => '2026-09-01']);
    Risk::factory()->create();
    Milestone::factory()->create(['planned_end_date' => '2026-09-20']);
    Donor::factory()->create();

    Livewire::actingAs(User::factory()->role('Super Admin')->create())
        ->test("desk.panels.{$panel}", ['size' => 'L'])
        ->assertOk()
        ->assertSeeHtml('data-panel="'.$panel.'"');
})->with(['glance', 'needs', 'waiting', 'coming', 'projects', 'pipeline', 'money', 'transfers', 'matrix', 'validate', 'templates', 'reviews', 'tasks', 'deliverables', 'myteam', 'targets', 'duties', 'milestones', 'donors']);

test('the desk page renders every panel of the role layout', function (string $role) {
    $this->actingAs(User::factory()->role($role)->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-panel="needs"', false)
        ->assertDontSee('<livewire:', false);
})->with(['Super Admin', 'General Manager', 'MEAL', 'Financial', 'Team Member']);
