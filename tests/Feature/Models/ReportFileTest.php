<?php

use Carbon\CarbonImmutable;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\ReportFile;

test('a report file belongs to a report', function () {
    $report = Report::factory()->create();
    $file = ReportFile::factory()->create(['report_id' => $report->id]);

    expect($file->report)->toBeInstanceOf(Report::class)
        ->and($file->report->id)->toBe($report->id);
});

test('a report file casts its date to a date', function () {
    $file = ReportFile::factory()->create()->fresh();

    expect($file->date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($file->date->toDateString())->toBe(now()->toDateString());
});
