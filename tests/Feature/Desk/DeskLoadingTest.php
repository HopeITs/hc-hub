<?php

use App\Support\Desk\Destinations;
use App\Support\Desk\PageCards;
use Livewire\Livewire;
use Modules\Core\Models\User;

/*
| The Desk paints at once: every panel on the grid is mounted with `defer`
| and shows a titled placeholder on its own grid cells until its data
| arrives in a request of its own (desk-load.js shows Retry if that fails).
*/

test('the desk page draws its panels as placeholders and leaves their data for later', function () {
    $this->actingAs(User::factory()->role('General Manager')->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="dk-panel-needs"', false)
        ->assertSee('data-desk-pending', false)
        ->assertSee('$wire.__lazyLoad(', false)
        ->assertSee(__('desk.panel.needs.title'))
        ->assertDontSee('data-health=', false);
});

test('a placeholder sits on the same grid cells as its panel and offers Retry, hidden until a load fails', function () {
    $user = User::factory()->role('Super Admin')->create();
    $size = ['size' => 'L', 'gridCols' => 5, 'gridRows' => 3, 'index' => 2, 'total' => 4];

    Livewire::actingAs($user)
        ->test('desk.panels.needs', ['defer' => true, ...$size])
        ->assertSeeHtml('id="dk-panel-needs" style="--span:5;--rows:3;--i:2" data-cols="5" data-rows="3" data-panel="needs" data-idx="2" data-desk-pending')
        ->assertSeeHtml('role="alert" hidden data-desk-failed')
        ->assertSeeHtml('data-desk-retry');

    Livewire::actingAs($user)
        ->test('desk.panels.needs', $size)
        ->assertSeeHtml('style="--span:5;--rows:3;--i:2" data-cols="5" data-rows="3" data-panel="needs"')
        ->assertDontSeeHtml('data-desk-pending');
});

test('a page card placeholder is titled with its page', function () {
    $card = PageCards::idFor('strategies.index');

    Livewire::actingAs(User::factory()->role('Super Admin')->create())
        ->test('desk.panels.page', ['defer' => true, 'card' => $card, 'size' => 'L'])
        ->assertSeeHtml('id="dk-panel-'.$card.'"')
        ->assertSee(app(Destinations::class)->find('strategies.index')['label']);
});
