<?php

use App\Support\PrintPalette;

/**
 * The light-theme design tokens of resources/css/tokens.css (everything before
 * the .dark block), name => value.
 *
 * @return array<string, string>
 */
function lightTokens(): array
{
    [$light] = explode('.dark {', file_get_contents(dirname(__DIR__, 2).'/resources/css/tokens.css'), 2);

    preg_match_all('/^\s*(--color-[a-z0-9-]+):\s*([^;]+);$/m', $light, $matches);

    return array_combine($matches[1], array_map('trim', $matches[2]));
}

test('every token-named print colour equals its design token in the stylesheet', function () {
    $tokens = lightTokens();
    $drifted = [];

    foreach (PrintPalette::TOKENS as $constant => $token) {
        if (($tokens[$token] ?? null) !== constant(PrintPalette::class.'::'.$constant)) {
            $drifted[$constant] = [constant(PrintPalette::class.'::'.$constant), $tokens[$token] ?? null];
        }
    }

    expect($drifted)->toBe([]);
});

test('every print colour is either mapped to a design token or marked print-only', function () {
    $unmapped = array_filter(
        array_keys((new ReflectionClass(PrintPalette::class))->getConstants()),
        fn (string $name): bool => $name !== 'TOKENS'
            && ! array_key_exists($name, PrintPalette::TOKENS)
            && ! str_starts_with($name, 'Print'),
    );

    expect($unmapped)->toBe([]);
});
