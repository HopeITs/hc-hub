<?php

use Illuminate\Support\Facades\Blade;

test('table rows dim while the page waits on the server', function () {
    $table = Blade::render('<x-ui.data-table><x-slot:head><th>Name</th></x-slot:head><tr><td>Row</td></tr></x-ui.data-table>');

    expect($table)->toContain('wire:loading.class.delay="opacity-60"')->toContain('motion-reduce:transition-none');
});

test('a linked stat tile lifts on hover only for people who accept motion', function () {
    $tile = Blade::render('<x-ui.stat-tile label="Open" value="3" href="/tasks" icon="inbox" />');

    expect($tile)->toContain('hover:-translate-y-0.5')->toContain('motion-reduce:hover:translate-y-0')
        ->toContain('motion-reduce:group-hover:scale-100')->not->toContain('transition-all');
});

test('the page arrival fade and the button press are switched off for reduced motion', function () {
    $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/base.css')));

    preg_match('/@media \(prefers-reduced-motion: reduce\) \{\s*main\[data-desk-page\] \{([^}]*)\}\s*\[data-flux-button\]:active \{([^}]*)\}/', $css, $reduced);

    expect($css)->toContain('animation: hc-page-in 180ms ease-out backwards;')
        ->and($reduced[1])->toContain('animation: none;')
        ->and($reduced[2])->toContain('scale: none;');
});

test('a progress bar slides its fill to the new value from the start edge in both directions', function () {
    $bar = Blade::render('<x-ui.progress-bar :value="40" />');

    expect($bar)->toContain('style="--fill: 40"')
        ->toContain('translate-x-[calc((var(--fill)_-_100)_*_1%)]')
        ->toContain('rtl:translate-x-[calc((100_-_var(--fill))_*_1%)]')
        ->toContain('motion-reduce:transition-none')
        ->and(Blade::render('<x-ui.progress-bar :value="140" />'))->toContain('style="--fill: 100"');
});

test('the sidebar and file field motion stays still for reduced motion', function () {
    $strip = fn (string $file): string => preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path($file)));
    $shell = $strip('css/desk-shell.css');
    $forms = $strip('css/forms.css');

    expect($shell)->toContain('.dk-side-gchev { transition: none; }')
        ->toContain('.dk-side-g > ul { transition: none !important; }')
        ->toContain('.dk-side-fly-from { transform: none; }')
        ->and($forms)->toMatch('/prefers-reduced-motion: reduce\) \{\s*\.form-file-from \{\s*transform: none;/');
});

test('the sidebar eases open and closed only while its toggle moves it, and not for reduced motion', function () {
    $shell = preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/desk-shell.css')));
    $script = file_get_contents(resource_path('js/side-nav.js'));

    expect($shell)->toContain('html.dk-nav-moving .dk-app { transition: grid-template-columns .22s var(--ease-drawer); }')
        ->toContain('html.dk-nav-open.dk-nav-closing { --side-w: var(--side-rail); }')
        ->not->toMatch('/^\s*\.dk-app \{[^}]*transition/m')
        ->toMatch('/prefers-reduced-motion: reduce\) \{[^@]*html\.dk-nav-moving \.dk-app \{ transition: none; \}/')
        ->and($script)->toContain("window.matchMedia('(prefers-reduced-motion: reduce)').matches")
        ->toContain("root.remove('dk-nav-closing', 'dk-nav-moving')");
});
