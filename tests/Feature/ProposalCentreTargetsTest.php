<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\IndicatorMeasure;
use Modules\Tracking\Enums\Period;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\LibraryIndicator;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Result;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->meal = mealManager();

    $program = Program::factory()->create();
    $this->proposal = Proposal::factory()->create();

    $cityId = insertHrCity('Idlib', 'إدلب');
    $this->sarmada = Project::factory()->create(['program_id' => $program->id, 'city_id' => $cityId, 'center_id' => insertHrCenter($cityId, 'Hope Center Sarmada', 'مركز الأمل سرمدا')]);
    $this->water = Project::factory()->create(['program_id' => $program->id, 'name' => 'Unmapped Water Project']);
    DB::table('project_proposal')->insert([
        ['project_id' => $this->sarmada->id, 'proposal_id' => $this->proposal->id],
        ['project_id' => $this->water->id, 'proposal_id' => $this->proposal->id],
    ]);
    // Y-10: a target belongs to its location row, so the dialog has one number per row.
    $this->rowSarmada = proposalRowForProject($this->proposal, $this->sarmada);
    $this->rowWater = proposalRowForProject($this->proposal, $this->water);

    $output = Result::factory()->create(['type' => ResultType::Output, 'program_id' => $program->id, 'proposal_id' => $this->proposal->id]);
    $entry = LibraryIndicator::factory()->create(['measure' => IndicatorMeasure::Numeric, 'program_id' => $program->id]);
    $this->indicator = Indicator::factory()->create(['result_id' => $output->id, 'library_indicator_id' => $entry->id, 'code' => $entry->code, 'measure' => IndicatorMeasure::Numeric, 'target' => 100, 'frequency' => Period::Quarterly]);
});

test('an indicator not linked to the library says so instead of offering numbers', function () {
    $this->indicator->update(['library_indicator_id' => null, 'code' => null]);

    centreTargetsBox($this->meal)->call('openCentreTargets', $this->indicator->id)
        ->assertSeeHtml('data-test="centre-targets-not-linked"')
        ->assertDontSeeHtml('data-test="centre-targets-save"')
        ->assertDontSeeHtml('data-test="centre-target-'.$this->rowSarmada->id.'"');
});

function centreTargetsBox(User $user): Testable
{
    return Livewire::actingAs($user)->test('proposals.logframe', ['proposalId' => test()->proposal->id]);
}

test('the dialog lists each location row by its city and program, under one period', function () {
    $box = centreTargetsBox($this->meal)->call('openCentreTargets', $this->indicator->id)
        ->assertSet('showCentreTargetsModal', true)
        ->assertSet('centreTargetsPeriod', 'quarterly')
        ->assertSee('Idlib')
        ->assertSee(__('proposals.framework.not_committed'));

    expect(array_column($box->instance()->centreTargetRows, 'locationId'))->toEqualCanonicalizing([$this->rowSarmada->id, $this->rowWater->id]);
});

test('saving writes one target per location row with the chosen period and the framework shows them', function () {
    centreTargetsBox($this->meal)->call('openCentreTargets', $this->indicator->id)
        ->set('centreTargetsPeriod', 'monthly')
        ->set("centreTargets.{$this->rowSarmada->id}", '60')
        ->set("centreTargets.{$this->rowWater->id}", '40')
        ->call('saveCentreTargets')
        ->assertHasNoErrors()
        ->assertSet('showCentreTargetsModal', false);

    $saved = PlanIndicator::query()->where('source_type', 'proposal')->where('source_id', $this->proposal->id)->where('indicator_id', $this->indicator->id)->get()->keyBy('proposal_location_id');

    expect((float) $saved[$this->rowSarmada->id]->target)->toBe(60.0)
        ->and($saved[$this->rowSarmada->id]->period)->toBe(Period::Monthly)
        ->and((float) $saved[$this->rowWater->id]->target)->toBe(40.0);
});

test('clearing a number removes that centre target and leaves the others', function () {
    centreTargetsBox($this->meal)->call('openCentreTargets', $this->indicator->id)
        ->set("centreTargets.{$this->rowSarmada->id}", '60')
        ->set("centreTargets.{$this->rowWater->id}", '40')
        ->call('saveCentreTargets');

    centreTargetsBox($this->meal)->call('openCentreTargets', $this->indicator->id)
        ->assertSet("centreTargets.{$this->rowSarmada->id}", '60')
        ->set("centreTargets.{$this->rowWater->id}", '')
        ->call('saveCentreTargets')
        ->assertHasNoErrors();

    expect(PlanIndicator::query()->where('source_id', $this->proposal->id)->where('indicator_id', $this->indicator->id)->pluck('proposal_location_id')->all())->toBe([$this->rowSarmada->id]);
});

test('a percent target above 100 is refused on its own centre', function () {
    $this->indicator->update(['measure' => IndicatorMeasure::Percentage]);
    $this->indicator->libraryIndicator?->update(['measure' => IndicatorMeasure::Percentage]);

    centreTargetsBox($this->meal)->call('openCentreTargets', $this->indicator->id)
        ->assertSee(__('proposals.framework.centre_sum_percent'))
        ->set("centreTargets.{$this->rowSarmada->id}", '150')
        ->call('saveCentreTargets')
        ->assertHasErrors("centreTargets.{$this->rowSarmada->id}");

    expect(PlanIndicator::query()->where('source_id', $this->proposal->id)->exists())->toBeFalse();
});

test('someone without targets.update gets no link and is refused by the server', function () {
    $viewer = User::factory()->role('Public Relations Manager')->create();

    centreTargetsBox($viewer)
        ->set('layers.targets', true)
        ->assertDontSeeHtml('data-test="edit-centre-targets-'.$this->indicator->id.'"');

    centreTargetsBox($viewer)->call('openCentreTargets', $this->indicator->id)->assertForbidden();

    centreTargetsBox($this->meal)
        ->set('layers.targets', true)
        ->call('openAll')
        ->assertSeeHtml('data-test="edit-centre-targets-'.$this->indicator->id.'"');
});
