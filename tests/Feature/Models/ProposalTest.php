<?php

use Carbon\CarbonImmutable;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalStatusNote;
use Modules\Tracking\Models\Task;

test('a proposal has its funding agreements and donors', function () {
    $proposal = Proposal::factory()->create();
    $donor = Donor::factory()->create();

    Proposal::query()->whereKey($proposal->id)->update(['donor_id' => $donor->id]);
    DonorProposal::factory()->create([
        'proposal_id' => $proposal->id,
        'donor_id' => $donor->id,
    ]);

    expect($proposal->donorProposals)->toHaveCount(1)
        ->and($proposal->donorProposals->first())->toBeInstanceOf(DonorProposal::class)
        ->and($proposal->donors()->sole()->id)->toBe($donor->id);
});

test('a proposal has its projects, tasks and status notes', function () {
    $proposal = Proposal::factory()->create();

    $proposal->projects()->attach(Project::factory()->create());
    Task::factory(2)->create(['source_type' => 'proposal', 'source_id' => $proposal->id]);
    ProposalStatusNote::factory()->create(['proposal_id' => $proposal->id]);

    expect($proposal->projects)->toHaveCount(1)
        ->and($proposal->tasks)->toHaveCount(2)
        ->and($proposal->statusNotes)->toHaveCount(1);
});

test('a proposal has the targets committed in it', function () {
    $proposal = Proposal::factory()->create();

    PlanIndicator::factory(2)->forProposal($proposal)->create();
    PlanIndicator::factory()->create();

    expect($proposal->planIndicators)->toHaveCount(2);
});

test('a proposal casts all four of its dates', function () {
    $proposal = Proposal::factory()->create([
        'planned_date_begin' => '2026-01-01',
        'planned_date_end' => '2026-12-31',
        'actual_date_begin' => '2026-02-15',
        'actual_date_end' => null,
    ])->fresh();

    expect($proposal->planned_date_begin)->toBeInstanceOf(CarbonImmutable::class)
        ->and($proposal->planned_date_begin->toDateString())->toBe('2026-01-01')
        ->and($proposal->planned_date_end->toDateString())->toBe('2026-12-31')
        ->and($proposal->actual_date_begin->toDateString())->toBe('2026-02-15')
        ->and($proposal->actual_date_end)->toBeNull();
});
