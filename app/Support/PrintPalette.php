<?php

namespace App\Support;

/**
 * Literal light-theme colours for the standalone documents that never load
 * app.css (print sheets, the job-post SVG rendered for image export), so CSS
 * custom properties cannot resolve there.
 *
 * Every constant listed in TOKENS carries the value of the design token it is
 * mapped to in resources/css/tokens.css; tests/Unit/PrintPaletteTest.php keeps
 * the two from drifting. Constants prefixed Print have no token.
 */
final class PrintPalette
{
    public const string Primary = '#be1f24';

    public const string OnBrand = '#ffffff';

    public const string Paper = '#ffffff';

    public const string Ground = '#f3f3f4';

    public const string Heading = '#414042';

    public const string DeskInk = '#232427';

    public const string DeskLine = '#e1e1e3';

    public const string DeskLine2 = '#c6c7c9';

    public const string SecondaryDark = '#58595b';

    public const string SecondaryMedium = '#6d6e71';

    public const string SecondaryLight = '#939598';

    public const string SecondarySoft = '#dcddde';

    public const string StatusGood = '#1d7a4c';

    public const string StatusActive = '#2563a8';

    public const string PrintWarn = '#d6941f';

    /**
     * Palette constant name => the design token carrying the same value.
     *
     * @var array<string, string>
     */
    public const array TOKENS = [
        'Primary' => '--color-primary',
        'OnBrand' => '--color-on-brand',
        'Paper' => '--color-paper',
        'Ground' => '--color-ground',
        'Heading' => '--color-heading',
        'DeskInk' => '--color-desk-ink',
        'DeskLine' => '--color-desk-line',
        'DeskLine2' => '--color-desk-line-2',
        'SecondaryDark' => '--color-secondary-dark',
        'SecondaryMedium' => '--color-secondary-medium',
        'SecondaryLight' => '--color-secondary-light',
        'SecondarySoft' => '--color-secondary-soft',
        'StatusGood' => '--color-status-good',
        'StatusActive' => '--color-status-active',
    ];
}
