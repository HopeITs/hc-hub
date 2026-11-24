<?php

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Http\UserAgent;
use App\Models\Activity;
use App\Models\NotificationMute;
use App\Models\NotificationOutboxEntry;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\UserNotification;
use App\Notifications\Alerts\AlertDispatcher;
use App\Notifications\AppNotification;
use App\Notifications\NewDeviceSignIn;
use App\Notifications\NotificationPayload;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Core\Models\User;

/*
| From an AppNotification to the bell, the person's devices and their inbox,
| as their notification settings say. The queue is sync in tests, so a copy
| due now is sent before notify() returns.
*/

function deliveryPayload(NotificationPriority $priority = NotificationPriority::Info, ?string $groupKey = null, string $module = 'tasks'): NotificationPayload
{
    return new NotificationPayload(
        kind: NotificationKind::Action,
        module: $module,
        priority: $priority,
        key: 'notifications.events.task.updated',
        params: ['actor' => 'Sami', 'subject' => 'Plan'],
        subjectType: 'task',
        subjectId: 9,
        groupKey: $groupKey ?? 'task:'.Str::random(6),
    );
}

/**
 * @return Collection<int, mixed>
 */
function sentMails(): Collection
{
    return app('mailer')->getSymfonyTransport()->messages();
}

beforeEach(function () {
    $this->user = Activity::withoutRecording(fn (): User => User::factory()->create());
    $this->push = fakePushService();
    $this->device = PushSubscription::factory()->for($this->user)->create();
    $this->prefer = fn (array $attributes) => NotificationPreference::factory()->for($this->user)->create($attributes);
});

test('by default a notification reaches the bell and the devices but not the inbox', function () {
    $this->user->notify(new AppNotification(deliveryPayload()));

    $row = UserNotification::query()->forUser($this->user)->sole();

    expect($this->push->sent)->toHaveCount(1)
        ->and($this->push->sent[0]['message']['url'])->toBe(route('notifications.open', $row->id))
        ->and(NotificationOutboxEntry::query()->sole()->sent_at)->not->toBeNull()
        ->and(sentMails())->toHaveCount(0);
});

test('a module opted into email sends one email and the others do not', function () {
    ($this->prefer)(['categories' => ['tasks' => ['email' => true]]]);

    $this->user->notify(new AppNotification(deliveryPayload()));
    $this->user->notify(new AppNotification(deliveryPayload(module: 'projects')));

    expect(sentMails())->toHaveCount(1)
        ->and(array_keys(sentMails()->first()->getOriginalMessage()->getTo()))->toBe([0])
        ->and(sentMails()->first()->getOriginalMessage()->getTo()[0]->getAddress())->toBe($this->user->email);
});

test('with in-app off nothing reaches the bell but push still goes out', function () {
    ($this->prefer)(['in_app' => false]);

    $this->user->notify(new AppNotification(deliveryPayload()));

    expect(UserNotification::query()->count())->toBe(0)
        ->and($this->push->sent)->toHaveCount(1)
        ->and($this->push->sent[0]['message']['url'])->toBe(route('notifications.index'))
        ->and(NotificationOutboxEntry::query()->sole()->sent_at)->not->toBeNull();
});

test('the master switch stops every channel', function () {
    ($this->prefer)(['enabled' => false, 'categories' => ['tasks' => ['email' => true]]]);

    $this->user->notify(new AppNotification(deliveryPayload()));

    expect(UserNotification::query()->count())->toBe(0)
        ->and(NotificationOutboxEntry::query()->count())->toBe(0)
        ->and($this->push->sent)->toBe([])
        ->and(sentMails())->toHaveCount(0);
});

test('a device switched to urgent only gets urgent notifications only', function () {
    $this->device->update(['urgent_only' => true]);

    $this->user->notify(new AppNotification(deliveryPayload()));
    $this->user->notify(new AppNotification(deliveryPayload(NotificationPriority::Critical)));

    expect($this->push->sent)->toHaveCount(1)
        ->and($this->push->sent[0]['message']['urgent'])->toBeTrue()
        ->and($this->push->sent[0]['options']['urgency'])->toBe('high');
});

test('desktop and mobile push are separate switches', function () {
    $phone = PushSubscription::factory()->for($this->user)->mobile()->create();
    ($this->prefer)(['desktop' => false]);

    $this->user->notify(new AppNotification(deliveryPayload()));

    expect(array_column($this->push->sent, 'subscription_id'))->toBe([$phone->id]);
});

test('grouped events do not send a second push, but an urgent one does', function () {
    $this->user->notify(new AppNotification(deliveryPayload(groupKey: 'task:9')));
    $this->travel(2)->minutes();
    $this->user->notify(new AppNotification(deliveryPayload(groupKey: 'task:9')));

    expect($this->push->sent)->toHaveCount(1)
        ->and(UserNotification::query()->sole()->data['count'])->toBe(2);

    $this->user->notify(new AppNotification(deliveryPayload(NotificationPriority::Critical, 'task:9')));

    expect($this->push->sent)->toHaveCount(2);
});

test('quiet hours hold push until they end, then it goes', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 23:30', config('app.timezone')));
    ($this->prefer)(['quiet_hours' => true, 'quiet_start' => '22:00', 'quiet_end' => '07:00']);

    $this->user->notify(new AppNotification(deliveryPayload()));

    expect($this->push->sent)->toBe([])
        ->and(UserNotification::query()->count())->toBe(1);

    $this->travelTo(CarbonImmutable::parse('2026-11-26 07:00', config('app.timezone')));
    $this->artisan('notifications:dispatch-due')->assertSuccessful();

    expect($this->push->sent)->toHaveCount(1);
});

test('a daily digest sends one summary for several notifications', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00', config('app.timezone')));
    ($this->prefer)(['frequency' => 'daily', 'digest_time' => '09:00']);

    foreach (range(1, 3) as $i) {
        $this->user->notify(new AppNotification(deliveryPayload()));
    }

    $this->travelTo(CarbonImmutable::parse('2026-11-26 09:00', config('app.timezone')));
    $this->artisan('notifications:dispatch-due')->assertSuccessful();

    expect($this->push->sent)->toHaveCount(1)
        ->and($this->push->sent[0]['message']['title'])->toBe(__('notification_delivery.summary.title', ['count' => 3]))
        ->and(NotificationOutboxEntry::query()->whereNull('sent_at')->count())->toBe(0);
});

test('a copy whose notification was read in the app is dropped', function () {
    Queue::fake();

    $this->user->notify(new AppNotification(deliveryPayload()));
    UserNotification::query()->sole()->markAsRead();

    $this->artisan('notifications:dispatch-due')->assertSuccessful();

    expect($this->push->sent)->toBe([])
        ->and(NotificationOutboxEntry::query()->sole()->error)->toBe('read_in_app');
});

test('without a VAPID pair push is skipped silently and the bell still works', function () {
    config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

    $this->user->notify(new AppNotification(deliveryPayload()));

    expect(UserNotification::query()->count())->toBe(1)
        ->and(NotificationOutboxEntry::query()->count())->toBe(0)
        ->and($this->push->sent)->toBe([]);
});

test('a broken preference is reported and the notification still reaches the bell', function () {
    Exceptions::fake();
    ($this->prefer)([]);
    NotificationPreference::retrieved(fn () => throw new RuntimeException('preferences unreadable'));

    $this->user->notify(new AppNotification(deliveryPayload()));

    expect(UserNotification::query()->count())->toBe(1);
    Exceptions::assertReported(RuntimeException::class);
});

test('a muted module still reaches nobody on any channel', function () {
    $muted = userWithRole('Managers');
    $listening = userWithRole('Managers');
    PushSubscription::factory()->for($muted)->create();
    $listeningDevice = PushSubscription::factory()->for($listening)->create();
    NotificationMute::query()->create(['user_id' => $muted->id, 'module' => 'tasks']);

    $payload = app(AlertDispatcher::class)->payload('tasks', NotificationPriority::Info, 'notifications.alerts.due_soon.today', ['subject' => 'Plan'], 'task', 7);
    app(AlertDispatcher::class)->send('due_soon', 'd0:x', $payload, [], [$muted->id, $listening->id]);

    expect(NotificationOutboxEntry::query()->pluck('user_id')->all())->toBe([$listening->id])
        ->and(array_column($this->push->sent, 'subscription_id'))->toBe([$listeningDevice->id]);
});

test('security emails ignore the notification settings', function () {
    ($this->prefer)(['enabled' => false, 'email' => false]);

    $this->user->notify(new NewDeviceSignIn(new UserAgent('Chrome', 'Windows'), '10.0.0.1', CarbonImmutable::now()));

    expect(sentMails())->toHaveCount(1);
});
