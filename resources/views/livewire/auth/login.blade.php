<section class="min-h-[90dvh] rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>

        {{-- logo --}}
        <div class="text-center mb-6">
            <x-app-logo class="mx-auto w-36" />
        </div>

        {{-- login card --}}
        <x-auth-card class="rounded-2xl shadow-xl p-8 bg-white w-full space-y-6">

            {{-- Title --}}
            <div class="text-center space-y-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('auth.sign-in') }}</h2>
                <p class="text-sm text-gray-500">{{ __('auth.sign-in-text') }}</p>
            </div>

            {{-- Status --}}
            @if (session('status'))
                <div class="text-green-600 text-sm text-center">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Login form --}}
            <form wire:submit.prevent="login" class="space-y-5">

                {{-- Email --}}
                <div>
                    <x-input-label for="email" value="{{ __('auth.email-address') }}" />
                    <x-text-input id="email" type="email" wire:model.defer="email" placeholder="email@example.com" />
                    <x-input-error field="email" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" value="{{ __('auth.password') }}" />
                    <x-text-input id="password" type="password" wire:model.defer="password" placeholder="••••••••" />
                    <x-input-error field="password" />
                </div>

                {{-- Remember + Forgot --}}
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <label class="flex items-center gap-2">
                        <x-checkbox wire:model.defer="remember" />
                        <span>{{ __('auth.remember-me') }}</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary-600 hover:underline">
                            {{ __('auth.forgot-password') }}
                        </a>
                    @endif
                </div>

                {{-- Submit --}}
                <x-primary-button>
                    {{ __('auth.sign-in') }}
                </x-primary-button>

            </form>

        </x-auth-card>

    </x-auth-card-wrapper>
</section>
