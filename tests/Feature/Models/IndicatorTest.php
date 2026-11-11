<?php

use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Result;

test('an indicator belongs to a result', function () {
    $result = Result::factory()->create();
    $indicator = Indicator::factory()->create(['result_id' => $result->id]);

    expect($indicator->result)->toBeInstanceOf(Result::class)
        ->and($indicator->result->id)->toBe($result->id);
});

test('an indicator has the targets planned against it', function () {
    $indicator = Indicator::factory()->create();

    PlanIndicator::factory(2)->create(['indicator_id' => $indicator->id]);
    PlanIndicator::factory()->create();

    expect($indicator->planIndicators)->toHaveCount(2);
});
