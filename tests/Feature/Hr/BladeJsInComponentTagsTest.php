<?php

use Illuminate\Support\Facades\File;

/*
| Blade does not compile the @js directive inside the tag of a component (<x-ui.button ...>): the
| browser receives the literal text "@js($value)" and Alpine throws "Invalid or unexpected token",
| which silently breaks the page. Tests rendering the HTML never see it. Inside a component tag use
| {{ \Illuminate\Support\Js::from($value) }}; on a plain element @js is fine.
*/

test('no HR view puts the js directive inside a component tag', function () {
    $offenders = [];

    foreach (File::allFiles(base_path('modules/Hr/resources/views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = $file->getContents();

        if (preg_match_all('/<x-[\w.\-:]+(?:[^>]|=>)*?@js\(/s', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [, $offset]) {
                $offenders[] = str_replace('\\', '/', $file->getRelativePathname()).':'.(substr_count($contents, "\n", 0, $offset) + 1);
            }
        }
    }

    expect($offenders)->toBe([], "@js inside a component tag is not compiled (use {{ Js::from() }}):\n  ".implode("\n  ", $offenders));
});
