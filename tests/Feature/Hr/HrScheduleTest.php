<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/*
| The HR background jobs HrServiceProvider schedules, at the times the HR app
| ran them. A missing row here is a job that silently never runs.
*/

/**
 * @return array<string, Event>
 */
function hrScheduledEvents(): array
{
    return collect(app(Schedule::class)->events())
        ->mapWithKeys(fn (Event $event): array => [trim((string) preg_replace('/^.*artisan"?\s+/', '', (string) $event->command)) => $event])
        ->all();
}

test('the recruitment jobs are scheduled as the HR app ran them', function (string $command, string $expression, bool $withoutOverlapping) {
    $events = hrScheduledEvents();

    expect($events)->toHaveKey($command)
        ->and($events[$command]->expression)->toBe($expression)
        ->and($events[$command]->withoutOverlapping)->toBe($withoutOverlapping)
        ->and($events[$command]->onOneServer)->toBeTrue();
})->with([
    ['recruitment:sync-campaign-states', '* * * * *', true],
    ['recruitment:scan-files', '*/15 * * * *', true],
    ['recruitment:interview-reminders', '*/5 * * * *', true],
    ['recruitment:expire-offers', '*/15 * * * *', true],
    ['recruitment:rejection-notices', '*/5 * * * *', true],
    ['recruitment:apply-due', '30 0 * * *', false],
    ['recruitment:retention-sweep', '15 2 * * *', true],
]);

test('the contract sweep and the auto-renewals run after the amendments and before the evening journeys', function () {
    $events = hrScheduledEvents();

    expect(array_map(fn (string $command): string => $events[$command]->expression, [
        'contracts:apply-amendments',
        'contracts:deactivate-expired',
        'contracts:process-auto-renewals',
        'hr:track-renewal-journeys',
        'hr:deactivate-departed',
    ]))->toBe(['25 21 * * *', '30 21 * * *', '35 21 * * *', '45 21 * * *', '50 21 * * *']);
});

test('the leave decision reminders run every morning', function () {
    expect(hrScheduledEvents()['leaves:send-decision-reminders']->expression)->toBe('5 8 * * *');
});

test('every recruitment command is registered with Artisan', function () {
    $registered = array_keys(Artisan::all());

    expect($registered)->toContain(
        'recruitment:analyze-cvs',
        'recruitment:apply-due',
        'recruitment:scanner-check',
        'recruitment:expire-campaigns',
        'recruitment:expire-offers',
        'recruitment:rejection-notices',
        'recruitment:scan-files',
        'recruitment:demo-application-link',
        'recruitment:interview-reminders',
        'recruitment:indicators:snapshot',
        'recruitment:retention-sweep',
        'recruitment:sync-campaign-states',
    );
});
