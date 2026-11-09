<?php

/**
 * The Desk stylesheets, every comment removed, as one string.
 */
function deskStylesheets(): string
{
    $combined = '';

    foreach (glob(resource_path('css/desk*.css')) as $stylesheet) {
        $combined .= preg_replace('#/\*.*?\*/#s', '', file_get_contents($stylesheet));
    }

    return $combined;
}

test('desk panels take their radius and border from the shared panel tokens', function () {
    $css = deskStylesheets();

    expect($css)
        ->toContain('--panel-r: var(--radius-panel);')
        ->toContain('.dk-panel { border-color: var(--color-panel-border); }')
        ->toContain('.dk-panel.dk-needs { border-color: var(--color-panel-border); }')
        ->not->toContain('--panel-r: 12px');
});

test('panels restyled inside registers and the project story keep the shared panel tokens', function () {
    $css = deskStylesheets();

    preg_match('/\[data-desk-register\] \[data-panel\],\s*\[data-desk-register\] \[data-data-table\] \{([^}]*)\}/', $css, $register);
    preg_match('/\.dk-story \.dk-pjsec \[data-panel\] \{([^}]*)\}/', $css, $story);

    expect($register[1])->toContain('border: 1px solid var(--color-panel-border);')->toContain('border-radius: var(--radius-panel);')
        ->and($story[1])->toContain('border-color: var(--color-panel-border);')->toContain('border-radius: var(--radius-panel);');
});

test('desk stylesheets never select on the inline --span style', function () {
    expect(deskStylesheets())->not->toContain('[style*="--span');
});

test('needs you moves its actions under the text on a narrow card, except at two rows', function () {
    $css = deskStylesheets();

    expect($css)
        ->toContain('.dk-panel.dk-needs:not([data-rows="2"]) .dk-pb .dk-items > li { grid-template-columns: 12px minmax(0, 1fr) !important; }')
        ->toContain('.dk-panel.dk-needs:not([data-rows="2"]) .dk-pb .dk-items .dk-acts { grid-column: 2 !important;');
});

test('line-clamped titles stay out of the arabic plaintext rule, which would drop their ellipsis', function () {
    preg_match('/\[dir="rtl"\] \.dk-panel :is\(\.dk-fp-n[^{]*\{ unicode-bidi: plaintext;/', deskStylesheets(), $plaintextRule);

    expect($plaintextRule[0])->not->toContain('.dk-focus-title')
        ->not->toContain('.dk-wt-t')
        ->not->toContain(' .dk-rv-m,');
});

test('monthly reports hide the oldest months on a narrow card so the name stays readable', function () {
    $css = deskStylesheets();

    expect($css)->toMatch('/@container pb \(max-width: 639px\)\{ \.dk-mx2-r>:not\(:first-child\):nth-last-child\(n\+12\)\{ display:none \} \}/')
        ->and($css)->toMatch('/@container pb \(max-width: 293px\)\{ \.dk-mx2-r>:not\(:first-child\):nth-last-child\(n\+4\)\{ display:none \} \}/')
        ->and($css)->not->toContain('repeat(var(--months)');
});

test('the top bar and the page column pad by the same edge so the breadcrumb, greeting and panels share one start and end line', function () {
    $css = deskStylesheets();

    expect($css)
        ->toContain('--page-edge: max(var(--page-gutter), calc((100% - var(--page-w)) / 2));')
        ->toMatch('/\.dk-top-in \{[^}]*padding-inline: var\(--page-edge\); \}/')
        ->toContain('.dk-wrap{ max-width:none;padding-left:max(var(--page-edge),env(safe-area-inset-left,0px));padding-right:max(var(--page-edge),env(safe-area-inset-right,0px)) }')
        ->not->toContain('1680px');
});

test('desk panels sit 20px apart on wide screens', function () {
    expect(deskStylesheets())->toContain(':root{ --row-unit:104px;--grid-gap:20px;');
});

test('on a touch screen the desk controls are at least 44px to tap', function () {
    preg_match('/@media \(pointer: coarse\) \{\s*\.dk-panel \.dk-iconbtn\.dk-sm[^}]*\}(.*?)\n    \}/s', deskStylesheets(), $coarse);

    expect($coarse[1] ?? '')
        ->toContain('.dk-panel .dk-seg button { height: auto; min-height: 44px;')
        ->toContain('.dk-panel .dk-flow-step > a { min-height: 44px; }')
        ->toContain('.dk-hint .dk-btn { min-height: 44px; }')
        ->toContain('.dk-panel .dk-load { grid-template-columns: repeat(14, 44px);');
});

test('on a touch screen the strategies tabs, buttons and figure links are at least 44px to tap', function () {
    expect(deskStylesheets())
        ->toContain('.dk-stabs button { height: auto; min-height: 44px; }')
        ->toContain('.dk-strategies .dk-btn, .dk-snotset .dk-btn, .dk-slist .dk-slink { min-height: 44px; }')
        ->toContain('.dk-sfigs dd a { display: inline-block; min-width: 44px; min-height: 44px; }');
});
