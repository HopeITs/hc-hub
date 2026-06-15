<section class="min-h-[90dvh] rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>

        {{-- Logo --}}
        <div class="text-center mb-12">
            <x-app-logo class="mx-auto w-36" />
        </div>

        {{-- Forgot Password Card --}}
        <x-auth-card class="w-96 space-y-6">
            {{-- Title --}}
            <div class="text-center space-y-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('auth.forgot-password') }}</h2>
                <p class="text-sm text-gray-500">
                    {{ __('auth.forgot-password-text') }}
                </p>
            </div>

            {{-- Status --}}
            <x-auth-session-status class="text-center text-green-600 text-sm" :status="session('status')" />

            {{-- Form --}}
            <form wire:submit.prevent="sendPasswordResetLink" class="space-y-5">
                {{-- Email --}}
                <div>
                    <x-forms.input-label for="email" required :value="__('auth.email-address')" />
                    <x-forms.input
                        id="email"
                        type="email"
                        model="email"
                        placeholder="email@example.com"
                        autocomplete="email"
                    />
                    <x-forms.input-error field="email" />
                </div>

                {{-- Submit --}}
                <x-primary-button class="w-full">
                    {{ __('auth.submit') }}
                </x-primary-button>
            </form>
        </x-auth-card>

        {{-- Back to Login --}}
        <div class="text-center text-gray-500 text-sm mt-6">
            {{ __('auth.back-to') }}
            <a href="{{ route('login') }}" class="text-primary-600 hover:underline">
                {{ __('auth.sign-in') }}
            </a>
        </div>

    </x-auth-card-wrapper>
</section>
