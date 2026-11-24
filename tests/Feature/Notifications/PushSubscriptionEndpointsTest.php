<?php

use App\Http\Controllers\PushSubscriptionController;
use App\Models\Activity;
use App\Models\PushSubscription;
use Illuminate\Support\Str;
use Modules\Core\Models\User;

/*
| The browser side of a push subscription: every endpoint works only on the
| signed-in person's own devices.
*/

/**
 * @return array<string, mixed>
 */
function browserSubscription(string $endpoint = 'https://push.example.test/send/abc'): array
{
    return [
        'endpoint' => $endpoint,
        'expirationTime' => null,
        'keys' => [
            'p256dh' => str_repeat('B', 87),
            'auth' => str_repeat('a', 22),
        ],
        'device' => ['name' => 'Chrome on Windows', 'type' => 'desktop', 'browser' => 'Chrome', 'platform' => 'Windows'],
    ];
}

beforeEach(function () {
    $this->user = Activity::withoutRecording(fn (): User => User::factory()->create());
    fakePushService();
});

test('a guest cannot reach the push endpoints', function () {
    $this->postJson(route('push.store'), browserSubscription())->assertUnauthorized();
    $this->deleteJson(route('push.destroy'), ['endpoint' => 'https://push.example.test/send/abc'])->assertUnauthorized();
});

test('subscribing stores the device for the signed-in person and remembers the browser', function () {
    $response = $this->actingAs($this->user)->postJson(route('push.store'), browserSubscription())
        ->assertOk()
        ->assertJson(['enabled' => true])
        ->assertCookie(PushSubscriptionController::DEVICE_COOKIE);

    $subscription = PushSubscription::query()->sole();

    expect($subscription->user_id)->toBe($this->user->id)
        ->and($response->json('id'))->toBe($subscription->id)
        ->and($subscription->device_id)->not->toBeNull();
});

test('subscribing without a VAPID pair says push is not configured and stores nothing', function () {
    config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

    $this->actingAs($this->user)->postJson(route('push.store'), browserSubscription())
        ->assertStatus(503)
        ->assertJson(['error' => 'not_configured']);

    expect(PushSubscription::query()->count())->toBe(0);
});

test('only an https push endpoint with well-formed keys is accepted', function (array $override, string $field) {
    $this->actingAs($this->user)->postJson(route('push.store'), array_replace_recursive(browserSubscription(), $override))
        ->assertJsonValidationErrors($field);
})->with([
    'plain http' => [['endpoint' => 'http://push.example.test/send/abc'], 'endpoint'],
    'key with bad characters' => [['keys' => ['p256dh' => str_repeat('<', 87)]], 'keys.p256dh'],
]);

test('sync and unsubscribe never touch another person\'s device', function () {
    $other = PushSubscription::factory()->create(['endpoint' => 'https://push.example.test/send/theirs']);

    $this->actingAs($this->user)->postJson(route('push.sync'), browserSubscription('https://push.example.test/send/theirs'))
        ->assertOk()
        ->assertJson(['known' => false, 'id' => null]);

    $this->actingAs($this->user)->deleteJson(route('push.destroy'), ['endpoint' => 'https://push.example.test/send/theirs'])
        ->assertOk()
        ->assertJson(['deleted' => false]);

    expect($other->fresh())->not->toBeNull()
        ->and($other->fresh()->user_id)->not->toBe($this->user->id);
});

test('unsubscribing removes the person\'s own device', function () {
    $mine = PushSubscription::factory()->for($this->user)->create();

    $this->actingAs($this->user)->deleteJson(route('push.destroy'), ['endpoint' => $mine->endpoint])
        ->assertOk()
        ->assertJson(['deleted' => true]);

    expect($mine->fresh())->toBeNull();
});

test('signing out forgets this browser\'s devices for that person only', function () {
    $browser = (string) Str::uuid();
    $here = PushSubscription::factory()->for($this->user)->create(['device_id' => $browser]);
    $elsewhere = PushSubscription::factory()->for($this->user)->create();
    $someoneElseHere = PushSubscription::factory()->create(['device_id' => $browser]);

    $this->actingAs($this->user)
        ->withCookie(PushSubscriptionController::DEVICE_COOKIE, $browser)
        ->post(route('logout'));

    expect($here->fresh())->toBeNull()
        ->and($elsewhere->fresh())->not->toBeNull()
        ->and($someoneElseHere->fresh())->not->toBeNull();
});

test('the service worker renews a rotated subscription with the old secret and no session', function () {
    $subscription = PushSubscription::factory()->for($this->user)->create(['failure_count' => 2]);
    $new = collect(browserSubscription('https://push.example.test/send/rotated'))->except('device')->all();

    $this->postJson(route('push.renew'), [
        'old_endpoint' => $subscription->endpoint,
        'old_auth' => $subscription->auth_token,
        'subscription' => $new,
    ])->assertOk()->assertJson(['ok' => true]);

    $subscription->refresh();

    expect($subscription->endpoint)->toBe('https://push.example.test/send/rotated')
        ->and($subscription->user_id)->toBe($this->user->id)
        ->and($subscription->failure_count)->toBe(0);
});

test('renewing with a wrong secret or an unknown endpoint changes nothing', function (bool $known) {
    $subscription = PushSubscription::factory()->for($this->user)->create();
    $endpoint = $subscription->endpoint;

    $this->postJson(route('push.renew'), [
        'old_endpoint' => $known ? $endpoint : 'https://push.example.test/send/unknown',
        'old_auth' => str_repeat('z', 22),
        'subscription' => collect(browserSubscription('https://push.example.test/send/stolen'))->except('device')->all(),
    ])->assertNotFound();

    expect($subscription->refresh()->endpoint)->toBe($endpoint);
})->with(['known endpoint' => [true], 'unknown endpoint' => [false]]);

test('renewing never takes over another person\'s device', function () {
    $mine = PushSubscription::factory()->for($this->user)->create();
    $theirs = PushSubscription::factory()->for(Activity::withoutRecording(fn (): User => User::factory()->create()))->create();

    $this->postJson(route('push.renew'), [
        'old_endpoint' => $mine->endpoint,
        'old_auth' => $mine->auth_token,
        'subscription' => [
            'endpoint' => $theirs->endpoint,
            'expirationTime' => null,
            'keys' => ['p256dh' => str_repeat('B', 87), 'auth' => str_repeat('a', 22)],
        ],
    ])->assertNotFound();

    expect($theirs->refresh()->user_id)->not->toBe($this->user->id)
        ->and(PushSubscription::query()->whereKey($mine->id)->exists())->toBeTrue();
});
