<section class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-[420px] mx-auto">
        <!-- Logo -->
        <div class="text-center mb-12">
            <x-app-logo class="mx-auto w-36"></x-app-logo>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-lg shadow p-8">
            <div class="text-center mb-12">
                <h2 class="text-[24px] font-semibold text-title mb-2">Sign In</h2>
                <p class="text-sm text-[#6c757d]">Please enter your details to sign in</p>
            </div>

            @if (session('status'))
                <div class="text-green-600 text-sm text-center mb-4">{{ session('status') }}</div>
            @endif

            <form wire:submit.prevent="login" class="space-y-4">
                <!-- Email -->
                <div>
                    <x-input-label for="email" value="Email Address" />
                    <x-text-input
                        id="email"
                        type="email"
                        model="email"
                        placeholder="email@example.com"
                    />
                    <x-input-error field="email" />
                </div>


                <div>
                    <x-input-label for="password" value="Password" class="mt-6" />
                    <x-text-input
                        id="password"
                        type="password"
                        model="password"
                        placeholder="••••••••"
                    />
                    <x-input-error field="password" />

                </div>

                <div class="flex py-3 items-center justify-between text-sm">
                    <x-checkbox model="remember">
                        Remember Me
                    </x-checkbox>
                @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary-600 hover:underline">Forgot Password?</a>
                    @endif
                </div>

                <x-primary-button>
                    Sign In
                </x-primary-button>

            </form>
        </div>

        <div class="text-center text-gray-500 text-[13px] mt-6">
            &copy; 2025 - Hope Center HR
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('.toggle-password').forEach(el => {
        el.addEventListener('click', function () {
            const input = this.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
            this.classList.toggle('ti-eye');
            this.classList.toggle('ti-eye-off');
        });
    });
</script>
