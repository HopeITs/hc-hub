<section class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>
        {{-- logo --}}
        <div class="text-center mb-12">
            <x-app-logo class="mx-auto w-36" />
        </div>

        {{-- Reset password card --}}
        <x-auth-card>
            <div class="text-center mb-8">
                <h2 class="text-[24px] font-semibold text-title mb-2">{{ __('Reset Password') }}</h2>
                <p class="text-sm text-[#6c757d]">
                    {{ __('Your new password must be different from previously used passwords.') }}
                </p>
            </div>

            <x-auth-session-status class="text-center text-green-600 text-sm mb-4" :status="session('status')" />

            <form wire:submit.prevent="resetPassword" class="space-y-4">
                <input type="hidden" wire:model="email" />

                <div>
                    <x-input-label for="password" :value="__('Password')" />

                    <div class="relative">
                        <x-text-input
                            id="password"
                            type="password"
                            model="password"
                            placeholder="••••••••"
                            oninput="checkPasswordStrength(this.value)"
                        />
                    </div>

                    {{-- password strength bar --}}
                    <div class="flex mt-2 gap-2" id="passwordStrengthBar">
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                        <span class="w-1/4 h-[5px] rounded bg-gray-200"></span>
                    </div>

                    {{-- password hint  --}}
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('Use 8 or more characters with a mix of letters, numbers, and symbols.') }}
                    </p>
                    <x-input-error field="password" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input
                        id="password_confirmation"
                        type="password"
                        model="password_confirmation"
                        placeholder="••••••••"
                    />
                    <x-input-error field="password_confirmation" />
                </div>

                <x-primary-button class="w-full">
                    {{ __('Submit') }}
                </x-primary-button>
            </form>
        </x-auth-card>

        <div class="text-center text-gray-500 text-[13px] mt-6">
            &copy; {{ date('Y') }} - Hope Center HR
        </div>
    </x-auth-card-wrapper>
</section>

<script>
    function checkPasswordStrength(password) {
        const bars = document.querySelectorAll('#passwordStrengthBar span');

        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;

        let colorClass = 'bg-danger';
        if (score === 2) colorClass = 'bg-warning';
        else if (score === 3) colorClass = 'bg-info';
        else if (score === 4) colorClass = 'bg-success';

        bars.forEach((bar, index) => {
            bar.classList.remove('bg-danger', 'bg-warning', 'bg-info', 'bg-success', 'bg-gray-200');
            if (index < score) {
                bar.classList.add(colorClass);
            } else {
                bar.classList.add('bg-gray-200');
            }
        });
    }

    //password eye toggle
    document.querySelectorAll('.toggle-password').forEach(el => {
        el.addEventListener('click', function () {
            const input = this.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
            this.classList.toggle('ti-eye');
            this.classList.toggle('ti-eye-off');
        });
    });
</script>

