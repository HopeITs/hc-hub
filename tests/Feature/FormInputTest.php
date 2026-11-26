<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * Parse rendered component HTML so tests can query it with XPath.
 */
function inputXPath(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    return new DOMXPath($document);
}

test('the form input shows a visible label, a hint and the error under the field', function () {
    View::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['name' => ['Enter a name.']])));

    $xpath = inputXPath(Blade::render('<x-ui.form-input wire:model="name" label="Name" help="As on the ID." />'));
    $input = $xpath->query('//input')->item(0);
    $id = $input->getAttribute('id');

    expect($xpath->query('//label[@for="'.$id.'" and contains(@class, "form-label")]')->length)->toBe(1)
        ->and(trim($xpath->query('//*[@id="'.$id.'-help"]')->item(0)->textContent))->toBe('As on the ID.')
        ->and(trim($xpath->query('//*[@id="'.$id.'-error"]')->item(0)->textContent))->toBe('Enter a name.')
        ->and($xpath->query('//div[@data-invalid]/input')->length)->toBe(1);
});

test('the form input search variant adds the magnifier, type search and a clear button', function () {
    View::share('errors', new ViewErrorBag);

    $xpath = inputXPath(Blade::render('<x-ui.form-input variant="search" label="Find" clearable wire:model.live="search" />'));
    $input = $xpath->query('//input')->item(0);

    expect($input->getAttribute('type'))->toBe('search')
        ->and($xpath->query('//div[contains(@class, "form-box")]//svg')->length)->toBeGreaterThan(0)
        ->and($xpath->query('//button[contains(@class, "form-clear")]')->length)->toBe(1)
        ->and($xpath->query('//label[@for="'.$input->getAttribute('id').'"]')->length)->toBe(1);
});

test('x-ui.input is an alias of the form input with a screen-reader label and compact search look', function () {
    View::share('errors', new ViewErrorBag);

    $xpath = inputXPath(Blade::render('<x-ui.input type="search" size="sm" icon="magnifying-glass" aria-label="Find a donor" wire:model="q" />'));
    $input = $xpath->query('//input')->item(0);
    $label = $xpath->query('//label[@for="'.$input->getAttribute('id').'"]')->item(0);

    expect($input->getAttribute('type'))->toBe('search')
        ->and(trim($label->textContent))->toBe('Find a donor')
        ->and($label->getAttribute('class'))->toContain('sr-only')
        ->and($xpath->query('//div[@data-size="sm"]/input')->length)->toBe(1);
});
