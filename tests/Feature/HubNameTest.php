<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/*
| One name, HC Hub (roadmap 3.1): no interface string and no page still
| carries the old "HC Tracking" name or its logo file.
*/

test('no interface string says HC Tracking', function () {
    $leftovers = [];

    foreach (File::allFiles(lang_path()) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        foreach (Arr::dot((array) require $file->getPathname()) as $key => $line) {
            if (is_string($line) && str_contains($line, 'HC Tracking')) {
                $leftovers[] = $file->getRelativePathname().': '.$key;
            }
        }
    }

    expect($leftovers)->toBe([]);
});

test('the round mark is a hub asset, and nothing points at the old logo file', function () {
    $pointing = [];

    foreach ([resource_path('views'), base_path('modules'), app_path(), public_path('manifest.webmanifest')] as $path) {
        foreach (is_dir($path) ? File::allFiles($path) : [new SplFileInfo($path)] as $file) {
            if (str_contains((string) file_get_contents($file->getPathname()), 'logo-tracking')) {
                $pointing[] = $file->getPathname();
            }
        }
    }

    expect($pointing)->toBe([])
        ->and(is_file(public_path('logo-hub-icon.svg')))->toBeTrue()
        ->and(is_file(public_path('logo-tracking-icon.svg')))->toBeFalse();
});

test('a signed-out visitor is shown the hub mark as the page icon', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('logo-hub-icon.svg', false);
});
