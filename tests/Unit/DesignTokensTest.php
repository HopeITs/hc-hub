<?php

/**
 * Every stylesheet under resources/css, concatenated: app.css imports the others.
 */
function stylesheetSource(): string
{
    $files = glob(dirname(__DIR__, 2).'/resources/css/*.css');
    sort($files);

    return implode("\n", array_map('file_get_contents', $files));
}

/**
 * The project's own Blade views (resources/views and each module's
 * resources/views), keyed by path relative to that views folder.
 *
 * @return array<string, string>
 */
function bladeViews(): array
{
    $roots = [dirname(__DIR__, 2).'/resources/views', ...(glob(dirname(__DIR__, 2).'/modules/*/resources/views') ?: [])];
    $views = [];

    foreach ($roots as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

            if (str_ends_with($relative, '.blade.php') && ! str_starts_with($relative, 'vendor/')) {
                $views[$relative] = file_get_contents($file->getPathname());
            }
        }
    }

    return $views;
}

test('the stylesheet exposes the project design tokens', function () {
    preg_match_all(
        '/^\s*(--(?:color|spacing)-[a-z0-9-]+):\s*([^;]+);$/m',
        stylesheetSource(),
        $matches,
    );

    $tokens = array_combine($matches[1], array_map('trim', $matches[2]));

    expect($tokens)->toMatchArray([
        '--color-primary' => '#be1f24',
        '--color-secondary-dark' => 'var(--color-grey-ink)',
        '--color-secondary-medium' => 'var(--color-grey-ink-muted)',
        '--color-secondary-light' => 'var(--color-grey-ink-faint)',
        '--color-secondary-soft' => 'var(--color-grey-line)',
        '--spacing-ui-2xs' => '0.25rem',
        '--spacing-ui-xs' => '0.5rem',
        '--spacing-ui-sm' => '0.75rem',
        '--spacing-ui-md' => '1rem',
        '--spacing-ui-lg' => '1.5rem',
        '--spacing-ui-xl' => '2rem',
        '--spacing-ui-2xl' => '3rem',
        '--spacing-ui-3xl' => '4rem',
        '--spacing-control' => 'var(--spacing-ui-sm)',
        '--spacing-card' => 'var(--spacing-ui-lg)',
        '--spacing-section' => 'var(--spacing-ui-3xl)',
        '--spacing-page' => 'var(--spacing-ui-xl)',
    ]);
});

test('light mode has one grey ramp of the five brand greys, dark has its own five steps, and the old grey token names still resolve', function () {
    [$light, $dark] = explode('.dark {', file_get_contents(dirname(__DIR__, 2).'/resources/css/tokens.css'), 2);
    $ramp = ['ink' => '414042', 'ink-muted' => '6d6e71', 'ink-faint' => '939598', 'line-strong' => 'bcbec0', 'line' => 'e6e7e8'];

    foreach ($ramp as $step => $hex) {
        expect($light)->toContain("--color-grey-{$step}: #{$hex};")
            ->and(substr_count(strtolower($light), "#{$hex}"))->toBe(1)
            ->and($dark)->toMatch("/^\s*--color-graphite-{$step}: #[0-9a-f]{6};$/m");
    }

    // A grey is a hex whose red, green and blue sit within 10 of each other; only the ramp and white may appear.
    preg_match_all('/#([0-9a-f]{6}|[0-9a-f]{3})\b/i', $light, $matches);
    $offRamp = [];

    foreach (array_unique(array_map('strtolower', $matches[1])) as $hex) {
        $full = strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex;
        $channels = array_map('hexdec', str_split($full, 2));

        if (max($channels) - min($channels) <= 10 && ! in_array($full, [...array_values($ramp), 'ffffff'], true)) {
            $offRamp[] = '#'.$hex;
        }
    }

    expect($offRamp)->toBe([]);

    $legacy = [
        'secondary-dark', 'secondary-medium', 'secondary-light', 'secondary-soft',
        'red-50', 'red-100', 'red-200', 'red-300',
        'zinc-50', 'zinc-100', 'zinc-200', 'zinc-300', 'zinc-400', 'zinc-500', 'zinc-600', 'zinc-700', 'zinc-800', 'zinc-900', 'zinc-950',
        'ink', 'ink-muted', 'ink-faint', 'line', 'line-strong', 'field-line', 'heading',
        'desk-ink', 'desk-ink-2', 'desk-ink-3', 'desk-faint', 'desk-line', 'desk-line-2',
        'paper', 'tint', 'sunk', 'ground', 'board', 'board-line', 'board-head', 'auth-ground',
        'surface-0', 'surface-1', 'surface-2', 'surface-3', 'brand-soft', 'scrim', 'header-bg',
    ];

    foreach ($legacy as $name) {
        expect($light)->toMatch("/^\s*--color-{$name}: [^;]+;$/m");
    }
});

test('shape tokens define the four radii and the three ink-tinted elevations, and the old names alias them', function () {
    $tokens = file_get_contents(dirname(__DIR__, 2).'/resources/css/tokens.css');

    expect($tokens)->toContain('--radius-inset: 0.25rem;')
        ->toContain('--radius-control: 0.5rem;')
        ->toContain('--radius-surface: 0.75rem;')
        ->toContain('--radius-round: 9999px;')
        ->toContain('--radius-panel: var(--radius-surface);')
        ->toContain('--shadow-e1: 0 1px 3px 0 rgb(65 64 66 / 0.08);')
        ->toContain('--shadow-e2: 0 2px 8px -2px rgb(65 64 66 / 0.1);')
        ->toContain('--shadow-e3: 0 6px 24px 0 rgb(65 64 66 / 0.14);')
        ->toContain('--shadow-hairline: var(--shadow-e1);')
        ->toContain('--shadow-card: var(--shadow-e1);')
        ->toContain('--shadow-card-hover: var(--shadow-e2);')
        ->toContain('--shadow-overlay: var(--shadow-e3);');
});

test('no stylesheet sets a literal border radius outside the documented exceptions', function () {
    // Exceptions: 0 / 50% / inherit, plus fixed shapes with no token (sheet top corners, the bell sheet, the gauge arcs).
    $allowed = ['16px 16px 0 0', '1.5rem 1.5rem 0 0', '0 0 22px 22px', '76px 76px 0 0', '60px 60px 0 0'];
    $tokenOrZero = '(?:var\(--(?:radius-(?:inset|control|surface|round|panel)|panel-r|prog-radius|qa-r)\)|0|50%|inherit)';
    $offenders = [];

    foreach (glob(dirname(__DIR__, 2).'/resources/css/*.css') as $file) {
        preg_match_all('/(?:border(?:-(?:start|end)-(?:start|end)|-(?:top|bottom)-(?:left|right))?-radius|--panel-r|--prog-radius|--qa-r)\s*:\s*([^;}]+)/', file_get_contents($file), $matches);

        foreach ($matches[1] as $value) {
            $value = trim(str_replace('!important', '', $value));

            if (! in_array($value, $allowed, true) && ! preg_match('/^'.$tokenOrZero.'(?:\s+'.$tokenOrZero.')*$/', $value)) {
                $offenders[] = basename($file).': '.$value;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('panels share one radius token and one border token that follows the line colour in both themes', function () {
    $tokens = file_get_contents(dirname(__DIR__, 2).'/resources/css/tokens.css');
    [$light, $dark] = explode('.dark {', $tokens, 2);

    expect($light)->toContain('--radius-panel: var(--radius-surface);')
        ->toContain('--color-panel-border: var(--color-line);')
        ->and($dark)->toContain('--color-panel-border: var(--color-line);');
});

test('the type scale has a token for every text role and Arabic raises the body size', function (string $token, string $size) {
    $tokens = file_get_contents(dirname(__DIR__, 2).'/resources/css/tokens.css');

    expect($tokens)->toContain("--text-{$token}: {$size};")
        ->toContain("--text-{$token}--line-height:")
        ->toContain("--text-{$token}--font-weight:");
})->with([
    ['h1', 'clamp(1.625rem, 4vw, 2.125rem)'],
    ['h2', '1.6875rem'],
    ['h3', '1.375rem'],
    ['h4', '1.125rem'],
    ['body', '1rem'],
    ['table', '0.9375rem'],
    ['small', '0.875rem'],
    ['th', '0.8125rem'],
    ['caption', '0.75rem'],
]);

test('Arabic pages read body text at 17px on a 1.7 line height', function () {
    $typography = file_get_contents(dirname(__DIR__, 2).'/resources/css/typography.css');

    expect($typography)->toMatch('/html\[lang=\'ar\'\]\s*\{\s*--text-body: 1\.0625rem;\s*--text-body--line-height: 1\.7;/')
        ->toContain('font-size: var(--text-body);')
        ->toContain('line-height: var(--text-body--line-height);');
});

test('every page title shares the HR title style and the eyebrow uses the muted ink', function () {
    $css = dirname(__DIR__, 2).'/resources/css';
    [$light, $dark] = explode('.dark {', file_get_contents($css.'/tokens.css'), 2);
    $views = bladeViews();

    expect($light)->toContain('--text-h1: clamp(1.625rem, 4vw, 2.125rem);')
        ->toContain('--text-h1--line-height: 1.2;')
        ->toContain('--color-heading: var(--color-grey-ink);')
        ->and($dark)->toContain('--color-heading: var(--color-desk-ink);')
        ->and(file_get_contents($css.'/typography.css'))->toContain('@utility page-title')
        ->and(file_get_contents($css.'/desk.css'))->toContain('.dk-eyebrow{ font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3) }');

    foreach (['pages/desk/⚡index.blade.php', 'components/desk/register.blade.php', 'pages/projects/_story-header.blade.php', 'pages/programs/⚡show.blade.php'] as $path) {
        expect($views[$path])->toContain('<h1 class="page-title"');
    }

    expect($views['components/ui/page-header.blade.php'])->toContain('class="page-title!"')
        ->and($views['components/ui/detail-header.blade.php'])->toContain('class="page-title! {{ $titleClass }}"')
        ->and(implode("\n", $views))->not->toMatch('/title-class="[^"]*(text-3xl|text-\[|font-|leading-|tracking-)/');
});

test('the stylesheet never selects elements by their translated text', function () {
    expect(stylesheetSource())->not->toMatch('/\[(aria-label|title|placeholder|alt)\s*[~|^$*]?=/');
});

test('dark mode classes in views only use the Graphite tokens', function () {
    $offenders = [];

    foreach (bladeViews() as $path => $source) {
        if (preg_match_all('/dark:(?:[\w\[\]&*:-]+:)?(?:bg|text|border|ring|divide|fill|stroke|outline|placeholder|from|via|to)-(?:zinc|neutral|stone|gray|slate|secondary)-[\w\/]+/', $source, $matches)) {
            $offenders[$path] = array_values(array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe([]);
});

test('views use logical direction utilities so Arabic mirrors correctly', function () {
    $offenders = [];
    $physical = '/(?<=[\s"\'`])((?:[\w@\/\[\]&*.()-]+:)*)(-?(?:ml|mr|pl|pr)-[\w.\[\]\/-]+|text-(?:left|right)|rounded-(?:l|r|tl|tr|bl|br)(?:-[\w\[\]]+)?|border-(?:l|r)(?:-[\w\[\]\/-]+)?|float-(?:left|right))(?=[\s"\'`])/';

    foreach (bladeViews() as $path => $source) {
        preg_match_all($physical, $source, $matches, PREG_SET_ORDER);

        foreach ($matches as [$class, $variants]) {
            if (! preg_match('/(^|:)(rtl|ltr):/', $variants)) {
                $offenders[$path][] = $class;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('neither the views nor an included Flux component render a Flux component the stylesheet skips', function () {
    preg_match_all('#@source not \'[^\']*/stubs/resources/views/flux/([a-z-]+)#', stylesheetSource(), $matches);

    $skipped = $matches[1];
    $offenders = [];
    $sources = bladeViews();

    // Flux components render each other, so the stubs the stylesheet still scans count too.
    $stubs = dirname(__DIR__, 2).'/vendor/livewire/flux/stubs/resources/views/flux';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stubs, FilesystemIterator::SKIP_DOTS)) as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($stubs) + 1));
        $component = preg_replace('/(\/.*|\.blade\.php)$/', '', $relative);

        if (! in_array($component, $skipped, true)) {
            $sources['flux-stub:'.$relative] = file_get_contents($file->getPathname());
        }
    }

    foreach ($sources as $path => $source) {
        foreach ($skipped as $component) {
            if (preg_match('/<flux:'.preg_quote($component, '/').'[\s.>\/]/', $source)) {
                $offenders[$path][] = $component;
            }
        }
    }

    expect($skipped)->not->toBeEmpty()
        ->and($offenders)->toBe([]);
});

test('arbitrary letter-spacing in views is a value the Arabic reset covers', function () {
    $offenders = [];

    foreach (bladeViews() as $path => $source) {
        preg_match_all('/tracking-(\[[^\]]*\]|\([^)]*\))/', $source, $matches);

        foreach ($matches[1] as $value) {
            // typography.css resets tracking-[0…] and tracking-[.…] under :lang(ar); negative values are tight figures, left alone.
            if (! preg_match('/^\[(0|\.|-)/', $value)) {
                $offenders[$path][] = 'tracking-'.$value;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('views reference colours through design tokens rather than raw hex', function () {
    // Standalone documents that never load app.css: the notification image templates
    // and the browser theme-color metas, which cannot read CSS variables.
    $standalone = [
        'notifications/banner.blade.php', 'notifications/tile.blade.php', 'partials/head.blade.php',
        // Print sheets and the job-post SVG are standalone documents that never load app.css.
        'components/recruitment/job-post-svg.blade.php',
        'print/attendance/timesheets.blade.php',
        'print/contract-legal/amendment.blade.php',
        'print/contract-legal/document.blade.php',
        'print/contract-legal/_styles.blade.php',
        'pages/annual-plans/_plan-print.blade.php',
        'pages/annual-plans/_update-print.blade.php',
    ];
    $offenders = [];

    foreach (bladeViews() as $path => $source) {
        if (in_array($path, $standalone, true)) {
            continue;
        }

        $markup = preg_replace('/\{\{--.*?--\}\}/s', '', $source);

        if (preg_match_all('/(?<![\w&])#[0-9a-fA-F]{6}\b|(?<![\w&])#[0-9a-fA-F]{3}\b(?![\w-])/', $markup, $matches)) {
            $offenders[$path] = array_values(array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe([]);
});

test('control size tokens exist, compact grows to 44px on touch, and buttons and fields read them', function () {
    $css = dirname(__DIR__, 2).'/resources/css/';
    $tokens = file_get_contents($css.'tokens.css');

    expect($tokens)->toContain('--control-h: 2.75rem;')
        ->toContain('--control-h-compact: 2.375rem;')
        ->toContain('--field-h: 2.875rem;')
        ->toContain('--border-control: 1.5px;')
        ->toMatch('/@media \(pointer: coarse\) \{\s*:root \{\s*--control-h-compact: 2\.75rem;/');

    expect(file_get_contents($css.'flux-overrides.css'))
        ->toMatch('/\[data-flux-button\]\.h-10 \{\s*height: var\(--control-h\);/')
        ->toMatch('/\[data-flux-button\]\.h-8 \{\s*height: var\(--control-h-compact\);/')
        ->toMatch('/\[data-flux-control\]\.h-10[^{]*\{\s*height: var\(--field-h\);\s*border-width: var\(--border-control\);\s*font-size: var\(--text-body\);/')
        ->toMatch('/\.page-action \{\s*height: var\(--control-h\);/');

    expect(file_get_contents($css.'forms.css'))
        ->toMatch('/\.form-box \{[^}]*min-height: var\(--field-h\);[^}]*border: var\(--border-control\) solid[^}]*font-size: var\(--text-body\);/s');

    expect(file_get_contents($css.'desk.css'))
        ->toMatch('/\.dk-btn\{[^}]*height:var\(--control-h\);/')
        ->toContain('.dk-btn.dk-sm{ height:var(--control-h-compact);')
        ->toMatch('/\.dk-field input[^{]*\{[^}]*min-height:var\(--field-h\);[^}]*border:var\(--border-control\) solid[^}]*font-size:var\(--text-body\)/');
});

test('one global focus ring: an ink token in both themes, never red, and no stylesheet switches the outline off outside the documented cases', function () {
    $css = dirname(__DIR__, 2).'/resources/css/';
    $tokens = file_get_contents($css.'tokens.css');
    [$light, $dark] = explode('.dark {', $tokens, 2);

    expect($light)->toMatch('/^\s*--color-focus:\s*var\(--color-heading\);$/m')
        ->and($light)->toMatch('/^\s*--color-focus-ring:\s*color-mix\(in srgb, var\(--color-focus\) 12%, transparent\);$/m')
        ->and($dark)->toMatch('/^\s*--color-focus:\s*var\(--color-ink\);$/m')
        ->and($dark)->toMatch('/^\s*--color-focus-ring:\s*color-mix\(in srgb, var\(--color-focus\) 12%, transparent\);$/m')
        ->and($tokens)->not->toMatch('/--color-focus(-ring)?:[^;]*(primary|brand|#be1f24)/i');

    $base = file_get_contents($css.'base.css');

    expect($base)->toMatch('/^:focus-visible \{\s*outline: 2px solid var\(--color-focus\);\s*outline-offset: 2px;\s*\}/m')
        ->and($base)->toMatch('/textarea, select\):focus-visible \{\s*outline: none;\s*border-color: var\(--color-focus\);\s*box-shadow: 0 0 0 3px var\(--color-focus-ring\);/');

    // Allowed `outline: none`: the two field rules in base.css (ink border and ring in the same rule, or left to the
    // field box) and two dialog containers that only ever take programmatic focus (tabindex -1 / focus trap).
    $allowed = ['base.css' => 2, 'desk-bell.css' => 1, 'desk-customize.css' => 1];
    $found = [];

    foreach (glob($css.'*.css') as $file) {
        $count = preg_match_all('/outline:\s*(?:none|0)(?![\d.])/', file_get_contents($file));

        if ($count > 0) {
            $found[basename($file)] = $count;
        }
    }

    ksort($allowed);
    ksort($found);

    expect($found)->toBe($allowed);

    foreach (glob($css.'*.css') as $file) {
        expect(file_get_contents($file))->not->toMatch('/:focus(?:-visible|-within)?[^{}]*\{[^{}]*outline[^{}]*(?:--color-primary|--color-brand|--rules-accent)/');
    }
});

test('readable text never uses the faint grey: it is for icons, borders and disabled states, and the text rules read the muted ink', function () {
    $css = dirname(__DIR__, 2).'/resources/css/';
    // The text roles (labels, meta, captions, headers, hints, empty states) of the Desk and the annual plan.
    $textRoles = [
        'desk.css' => ['.dk-eyebrow', '.dk-muted', '.dk-tl-legend'],
        'desk-shell.css' => ['.dk-side-empty', '.dk-where-g span', '.dk-pal-in input::placeholder'],
        'desk-story.css' => ['.dk-prog-stat-n.dk-muted', '.dk-ptk-card-n small'],
        'annual-plan.css' => ['.ap-faint', '.ap-plab', '.ap-fact span', '.ap-t th'],
    ];

    foreach ($textRoles as $file => $selectors) {
        $source = file_get_contents($css.$file);

        foreach ($selectors as $selector) {
            expect($source)->toMatch('/'.preg_quote($selector, '/').'\s*\{[^}]*\}/', $file.' '.$selector);

            preg_match('/'.preg_quote($selector, '/').'\s*\{[^}]*\}/', $source, $rule);

            expect($rule[0])->not->toMatch('/(?<![-\w])color:\s*var\(--faint\)/', $file.' '.$selector);
        }
    }

    // Placeholders read the muted ink in light (the dark rules next to them keep the faint step, which is 4.5 : 1 or more on every Graphite surface).
    $light = fn (string $file): string => preg_replace('/\.dark[^{}]*\{[^{}]*\}/', '', file_get_contents($css.$file));

    expect($light('forms.css'))->toMatch('/\.form-box-input::placeholder\s*\{\s*color:\s*var\(--color-secondary-medium\)/')
        ->and($light('forms.css'))->toMatch('/\.task-composer-description::placeholder\s*\{\s*color:\s*var\(--color-secondary-medium\)/')
        ->and($light('flux-overrides.css'))->toMatch('/:where\(input, textarea\)\[data-flux-control\]::placeholder\s*\{\s*color:\s*var\(--color-ink-muted\)/');

    // Flux's dark:text-zinc-400 / -500 read the muted step, not the 4.49 : 1 and 2.9 : 1 greys.
    [, $dark] = explode('.dark {', file_get_contents($css.'tokens.css'), 2);

    expect($dark)->toMatch('/--color-zinc-400:\s*var\(--color-graphite-ink-muted\);/')
        ->and($dark)->toMatch('/--color-zinc-500:\s*var\(--color-graphite-ink-muted\);/');

    // Class-level: no input or select placeholder in the shared form controls is the faint grey.
    $views = bladeViews();

    expect($views['components/ui/input.blade.php'])->toContain('placeholder:text-ink-muted!')->not->toContain('placeholder:text-secondary-light')
        ->and($views['components/ui/select.blade.php'])->toContain('placeholder:text-ink-muted!')->not->toContain('placeholder:text-secondary-light');
});

test('one catch-all rule removes animations, transitions and smooth scrolling for reduced motion', function () {
    $base = file_get_contents(dirname(__DIR__, 2).'/resources/css/base.css');

    expect($base)->toMatch('/@media \(prefers-reduced-motion: reduce\) \{\s*\*,\s*\*::before,\s*\*::after \{[^}]*animation-duration: 0\.01ms !important;[^}]*animation-iteration-count: 1 !important;[^}]*transition-duration: 0\.01ms !important;[^}]*scroll-behavior: auto !important;/');
});

test('dark mode keeps the brand red as a fill and never brightens it', function () {
    $css = dirname(__DIR__, 2).'/resources/css/';

    // D2: the three brightened reds are gone from every stylesheet.
    expect(preg_match('/#(?:d4343a|e04046|ff5a60)/i', stylesheetSource()))->toBe(0);

    // The dark fill tokens are the brand red itself.
    [, $dark] = explode('.dark {', file_get_contents($css.'tokens.css'), 2);

    expect($dark)->toContain('--color-brand: var(--color-primary);')
        ->and($dark)->toContain('--color-status-critical: var(--color-primary);');

    // Red is never small text in dark: the text-side tokens read in light ink.
    expect($dark)->toContain('--color-brand-text: var(--color-graphite-ink);')
        ->and($dark)->toContain('--color-brand-ink: var(--color-graphite-ink);')
        ->and($dark)->toContain('--color-status-critical-text: var(--color-graphite-ink);');
});

test('status, funding and series tokens stay on the brand palette in both themes', function (int $theme) {
    $themes = explode('.dark {', file_get_contents(dirname(__DIR__, 2).'/resources/css/tokens.css'), 2);

    preg_match_all('/--color-(?:status-[a-z-]+|funding[a-z-]*|series-\d)\s*:\s*([^;]+);/', $themes[$theme], $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $value) {
        expect($value)->not->toMatch('/#[0-9a-f]{3,8}\b/i')
            ->and($value)->not->toMatch('/--color-(?:green|blue|teal|amber|emerald|sky|yellow|orange)/');
    }
})->with(['light' => 0, 'dark' => 1]);
