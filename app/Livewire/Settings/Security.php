<?php

namespace App\Livewire\Settings;

use App\Actions\Fortify\SignOutOtherDevices;
use App\Actions\Fortify\TrustedDevices;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Http\UserAgent;
use App\Livewire\Concerns\RequiresConfirmedPassword;
use App\Livewire\Concerns\WithFeedback;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Passkey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Settings > Security (ported from hc-tracking's security page, task 1.1):
 * what a sign-in asks for after the password (emailed code, authenticator
 * app), recovery codes, passkeys, the browsers signed in and the browsers
 * trusted to skip the second step.
 *
 * The route sits behind password.confirm, but Livewire update requests skip
 * that middleware, so every action that changes something re-checks the
 * confirmation (RequiresConfirmedPassword).
 */
class Security extends Component
{
    use RequiresConfirmedPassword;
    use WithFeedback;

    #[Locked]
    public bool $canManageTwoFactor = false;

    #[Locked]
    public bool $twoFactorEnabled = false;

    #[Locked]
    public bool $requiresConfirmation = false;

    /**
     * Whether this person's role must keep the authenticator app on
     * (see EnsureTwoFactorIsEnabled).
     */
    #[Locked]
    public bool $twoFactorRequired = false;

    /**
     * Whether each sign-in asks for a code emailed to the user
     * (see App\Actions\Fortify\RedirectIfTwoFactorRequired).
     */
    #[Locked]
    public bool $emailTwoFactorEnabled = false;

    /**
     * Whether the user's role makes the emailed code compulsory.
     */
    #[Locked]
    public bool $emailTwoFactorRequired = false;

    #[Locked]
    public bool $canManageSignedInDevices = false;

    #[Locked]
    public bool $canManagePasskeys = false;

    /** @var list<array{id: int, name: string, authenticator: string|null, created_at_diff: string, last_used_at_diff: string|null}> */
    #[Locked]
    public array $passkeys = [];

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $user = $this->user();

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            // A set-up started and never confirmed is thrown away, so the page
            // always offers a clean start.
            if (Fortify::confirmsTwoFactorAuthentication() && $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null) {
                $disableTwoFactorAuthentication($user);
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
            $this->twoFactorRequired = EnsureTwoFactorIsEnabled::appIsOnlyFactorFor($user);
        }

        $this->emailTwoFactorEnabled = $user->usesEmailTwoFactor();
        $this->emailTwoFactorRequired = EnsureTwoFactorIsEnabled::emailCodeRequiredFor($user);

        $this->canManageSignedInDevices = config('session.driver') === 'database';

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Turn the emailed sign-in code on or off for the signed-in user.
     */
    public function setEmailTwoFactor(bool $enabled): void
    {
        if (! $enabled && EnsureTwoFactorIsEnabled::emailCodeRequiredFor($this->user())) {
            $this->emailTwoFactorRequired = true;

            $this->toast(__('security.email_2fa_required'), 'error');

            return;
        }

        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        $this->user()->forceFill(['email_two_factor_enabled' => $enabled])->save();

        $this->emailTwoFactorEnabled = $enabled;

        $this->toast(__($enabled ? 'security.email_2fa_enabled' : 'security.email_2fa_disabled'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = array_values($this->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => (int) $passkey->id,
                'name' => (string) $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => (string) $passkey->created_at?->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->all());
    }

    /**
     * Ask before removing a passkey (the hub's confirm dialog).
     */
    public function confirmDelete(int $passkeyId): void
    {
        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        $passkey = $this->user()->passkeys()->findOrFail($passkeyId);

        $this->confirm(
            'deletePasskey',
            payload: $passkey->getKey(),
            title: __('security.remove_passkey'),
            message: __('security.remove_passkey_confirm', ['name' => $passkey->name]),
            confirmLabel: __('security.remove_passkey'),
        );
    }

    /**
     * Remove one of the user's own passkeys.
     */
    public function deletePasskey(int $passkeyId, DeletePasskey $deletePasskey): void
    {
        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        $passkey = $this->user()->passkeys()->findOrFail($passkeyId);

        $deletePasskey($this->user(), $passkey);

        $this->loadPasskeys();

        $this->toast(__('security.passkey_removed'));
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = $this->user()->fresh()?->hasEnabledTwoFactorAuthentication() ?? false;
    }

    /**
     * Turn the authenticator app off, unless it is the only second factor the
     * user's role can use.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        if (EnsureTwoFactorIsEnabled::appIsOnlyFactorFor($this->user())) {
            $this->twoFactorRequired = true;

            $this->toast(__('security.two_factor_required_cannot_disable'), 'error');

            return;
        }

        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        $disableTwoFactorAuthentication($this->user());

        $this->twoFactorEnabled = false;

        $this->toast(__('security.two_factor_authentication_disabled'));
    }

    /**
     * What a new sign-in asks for after the password: the authenticator app
     * takes priority over the emailed code (see RedirectIfTwoFactorRequired).
     *
     * @return 'app'|'email'|'password'
     */
    #[Computed]
    public function signInCheck(): string
    {
        return match (true) {
            $this->canManageTwoFactor && $this->twoFactorEnabled => 'app',
            $this->emailTwoFactorEnabled => 'email',
            default => 'password',
        };
    }

    /**
     * The browsers signed in to this account, newest first. Rows past the
     * session lifetime are left out; session ids and payloads never leave the
     * server.
     *
     * @return list<array{label: string, mobile: bool, ip_address: ?string, last_active: string, current: bool}>
     */
    #[Computed]
    public function signedInDevices(): array
    {
        if (! $this->canManageSignedInDevices) {
            return [];
        }

        return array_values($this->sessionRows()
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function (object $session): array {
                $userAgent = UserAgent::parse($session->user_agent ?? null);

                return [
                    'label' => $userAgent->label(),
                    'mobile' => in_array($userAgent->platform, ['Android', 'iOS', 'iPadOS'], true),
                    'ip_address' => $session->ip_address ?? null,
                    'last_active' => Date::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                    'current' => $session->id === session()->getId(),
                ];
            })
            ->all());
    }

    /**
     * Sign this account out everywhere but here: the other session rows go,
     * a new remember token stops remember-me cookies from signing those
     * devices straight back in, and their trust to skip the second step ends.
     */
    public function signOutOtherDevices(): void
    {
        if (! $this->canManageSignedInDevices || ! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        SignOutOtherDevices::run(request(), $this->user());

        TrustedDevices::forgetOthers(request(), $this->user());

        unset($this->signedInDevices, $this->trustedDevices);

        $this->toast(__('security.other_devices_signed_out'));
    }

    /**
     * Browsers that skip the second sign-in step, newest use first.
     *
     * @return list<array{label: string, last_used: string, expires: string, current: bool}>
     */
    #[Computed]
    public function trustedDevices(): array
    {
        $currentId = TrustedDevices::current(request(), $this->user())?->getKey();

        return array_values($this->user()->trustedDevices()
            ->where('expires_at', '>', now())
            ->latest('last_used_at')
            ->get()
            ->map(fn (TrustedDevice $device): array => [
                'label' => UserAgent::fromDescription($device->label)->label(),
                'last_used' => ($device->last_used_at ?? $device->created_at)?->diffForHumans() ?? '',
                'expires' => $device->expires_at->translatedFormat('j M Y'),
                'current' => $device->getKey() === $currentId,
            ])
            ->all());
    }

    /**
     * Make every browser, this one included, ask for the second step again.
     */
    public function forgetTrustedDevices(): void
    {
        if (! $this->ensurePasswordIsConfirmed()) {
            return;
        }

        TrustedDevices::forgetAll($this->user());

        unset($this->trustedDevices);

        $this->toast(__('security.trusted_devices_forgotten'));
    }

    public function render(): View
    {
        return view('livewire.settings.security');
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }

    /**
     * This account's rows in the session table.
     */
    private function sessionRows(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', Auth::id());
    }
}
