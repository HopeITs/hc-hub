<?php

use Illuminate\Support\Facades\File;
use Livewire\Component;
use Symfony\Component\Finder\SplFileInfo;
use Tests\Support\HubPaths;

/*
 * Modules (modules/<Name>/) register their views, translations and Livewire
 * components under the same unprefixed names as the platform's, as extra
 * lookup locations. That only works while names stay unique: the platform's
 * folder is searched first, so a module file with a platform name is
 * silently shadowed.
 */

test('every module has its service provider registered', function () {
    $providers = require base_path('bootstrap/providers.php');

    foreach (HubPaths::moduleNames() as $module) {
        expect($providers)->toContain("Modules\\{$module}\\Providers\\{$module}ServiceProvider");
    }
})->skip(fn () => HubPaths::moduleNames() === [], 'no modules yet');

test('no view name is defined both by the platform and by a module', function () {
    $names = [];

    foreach (HubPaths::views() as $root) {
        foreach (File::allFiles($root) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $names[str_replace('\\', '/', $file->getRelativePathname())][] = $root;
            }
        }
    }

    expect(array_keys(array_filter($names, fn (array $roots) => count($roots) > 1)))->toBe([]);
});

test('no translation group is defined both by the platform and by a module', function () {
    $groups = [];

    foreach (HubPaths::lang() as $root) {
        foreach (glob($root.'/*/*.php') ?: [] as $file) {
            $groups[basename(dirname($file)).'/'.basename($file)][] = $root;
        }
    }

    expect(array_keys(array_filter($groups, fn (array $roots) => count($roots) > 1)))->toBe([]);
});

test('every module Livewire component names its own view', function () {
    // Livewire's implicit view (no render()) is looked up in
    // resources/views/livewire only, never in a module.
    $missing = [];

    foreach (HubPaths::livewire() as $path => $namespace) {
        if (! str_starts_with($namespace, 'Modules\\')) {
            continue;
        }

        foreach (File::allFiles($path) as $file) {
            /** @var SplFileInfo $file */
            $class = $namespace.str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $file->getRelativePathname());

            if (! class_exists($class) || ! is_subclass_of($class, Component::class) || (new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            if (! method_exists($class, 'render')) {
                $missing[] = $class;
            }
        }
    }

    expect($missing)->toBe([]);
});
