<?php

use App\Models\Activity;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\UserNotification;
use App\Notifications\Delivery\NotificationTester;
use Modules\Core\Models\User;

/*
| "Send a test" on the settings page: real sends, and messages that say only
| what the other end confirmed.
*/

beforeEach(function () {
    $this->user = Activity::withoutRecording(fn (): User => User::factory()->create());
});

test('a device test reaches only the person\'s own device', function () {
    $push = fakePushService();
    $theirs = PushSubscription::factory()->create();

    expect(app(NotificationTester::class)->send($this->user, 'device', $theirs->id))
        ->toBe(['ok' => false, 'message' => __('notification_delivery.tester.device_not_found')])
        ->and($push->sent)->toBe([]);
});

test('a device test to a device the push service forgot removes it', function () {
    fakePushService([410]);
    $mine = PushSubscription::factory()->for($this->user)->create();

    expect(app(NotificationTester::class)->send($this->user, 'device', $mine->id))
        ->toBe(['ok' => false, 'message' => __('notification_delivery.tester.device_gone')])
        ->and($mine->fresh())->toBeNull();
});

test('a device test says when push is switched off for that device', function () {
    fakePushService();
    $mine = PushSubscription::factory()->for($this->user)->create();
    NotificationPreference::factory()->for($this->user)->create(['desktop' => false]);

    expect(app(NotificationTester::class)->send($this->user, 'device', $mine->id))
        ->toBe(['ok' => true, 'message' => __('notification_delivery.tester.device_accepted_push_off')]);
});

test('testing every device counts what the push service accepted', function () {
    $push = fakePushService([201, 500]);
    PushSubscription::factory()->for($this->user)->count(2)->create();

    expect(app(NotificationTester::class)->send($this->user, 'devices'))
        ->toBe(['ok' => true, 'message' => __('notification_delivery.tester.devices_accepted', ['accepted' => 1, 'total' => 2])])
        ->and($push->sent)->toHaveCount(2);
});

test('push tests say when push is not set up on the server', function () {
    fakePushService();
    config(['webpush.vapid.public_key' => null]);

    expect(app(NotificationTester::class)->send($this->user, 'devices'))
        ->toBe(['ok' => false, 'message' => __('notification_delivery.tester.push_not_configured')]);
});

test('an in-app test lands in the bell unless in-app is off', function () {
    expect(app(NotificationTester::class)->send($this->user, 'in_app')['ok'])->toBeTrue()
        ->and(UserNotification::query()->forUser($this->user)->count())->toBe(1);

    NotificationPreference::factory()->for($this->user)->create(['in_app' => false]);

    expect(app(NotificationTester::class)->send($this->user, 'in_app'))
        ->toBe(['ok' => false, 'message' => __('notification_delivery.tester.in_app_off')]);
});

test('an email test goes out only with email switched on', function () {
    expect(app(NotificationTester::class)->send($this->user, 'email'))
        ->toBe(['ok' => true, 'message' => __('notification_delivery.tester.email_sent', ['email' => $this->user->email])])
        ->and(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(1);

    NotificationPreference::factory()->for($this->user)->create(['email' => false]);

    expect(app(NotificationTester::class)->send($this->user, 'email'))
        ->toBe(['ok' => false, 'message' => __('notification_delivery.tester.email_off')]);
});

test('an unknown target is refused', function () {
    expect(app(NotificationTester::class)->send($this->user, 'fax')['ok'])->toBeFalse();
});
