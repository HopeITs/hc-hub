<?php

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Event;

test('confirm password screen can be rendered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('password.confirm'))
        ->assertOk();
});

test('the account password confirms the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('a wrong password does not confirm the session', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect(session('auth.password_confirmed_at'))->toBeNull();
});

test('the sixth confirmation attempt within a minute is refused even with the right password', function () {
    $this->freezeTime();
    $this->actingAs(User::factory()->create());

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.confirm.store'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
    }

    $this->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertSessionHasErrors(['password' => __('security.confirm_throttle', ['seconds' => 60])]);

    expect(session('auth.password_confirmed_at'))->toBeNull();
});

test('a right password before the limit clears the failed confirmations', function () {
    $this->actingAs(User::factory()->create());

    foreach ([4, 4, 4] as $failedAttempts) {
        foreach (range(1, $failedAttempts) as $attempt) {
            $this->post(route('password.confirm.store'), ['password' => 'wrong-password']);
        }

        $this->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertSessionHasNoErrors();
    }

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('the confirmation limit is per user', function () {
    $lockedOut = User::factory()->create();
    $colleague = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($lockedOut)->post(route('password.confirm.store'), ['password' => 'wrong-password']);
    }

    $this->actingAs($colleague)
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertSessionHasNoErrors();
});

test('ten failed confirmations from any ips lock the user out of confirming for fifteen minutes', function () {
    $this->freezeTime();
    $this->actingAs(User::factory()->create());

    foreach (range(1, 10) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.5.{$attempt}"])
            ->post(route('password.confirm.store'), ['password' => 'wrong-password']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.5.99'])
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertSessionHasErrors(['password' => __('security.confirm_throttle', ['seconds' => 900])]);
});

test('a wrong confirmation password is reported as a failed attempt without the password', function () {
    Event::fake([Failed::class]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'wrong-password']);

    Event::assertDispatched(Failed::class, fn (Failed $event): bool => $event->guard === 'web'
        && $event->user?->is($user)
        && $event->credentials === ['email' => $user->email]);
});

test('a password login counts as a fresh password confirmation', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('a failed login confirms nothing', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

    expect(session('auth.password_confirmed_at'))->toBeNull()
        ->and(session('auth.password_checked'))->toBeNull();
});
