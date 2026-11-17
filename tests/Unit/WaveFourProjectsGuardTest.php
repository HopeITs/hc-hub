<?php

declare(strict_types=1);

/**
 * D-74: a wave 4 file names `Project` only inside PlaceBridge. Starts with the
 * files Y-02 creates or lists; later tasks add theirs. Do not add a file to
 * the allow-list without Dev 1.
 */
it('names the Project model only in the place bridge', function (): void {
    $root = dirname(__DIR__, 2);

    $paths = [
        ...(glob($root.'/modules/Tracking/Support/Reporting/*.php') ?: []),
        $root.'/modules/Tracking/Support/ReportTemplatePrefiller.php',
        $root.'/modules/Tracking/Support/ReportTemplateReader.php',
        $root.'/modules/Tracking/Support/ReportTemplateQueue.php',
        ...(glob($root.'/modules/Tracking/Actions/ReportTemplates/*.php') ?: []),
    ];

    $offenders = [];

    foreach ($paths as $path) {
        if (basename($path) === 'PlaceBridge.php') {
            continue;
        }

        if (preg_match('/Tracking\\\\Models\\\\Project\b|\bProject::/', (string) file_get_contents($path)) === 1) {
            $offenders[] = str_replace($root.'/', '', $path);
        }
    }

    expect($offenders)->toBe([]);
});
