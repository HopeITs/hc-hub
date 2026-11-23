<?php

use Illuminate\Support\Facades\Blade;

test('the group header collapse button has a translated aria-label in English', function () {
    app()->setLocale('en');

    $html = Blade::render('<x-ui.group-header label="Open" count="2" storageKey="group-test" />');

    expect($html)->toContain('aria-label="Toggle group"')
        ->and($html)->toContain(':aria-expanded="open.toString()"');
});

test('the group header collapse button has a translated aria-label in Arabic', function () {
    app()->setLocale('ar');

    $html = Blade::render('<x-ui.group-header label="Open" count="2" storageKey="group-test" />');

    expect($html)->toContain('aria-label="تبديل المجموعة"')
        ->and($html)->toContain(':aria-expanded="open.toString()"');

    app()->setLocale('en');
});
