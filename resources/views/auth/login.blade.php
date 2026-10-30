<x-layouts.auth :title="__('auth.sign-in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.sign-in')" :description="__('auth.sign-in-text')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5" data-test="login-form">
            @csrf

            <div>
                <x-forms.input-label for="email" required :value="__('security.email_or_username')" />
                <x-forms.input
                    id="email"
                    name="email"
                    type="text"
                    error-key="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    dir="ltr"
                    placeholder="email@example.com"
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
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <x-forms.input-error field="password" input-id="password" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 text-body">
                <label for="remember" class="flex min-h-11 cursor-pointer items-center gap-2 text-ink-2">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))
                           class="size-4 rounded-inset border border-primary bg-field text-primary focus:ring-primary" />
                    {{ __('auth.remember-me') }}
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="font-semibold text-primary hover:underline">
                        {{ __('auth.forgot-password') }}
                    </a>
                @endif
            </div>

            @if (\App\Support\Turnstile::isEnabled())
                {{-- Human check (Cloudflare Turnstile). --}}
                <div>
                    <div
                        class="cf-turnstile"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-language="{{ app()->getLocale() }}"
                        data-size="flexible"
                        data-theme="auto"
                        data-test="login-human-check"
                    ></div>
                    <x-forms.input-error :field="\App\Support\Turnstile::ResponseField" />
                </div>

                <script nonce="{{ Vite::cspNonce() }}" src="{{ \App\Support\Turnstile::Origin }}/turnstile/v0/api.js" async defer></script>
            @endif

            <x-primary-button :full="true" :show-spinner="false" data-test="login-button">
                {{ __('auth.sign-in') }}
            </x-primary-button>
        </form>
    </div>
</x-layouts.auth>
