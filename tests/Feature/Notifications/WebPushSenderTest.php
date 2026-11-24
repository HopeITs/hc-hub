<?php

use App\Models\Activity;
use App\Models\NotificationOutboxEntry;
use App\Models\PushSubscription;
use App\Notifications\Delivery\WebPushSender;
use Illuminate\Support\Str;
use Modules\Core\Models\User;

/*
| What a push service's answer does to the stored subscription, and the daily
| clean-up of devices and outbox rows.
*/

beforeEach(function () {
    $this->user = Activity::withoutRecording(fn (): User => User::factory()->create());
    $this->subscription = PushSubscription::factory()->for($this->user)->create();
    $this->message = ['title' => 'Plan', 'body' => 'Updated', 'url' => '/'];
});

test('a subscription the push service no longer knows is deleted', function (int $status) {
    fakePushService([$status]);

    $outcome = app(WebPushSender::class)->sendToSubscription($this->subscription, $this->message);

    expect($outcome['expired'])->toBeTrue()
        ->and($this->subscription->fresh())->toBeNull();
})->with([404, 410]);

test('other failures are counted and the subscription goes at the limit', function () {
    config(['webpush.max_failures' => 2]);
    fakePushService([500, 500]);
    $sender = app(WebPushSender::class);

    $sender->sendToSubscription($this->subscription, $this->message);

    expect($this->subscription->fresh()->failure_count)->toBe(1);

    $sender->sendToSubscription($this->subscription->fresh(), $this->message);

    expect($this->subscription->fresh())->toBeNull();
});

test('a success clears the failure count', function () {
    fakePushService([201]);
    $this->subscription->forceFill(['failure_count' => 3, 'last_error' => 'HTTP 500'])->save();

    app(WebPushSender::class)->sendToSubscription($this->subscription, $this->message);

    expect($this->subscription->fresh()->failure_count)->toBe(0)
        ->and($this->subscription->fresh()->last_success_at)->not->toBeNull();
});

test('nothing is sent and nothing breaks without a VAPID pair', function () {
    $push = fakePushService();
    config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

    expect(WebPushSender::configured())->toBeFalse()
        ->and(app(WebPushSender::class)->sendToUserForModules($this->user, $this->message, ['tasks'], urgent: false))->toBe(0)
        ->and($push->sent)->toBe([]);
});

test('the daily prune removes expired and stale devices and old outbox rows', function () {
    $expired = PushSubscription::factory()->create(['expires_at' => now()->subMinute()]);
    $stale = PushSubscription::factory()->create(['last_seen_at' => now()->subDays(121)]);
    $outboxRow = fn (int $sentDaysAgo): NotificationOutboxEntry => NotificationOutboxEntry::query()->forceCreate([
        'user_id' => $this->user->id,
        'notification_id' => (string) Str::uuid(),
        'channel' => NotificationOutboxEntry::PUSH,
        'category' => 'tasks',
        'payload' => [],
        'deliver_after' => now()->subDays($sentDaysAgo),
        'sent_at' => now()->subDays($sentDaysAgo),
    ]);
    $oldRow = $outboxRow(31);
    $recentRow = $outboxRow(1);

    $this->artisan('push:prune')->assertSuccessful();

    expect($expired->fresh())->toBeNull()
        ->and($stale->fresh())->toBeNull()
        ->and($this->subscription->fresh())->not->toBeNull()
        ->and($oldRow->fresh())->toBeNull()
        ->and($recentRow->fresh())->not->toBeNull();
});
