<x-layouts.auth :title="__('auth.confirm-password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.confirm-password')" :description="__('security.confirm_password_description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('security.confirm_with_passkey')"
            :loading-label="__('security.confirming')"
            :separator="__('security.or_confirm_with_password')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <x-forms.password-input
                    id="password"
                    name="password"
                    :label="__('auth.password')"
                    error-key="password"
                    required
                    autofocus
                    autocomplete="current-password"
                />
                <x-forms.input-error field="password" input-id="password" />
            </div>

            <x-primary-button :full="true" :show-spinner="false" data-test="confirm-password-button">
                {{ __('auth.confirm') }}
            </x-primary-button>
        </form>
    </div>
</x-layouts.auth>
