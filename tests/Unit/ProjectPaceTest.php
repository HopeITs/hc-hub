<?php

use Carbon\CarbonImmutable;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Support\ProjectPace;

test('a project is rated by its progress against the share of its window elapsed', function (ProjectStatus $status, string $start, string $end, ?int $progress, string $expected) {
    $today = CarbonImmutable::parse('2026-07-01');

    expect(ProjectPace::rate($status, CarbonImmutable::parse($start), CarbonImmutable::parse($end), $progress, $today))->toBe($expected);
})->with([
    'on pace' => [ProjectStatus::Active, '2026-01-01', '2026-12-31', 50, 'on_track'],
    'a little behind' => [ProjectStatus::Active, '2026-01-01', '2026-12-31', 40, 'slipping'],
    'far behind' => [ProjectStatus::Active, '2026-01-01', '2026-12-31', 20, 'behind'],
    'past its end unfinished' => [ProjectStatus::Active, '2025-01-01', '2026-03-31', 60, 'past_end'],
    'finished window without milestones' => [ProjectStatus::Active, '2025-01-01', '2026-03-31', null, 'past_end'],
    'not yet active' => [ProjectStatus::Approved, '2026-01-01', '2026-12-31', 10, 'not_started'],
    'starts in the future' => [ProjectStatus::Active, '2026-09-01', '2027-08-31', 0, 'not_started'],
    'no weighted milestones' => [ProjectStatus::Active, '2026-01-01', '2026-12-31', null, 'unmeasured'],
    'complete' => [ProjectStatus::Active, '2026-01-01', '2026-12-31', 100, 'complete'],
    'closed' => [ProjectStatus::Closed, '2025-01-01', '2025-12-31', 30, 'closed'],
]);

test('weighted progress is a whole capped percentage, or null without weights', function () {
    expect(ProjectPace::progress(25, 100))->toBe(25)
        ->and(ProjectPace::progress(150, 100))->toBe(100)
        ->and(ProjectPace::progress(0, 0))->toBeNull();
});
