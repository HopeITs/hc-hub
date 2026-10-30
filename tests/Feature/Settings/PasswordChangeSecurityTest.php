<?php

use App\Livewire\Settings\Password;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Change the password through Settings > Password with this current password.
 */
function changePassword(string $currentPassword): Testable
{
    return Livewire::test(Password::class)
        ->set('current_password', $currentPassword)
        ->set('password', 'correct-horse-battery-staple')
        ->set('password_confirmation', 'correct-horse-battery-staple')
        ->call('updatePassword');
}

test('the sixth current-password check within a minute is refused even with the right password', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $this->actingAs($user);

    foreach (range(1, 5) as $attempt) {
        changePassword('wrong-password')->assertHasErrors(['current_password' => __('validation.current_password')]);
    }

    changePassword('password')
        ->assertHasErrors(['current_password' => __('security.confirm_throttle', ['seconds' => 60])]);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('a password change signs other browsers out and forgets every trusted browser', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create(['remember_token' => 'old-remember-token']);
    $colleague = User::factory()->create();
    TrustedDevice::factory()->for($user)->count(2)->create();
    TrustedDevice::factory()->for($colleague)->create();

    $this->actingAs($user);

    foreach ([[session()->getId(), $user->id], ['laptop-session', $user->id], ['colleague-session', $colleague->id]] as [$id, $userId]) {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => base64_encode('payload'),
            'last_activity' => time(),
        ]);
    }

    changePassword('password')->assertHasNoErrors();

    $user->refresh();

    expect(Hash::check('correct-horse-battery-staple', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-remember-token')
        ->and(DB::table('sessions')->pluck('id')->all())->toEqualCanonicalizing([session()->getId(), 'colleague-session'])
        ->and($user->trustedDevices()->count())->toBe(0)
        ->and($colleague->trustedDevices()->count())->toBe(1);
});
