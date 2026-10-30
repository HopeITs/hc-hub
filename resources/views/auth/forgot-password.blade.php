<x-layouts.auth :title="__('auth.forgot-password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.forgot-password')" :description="__('auth.forgot-password-text')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <x-forms.input-label for="email" required :value="__('auth.email-address')" />
                <x-forms.input
                    id="email"
                    name="email"
                    type="email"
                    error-key="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="email"
                    dir="ltr"
                    placeholder="email@example.com"
                />
                <x-forms.input-error field="email" input-id="email" />
            </div>

            <x-primary-button :full="true" :show-spinner="false">
                {{ __('auth.submit') }}
            </x-primary-button>
        </form>

        <p class="text-center text-body text-ink-2">
            {{ __('auth.back-to') }}
            <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">{{ __('auth.sign-in') }}</a>
        </p>
    </div>
</x-layouts.auth>
