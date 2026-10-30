<x-layouts.auth :title="__('auth.reset-password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.reset-password')" :description="__('auth.reset-password-text')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-forms.input-label for="email" required :value="__('auth.email-address')" />
                <x-forms.input
                    id="email"
                    name="email"
                    type="email"
                    error-key="email"
                    :value="old('email', (string) $request->query('email', ''))"
                    required
                    autocomplete="email"
                    dir="ltr"
                />
                <x-forms.input-error field="email" input-id="email" />
            </div>

            <div>
                <x-forms.password-input
                    id="password"
                    name="password"
                    :label="__('auth.password')"
                    error-key="password"
                    required
                    autofocus
                    autocomplete="new-password"
                />
                <p class="mt-1 text-body text-ink-2">{{ __('auth.password-text') }}</p>
                <x-forms.input-error field="password" input-id="password" />
            </div>

            <div>
                <x-forms.password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    :label="__('auth.confirm-password')"
                    required
                    autocomplete="new-password"
                />
            </div>

            <x-primary-button :full="true" :show-spinner="false">
                {{ __('auth.reset-password') }}
            </x-primary-button>
        </form>
    </div>
</x-layouts.auth>
