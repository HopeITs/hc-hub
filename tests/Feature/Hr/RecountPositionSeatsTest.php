<?php

use Modules\Hr\Console\RecountPositionSeatsCommand;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;

/*
| `positions:recount-seats` sets positions.exist_emp back to the contracts
| that hold a seat: active and not a draft.
*/

beforeEach(function () {
    $this->position = Position::factory()->create(['max_emp' => 3, 'exist_emp' => 5]);
    $contract = fn (array $attributes): Contract => Contract::factory()->create($attributes + ['position_id' => $this->position->id]);

    $contract(['type' => ContractTypeEnum::FullTime->value, 'is_active' => true]);
    $contract(['type' => ContractTypeEnum::FullTime->value, 'is_active' => false]);
    $contract(['type' => ContractTypeEnum::Draft->value, 'is_active' => true]);
});

test('a dry run reports the drift and writes nothing', function () {
    $this->artisan(RecountPositionSeatsCommand::class, ['--dry-run' => true])
        ->expectsOutputToContain("Position #{$this->position->id}: exist_emp 5 -> 1 (max_emp 3)")
        ->assertSuccessful();

    expect($this->position->refresh()->exist_emp)->toBe(5);
});

test('the counter is set to the active non-draft contracts, max_emp is untouched and a second run finds nothing', function () {
    $right = Position::factory()->create(['exist_emp' => 0]);

    $this->artisan(RecountPositionSeatsCommand::class)
        ->expectsOutputToContain("Position #{$this->position->id}: exist_emp 5 -> 1 (max_emp 3)")
        ->doesntExpectOutputToContain("Position #{$right->id}:")
        ->assertSuccessful();

    expect($this->position->refresh()->exist_emp)->toBe(1)
        ->and($this->position->max_emp)->toBe(3)
        ->and($right->refresh()->exist_emp)->toBe(0);

    $this->artisan(RecountPositionSeatsCommand::class)
        ->doesntExpectOutputToContain("Position #{$this->position->id}:")
        ->assertSuccessful();
});
