<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Result;

dataset('framework deletes', [
    'result' => [fn (): array => ['tracking::results.index', Result::factory()->create()->id, 'deleteResult', Result::class]],
    'indicator' => [fn (): array => ['tracking::indicators.index', Indicator::factory()->create()->id, 'deleteIndicator', Indicator::class]],
    'target' => [fn (): array => ['tracking::targets.index', PlanIndicator::factory()->create()->id, 'deleteTarget', PlanIndicator::class]],
]);

test('Team Members, Managers and MEAL officers cannot delete framework records; the MEAL manager can', function (Closure $case) {
    [$page, $id, $delete, $model] = $case();
    $attempt = fn (User $user) => Livewire::actingAs($user)->test($page)->call('confirmDelete', $id);

    $attempt(userWithRole('Team Member'))->assertForbidden();
    $attempt(userWithRole('Managers'))->assertForbidden();
    $attempt(userWithRole('MEAL'))->assertForbidden();
    $attempt(mealManager())->call($delete)->assertOk();

    expect($model::query()->find($id))->toBeNull();
})->with('framework deletes');

test('a Team Member cannot open any framework create form', function (string $page) {
    Livewire::actingAs(userWithRole('Team Member'))->test($page)->call('openCreateModal')->assertForbidden();
})->with(['tracking::results.index', 'tracking::indicators.index', 'tracking::targets.index']);

test('only the MEAL manager edits framework records', function () {
    $indicator = Indicator::factory()->create();
    $edit = fn (User $user) => Livewire::actingAs($user)->test('tracking::indicators.index')->call('openEditModal', $indicator->id);

    $edit(userWithRole('Managers'))->assertForbidden();
    $edit(userWithRole('MEAL'))->assertForbidden();
    $edit(mealManager())->assertOk();
});

test('recording an indicator reading needs indicators.record', function () {
    $indicator = Indicator::factory()->create();

    Livewire::actingAs(userWithRole('Team Member'))->test('tracking::indicators.index')->call('openProgressModal', $indicator->id)->assertForbidden();
    Livewire::actingAs(userWithRole('MEAL'))->test('tracking::indicators.index')->call('openProgressModal', $indicator->id)->assertOk();
});

test('a create deep link does nothing for someone without the permission', function () {
    Livewire::actingAs(userWithRole('Team Member'))->withQueryParams(['create' => Result::factory()->create()->id])
        ->test('tracking::indicators.index')
        ->assertOk()
        ->assertSet('showIndicatorModal', false);

    Livewire::actingAs(userWithRole('Team Member'))->withQueryParams(['create' => '1'])
        ->test('tracking::targets.index')
        ->assertOk()
        ->assertSet('showTargetModal', false);
});
