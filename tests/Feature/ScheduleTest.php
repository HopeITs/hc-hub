<?php

use App\Console\PlatformSchedule;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Hr\Console\HrSchedule;
use Modules\Tracking\Console\TrackingSchedule;

/*
| Every background job the hub runs, with its cron expression and whether it
| refuses to overlap itself or to run on more than one server (roadmap 4.13).
| The platform and each module list their jobs in a ScheduleSource; this is
| the whole list they add up to. A job added, dropped or retimed fails here.
*/

test('the schedule is exactly these jobs at these times', function () {
    $scheduled = collect(app(Schedule::class)->events())
        ->map(fn (Event $event): string => implode(' | ', [
            trim((string) preg_replace('/^.*artisan"?\s+/', '', (string) $event->command)),
            $event->expression,
            $event->withoutOverlapping ? 'no overlap' : 'may overlap',
            $event->onOneServer ? 'one server' : 'every server',
        ]))
        ->sort()
        ->values()
        ->all();

    expect($scheduled)->toBe([
        'annual-plans:remind | 0 7 * * * | no overlap | one server',
        'annual-plans:scan-milestones | * * * * * | no overlap | every server',
        'attendance:send-reminders | 15 8 * * * | no overlap | one server',
        'birthday:notify-conflicts | 25 8 * * * | no overlap | one server',
        'cache:prune-expired | 0 * * * * | no overlap | every server',
        'contracts:amendments-check | 0 6 * * 0 | may overlap | one server',
        'contracts:apply-amendments | 25 21 * * * | no overlap | one server',
        'contracts:deactivate-expired | 30 21 * * * | no overlap | one server',
        'contracts:process-auto-renewals | 35 21 * * * | no overlap | one server',
        'files:prune | 0 3 * * * | no overlap | every server',
        'hr:deactivate-departed | 50 21 * * * | no overlap | one server',
        'hr:send-contract-expiration-warnings | 0 8 * * * | no overlap | one server',
        'hr:send-onboarding-step-reminders | 12 8 * * * | no overlap | one server',
        'hr:send-orientation-reminders | 10 8 * * * | no overlap | one server',
        'hr:track-renewal-journeys | 45 21 * * * | no overlap | one server',
        'leavers:remind-missing-reasons | 30 8 1 * * | no overlap | one server',
        'leaves:send-decision-reminders | 5 8 * * * | no overlap | one server',
        'mail-lists:queue-starters | 20 8 * * * | no overlap | one server',
        'model:prune --model="App\Models\Activity" --model="App\Models\TrustedDevice" --model="App\Models\UserNotification" | 0 0 * * * | may overlap | every server',
        'monthly-reports:prepare | 0 5 * * * | no overlap | one server',
        'notifications:digests | * * * * * | no overlap | every server',
        'notifications:scan daily | 0 6 * * * | no overlap | every server',
        'notifications:scan frequent | */15 * * * * | no overlap | every server',
        'notifications:wake-snoozed | * * * * * | no overlap | every server',
        'positions:notify-critical | 40 7 * * * | no overlap | one server',
        'probation:start-reviews | 20 21 * * * | no overlap | one server',
        'proposals:remind-owners | 5 7 * * * | no overlap | one server',
        'recruitment:apply-due | 30 0 * * * | may overlap | one server',
        'recruitment:expire-offers | */15 * * * * | no overlap | one server',
        'recruitment:interview-reminders | */5 * * * * | no overlap | one server',
        'recruitment:rejection-notices | */5 * * * * | no overlap | one server',
        'recruitment:retention-sweep | 15 2 * * * | no overlap | one server',
        'recruitment:scan-files | */15 * * * * | no overlap | one server',
        'recruitment:sync-campaign-states | * * * * * | no overlap | one server',
        'reports:monthly-digest | 0 8 * * * | no overlap | one server',
    ]);
});

test('the platform and each module register their jobs the same way', function () {
    $sources = array_map(fn (object $source): string => $source::class, app(ModuleRegistry::class)->scheduleSources());

    expect($sources)->toEqualCanonicalizing([PlatformSchedule::class, HrSchedule::class, TrackingSchedule::class]);
});
