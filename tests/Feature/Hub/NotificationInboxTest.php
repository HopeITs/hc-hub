<?php

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Settings;
use App\Models\NotificationMute;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\AppDatabaseChannel;
use App\Notifications\AppNotification;
use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use App\Notifications\NotificationPayload;
use App\Notifications\TestNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Hr\Notifications\CenterClosed;

/** A general (not personally addressed) notification of a mutable module. */
function centersAnnouncement(): Notification
{
    return new class extends Notification implements InboxNotification
    {
        use RoutesToInbox;

        public function inboxModule(): string
        {
            return 'centers';
        }

        public function inboxKind(): NotificationKind
        {
            return NotificationKind::Alert;
        }

        public function inboxPriority(object $notifiable): NotificationPriority
        {
            return NotificationPriority::Critical;
        }

        public function isDirect(): bool
        {
            return false;
        }

        /** @return array<string, mixed> */
        public function toArray(object $notifiable): array
        {
            return [
                'title' => ['en' => 'Center closed', 'ar' => 'تم إغلاق المركز'],
                'body' => ['en' => 'Main center', 'ar' => 'المركز الرئيسي'],
                'subject' => ['type' => 'center', 'id' => 7],
            ];
        }
    };
}

function keyedPayload(string $groupKey, NotificationPriority $priority = NotificationPriority::Info): NotificationPayload
{
    return new NotificationPayload(
        kind: NotificationKind::Action,
        module: 'leaves',
        priority: $priority,
        key: 'notifications.settings.toggle',
        params: ['module' => ['localized' => ['en' => 'Leaves', 'ar' => 'الإجازات']]],
        groupKey: $groupKey,
    );
}

test('an HR-style notification is filed with its module, kind, priority, subject and search text', function () {
    $user = User::factory()->create();

    $user->notify(centersAnnouncement());

    $row = UserNotification::query()->sole();
    expect($row->module)->toBe('centers')
        ->and($row->kind)->toBe(NotificationKind::Alert)
        ->and($row->priority)->toBe(NotificationPriority::Critical)
        ->and($row->subject_type)->toBe('center')
        ->and($row->subject_id)->toBe(7)
        ->and($row->notifiable_type)->toBe('user')
        ->and($row->data['title']['ar'])->toBe('تم إغلاق المركز')
        ->and($row->search_text)->toContain('Center closed')->toContain('المركز الرئيسي')
        ->and($row->type)->not->toBe(AppNotification::class);
});

test('HR notification classes go through the hub channel and keep their class as the type', function () {
    expect(new CenterClosed(new App\Models\Center))
        ->toBeInstanceOf(InboxNotification::class)
        ->and((new CenterClosed(new App\Models\Center))->via(new User))->toBe([AppDatabaseChannel::class])
        ->and((new CenterClosed(new App\Models\Center))->isDirect())->toBeFalse();

    $user = User::factory()->create();
    $user->notify(new TestNotification);

    expect(UserNotification::query()->sole()->type)->toBe(TestNotification::class);
});

test('a job queued with the old database channel is still filed by the hub channel', function () {
    $user = User::factory()->create();
    $notification = new TestNotification;
    $notification->id = (string) Str::uuid();

    app(Illuminate\Notifications\ChannelManager::class)->driver('database')->send($user, $notification);

    expect(UserNotification::query()->sole())
        ->module->toBe('hub')
        ->search_text->toContain('Test Notification');
});

test('old App\\Notifications class names of HR resolve to the moved classes', function () {
    expect(class_exists('App\\Notifications\\CenterClosed'))->toBeTrue()
        ->and(is_a('App\\Notifications\\NewLeaveRequest', Modules\Hr\Notifications\NewLeaveRequest::class, true))->toBeTrue()
        ->and(class_exists('App\\Notifications\\NoSuchNotification'))->toBeFalse();
});

test('a muted module silences general notifications but never direct ones', function () {
    $user = User::factory()->create();
    NotificationMute::query()->create(['user_id' => $user->id, 'module' => 'centers']);
    NotificationMute::query()->create(['user_id' => $user->id, 'module' => 'hub']);

    $user->notify(centersAnnouncement());
    $user->notify(new TestNotification);

    expect(UserNotification::query()->pluck('module')->all())->toBe(['hub']);
});

test('the settings page mutes and unmutes only mutable modules for the signed-in user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $page = Livewire::actingAs($user)->test(Settings::class)
        ->assertSeeHtml('data-mute-module="centers"')
        ->assertDontSeeHtml('data-mute-module="leaves"')
        ->call('toggleMute', 'centers')
        ->call('toggleMute', 'leaves');

    expect(NotificationMute::query()->get(['user_id', 'module'])->toArray())->toBe([['user_id' => $user->id, 'module' => 'centers']]);

    $page->call('toggleMute', 'centers');
    expect(NotificationMute::query()->count())->toBe(0)
        ->and($other->id)->not->toBe($user->id);
});

test('keyed notifications merge within the grouping window and render in both languages', function () {
    $user = User::factory()->create();

    $user->notify(new AppNotification(keyedPayload('leave:1')));
    $user->notify(new AppNotification(keyedPayload('leave:1', NotificationPriority::Important)));
    $user->notify(new AppNotification(keyedPayload('leave:2')));

    $merged = UserNotification::query()->where('group_key', 'leave:1')->sole();
    expect(UserNotification::query()->count())->toBe(2)
        ->and($merged->data['count'])->toBe(2)
        ->and($merged->priority)->toBe(NotificationPriority::Important)
        ->and($merged->search_text)->toContain('Notifications about Leaves')->toContain('إشعارات الإجازات');

    app()->setLocale('ar');
    Livewire::actingAs($user)->test(Bell::class)->call('loadPanel')
        ->assertSee('إشعارات الإجازات')
        ->assertSee('(+تحديث آخر)');
});

test('the bell counts, filters and snoozes only the signed-in user\'s visible rows', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $user->notify(centersAnnouncement());
    $user->notify(new TestNotification);
    $other->notify(new TestNotification);
    $mine = UserNotification::query()->forUser($user)->where('module', 'hub')->sole();
    $theirs = UserNotification::query()->forUser($other)->sole();

    $bell = Livewire::actingAs($user)->test(Bell::class)
        ->assertSet('count', 2)
        ->assertSeeHtml('data-critical-unread')
        ->call('loadPanel')
        ->assertSeeHtml('data-bell-pinned')
        ->assertSeeHtml('data-bell-module="centers"')
        ->call('showTab', 'alerts')
        ->assertSee('Center closed')
        ->assertDontSee('Test Notification')
        ->call('showTab', 'all')
        ->call('showModule', 'hub')
        ->assertSee('Test Notification')
        ->assertDontSee('Center closed');

    $bell->call('snooze', $mine->id, 'tomorrow')->call('snooze', $theirs->id, '1h')->call('snooze', $mine->id, 'forever');

    expect($mine->fresh()->snoozed_until?->format('H:i'))->toBe('08:00')
        ->and($theirs->fresh()->snoozed_until)->toBeNull();

    $bell->call('refreshList')->assertSet('count', 1)->assertDontSee('Test Notification');

    $bell->call('markAllAsRead');
    expect($mine->fresh()->read_at)->toBeNull('a snoozed row waits until it wakes')
        ->and(UserNotification::query()->forUser($user)->where('module', 'centers')->sole()->read_at)->not->toBeNull();

    $bell->call('markUnread', $theirs->id)->call('refreshList')->assertSet('count', 0);

    // Tomorrow 09:00 the snoozed row is back, unread, and the poll rings.
    $this->travelTo(now()->addDay()->setTime(9, 0));
    $bell->call('refreshCounts')->assertSet('count', 1)->assertDispatched('notif-arrived');
});

test('the data migration files existing HR rows under their module without touching the type', function () {
    $user = User::factory()->create();
    $id = (string) Str::uuid();
    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'App\\Notifications\\NewLeaveRequest',
        'notifiable_type' => 'user',
        'notifiable_id' => $user->id,
        'data' => json_encode([
            'title' => ['en' => 'New Leave Request', 'ar' => 'طلب إجازة جديد'],
            'body' => ['en' => 'From Sami', 'ar' => 'من سامي'],
            'subject' => ['type' => 'leave_request', 'id' => 42, 'action' => 'decide'],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (require database_path('migrations/2026_10_30_200002_fill_inbox_columns_of_existing_notifications.php'))->up();

    expect(DB::table('notifications')->where('id', $id)->first())
        ->type->toBe('App\\Notifications\\NewLeaveRequest')
        ->module->toBe('leaves')
        ->priority->toBe('important')
        ->kind->toBe('action')
        ->subject_type->toBe('leave_request')
        ->subject_id->toEqual(42)
        ->search_text->toBe("New Leave Request\nFrom Sami\nطلب إجازة جديد\nمن سامي");
});
