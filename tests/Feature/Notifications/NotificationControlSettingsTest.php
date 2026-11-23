<?php

use App\Models\Activity;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Core\Models\User;

/*
| The notification settings page: every control saves on its own, bad values
| are refused, and a person only ever reaches their own devices.
*/

beforeEach(function () {
    $this->user = Activity::withoutRecording(fn (): User => User::factory()->create());
    RateLimiter::clear('notification-test:'.$this->user->getKey());
});

function controlSettings(User $user): Testable
{
    return Livewire::actingAs($user)->test('pages::notifications.settings');
}

test('the page shows every section and keeps the existing ones', function () {
    fakePushService();
    PushSubscription::factory()->for($this->user)->create(['device_name' => 'Office laptop']);

    $this->actingAs($this->user)->get(route('notification-settings.edit'))
        ->assertOk()
        ->assertSeeHtml('id="desktop-app"')
        ->assertSeeHtml('data-popup-preference')
        ->assertSeeHtml('data-module-mutes')
        ->assertSeeHtml('data-push-configured="yes"')
        ->assertSeeHtml('data-general-settings')
        ->assertSeeHtml('data-module-channels')
        ->assertSeeHtml('data-matrix-cell="system.email"')
        ->assertSeeHtml('data-schedule-settings')
        ->assertSeeHtml('data-test-send')
        ->assertSeeText(__('notification_settings.mutes_versus_matrix'))
        ->assertSeeText('Office laptop')
        ->assertDontSee('test-private-key');
});

test('without push on the server the page says so and disables the push controls', function () {
    config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

    $this->actingAs($this->user)->get(route('notification-settings.edit'))
        ->assertOk()
        ->assertSeeHtml('data-push-configured="no"')
        ->assertSeeHtml('data-push-not-configured')
        ->assertSeeText(__('notification_settings.device.not_configured'))
        ->assertSeeText(__('notification_settings.general.push_off'))
        ->assertDontSeeHtml('data-test="push-enable"');
});

test('each switch saves as soon as it changes', function (string $field) {
    $before = (bool) NotificationPreference::for($this->user)->{$field};

    controlSettings($this->user)
        ->set('prefs.'.$field, ! $before)
        ->assertHasNoErrors()
        ->assertSet('saveState', 'saved');

    expect((bool) NotificationPreference::for($this->user)->{$field})->toBe(! $before);
})->with(['enabled', 'in_app', 'desktop', 'mobile', 'email', 'sound', 'previews', 'quiet_hours', 'urgent_bypass']);

test('frequency, times and time zone save', function () {
    controlSettings($this->user)
        ->set('prefs.frequency', 'daily')
        ->set('prefs.digest_time', '07:30')
        ->set('prefs.quiet_start', '21:00')
        ->set('prefs.quiet_end', '06:15')
        ->set('prefs.timezone', 'Asia/Damascus')
        ->assertHasNoErrors();

    $preference = NotificationPreference::for($this->user);

    expect($preference->frequency)->toBe('daily')
        ->and($preference->digest_time)->toBe('07:30')
        ->and($preference->quiet_start)->toBe('21:00')
        ->and($preference->quiet_end)->toBe('06:15')
        ->and($preference->timezone)->toBe('Asia/Damascus');
});

test('an empty time zone falls back to the hub default', function () {
    NotificationPreference::for($this->user)->fill(['timezone' => 'Asia/Damascus'])->save();

    controlSettings($this->user)->set('prefs.timezone', '')->assertHasNoErrors();

    expect(NotificationPreference::for($this->user)->timezone)->toBeNull();
});

test('a value that is not allowed is refused and put back', function (string $field, mixed $bad) {
    $before = NotificationPreference::for($this->user)->{$field};

    controlSettings($this->user)
        ->set('prefs.'.$field, $bad)
        ->assertHasErrors('prefs.'.$field)
        ->assertSet('saveState', 'error')
        ->assertSet('prefs.'.$field, (string) ($before ?? ''));

    expect(NotificationPreference::for($this->user)->{$field})->toBe($before);
})->with([
    'unknown frequency' => ['frequency', 'weekly'],
    'time out of range' => ['quiet_start', '25:00'],
    'time with seconds' => ['quiet_end', '10:00:00'],
    'time as text' => ['digest_time', 'noon'],
    'unknown time zone' => ['timezone', 'Mars/Olympus'],
]);

test('a field outside the list is never written', function () {
    controlSettings($this->user)->set('prefs.user_id', 999);

    expect(NotificationPreference::for($this->user)->user_id)->toBe($this->user->id);
});

test('working days save, and all seven means every day', function () {
    $page = controlSettings($this->user)->set('workingDays', [0, 1, 2, 3, 4])->assertHasNoErrors();

    expect(NotificationPreference::for($this->user)->working_days)->toBe([0, 1, 2, 3, 4]);

    $page->set('workingDays', [0, 1, 2, 3, 4, 5, 6])->assertHasNoErrors();

    expect(NotificationPreference::for($this->user)->working_days)->toBeNull();
});

test('working days refuse an empty week and a day that does not exist', function (array $days) {
    NotificationPreference::for($this->user)->fill(['working_days' => [1, 2]])->save();

    controlSettings($this->user)
        ->set('workingDays', $days)
        ->assertHasErrors('workingDays')
        ->assertSet('workingDays', [1, 2]);

    expect(NotificationPreference::for($this->user)->working_days)->toBe([1, 2]);
})->with([
    'no day' => [[]],
    'day 7' => [[1, 7]],
    'a repeated day' => [[1, 1]],
]);

test('a module can be switched off for one channel only', function () {
    controlSettings($this->user)
        ->call('setModuleChannel', 'system', 'email', false)
        ->assertHasNoErrors()
        ->assertSet('matrix.system.email', false)
        ->assertSet('matrix.system.in_app', true);

    $matrix = NotificationPreference::for($this->user)->matrix(['system']);

    expect($matrix['system']['email'])->toBeFalse()
        ->and($matrix['system']['in_app'])->toBeTrue();
});

test('the table refuses a module or channel that is not offered', function (string $module, string $channel) {
    $before = NotificationPreference::for($this->user)->categories;

    controlSettings($this->user)
        ->call('setModuleChannel', $module, $channel, false)
        ->assertHasErrors('matrix');

    expect(NotificationPreference::for($this->user)->categories)->toBe($before);
})->with([
    'unknown module' => ['payroll-secrets', 'email'],
    'unknown channel' => ['system', 'sms'],
]);

test('a person renames, switches and removes their own device', function () {
    $device = PushSubscription::factory()->for($this->user)->create();

    $page = controlSettings($this->user)
        ->call('renameDevice', $device->id, '  Office laptop  ')
        ->call('setDeviceEnabled', $device->id, false)
        ->call('setDeviceUrgentOnly', $device->id, true)
        ->assertDispatched('push-devices-updated');

    expect($device->fresh())
        ->device_name->toBe('Office laptop')
        ->enabled->toBeFalse()
        ->urgent_only->toBeTrue();

    $page->call('confirmRemoveDevice', $device->id)
        ->assertSet('showRemoveDevice', true)
        ->call('removeDevice')
        ->assertSet('showRemoveDevice', false)
        ->assertDispatched('push-device-removed', id: $device->id);

    expect($device->fresh())->toBeNull();
});

test('a device name longer than 80 characters is refused', function () {
    $device = PushSubscription::factory()->for($this->user)->create(['device_name' => 'Phone']);

    controlSettings($this->user)
        ->call('renameDevice', $device->id, str_repeat('a', 81))
        ->assertHasErrors('device.'.$device->id);

    expect($device->fresh()->device_name)->toBe('Phone');
});

test('nobody can touch someone else\'s device', function (string $method, array $arguments) {
    $theirs = PushSubscription::factory()->create(['device_name' => 'Theirs', 'enabled' => true, 'urgent_only' => false]);

    controlSettings($this->user)->call($method, $theirs->id, ...$arguments)->assertNotFound();

    expect($theirs->fresh())
        ->not->toBeNull()
        ->device_name->toBe('Theirs')
        ->enabled->toBeTrue()
        ->urgent_only->toBeFalse();
})->with([
    'rename' => ['renameDevice', ['Mine now']],
    'switch off' => ['setDeviceEnabled', [false]],
    'urgent only' => ['setDeviceUrgentOnly', [true]],
    'remove' => ['confirmRemoveDevice', []],
    'test' => ['testDevice', []],
]);

test('someone else\'s device is not listed', function () {
    PushSubscription::factory()->create(['device_name' => 'Stranger phone']);

    $this->actingAs($this->user)->get(route('notification-settings.edit'))
        ->assertDontSeeText('Stranger phone')
        ->assertSeeHtml('data-devices-empty');
});

test('a test send goes through the real path', function () {
    controlSettings($this->user)
        ->set('testTarget', 'in_app')
        ->call('sendTest')
        ->assertSet('testResult.ok', true);

    expect($this->user->notifications()->count())->toBe(1);
});

test('a test to this device needs this device to be subscribed', function () {
    controlSettings($this->user)
        ->set('testTarget', 'device')
        ->call('sendTest', null)
        ->assertSet('testResult', ['ok' => false, 'message' => __('notification_settings.test.no_device')]);
});

test('a test to a target that does not exist is refused', function () {
    controlSettings($this->user)
        ->call('sendTest', null, 'sms')
        ->assertSet('testResult', ['ok' => false, 'message' => __('notification_settings.test.invalid_target')]);

    expect($this->user->notifications()->count())->toBe(0);
});

test('test sends are limited to five a minute per person', function () {
    $page = controlSettings($this->user)->set('testTarget', 'in_app');

    foreach (range(1, 5) as $attempt) {
        $page->call('sendTest')->assertSet('testResult.ok', true);
    }

    $page->call('sendTest')->assertSet('testResult.ok', false);

    expect($this->user->notifications()->count())->toBe(5);
});

test('signed-in pages carry the push metas and never the private key', function () {
    fakePushService();
    NotificationPreference::for($this->user)->fill(['sound' => true])->save();

    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeHtml('<meta name="hc-vapid-key" content="test-public-key" />')
        ->assertSeeHtml('name="hc-push-routes"')
        ->assertSee(json_encode(['store' => route('push.store'), 'sync' => route('push.sync'), 'destroy' => route('push.destroy')]))
        ->assertSeeHtml('<meta name="hc-notif-sound" content="1" />')
        ->assertDontSee('test-private-key');
});

test('the public key meta is left out when push is not set up', function () {
    config(['webpush.vapid.public_key' => 'test-public-key', 'webpush.vapid.private_key' => null]);

    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeHtml('name="hc-vapid-key"')
        ->assertSeeHtml('name="hc-push-routes"');
});

test('the sound meta is off when sound or all notifications are off', function (array $state) {
    NotificationPreference::for($this->user)->fill($state)->save();

    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertSeeHtml('<meta name="hc-notif-sound" content="0" />');
})->with([
    'sound off' => [['enabled' => true, 'sound' => false]],
    'everything off' => [['enabled' => false, 'sound' => true]],
]);

test('guests get no push metas', function () {
    fakePushService();

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSeeHtml('hc-vapid-key')
        ->assertDontSeeHtml('hc-push-routes')
        ->assertDontSeeHtml('hc-notif-sound');
});

test('the service worker shows pushes and renews its subscription without touching page requests', function () {
    $worker = file_get_contents(public_path('sw.js'));

    expect($worker)
        ->toContain("addEventListener('push'")
        ->toContain("addEventListener('notificationclick'")
        ->toContain("addEventListener('pushsubscriptionchange'")
        ->toContain("'/push/renew'")
        ->not->toContain("addEventListener('fetch'")
        ->not->toContain('caches.')
        ->not->toContain('importScripts');
});
