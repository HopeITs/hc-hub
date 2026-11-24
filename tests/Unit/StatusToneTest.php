<?php

use App\Enums\Concerns\HasStatusTone;
use App\Enums\StatusTone;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Enums\RagStatus;
use Modules\Tracking\Enums\TaskStatus;

/**
 * Every enum under app/Enums or modules/*\/Enums that implements HasStatusTone.
 *
 * @return array<string, class-string<HasStatusTone&UnitEnum>>
 */
function statusToneEnums(): array
{
    $enums = [];
    $classes = array_map(fn (string $file): string => 'App\\Enums\\'.basename($file, '.php'), glob(dirname(__DIR__, 2).'/app/Enums/*.php') ?: []);

    foreach (glob(dirname(__DIR__, 2).'/modules/*/Enums/*.php') ?: [] as $file) {
        $classes[] = 'Modules\\'.basename(dirname($file, 2)).'\\Enums\\'.basename($file, '.php');
    }

    foreach ($classes as $class) {
        if (enum_exists($class) && is_subclass_of($class, HasStatusTone::class)) {
            $enums[class_basename($class)] = $class;
        }
    }

    return $enums;
}

test('every status enum answers a tone for every case', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->tone())->toBeInstanceOf(StatusTone::class);
    }
})->with(fn () => array_map(fn (string $enum) => [$enum], statusToneEnums()));

test('the status-like enums all declare a tone', function () {
    expect(array_keys(statusToneEnums()))->toContain(
        'TaskStatus', 'ProposalStatus', 'ProjectStatus', 'ArStatus', 'TaskPriority',
        'RagStatus', 'DeliveryState', 'ReportStatus', 'MonthlyReportStatus', 'ActivityStatus',
        'ReportTemplateStatus', 'ReportTemplateLineStatus', 'AssignmentStatus', 'ReopenRequestStatus',
    );
});

test('the W-28 inconsistencies map onto the unified tones', function (HasStatusTone $case, StatusTone $tone) {
    expect($case->tone())->toBe($tone);
})->with([
    'red is critical' => [RagStatus::Red, StatusTone::Critical],
    'amber is warn' => [RagStatus::Amber, StatusTone::Warn],
    'done is good' => [TaskStatus::Done, StatusTone::Good],
    'in progress is active' => [TaskStatus::InProgress, StatusTone::Active],
    'under review is warn' => [TaskStatus::UnderReview, StatusTone::Warn],
    'approved project is good' => [ProjectStatus::Approved, StatusTone::Good],
]);

test('each tone has badge and dot classes on the status tokens', function (StatusTone $tone) {
    expect($tone->badgeClasses())->toContain('text-')
        ->and($tone->badgeClasses())->toContain('bg-')
        ->and($tone->dotClass())->toStartWith('bg-');
})->with(StatusTone::cases());

test('the critical tone is a status-critical fill with white text, not the brand red class', function () {
    expect(StatusTone::Critical->badgeClasses())->toContain('bg-status-critical')
        ->and(StatusTone::Critical->badgeClasses())->toContain('text-white')
        ->and(StatusTone::Critical->badgeClasses())->not->toContain('bg-primary')
        ->and(StatusTone::Critical->dotClass())->toBe('bg-status-critical');
});

/** The only cases allowed the red (D1): critical, overdue and failed-type states. */
const CRITICAL_STATUS_CASES = [
    'ArStatus::Failed',
    'AssignmentStatus::Rejected',
    'DonorReportState::Overdue',
    'ProposalStatus::Rejected',
    'RagStatus::Red',
    'ReopenRequestStatus::Denied',
    'TaskPriority::High',
];

test('every status case has an icon and a palette tone, and only critical-type cases are red', function (string $enum) {
    foreach ($enum::cases() as $case) {
        $key = class_basename($enum).'::'.$case->name;

        expect($case->icon())->toBeString()->not->toBeEmpty($key.' has no icon')
            ->and(StatusTone::cases())->toContain($case->tone())
            ->and($case->tone() === StatusTone::Critical)->toBe(in_array($key, CRITICAL_STATUS_CASES, true), $key.' red mismatch');
    }
})->with(fn () => array_map(fn (string $enum) => [$enum], statusToneEnums()));

test('each tone has its own icon', function () {
    $icons = array_map(fn (StatusTone $tone): string => $tone->icon(), StatusTone::cases());

    expect($icons)->each->not->toBeEmpty()
        ->and(array_unique($icons))->toHaveCount(count(StatusTone::cases()));
});
