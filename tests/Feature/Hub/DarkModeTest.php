<?php

use Illuminate\Support\Facades\Blade;

test('the theme toggle is a named 44px toggle button in both languages', function (string $locale, string $name) {
    app()->setLocale($locale);
    $html = Blade::render('<x-theme-toggle />');

    expect($html)
        ->toContain(':aria-pressed="dark.toString()"')
        ->toContain('aria-label="'.$name.'"')
        ->toContain('size-11');
})->with([
    'en' => ['en', 'Dark mode'],
    'ar' => ['ar', 'الوضع الداكن'],
]);

test('in the account menu the theme toggle is a full-width checkbox menu item', function () {
    $menu = Blade::render('<x-theme-toggle :menu="true" />');

    expect($menu)
        ->toContain('role="menuitemcheckbox"')
        ->toContain(':aria-checked="dark.toString()"')
        ->toContain('min-h-11');
});

test('the stored theme is applied before first paint by a nonced script', function () {
    $login = $this->get(route('login'))->getContent();

    expect($login)
        ->toContain('<meta name="color-scheme" content="light dark" />')
        ->toContain("localStorage.getItem('hopehr-theme')")
        ->toMatch('/<script nonce="[^"]+">\s*\(function \(\) \{\s*var root = document\.documentElement;/');
});

test('white fills flip in dark mode while white text stays white', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    // Both dark blocks (system setting and data-theme) flip the white token itself,
    // so every variant of bg-white (odd:, group-odd:, hover:, md: …) follows the theme.
    expect(substr_count($css, '--color-white: #232325;'))->toBe(2)
        // Text, fill and stroke read their own keys, which never flip (white on the red).
        ->and($css)->toContain('--text-color-white: #FFFFFF;')
        ->and($css)->toContain('--fill-white: #FFFFFF;')
        ->and($css)->toContain('--stroke-white: #FFFFFF;');
});

test('views use colour tokens, not arbitrary hex classes that cannot follow the theme', function () {
    // The one tint left is task 1.3 (status pills and pink tints move to media-request's style).
    $allowed = ['bg-[#fbeaea]'];
    $found = [];

    // The app's views and every module's own views (modules/*/resources/views).
    $roots = array_filter([resource_path('views'), ...glob(base_path('modules/*/resources/views'), GLOB_ONLYDIR) ?: []], 'is_dir');
    $files = [];

    foreach ($roots as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[] = $file;
        }
    }

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());

        // Print / document surfaces with their own fixed palette.
        if (preg_match('#/views/(pdf|errors)/|/contract/details/|/employee-cards/#', $path)) {
            continue;
        }

        preg_match_all('/(?<![\w-])(?:[\w-]+:)*(?:bg|text|border|ring|divide|from|via|to|fill|stroke|outline|decoration)-\[#[0-9a-fA-F]{3,8}\]/', file_get_contents($path), $matches);

        foreach ($matches[0] as $class) {
            $bare = preg_replace('/^(?:[\w-]+:)+/', '', $class);

            if (! in_array($bare, $allowed, true)) {
                $found[] = basename($path).': '.$class;
            }
        }
    }

    expect($found)->toBe([]);
});
