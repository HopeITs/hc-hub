<?php

use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Strategy;

test('program cards print their labels instead of handing PHP to Alpine', function () {
    $program = Program::factory()->create(['name' => 'Children University']);

    Livewire::actingAs(User::factory()->role('Super Admin')->create())
        ->test('tracking::programs.index')
        ->assertSeeHtml('aria-label="'.e(__('programs.open_name', ['name' => $program->name])).'"')
        ->assertSee(route('programs.show', $program), false)
        ->assertDontSeeHtml(':aria-label="__(');
});

test('the strategy page prints its menu and row labels instead of handing PHP to Alpine', function () {
    $strategy = Strategy::factory()->create();
    $program = Program::factory()->create(['name' => 'Children University', 'strategy_id' => $strategy->id]);

    Livewire::actingAs(User::factory()->role('Super Admin')->create())
        ->test('tracking::strategies.show', ['strategy' => $strategy])
        ->assertSeeHtml('aria-label="'.e(__('strategies.more_actions')).'"')
        ->assertSeeHtml('aria-label="'.e(__('strategies.choose_pillar_for', ['program' => $program->name])).'"')
        ->assertDontSeeHtml(':aria-label="__(');
});

test('a page without its own layout gets the Desk frame', function () {
    expect(config('livewire.component_layout'))->toBe('layouts::desk');
});
