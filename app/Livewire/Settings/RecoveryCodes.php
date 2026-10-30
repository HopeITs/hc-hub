<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\RequiresConfirmedPassword;
use App\Livewire\Concerns\WithFeedback;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * The authenticator app's recovery codes on the security page (ported from
 * hc-tracking). The codes are only read while the password confirmation is
 * fresh: this component can also be mounted inside a Livewire update of the
 * security page, which skips password.confirm.
 */
class RecoveryCodes extends Component
{
    use RequiresConfirmedPassword;
    use WithFeedback;

    /** @var list<string> */
    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        if ($this->passwordRecentlyConfirmed()) {
            $this->loadRecoveryCodes();
        }
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        $generateNewRecoveryCodes($this->user());

        $this->loadRecoveryCodes();

        $this->toast(__('security.recovery_codes_regenerated'));
    }

    public function render(): View
    {
        return view('livewire.settings.recovery-codes');
    }

    private function loadRecoveryCodes(): void
    {
        $user = $this->user()->fresh() ?? $this->user();

        if (! $user->hasEnabledTwoFactorAuthentication() || $user->two_factor_recovery_codes === null) {
            return;
        }

        try {
            $codes = json_decode((string) decrypt($user->two_factor_recovery_codes), true);
            $this->recoveryCodes = is_array($codes) ? array_values(array_map('strval', $codes)) : [];
        } catch (Throwable) {
            $this->addError('recoveryCodes', __('security.recovery_codes_failed'));

            $this->recoveryCodes = [];
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
