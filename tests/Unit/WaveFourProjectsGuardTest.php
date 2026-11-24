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
        // Y-09: the change-request actions.
        ...(glob($root.'/modules/Tracking/Actions/Proposals/*ProposalChange.php') ?: []),
        $root.'/modules/Tracking/Support/ReportTemplateQueue.php',
        ...(glob($root.'/modules/Tracking/Actions/ReportTemplates/*.php') ?: []),
        // Y-03: monthly reports follow the template.
        $root.'/modules/Tracking/Support/MonthlyReportGenerator.php',
        $root.'/modules/Tracking/Console/Commands/TemplateBackfillMonthlyReports.php',
        // Y-03 slice 2: every monthly-report action and support class, and the two commands.
        ...(glob($root.'/modules/Tracking/Actions/MonthlyReports/*.php') ?: []),
        ...(glob($root.'/modules/Tracking/Support/MonthlyReport*.php') ?: []),
        $root.'/modules/Tracking/Console/Commands/PrepareMonthlyReports.php',
        $root.'/modules/Tracking/Console/Commands/BackfillMonthlyReports.php',
        // Y-03 slice 3: monthly-report notification recipients follow the template.
        $root.'/modules/Tracking/Notifications/Recipients/MonthlyReportRecipients.php',
        // Y-05: the Monthly Matrix reads through ReportExtraction (its Activities tab through PlaceBridge).
        $root.'/modules/Tracking/Livewire/Concerns/Reports/BuildsMonthlyMatrix.php',
        $root.'/modules/Tracking/Livewire/Concerns/Reports/GroupsMonthlyMatrix.php',
        $root.'/modules/Tracking/Livewire/Concerns/Reports/BuildsMatrixBreakdowns.php',
        $root.'/modules/Tracking/Livewire/Concerns/Reports/BuildsMatrixActivities.php',
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
