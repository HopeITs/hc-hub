<div>
    @if ($open)
        <div class="grid gap-5 rounded-surface border border-line-soft bg-soft p-5" role="region" aria-labelledby="two-factor-setup-heading" data-test="two-factor-setup">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 id="two-factor-setup-heading" class="font-bold text-ink">
                        {{ $showVerificationStep ? __('security.verify_authentication_code') : __('security.enable_two_factor_authentication') }}
                    </h3>
                    <p class="mt-1 text-body text-ink-2">
                        {{ $showVerificationStep ? __('security.enter_the_6_digit_code') : __('security.scan_the_qr_code') }}
                    </p>
                </div>
                <button type="button" wire:click="closeModal" aria-label="{{ __('security.cancel') }}"
                        class="inline-grid size-11 shrink-0 cursor-pointer place-items-center rounded-control text-ink-2 transition hover:bg-hover">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            @if ($showVerificationStep)
                <div class="grid gap-4" x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                    <div>
                        <x-forms.input-label for="two-factor-code" :value="__('security.authentication_code')" class="sr-only" />
                        <x-forms.input
                            id="two-factor-code"
                            wire:model="code"
                            error-key="code"
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="one-time-code"
                            dir="ltr"
                            class="text-center font-mono text-h3 tracking-[0.5em]"
                            x-on:keydown.enter.prevent="$wire.confirmTwoFactor()"
                        />
                        <x-forms.input-error field="code" input-id="two-factor-code" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="resetVerification"
                                class="min-h-11 cursor-pointer rounded-control border-[1.5px] border-line bg-card px-4 text-body font-bold text-ink transition hover:bg-hover">
                            {{ __('security.back') }}
                        </button>
                        <x-primary-button type="button" wire:click="confirmTwoFactor" data-test="confirm-two-factor">
                            {{ __('auth.confirm') }}
                        </x-primary-button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <p role="alert" class="text-body text-primary">{{ $message }}</p>
                @enderror

                @if ($qrCodeSvg !== '')
                    {{-- The QR code stays dark on white in both themes so every scanner reads it. --}}
                    <div class="mx-auto w-56 rounded-control border border-line-soft p-3" style="background:#FFFFFF">
                        {!! $qrCodeSvg !!}
                    </div>
                @endif

                @if ($manualSetupKey !== '')
                    <div class="grid gap-2"
                         x-data="{ copied: false, async copy() { try { await navigator.clipboard.writeText(@js($manualSetupKey)); this.copied = true; setTimeout(() => this.copied = false, 1500); } catch (e) {} } }">
                        <p class="text-body text-ink-2">{{ __('security.or_enter_the_code_manually') }}</p>
                        <div class="flex items-stretch overflow-hidden rounded-control border border-line bg-field">
                            <input type="text" readonly value="{{ $manualSetupKey }}" dir="ltr"
                                   aria-label="{{ __('security.setup_key') }}"
                                   class="w-full border-0 bg-transparent p-3 font-mono text-body text-ink outline-none" />
                            <button type="button" x-on:click="copy()" class="min-w-11 cursor-pointer border-s border-line px-3 text-ink-2 transition hover:bg-hover"
                                    aria-label="{{ __('security.copy') }}">
                                <i class="fa-regular fa-copy" x-show="! copied" aria-hidden="true"></i>
                                <i class="fa-solid fa-check" x-show="copied" x-cloak aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <x-primary-button type="button" :full="true" wire:click="showVerificationIfNecessary" :disabled="$errors->has('setupData')">
                    {{ __('security.continue') }}
                </x-primary-button>
            @endif
        </div>
    @endif
</div>
