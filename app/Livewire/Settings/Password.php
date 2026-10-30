<?php

namespace App\Livewire\Settings;

use App\Actions\Fortify\AuthenticateWithPassword;
use App\Actions\Fortify\SignOutOtherDevices;
use App\Actions\Fortify\TrustedDevices;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Password extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     *
     * The current password is checked like a password confirmation (the same
     * limits per user and IP and per user, with backoff), so this form cannot
     * be used to guess it. A new password signs every other browser out and
     * ends the trust of every browser, as a password reset does.
     */
    public function updatePassword(): void
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
            ]);

            if (! AuthenticateWithPassword::confirmsPassword($user, $this->current_password, 'current_password')) {
                throw ValidationException::withMessages([
                    'current_password' => [__('validation.current_password')],
                ]);
            }
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        SignOutOtherDevices::run(request(), $user);
        TrustedDevices::forgetAll($user);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}
