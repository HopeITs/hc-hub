<?php

use Carbon\CarbonImmutable;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalStatusNote;

test('a status note belongs to a proposal', function () {
    $proposal = Proposal::factory()->create();
    $note = ProposalStatusNote::factory()->create(['proposal_id' => $proposal->id]);

    expect($note->proposal)->toBeInstanceOf(Proposal::class)
        ->and($note->proposal->id)->toBe($proposal->id);
});

test('a status note casts its date to a date', function () {
    $note = ProposalStatusNote::factory()->create()->fresh();

    expect($note->date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($note->date->toDateString())->toBe(now()->toDateString());
});
