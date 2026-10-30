<x-slot:title>
    {{ __('security.page_title') }}
</x-slot:title>

@php
    $signInCheck = $this->signInCheck;
    [$protectionHeading, $protectionDetail] = match ($signInCheck) {
        'app' => [__('security.protection_app'), __('security.protection_app_detail')],
        'email' => [__('security.protection_email'), __('security.protection_email_detail')],
        default => [__('security.protection_password'), __('security.protection_password_detail')],
    };
    $sectionClass = 'grid gap-5 rounded-surface border border-line-soft bg-card p-6 shadow-e1 sm:p-8';
    $secondaryButton = 'inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-control border-[1.5px] border-line bg-card px-4 text-body font-bold text-ink transition hover:bg-hover';
    $stateBadge = 'rounded-full border border-line-soft px-2 py-0.5 text-body font-semibold text-ink-2';
@endphp

<div class="w-full space-y-8 px-4 py-8 sm:px-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('security.page_title') }}</h1>
            <p class="mt-1 text-body text-ink-2">{{ __('security.page_description') }}</p>
        </div>
        <a href="{{ route('settings.password') }}" wire:navigate class="{{ $secondaryButton }}">
            <i class="fa-solid fa-key" aria-hidden="true"></i>
            {{ __('general.my-account') }}
        </a>
    </div>

    <div class="grid max-w-3xl gap-8">
        {{-- Sign-in protection: what a new sign-in asks for after the password. --}}
        <section aria-labelledby="sign-in-protection-heading" class="{{ $sectionClass }}" data-test="sign-in-protection" data-sign-in-check="{{ $signInCheck }}">
            <div class="grid gap-2">
                <h2 id="sign-in-protection-heading" class="text-body font-semibold uppercase text-ink-3">{{ __('security.sign_in_protection') }}</h2>
                <p class="flex items-center gap-3 text-h3 font-bold text-ink">
                    <i @class([
                        'fa-solid shrink-0',
                        'fa-shield-halved' => $signInCheck !== 'password',
                        'fa-triangle-exclamation' => $signInCheck === 'password',
                    ]) aria-hidden="true"></i>
                    {{ $protectionHeading }}
                </p>
                <p class="text-body text-ink-2">{{ $protectionDetail }}</p>
            </div>

            @if ($canManageTwoFactor && $twoFactorRequired && ! $twoFactorEnabled)
                <div role="alert" class="rounded-control border border-primary px-4 py-3 text-body text-ink" data-test="two-factor-required-notice">
                    <p class="font-bold">{{ __('security.two_factor_required_heading') }}</p>
                    <p class="text-ink-2">{{ __('security.two_factor_required_explanation') }}</p>
                </div>
            @endif

            <ol class="grid divide-y divide-line-soft border-y border-line-soft" aria-label="{{ __('security.sign_in_steps') }}">
                {{-- Step 1: the password. --}}
                <li class="flex flex-wrap items-start gap-4 py-4">
                    <i class="fa-solid fa-lock mt-1 w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                    <div class="min-w-0 flex-1 basis-60">
                        <p class="flex flex-wrap items-center gap-2 font-semibold text-ink">
                            {{ __('auth.password') }}
                            <span class="{{ $stateBadge }}">{{ __('security.state_always_on') }}</span>
                        </p>
                        <p class="mt-1 text-body text-ink-2">{{ __('security.password_step_description') }}</p>
                    </div>
                </li>

                {{-- Step 2a: emailed code. --}}
                <li class="flex flex-wrap items-start gap-4 py-4" data-test="email-two-factor">
                    <i class="fa-solid fa-envelope mt-1 w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                    <div class="min-w-0 flex-1 basis-60">
                        <p class="flex flex-wrap items-center gap-2 font-semibold text-ink">
                            {{ __('security.email_2fa_heading') }}
                            <span class="{{ $stateBadge }}">{{ __($emailTwoFactorRequired ? 'security.state_required' : ($emailTwoFactorEnabled ? 'security.state_on' : 'security.state_off')) }}</span>
                        </p>
                        <p class="mt-1 text-body text-ink-2">{{ __('security.email_2fa_description') }}</p>
                        @if ($emailTwoFactorRequired)
                            <p class="mt-1 text-body text-ink-2" data-test="email-2fa-required">{{ __('security.email_2fa_required') }}</p>
                        @endif
                        @if ($emailTwoFactorEnabled && $signInCheck === 'app')
                            <p class="mt-1 text-body text-ink-2">{{ __('security.email_2fa_app_takes_priority') }}</p>
                        @endif
                    </div>
                    @unless ($emailTwoFactorRequired)
                        @if ($emailTwoFactorEnabled)
                            <button type="button" wire:click="setEmailTwoFactor(false)" data-test="email-2fa-disable" class="{{ $secondaryButton }}">
                                {{ __('security.email_2fa_disable') }}
                            </button>
                        @else
                            <x-primary-button type="button" wire:click="setEmailTwoFactor(true)" data-test="email-2fa-enable">
                                {{ __('security.email_2fa_enable') }}
                            </x-primary-button>
                        @endif
                    @endunless
                </li>

                {{-- Step 2b: authenticator app. --}}
                @if ($canManageTwoFactor)
                    <li class="flex flex-wrap items-start gap-4 py-4" data-test="app-two-factor">
                        <i class="fa-solid fa-mobile-screen mt-1 w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1 basis-60">
                            <p class="flex flex-wrap items-center gap-2 font-semibold text-ink">
                                {{ __('security.app_2fa_heading') }}
                                <span class="{{ $stateBadge }}">{{ __($twoFactorEnabled ? 'security.state_on' : ($twoFactorRequired ? 'security.state_required' : 'security.state_off')) }}</span>
                            </p>
                            <p class="mt-1 text-body text-ink-2">
                                {{ $twoFactorEnabled ? __('security.app_2fa_on_description') : __('security.app_2fa_off_description') }}
                            </p>
                            @if ($twoFactorEnabled && $twoFactorRequired)
                                <p class="mt-1 text-body text-ink-2" data-test="two-factor-required-cannot-disable">{{ __('security.two_factor_required_cannot_disable') }}</p>
                            @endif
                        </div>
                        @if (! $twoFactorEnabled)
                            <x-primary-button type="button" wire:click="$dispatch('start-two-factor-setup')" data-test="app-2fa-enable">
                                {{ __('security.enable_2fa') }}
                            </x-primary-button>
                        @elseif (! $twoFactorRequired)
                            <button type="button" wire:click="disable" class="{{ $secondaryButton }}" data-test="app-2fa-disable">
                                {{ __('security.disable_2fa') }}
                            </button>
                        @endif

                        @if ($twoFactorEnabled)
                            <div class="w-full">
                                <livewire:settings.recovery-codes :wire:key="'recovery-codes'" />
                            </div>
                        @endif
                    </li>
                @endif
            </ol>

            @if ($canManageTwoFactor && ! $twoFactorEnabled)
                <livewire:settings.two-factor-setup :requires-confirmation="$requiresConfirmation" :wire:key="'two-factor-setup'" />
            @endif
        </section>

        @if ($canManagePasskeys)
            <section aria-labelledby="passkeys-heading" class="{{ $sectionClass }}" data-test="passkeys">
                <div>
                    <h2 id="passkeys-heading" class="text-h3 font-bold text-ink">{{ __('security.passkeys') }}</h2>
                    <p class="mt-1 text-body text-ink-2">{{ __('security.passkeys_description') }}</p>
                </div>

                @if ($passkeys === [])
                    <div class="flex items-start gap-3 text-body">
                        <i class="fa-solid fa-fingerprint mt-1 w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold text-ink">{{ __('security.no_passkeys_yet') }}</p>
                            <p class="text-ink-2">{{ __('security.add_a_passkey') }}</p>
                        </div>
                    </div>
                @else
                    <ul class="divide-y divide-line-soft border-y border-line-soft text-body">
                        @foreach ($passkeys as $passkey)
                            <li class="flex items-center gap-4 py-3" wire:key="passkey-{{ $passkey['id'] }}">
                                <i class="fa-solid fa-key w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="truncate font-semibold text-ink">{{ $passkey['name'] }}</span>
                                        @if ($passkey['authenticator'])
                                            <span class="{{ $stateBadge }}">{{ $passkey['authenticator'] }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-ink-2">
                                        {{ __('security.added_time', ['time' => $passkey['created_at_diff']]) }}
                                        @if ($passkey['last_used_at_diff'])
                                            <span aria-hidden="true" class="mx-1">·</span>
                                            {{ __('security.last_used_time', ['time' => $passkey['last_used_at_diff']]) }}
                                        @endif
                                    </p>
                                </div>
                                <button type="button" wire:click="confirmDelete({{ $passkey['id'] }})"
                                        aria-label="{{ __('security.remove_passkey') }}"
                                        class="inline-grid size-11 shrink-0 cursor-pointer place-items-center rounded-control text-primary transition hover:bg-hover">
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <x-passkey-registration />
            </section>
        @endif

        {{-- Devices: where this account is signed in, and which browsers skip the second step. --}}
        <section aria-labelledby="devices-heading" class="{{ $sectionClass }}">
            <h2 id="devices-heading" class="text-h3 font-bold text-ink">{{ __('security.your_devices') }}</h2>

            @if ($canManageSignedInDevices)
                @php($signedInDevices = $this->signedInDevices)

                <div class="grid gap-4" data-test="signed-in-devices">
                    <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                        <div class="min-w-0 flex-1 basis-64">
                            <h3 class="font-bold text-ink">{{ __('security.signed_in_devices') }}</h3>
                            <p class="mt-1 text-body text-ink-2">{{ __('security.signed_in_devices_description') }}</p>
                        </div>

                        @if (collect($signedInDevices)->contains('current', false))
                            <button type="button" wire:click="signOutOtherDevices" class="{{ $secondaryButton }}">
                                <i class="fa-solid fa-arrow-right-from-bracket rtl:rotate-180" aria-hidden="true"></i>
                                {{ __('security.sign_out_other_devices') }}
                            </button>
                        @endif
                    </div>

                    <ul class="divide-y divide-line-soft border-y border-line-soft text-body">
                        @foreach ($signedInDevices as $device)
                            <li class="flex items-center gap-4 py-3" wire:key="signed-in-device-{{ $loop->index }}">
                                <i class="fa-solid {{ $device['mobile'] ? 'fa-mobile-screen' : 'fa-desktop' }} w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-ink">{{ $device['label'] }}</span>
                                        @if ($device['current'])
                                            <span class="{{ $stateBadge }}">{{ __('security.this_device') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-ink-2">
                                        @if ($device['ip_address'])
                                            <span dir="ltr">{{ $device['ip_address'] }}</span>
                                            <span aria-hidden="true" class="mx-1">·</span>
                                        @endif
                                        {{ $device['current'] ? __('security.active_now') : __('security.last_active_time', ['time' => $device['last_active']]) }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @unless (collect($signedInDevices)->contains('current', false))
                        <p class="text-body text-ink-2">{{ __('security.no_other_devices') }}</p>
                    @endunless
                </div>
            @endif

            @php($trustedDevices = $this->trustedDevices)

            <div class="grid gap-4" data-test="trusted-devices">
                <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                    <div class="min-w-0 flex-1 basis-64">
                        <h3 class="font-bold text-ink">{{ __('security.trusted_devices') }}</h3>
                        <p class="mt-1 text-body text-ink-2">{{ __('security.trusted_devices_description', ['days' => \App\Actions\Fortify\TrustedDevices::DaysTrusted]) }}</p>
                    </div>

                    @if ($trustedDevices !== [])
                        <button type="button" wire:click="forgetTrustedDevices" data-test="forget-trusted-devices" class="{{ $secondaryButton }}">
                            {{ __('security.forget_trusted_devices') }}
                        </button>
                    @endif
                </div>

                @if ($trustedDevices === [])
                    <p class="text-body text-ink-2">{{ __('security.no_trusted_devices') }}</p>
                @else
                    <ul class="divide-y divide-line-soft border-y border-line-soft text-body">
                        @foreach ($trustedDevices as $device)
                            <li class="flex items-center gap-4 py-3" wire:key="trusted-device-{{ $loop->index }}">
                                <i class="fa-solid fa-shield-halved w-5 shrink-0 text-center text-ink-3" aria-hidden="true"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-ink">{{ $device['label'] }}</span>
                                        @if ($device['current'])
                                            <span class="{{ $stateBadge }}">{{ __('security.this_device') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-ink-2">
                                        {{ __('security.last_active_time', ['time' => $device['last_used']]) }}
                                        <span aria-hidden="true" class="mx-1">·</span>
                                        {{ __('security.trusted_until', ['date' => $device['expires']]) }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</div>
