<?php

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('the login screen links to forgot password', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'), false);
});

test('reset password link screen can be rendered', function () {
    $this->get(route('password.request'))->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('auth.send-link-reset-password-status'));

    Notification::assertSentTo($user, ResetPassword::class);
});

test('an unknown email gets the same answer, so accounts cannot be enumerated', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'nobody@example.org'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('auth.send-link-reset-password-status'));

    Notification::assertNothingSent();
});

test('reset link requests are rate limited', function () {
    Notification::fake();
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.email'), ['email' => $user->email]);
    }

    $this->post(route('password.email'), ['email' => $user->email])->assertTooManyRequests();
});

test('reset password screen can be rendered', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
        $this->get(route('password.reset', $notification->token))->assertOk();

        return true;
    });
});

test('password can be reset with a valid token, which signs every device out', function () {
    Notification::fake();
    $user = User::factory()->create();
    TrustedDevice::factory()->for($user)->create();
    $oldRememberToken = $user->remember_token;

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });

    $user->refresh();

    expect(Hash::check('correct-horse-battery-staple', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldRememberToken)
        ->and($user->trustedDevices()->count())->toBe(0);
});
