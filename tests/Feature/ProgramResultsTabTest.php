<?php

use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\Result;

beforeEach(function () {
    $this->program = Program::factory()->create();
    $this->project = Project::factory()->create(['program_id' => $this->program->id]);
    $this->plan = AnnualPlan::factory()->create(['program_id' => $this->program->id, 'year' => 2026]);
});

function resultsTab(Program $program): Testable
{
    return Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('tracking::programs.show', ['program' => $program])
        ->set('tab', 'results');
}

/**
 * An indicator on the result, with a 10-unit target in the test plan when
 * $achieved is given (and an approved report of that value when above 0).
 */
function resultsTabIndicator(Result $result, string $name, ?float $achieved): Indicator
{
    $indicator = Indicator::factory()->create(['result_id' => $result->id, 'name' => $name]);

    if ($achieved !== null) {
        $target = PlanIndicator::factory()->create([
            'source_id' => test()->plan->id,
            'project_id' => test()->project->id,
            'indicator_id' => $indicator->id,
            'target' => 10,
        ]);

        if ($achieved > 0) {
            Report::factory()->approved()->create(['plan_indicator_id' => $target->id, 'value' => $achieved]);
        }
    }

    return $indicator;
}

test('the filter cards count indicators by state and filter the cards to one state', function () {
    $impact = Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id]);
    resultsTabIndicator($impact, 'Wells repaired', 10);
    resultsTabIndicator($impact, 'Households reached', 4);
    resultsTabIndicator($impact, 'Committees trained', 0);
    resultsTabIndicator($impact, 'Latrines built', null);

    $component = resultsTab($this->program);

    expect($component->instance()->resultsBoard['counts'])->toBe([
        'all' => 4, 'achieved' => 1, 'in_progress' => 1, 'not_started' => 1, 'no_target' => 1,
    ]);

    $component->call('pickResultFilter', 'no_target')
        ->assertSet('resultFilter', 'no_target')
        ->assertSee('Latrines built')
        ->assertDontSee('Wells repaired')
        ->call('pickResultFilter', 'no_target')
        ->assertSet('resultFilter', 'all')
        ->assertSee('Wells repaired');
});

test('the search keeps the results whose statement or indicators match and drops the rest', function () {
    $impact = Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id, 'statement' => 'Safe water for every family.']);
    resultsTabIndicator($impact, 'Wells repaired', 10);
    $crossCutting = Result::factory()->type(ResultType::CrossCutting)->create(['program_id' => $this->program->id, 'statement' => 'Gender is mainstreamed.']);
    resultsTabIndicator($crossCutting, 'Women trained', 10);

    resultsTab($this->program)
        ->set('resultSearch', 'WELLS')
        ->assertSee('Safe water for every family.')
        ->assertDontSee('Gender is mainstreamed.')
        ->set('resultSearch', 'mainstreamed')
        ->assertSee('Women trained')
        ->assertDontSee('Wells repaired');
});

test('picking a level lists only that level as flat cards under their lineage', function () {
    $impact = Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id, 'statement' => 'Root impact statement.']);
    Result::factory()->type(ResultType::Outcome)->create(['program_id' => $this->program->id, 'parent_id' => $impact->id, 'statement' => 'Nested outcome statement.']);

    resultsTab($this->program)
        ->call('pickResultLevel', 'outcome')
        ->assertSet('resultLevel', 'outcome')
        ->assertSeeHtml('data-result-type="outcome"')
        ->assertDontSeeHtml('data-result-type="impact"')
        ->assertSeeHtml('data-chain-lineage')
        ->call('pickResultLevel', 'not-a-level')
        ->assertSet('resultLevel', '')
        ->assertSeeHtml('data-result-type="impact"');
});

test('results with issues come before results without', function () {
    $healthy = Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id, 'statement' => 'Healthy impact.']);
    resultsTabIndicator($healthy, 'Wells repaired', 10);
    $troubled = Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id, 'statement' => 'Troubled impact.']);
    resultsTabIndicator($troubled, 'Latrines built', null);

    resultsTab($this->program)->assertSeeInOrder(['Troubled impact.', 'Healthy impact.']);
});

test('a card lists five indicators and shows more a page at a time', function () {
    $impact = Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id]);

    foreach (range(1, 7) as $number) {
        resultsTabIndicator($impact, "Indicator {$number}", 10);
    }

    $component = resultsTab($this->program);

    expect(substr_count($component->html(), 'data-chain-indicator='))->toBe(5);

    $component->call('showMoreResultIndicators', $impact->id);

    expect(substr_count($component->html(), 'data-chain-indicator='))->toBe(7);

    $component->call('showFewerResultIndicators', $impact->id);

    expect(substr_count($component->html(), 'data-chain-indicator='))->toBe(5);
});

test('milestones without an activity load in pages', function () {
    Result::factory()->type(ResultType::Impact)->create(['program_id' => $this->program->id]);
    Milestone::factory()->count(12)->create(['project_id' => $this->project->id])
        ->each(fn (Milestone $milestone) => $milestone->forceFill(['result_id' => null])->saveQuietly());

    $component = resultsTab($this->program)
        ->assertSee('12 milestones without an activity')
        ->assertSeeHtml('data-test="results-unlinked-more"');

    expect(substr_count($component->html(), 'chain-unlinked-'))->toBe(10);

    $component->call('showMoreUnlinkedMilestones')
        ->assertDontSeeHtml('data-test="results-unlinked-more"');

    expect(substr_count($component->html(), 'chain-unlinked-'))->toBe(12);
});
