<?php

use Illuminate\Support\Facades\Blade;

test('the action menu is a flux dropdown with a More trigger and destructive items after a divider', function () {
    $html = Blade::render('<x-ui.action-menu><flux:menu.item>Duplicate</flux:menu.item><x-slot:danger><flux:menu.item variant="danger">Delete</flux:menu.item></x-slot:danger></x-ui.action-menu>');

    expect($html)->toContain('data-action-menu')
        ->and($html)->toContain('More')
        ->and(strpos($html, 'Duplicate'))->toBeLessThan(strpos($html, 'Delete'))
        ->and($html)->toContain('data-flux-menu');
});

test('the detail header puts overflow actions in the More menu beside the visible actions', function () {
    $html = Blade::render('<x-ui.detail-header title="T"><x-slot:actions><a href="#">Edit</a></x-slot:actions><x-slot:menu><flux:menu.item>Archive</flux:menu.item></x-slot:menu><x-slot:menuDanger><flux:menu.item variant="danger">Delete</flux:menu.item></x-slot:menuDanger></x-ui.detail-header>');

    expect($html)->toContain('data-action-menu')
        ->and(strpos($html, 'Edit'))->toBeLessThan(strpos($html, 'Archive'))
        ->and(strpos($html, 'Archive'))->toBeLessThan(strpos($html, 'Delete'));
});

test('the detail header shows no More menu when it has no menu slot', function () {
    $html = Blade::render('<x-ui.detail-header title="T"><x-slot:actions><a href="#">Edit</a></x-slot:actions></x-ui.detail-header>');

    expect($html)->not->toContain('data-action-menu');
});
