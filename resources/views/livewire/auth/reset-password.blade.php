<section class="min-h-[90dvh] rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>

        {{-- Logo --}}
        <div class="text-center mb-12">
            <x-app-logo class="mx-auto" />
        </div>

        {{-- Reset Password Card --}}
        <x-auth-card class="w-96 space-y-6">
            <div class="text-center space-y-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('auth.reset-password') }}</h2>
                <p class="text-sm text-gray-500">
                    {{ __('auth.reset-password-text') }}
                </p>
            </div>

            <x-auth-session-status class="text-center text-green-600 text-sm" :status="session('status')" />

            <form wire:submit.prevent="resetPassword" class="space-y-5">
                {{-- Hidden Email --}}
                <input type="hidden" wire:model="email" />

                {{-- New Password --}}
                <div>
                    <x-forms.input-label for="password" :value="__('auth.password')" />

                    <x-forms.password-input
                        id="password"
                        model="password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        oninput="checkPasswordStrength(this.value)"
                    />

                    {{-- Password Strength Bar --}}
                    <div class="flex mt-2 gap-2" id="passwordStrengthBar">
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                    </div>

                    {{-- Hint --}}
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('auth.password-text') }}
                    </p>

                    <x-forms.input-error field="password" />
                </div>

                {{-- Confirm Password --}}
                <div>
                    <x-forms.input-label for="password_confirmation" :value="__('auth.confirm-password')" />
                    <x-forms.password-input
                        id="password_confirmation"
                        model="password_confirmation"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />
                    <x-forms.input-error field="password_confirmation" />
                </div>

                {{-- Submit --}}
                <x-primary-button class="w-full">
                    {{ __('auth.confirm') }}
                </x-primary-button>
            </form>
        </x-auth-card>
    </x-auth-card-wrapper>
</section>
