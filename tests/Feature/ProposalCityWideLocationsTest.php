<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Actions\Proposals\MaterialiseProposalLocations;
use Modules\Tracking\Actions\Proposals\SaveProposal;
use Modules\Tracking\Enums\ProposalStatus;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Support\ProposalLocationRows;

/*
| Y-01 slice 1 (D-88): a location row either runs from centers or is "field
| work, no center" (city_wide); the proposal page shows the rows in a
| Locations chapter, and the approve dialog says what the rows will do.
*/

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->role('Financial')->create();

    $this->aleppo = insertHrCity('Aleppo', 'حلب');
    $this->aleppoCenter = insertHrCenter($this->aleppo, 'Hope Center Aleppo', 'مركز الأمل حلب');
    $this->employee = insertHrEmployee($this->aleppo);
    $this->program = Program::factory()->create(['manager_id' => $this->employee, 'name' => 'Youth Clubs']);
});

test('a field-work row is saved without centers and ticking it clears the chosen centers', function () {
    Livewire::actingAs($this->user)->test('tracking::proposals.index')
        ->call('openCreateModal')
        ->set('name', 'Mobile Outreach')
        ->set('donorId', (string) Donor::factory()->create()->id)
        ->set('plannedDateBegin', '2026-11-01')
        ->set('plannedDateEnd', '2027-10-31')
        ->assertSeeHtml('data-test="location-city-wide"')
        ->set('locations.0.city_id', (string) $this->aleppo)
        ->set('locations.0.program_id', (string) $this->program->id)
        ->set('locations.0.center_ids', [(string) $this->aleppoCenter])
        ->set('locations.0.city_wide', true)
        ->assertSet('locations.0.center_ids', [])
        ->assertSeeText(__('proposals.location_city_wide_help'))
        ->call('saveProposal')
        ->assertHasNoErrors();

    $row = Proposal::query()->sole()->locations()->with('centers')->sole();

    expect($row->city_wide)->toBeTrue()
        ->and($row->centerIds())->toBe([])
        ->and(ProposalLocationRows::incompleteRowIds($row->proposal))->toBe([]);
});

test('the save refuses a field-work row that also names centers', function () {
    expect(fn () => app(SaveProposal::class)->handle(null, [
        'name' => 'Mobile Outreach',
        'donorId' => Donor::factory()->create()->id,
        'plannedDateBegin' => '2026-11-01',
        'plannedDateEnd' => '2027-10-31',
        'locations' => [[
            'city_id' => $this->aleppo,
            'program_id' => $this->program->id,
            'responsible_employee_id' => $this->employee,
            'center_ids' => [$this->aleppoCenter],
            'city_wide' => true,
        ]],
    ], $this->user))->toThrow(ValidationException::class, __('proposals.location_city_wide_has_centers'));

    expect(Proposal::query()->count())->toBe(0);
});

test('an edit turns a centered row into field work and drops its centers', function () {
    $proposal = Proposal::factory()->create();
    $row = ProposalLocation::factory()->create(['proposal_id' => $proposal->id, 'city_id' => $this->aleppo, 'program_id' => $this->program->id, 'responsible_employee_id' => $this->employee]);
    $row->centers()->create(['center_id' => $this->aleppoCenter]);

    Livewire::actingAs($this->user)->test('tracking::proposals.index')
        ->call('openEditModal', $proposal->id)
        ->assertSet('locations.0.city_wide', false)
        ->set('locations.0.city_wide', true)
        ->call('saveProposal')
        ->assertHasNoErrors();

    $row->refresh()->load('centers');

    expect($row->city_wide)->toBeTrue()
        ->and($row->centerIds())->toBe([])
        ->and(DB::table('proposal_location_centers')->where('proposal_location_id', $row->id)->count())->toBe(0);
});

test('the Locations chapter shows each row with its status and field work in place of centers', function () {
    $proposal = Proposal::factory()->create();
    ProposalLocation::factory()->cityWide()->create(['proposal_id' => $proposal->id, 'city_id' => $this->aleppo, 'program_id' => $this->program->id, 'responsible_employee_id' => $this->employee]);
    $missing = ProposalLocation::factory()->create(['proposal_id' => $proposal->id, 'city_id' => insertHrCity('Homs', 'حمص'), 'program_id' => $this->program->id, 'responsible_employee_id' => $this->employee, 'sort' => 1]);

    Livewire::actingAs($this->user)->test('tracking::proposals.show', ['proposal' => $proposal])
        ->set('tab', 'projects')
        ->assertSeeHtml('data-test="locations-chapter"')
        ->assertSeeHtml('data-location-status="complete"')
        ->assertSeeHtml('data-location-row="'.$missing->id.'" data-location-status="missing_center"')
        ->assertSeeText(__('proposals.location_field_work'))
        ->assertSeeText('Youth Clubs')
        ->assertSeeHtml('data-test="add-centers"')
        ->call('openEditModal', $proposal->id)
        ->assertSet('showProposalModal', true);
});

test('the chapter says so when the proposal has no rows yet', function () {
    $proposal = Proposal::factory()->create();

    Livewire::actingAs($this->user)->test('tracking::proposals.show', ['proposal' => $proposal])
        ->set('tab', 'projects')
        ->assertSeeHtml('data-test="locations-chapter-empty"')
        ->assertDontSeeHtml('data-test="add-centers"');
});

test('approval creates no project for a field-work row and the approve dialog previews the rows', function () {
    $proposal = Proposal::factory()->create(['status' => ProposalStatus::Submitted]);
    $centered = ProposalLocation::factory()->create(['proposal_id' => $proposal->id, 'city_id' => $this->aleppo, 'program_id' => $this->program->id, 'responsible_employee_id' => $this->employee]);
    $centered->centers()->create(['center_id' => $this->aleppoCenter]);
    ProposalLocation::factory()->cityWide()->create(['proposal_id' => $proposal->id, 'city_id' => insertHrCity('Homs', 'حمص'), 'program_id' => $this->program->id, 'responsible_employee_id' => $this->employee, 'sort' => 1]);

    $this->actingAs($this->user);
    $preview = app(MaterialiseProposalLocations::class)->preview($proposal);

    expect($preview->createdProjectIds)->toBe([$centered->id])
        ->and($preview->waitingLocationIds)->toBe([]);

    Livewire::actingAs(userWithRole('Public Relations Manager'))
        ->test('tracking::proposals.show', ['proposal' => $proposal])
        ->call('openApproveModal', $proposal->id)
        ->assertSet('approvalLocations.create', 1)
        ->assertSeeHtml('data-test="approve-locations-preview"')
        ->assertSeeText(trans_choice('proposals.approval_locations_create', 1, ['count' => 1]))
        ->assertDontSeeHtml('data-test="approve-no-projects"');
});
