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
