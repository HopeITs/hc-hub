<?php

use App\Enums\NotificationChannel;
use App\Models\Activity;
use App\Models\NotificationPreference;
use App\Notifications\Delivery\NotificationPreferenceGate;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

/*
| When a notification may reach someone, and on which channel. Times are in
| Asia/Damascus (the app's zone) unless the person picked another one.
*/

beforeEach(function () {
    config(['app.timezone' => 'Asia/Damascus']);
    $this->user = Activity::withoutRecording(fn (): User => User::factory()->create());
    $this->gate = app(NotificationPreferenceGate::class);
    $this->prefer = function (array $attributes): void {
        NotificationPreference::factory()->for($this->user)->create($attributes);
        $this->gate->forget($this->user->id);
    };
    $this->at = fn (string $time): CarbonImmutable => CarbonImmutable::parse($time, 'Asia/Damascus');
});

test('someone who never saved settings gets every channel but email, instantly', function () {
    expect($this->gate->allows($this->user, 'tasks', NotificationChannel::InApp))->toBeTrue()
        ->and($this->gate->allows($this->user, 'tasks', NotificationChannel::Desktop))->toBeTrue()
        ->and($this->gate->allows($this->user, 'tasks', NotificationChannel::Mobile))->toBeTrue()
        ->and($this->gate->allows($this->user, 'tasks', NotificationChannel::Email))->toBeFalse()
        ->and($this->gate->deliverAt($this->user, false, ($this->at)('2026-11-25 23:30'))->toDateTimeString())->toBe('2026-11-25 23:30:00')
        ->and(NotificationPreference::query()->count())->toBe(0);
});

test('the master switch closes every channel and keeps the choices underneath', function () {
    ($this->prefer)(['enabled' => false, 'categories' => ['tasks' => ['email' => true]]]);

    expect($this->gate->allows($this->user, 'tasks', NotificationChannel::InApp))->toBeFalse()
        ->and($this->gate->allows($this->user, 'tasks', NotificationChannel::Email))->toBeFalse();

    $this->user->notificationPreference->update(['enabled' => true]);

    expect($this->gate->allows($this->user, 'tasks', NotificationChannel::Email))->toBeTrue();
});

test('a module cell and a channel switch each close only their own channel', function () {
    ($this->prefer)(['mobile' => false, 'categories' => ['tasks' => ['desktop' => false]]]);

    expect($this->gate->allows($this->user, 'tasks', NotificationChannel::Desktop))->toBeFalse()
        ->and($this->gate->allows($this->user, 'projects', NotificationChannel::Desktop))->toBeTrue()
        ->and($this->gate->allows($this->user, 'projects', NotificationChannel::Mobile))->toBeFalse()
        ->and($this->gate->allowsPush($this->user, 'tasks'))->toBeFalse()
        ->and($this->gate->allowsPush($this->user, 'projects'))->toBeTrue();
});

test('quiet hours that wrap midnight hold push until they end', function (string $now, string $expected) {
    ($this->prefer)(['quiet_hours' => true, 'quiet_start' => '22:00', 'quiet_end' => '07:00']);

    expect($this->gate->deliverAt($this->user, false, ($this->at)($now))->toDateTimeString())->toBe($expected);
})->with([
    'before they start' => ['2026-11-25 21:59', '2026-11-25 21:59:00'],
    'late evening' => ['2026-11-25 23:30', '2026-11-26 07:00:00'],
    'after midnight' => ['2026-11-26 03:15', '2026-11-26 07:00:00'],
    'when they end' => ['2026-11-26 07:00', '2026-11-26 07:00:00'],
]);

test('quiet hours follow the time zone the person picked', function () {
    ($this->prefer)(['quiet_hours' => true, 'quiet_start' => '22:00', 'quiet_end' => '07:00', 'timezone' => 'Europe/Berlin']);

    // Damascus is UTC+3 and Berlin UTC+1 in November: 21:30 Damascus is 19:30 in
    // Berlin (not quiet), 00:30 is 22:30 (quiet), and Berlin's 07:00 is 09:00 here.
    expect($this->gate->deliverAt($this->user, false, ($this->at)('2026-11-25 21:30'))->toDateTimeString())->toBe('2026-11-25 21:30:00')
        ->and($this->gate->deliverAt($this->user, false, ($this->at)('2026-11-26 00:30'))->toDateTimeString())->toBe('2026-11-26 09:00:00');
});

test('outside the working days push waits for the next one', function () {
    // Sunday to Thursday; 2026-11-27 is a Friday.
    ($this->prefer)(['working_days' => [0, 1, 2, 3, 4]]);

    expect($this->gate->deliverAt($this->user, false, ($this->at)('2026-11-27 10:00'))->toDateTimeString())->toBe('2026-11-29 00:00:00');
});

test('the hourly and daily digests wait for their moment', function (string $frequency, string $now, string $expected) {
    ($this->prefer)(['frequency' => $frequency, 'digest_time' => '09:00']);

    expect($this->gate->deliverAt($this->user, false, ($this->at)($now))->toDateTimeString())->toBe($expected);
})->with([
    'hourly' => ['hourly', '2026-11-25 10:20', '2026-11-25 11:00:00'],
    'daily before the time' => ['daily', '2026-11-25 08:00', '2026-11-25 09:00:00'],
    'daily after the time' => ['daily', '2026-11-25 10:00', '2026-11-26 09:00:00'],
]);

test('an urgent notification skips quiet hours, working days and the digest', function () {
    ($this->prefer)(['frequency' => 'daily', 'quiet_hours' => true, 'working_days' => [1]]);
    $now = ($this->at)('2026-11-27 23:30');

    expect($this->gate->deliverAt($this->user, true, $now)->toDateTimeString())->toBe('2026-11-27 23:30:00')
        ->and($this->gate->deliverAt($this->user, false, $now)->greaterThan($now))->toBeTrue();
});

test('with the urgent bypass off urgent notifications wait like the rest', function () {
    ($this->prefer)(['quiet_hours' => true, 'urgent_bypass' => false]);

    expect($this->gate->deliverAt($this->user, true, ($this->at)('2026-11-25 23:30'))->toDateTimeString())->toBe('2026-11-26 07:00:00');
});

test('saving a preference refreshes what the gate answers', function () {
    expect($this->gate->allows($this->user, 'tasks', NotificationChannel::InApp))->toBeTrue();

    NotificationPreference::factory()->for($this->user)->create(['in_app' => false]);

    expect($this->gate->allows($this->user, 'tasks', NotificationChannel::InApp))->toBeFalse();
});
