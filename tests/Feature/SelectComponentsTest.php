<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;

function selectXPath(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    return new DOMXPath($document);
}

test('the native x-ui.select and the header filter multi-select no longer exist', function () {
    expect(file_exists(resource_path('views/components/ui/select.blade.php')))->toBeFalse()
        ->and(file_exists(resource_path('views/components/ui/header-filter-multiselect.blade.php')))->toBeFalse();
});

test('no view renders a raw flux:select or the native x-ui.select', function () {
    $roots = [resource_path('views'), ...glob(base_path('modules/*/resources/views')) ?: []];
    $offenders = [];

    foreach ($roots as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (preg_match('/<flux:select(?![.\w-])|<x-ui\.select[\s>]/', file_get_contents($file->getPathname())) === 1) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the single select is a menu picker with the shared label, no native select', function () {
    View::share('errors', new ViewErrorBag);

    $xpath = selectXPath(Blade::render(<<<'BLADE'
        <x-ui.form-select wire:model="proposalId" label="Proposal" value="" placeholder="Pick one">
            <option value="">Pick one</option>
            <option value="1">Water</option>
        </x-ui.form-select>
    BLADE));

    expect($xpath->query('//select')->length)->toBe(0)
        ->and($xpath->query('//*[@data-form-select="proposalId"]//label[@for]')->length)->toBe(1)
        ->and($xpath->query('//*[contains(@class, "filter-picker")]//*[@data-flux-menu-item]')->length)->toBe(2);
});

test('the multi select keeps the menu open and names the picks', function () {
    View::share('errors', new ViewErrorBag);

    $html = Blade::render(<<<'BLADE'
        <x-ui.form-multiselect wire:model.live="years" label="Fiscal year" :value="[2025, 2026]">
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
        </x-ui.form-multiselect>
    BLADE);
    $xpath = selectXPath($html);

    expect($xpath->query('//select')->length)->toBe(0)
        ->and(trim($xpath->query('//*[@data-trigger-label]')->item(0)->textContent))->toBe('2025, 2026')
        ->and($html)->toContain('wire:model.live="years"')
        ->and($xpath->query('//*[@data-option-value]')->length)->toBe(3);
});

test('the select trigger takes the form box height and control border', function () {
    $css = file_get_contents(resource_path('css/flux-overrides.css'));

    expect($css)->toMatch('/\.filter-picker \[data-flux-button\] \{\s*min-height: var\(--field-h\);\s*border-width: var\(--border-control\);\s*border-radius: var\(--radius-control\);/');
});

test('picking a donor attribution in the program overview select saves it', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $program = Program::factory()->create();

    Livewire::actingAs(User::factory()->role('MEAL')->create())
        ->test('tracking::programs.show', ['program' => $program])
        ->assertSet('donorAttribution', 'shared')
        ->set('donorAttribution', 'split')
        ->assertHasNoErrors();

    expect(DB::table('programs')->where('id', $program->id)->value('donor_attribution'))->toBe('split');
});

test('years picked in the annual plan filter are kept as sorted whole numbers', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Livewire::actingAs(User::factory()->role('General Manager')->create())
        ->test('tracking::annual-plans.index')
        ->set('years', ['2026', '2024'])
        ->assertSet('years', [2024, 2026]);
});
