<?php

/**
 * UI-debt ratchet (plan task 0.10). Each metric is counted over the project's own files and may
 * never go UP; when it goes down the test still passes but prints the number to lower the ceiling to.
 *
 * Scanned: every *.css under resources/css, and every *.blade.php under resources/views and
 * modules/<name>/resources/views (vendor/ folders skipped). CSS comments are stripped first.
 *
 *  - smallFonts: `font-size: N(px|rem|em)` in CSS with N < 12 (px) or N < 0.75 (rem/em).
 *  - arbitraryTextSizes: Tailwind `text-[VALUE]` in views where VALUE is a length (`11px`, `0.7rem`,
 *    optionally `length:` prefixed). Colours such as `text-[#bbb]` or `text-[var(--x)]` are not counted.
 *  - rawButtons: every `<button` opening tag (case-insensitive); `<flux:button` is not matched.
 *  - importantOverrides: Tailwind v4 suffix important, a hyphenated class token directly followed
 *    by `!` and then whitespace, a quote, a backtick or `>` (e.g. `size-8!`, `md:px-2!`). Hyphenated
 *    prose such as "well-done!" would also match, so the number is an upper bound, which suits a ratchet.
 *  - rawHexColours: `#rgb`, `#rgba`, `#rrggbb` or `#rrggbbaa` in views, not preceded by a word
 *    character or `&` (so HTML entities like `&#8217;` are skipped).
 */
function uiDebtFiles(array $roots, string $suffix): array
{
    $files = [];

    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if (str_ends_with($path, $suffix) && ! str_contains(substr($path, strlen($root)), '/vendor/')) {
                $files[] = $path;
            }
        }
    }

    return $files;
}

/**
 * @return array{smallFonts: int, arbitraryTextSizes: int, rawButtons: int, importantOverrides: int, rawHexColours: int}
 */
function uiDebtCounts(): array
{
    $base = str_replace('\\', '/', dirname(__DIR__, 2));
    $counts = ['smallFonts' => 0, 'arbitraryTextSizes' => 0, 'rawButtons' => 0, 'importantOverrides' => 0, 'rawHexColours' => 0];

    foreach (uiDebtFiles(["$base/resources/css"], '.css') as $path) {
        $css = preg_replace('~/\*.*?\*/~s', '', file_get_contents($path));
        preg_match_all('/font-size\s*:\s*([\d.]+)(px|rem|em)\b/i', $css, $found, PREG_SET_ORDER);

        foreach ($found as [, $number, $unit]) {
            if ((float) $number < (strtolower($unit) === 'px' ? 12 : 0.75)) {
                $counts['smallFonts']++;
            }
        }
    }

    $viewRoots = ["$base/resources/views", ...array_map(fn ($dir) => str_replace('\\', '/', $dir), glob("$base/modules/*/resources/views") ?: [])];

    foreach (uiDebtFiles($viewRoots, '.blade.php') as $path) {
        $view = file_get_contents($path);

        $counts['arbitraryTextSizes'] += preg_match_all('/text-\[(?:length:)?[\d.]+(?:px|rem|em)\]/i', $view);
        $counts['rawButtons'] += preg_match_all('/<button\b/i', $view);
        $counts['importantOverrides'] += preg_match_all('/(?<=[\s"\'`])[a-z][\w:\[\]\.\/%#-]*-[\w:\[\]\.\/%#-]*!(?=[\s"\'`>])/', $view);
        $counts['rawHexColours'] += preg_match_all('/(?<![\w&])#(?:[0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{4}|[0-9a-f]{3})(?![\w-])/i', $view);
    }

    return $counts;
}

test('UI debt never goes up', function () {
    $ceilings = [
        'smallFonts' => 0,
        'arbitraryTextSizes' => 18,
        'rawButtons' => 625,
        'importantOverrides' => 397,
        'rawHexColours' => 25,
    ];

    $counts = uiDebtCounts();
    $increased = [];

    foreach ($ceilings as $metric => $ceiling) {
        if ($counts[$metric] > $ceiling) {
            $increased[] = "$metric rose to {$counts[$metric]} (ceiling $ceiling)";
        } elseif ($counts[$metric] < $ceiling) {
            fwrite(STDERR, "\nUI debt: $metric is now {$counts[$metric]}; lower its ceiling in UiDebtTest from $ceiling to {$counts[$metric]}.\n");
        }
    }

    expect($increased)->toBe([], 'UI debt went up: '.implode('; ', $increased));
});
