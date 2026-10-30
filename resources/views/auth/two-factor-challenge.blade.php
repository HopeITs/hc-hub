<x-layouts.auth :title="__('security.two_factor_authentication')">
    <div
        class="flex flex-col gap-6"
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            toggleInput() {
                this.showRecoveryInput = ! this.showRecoveryInput;
                this.$nextTick(() => (this.showRecoveryInput ? this.$refs.recovery_code : this.$refs.code)?.focus());
            },
        }"
    >
        <div x-show="! showRecoveryInput">
            <x-auth-header :title="__('security.authentication_code')" :description="__('security.enter_the_authentication_code')" />
        </div>
        <div x-show="showRecoveryInput" x-cloak>
            <x-auth-header :title="__('security.recovery_code')" :description="__('security.enter_a_recovery_code')" />
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-5">
            @csrf

            <div x-show="! showRecoveryInput">
                <x-forms.input-label for="code" :value="__('security.authentication_code')" class="sr-only" />
                <x-forms.input
                    id="code"
                    name="code"
                    x-ref="code"
                    error-key="code"
                    inputmode="numeric"
                    maxlength="6"
                    autocomplete="one-time-code"
                    autofocus
                    dir="ltr"
                    class="text-center font-mono text-h3 tracking-[0.5em]"
                    x-bind:disabled="showRecoveryInput"
                />
                <x-forms.input-error field="code" input-id="code" />
            </div>

            <div x-show="showRecoveryInput" x-cloak>
                <x-forms.input-label for="recovery_code" :value="__('security.recovery_code')" class="sr-only" />
                <x-forms.input
                    id="recovery_code"
                    name="recovery_code"
                    x-ref="recovery_code"
                    error-key="recovery_code"
                    autocomplete="one-time-code"
                    dir="ltr"
                    x-bind:disabled="! showRecoveryInput"
                />
                <x-forms.input-error field="recovery_code" input-id="recovery_code" />
            </div>

            @include('auth.partials.trust-device')

            <x-primary-button :full="true" :show-spinner="false">
                {{ __('security.continue') }}
            </x-primary-button>
        </form>

        <p class="text-center text-body text-ink-2">
            {{ __('security.or_you_can') }}
            <button type="button" class="min-h-11 font-semibold text-primary underline" x-on:click="toggleInput()">
                <span x-show="! showRecoveryInput">{{ __('security.login_using_a_recovery_code') }}</span>
                <span x-show="showRecoveryInput" x-cloak>{{ __('security.login_using_an_authentication_code') }}</span>
            </button>
        </p>
    </div>
</x-layouts.auth>
