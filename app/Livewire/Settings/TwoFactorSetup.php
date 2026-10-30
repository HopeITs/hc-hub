<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\RequiresConfirmedPassword;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;
use Throwable;

/**
 * Turning on the authenticator app (ported from hc-tracking's set-up modal):
 * show the QR code and key, then confirm with a first code. Shown inline on
 * the security page, inside its own panel, rather than in a Flux modal.
 */
class TwoFactorSetup extends Component
{
    use RequiresConfirmedPassword;

    #[Locked]
    public bool $requiresConfirmation = true;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $open = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(bool $requiresConfirmation = true): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        app(EnableTwoFactorAuthentication::class)($this->user());

        $this->open = true;
        $this->loadSetupData();
    }

    /**
     * Show the code step, or finish straight away when Fortify does not ask
     * for confirmation.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        $this->validate();

        $confirmTwoFactorAuthentication($this->user(), $this->code);

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg', 'showVerificationStep', 'open');

        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.settings.two-factor-setup');
    }

    private function loadSetupData(): void
    {
        $user = $this->user()->fresh();

        try {
            if ($user === null || $user->two_factor_secret === null) {
                throw new RuntimeException('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = (string) decrypt($user->two_factor_secret);
        } catch (Throwable) {
            $this->addError('setupData', __('security.setup_data_failed'));

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
