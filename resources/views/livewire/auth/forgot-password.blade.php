<section class="min-h-screen rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>
        {{-- logo --}}
        <div class="text-center mb-12">
            <x-app-logo class="mx-auto w-36" />
        </div>

        {{-- forgot password card --}}
        <x-auth-card>
            <div class="text-center mb-8">
                <h2 class="text-[24px] font-semibold text-title mb-2">{{ __('auth.forgot-password') }}</h2>
                <p class="text-sm text-[#6c757d]">
                    {{ __("auth.forgot-password-text") }}
                </p>
            </div>

            <x-auth-session-status class="text-center text-green-600 text-sm mb-4" :status="session('status')" />

            <form wire:submit.prevent="sendPasswordResetLink" class="space-y-4">
                <div>
                    <x-input-label for="email" :value="__('auth.email-address')" />
                    <x-text-input
                        id="email"
                        type="email"
                        model="email"
                        placeholder="email@example.com"
                    />
                    <x-input-error field="email" />
                </div>

                <x-primary-button class="w-full ">
                    {{ __('auth.submit') }}
                </x-primary-button>
            </form>
        </x-auth-card>

        <div class="text-center text-gray-500 text-[13px] mt-6">
            إرجع إلى
            <a href="{{ route('login') }}" class="text-primary-600 hover:underline">
                {{ __('auth.sign-in') }}
            </a>
        </div>
    </x-auth-card-wrapper>
</section>
