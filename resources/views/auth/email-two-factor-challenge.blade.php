<x-layouts.auth :title="__('security.two_factor_authentication')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('security.email_code_title')" :description="__('security.email_code_description')" />

        <x-auth-session-status class="text-center" :status="session('status')" data-test="email-code-status" />

        <form method="POST" action="{{ route('email-two-factor.login.store') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <x-forms.input-label for="code" :value="__('security.email_code_title')" class="sr-only" />
                <x-forms.input
                    id="code"
                    name="code"
                    error-key="code"
                    inputmode="numeric"
                    maxlength="6"
                    autocomplete="one-time-code"
                    autofocus
                    required
                    dir="ltr"
                    class="text-center font-mono text-h3 tracking-[0.5em]"
                />
                <x-forms.input-error field="code" input-id="code" />
            </div>

            @include('auth.partials.trust-device')

            <x-primary-button :full="true" :show-spinner="false" data-test="email-code-submit">
                {{ __('security.continue') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('email-two-factor.resend') }}" class="text-center">
            @csrf
            <button type="submit" class="min-h-11 text-body font-semibold text-primary underline">
                {{ __('security.email_code_resend') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
