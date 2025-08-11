<section class="min-h-[86.7dvh] rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>

        {{-- Logo --}}
        <div class="text-center mb-6">
            <x-app-logo class="mx-auto w-36" />
        </div>

        {{-- Login Card --}}
        <x-auth-card class="w-96 space-y-6">

            {{-- Title --}}
            <div class="text-center space-y-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('auth.sign-in') }}</h2>
                <p class="text-sm text-gray-500">{{ __('auth.sign-in-text') }}</p>
            </div>

            {{-- Status --}}
            <x-auth-session-status class="text-center text-green-600 text-sm mb-2" :status="session('status')" />

            {{-- Login Form --}}
            <form wire:submit.prevent="login" class="space-y-5">

                {{-- Email --}}
                <div>
                    <x-forms.input-label for="email" :value="__('auth.email-address')" />
                    <x-forms.input
                        id="email"
                        type="email"
                        model="email"
                        placeholder="email@example.com"
                        autocomplete="email"
                    />


                    <x-forms.input-error field="email" />
                </div>

                {{-- Password --}}
                <div>
                    <x-forms.input-label for="password" :value="__('auth.password')" />
                    <x-forms.password-input
                        id="password"
                        model="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    />
                    <x-forms.input-error field="password" />
                </div>

                {{-- Remember Me + Forgot Password --}}
                <div class="flex items-center justify-between text-sm text-gray-600">
                        <x-forms.input
                            type="checkbox"
                            id="remember"
                            model="remember"
                        >
                        <span>{{ __('auth.remember-me') }}</span>
                        </x-forms.input>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary-600 hover:underline">
                            {{ __('auth.forgot-password') }}
                        </a>
                    @endif
                </div>

                {{-- Submit --}}
                <x-primary-button class="w-full">
                    {{ __('auth.sign-in') }}
                </x-primary-button>

            </form>

        </x-auth-card>

    </x-auth-card-wrapper>
</section>
