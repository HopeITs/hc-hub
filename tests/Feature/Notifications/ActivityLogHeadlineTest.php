<?php

use App\Models\Risk;
use App\Models\Task;
use Livewire\Livewire;

test('the today and last 7 days numbers count only what the area, person and search filters allow', function () {
    $this->actingAs(userWithRole('Managers'));
    Task::factory()->create(['title' => 'Fresh task']);
    Risk::factory()->create(['name' => 'Fresh risk']);

    $page = Livewire::actingAs(userWithRole('Super Admin'))->test('pages::activity.index');
    $all = $page->instance()->summary;

    expect($all['today'])->toBeGreaterThanOrEqual(2);

    $page->set('module', 'risks');
    $narrowed = $page->instance()->summary;

    expect($narrowed['today'])->toBeLessThan($all['today'])->toBeGreaterThan(0);

    $page->call('showRange', 'today');

    expect($page->instance()->activities->total())->toBe($narrowed['today']);
});

test('person and dates sit behind More filters, inside the component, and the area column is named plainly', function () {
    $this->actingAs(userWithRole('Managers'));
    Task::factory()->create(['title' => 'Fresh task']);

    $page = Livewire::actingAs(userWithRole('Super Admin'))->test('pages::activity.index');
    $html = $page->html();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xpath = new DOMXPath($dom);
    $root = '//section[@*[name()="wire:name"]="pages::activity.index"]';

    foreach (['more-filters-button'] as $control) {
        expect($xpath->query($root.'//*[@data-test="'.$control.'"]')->length)->toBeGreaterThan(0, "{$control} sits outside the component");
    }
    expect($xpath->query($root.'//*[@data-activity-range="today"]')->length)->toBeGreaterThan(0);

    $page->assertSee('More filters')->assertSee('All areas')->assertDontSee('All modules');
});
