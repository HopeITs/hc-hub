<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ProposalStatus;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;

/*
| Y-01 slice 3: the "Missing a center" flag on the proposals register lists
| the proposals still needing a center, counts them on its pill, names the
| rows missing one and links to the proposal form on the show page.
*/

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->role('Financial')->create();

    $this->aleppo = insertHrCity('Aleppo', 'حلب');
    $this->homs = insertHrCity('Homs', 'حمص');
    $this->aleppoCenter = insertHrCenter($this->aleppo, 'Hope Center Aleppo', 'مركز الأمل حلب');
    $this->employee = insertHrEmployee($this->aleppo);
    $this->program = Program::factory()->create(['manager_id' => $this->employee]);
});

/**
 * A proposal with one row for the given city, optionally with a center or
 * as field work.
 */
function missingCenterProposal(string $name, ProposalStatus $status, bool $withCenter = false, bool $cityWide = false, ?int $cityId = null): Proposal
{
    $proposal = Proposal::factory()->create(['name' => $name, 'status' => $status]);
    $row = ProposalLocation::factory()->create([
        'proposal_id' => $proposal->id,
        'city_id' => $cityId ?? test()->aleppo,
        'program_id' => test()->program->id,
        'responsible_employee_id' => test()->employee,
        'city_wide' => $cityWide,
    ]);

    if ($withCenter) {
        $row->centers()->create(['center_id' => test()->aleppoCenter]);
    }

    return $proposal;
}

function missingCenterRegister(?User $user = null): Testable
{
    return Livewire::actingAs($user ?? test()->user)->test('tracking::proposals.index')->set('flag', 'no_center');
}

/**
 * @return list<int>
 */
function missingCenterListedIds(Testable $page): array
{
    return $page->instance()->rows->getCollection()->pluck('id')->unique()->sort()->values()->all();
}

test('the list holds exactly the non-rejected project proposals with a row lacking a center and not city-wide', function () {
    $missing = missingCenterProposal('Missing Place', ProposalStatus::Approved);
    $draft = missingCenterProposal('Draft Missing', ProposalStatus::ProposalDraft, cityId: $this->homs);
    missingCenterProposal('Field Work Place', ProposalStatus::Approved, cityWide: true);
    missingCenterProposal('Complete Place', ProposalStatus::Approved, withCenter: true);
    missingCenterProposal('Rejected Missing', ProposalStatus::Rejected);
    Proposal::factory()->create(['name' => 'No Rows At All', 'status' => ProposalStatus::Approved]);
    $payroll = Proposal::factory()->payroll()->create(['name' => 'Payroll Line']);
    ProposalLocation::factory()->create(['proposal_id' => $payroll->id, 'city_id' => $this->aleppo, 'program_id' => $this->program->id, 'responsible_employee_id' => $this->employee]);

    $page = missingCenterRegister();

    expect(missingCenterListedIds($page))->toBe(collect([$missing->id, $draft->id])->sort()->values()->all());

    $page->assertSeeText('Missing Place')
        ->assertSeeText('Draft Missing')
        ->assertDontSeeText('Field Work Place')
        ->assertDontSeeText('Complete Place')
        ->assertDontSeeText('Rejected Missing')
        ->assertDontSeeText('No Rows At All')
        ->assertDontSeeText('Payroll Line');
});

test('the pill count equals the listed proposals', function () {
    missingCenterProposal('One', ProposalStatus::Approved);
    missingCenterProposal('Two', ProposalStatus::Submitted);
    missingCenterProposal('Done', ProposalStatus::Approved, withCenter: true);
    missingCenterProposal('Rejected One', ProposalStatus::Rejected);

    $page = Livewire::actingAs($this->user)->test('tracking::proposals.index');

    expect($page->instance()->missingCenterCount)->toBe(2);

    $page->assertSeeHtml('data-test="flag-no-center"')
        ->assertSeeText(__('finance_tables.flag_no_center'));

    $page->set('flag', 'no_center');
    expect(count(missingCenterListedIds($page)))->toBe($page->instance()->missingCenterCount);
});

test('someone who may not see agreements still sees the pill and the list, without the money flags', function () {
    missingCenterProposal('Missing Place', ProposalStatus::Approved);
    $viewer = userWithRole(fundraiserWithoutAgreements());

    Livewire::actingAs($viewer)->test('tracking::proposals.index')
        ->assertSeeHtml('data-test="flag-no-center"')
        ->assertDontSeeHtml('data-test="flag-no-agreement"')
        ->assertDontSeeHtml('data-test="flag-report-overdue"')
        ->set('flag', 'no_center')
        ->assertSeeText('Missing Place');
});

test('each listed proposal names its rows missing a center and links to the proposal form', function () {
    $proposal = missingCenterProposal('Approved Missing', ProposalStatus::Approved, cityId: $this->homs);

    missingCenterRegister()
        ->assertSeeHtml('data-test="missing-center-rows-'.$proposal->id.'"')
        ->assertSeeText('Homs')
        ->assertSeeHtml('data-test="add-centers-link-'.$proposal->id.'"')
        ->assertSeeHtml(e(route('proposals.show', ['proposal' => $proposal, 'add-centers' => 1])));
});

test('the add-centers link opens the proposal form on the Projects tab for someone who may update it', function () {
    $proposal = missingCenterProposal('Approved Missing', ProposalStatus::Approved);

    Livewire::actingAs($this->user)->withQueryParams(['add-centers' => 1])
        ->test('tracking::proposals.show', ['proposal' => $proposal])
        ->assertSet('showProposalModal', true)
        ->assertSet('editingProposalId', $proposal->id)
        ->assertSet('tab', 'projects')
        ->assertSet('addCentersLink', false);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('proposals.view');

    Livewire::actingAs($viewer)->withQueryParams(['add-centers' => 1])
        ->test('tracking::proposals.show', ['proposal' => $proposal])
        ->assertSet('showProposalModal', false)
        ->assertSet('addCentersLink', false);
});
