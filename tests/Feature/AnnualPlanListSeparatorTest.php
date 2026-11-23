<?php

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Tracking\Actions\AnnualPlans\ChooseActivityPlanActivities;
use Modules\Tracking\Actions\AnnualPlans\CreateActivityPlan;
use Modules\Tracking\Actions\AnnualPlans\SaveActivityPlanItem;
use Modules\Tracking\Actions\AnnualPlans\SubmitActivityPlan;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Result;

beforeEach(function () {
    $this->managerEmployee = insertHrEmployee(firstNameEn: 'Meghety', lastNameEn: 'Nakkashian');
    $this->pm = userWithRole('Managers', $this->managerEmployee);
    $this->meal = userWithRole('MEAL', insertHrEmployee(firstNameEn: 'Rami', lastNameEn: 'Osman'));
    $this->meal->givePermissionTo('annual-plans.review');
    $this->program = Program::factory()->create(['manager_id' => $this->managerEmployee]);
    $this->proposal = Proposal::factory()->create(['planned_date_begin' => '2027-01-01', 'planned_date_end' => '2027-12-31']);
    $this->aleppo = insertHrCity('Aleppo', 'حلب');
    $this->homs = insertHrCity('Homs', 'حمص');

    foreach ([$this->aleppo, $this->homs] as $cityId) {
        $project = Project::factory()->create(['program_id' => $this->program->id, 'city_id' => $cityId]);
        DB::table('project_proposal')->insert(['project_id' => $project->id, 'proposal_id' => $this->proposal->id]);
    }

    $outcome = Result::factory()->type(ResultType::Outcome)->create(['proposal_id' => $this->proposal->id, 'program_id' => $this->program->id, 'statement' => 'Children find an academic path']);
    $output = Result::factory()->type(ResultType::Output)->childOf($outcome)->create(['proposal_id' => $this->proposal->id, 'program_id' => $this->program->id]);
    $this->basic = Result::factory()->activity($output)->create(['statement' => 'Basic sessions']);
});

test('the plan summary joins place names with the locale list separator', function (string $locale, string $expectedPlaces) {
    app()->setLocale($locale);

    $plan = app(CreateActivityPlan::class)->handle($this->pm, $this->proposal->id, $this->program->id);
    $plan = app(ChooseActivityPlanActivities::class)->handle($this->pm, $plan, [$this->basic->id]);
    foreach ($plan->items()->get() as $item) {
        app(SaveActivityPlanItem::class)->handle($this->pm, $item, [
            'single_week' => false,
            'unit' => 'session',
            'unit_other' => '',
            'cities' => [$this->aleppo, $this->homs],
            'expected_people' => '60',
            'note' => '',
            'batches' => [['start' => '2027-03-01', 'end' => '2027-04-26', 'count' => 16, 'hours' => 2]],
        ], true);
    }
    $plan = app(SubmitActivityPlan::class)->handle($this->pm, $plan->fresh());

    Livewire::actingAs($this->meal)->test('tracking::annual-plans.plan', ['activityPlan' => $plan])
        ->assertSet('mode', 'review')
        ->assertSeeText($expectedPlaces);
})->with([
    'english' => ['en', 'Aleppo, Homs'],
    'arabic' => ['ar', 'حلب، حمص'],
]);
