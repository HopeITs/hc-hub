<section class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-[420px] mx-auto">
        <!-- Logo -->
        <div class="text-center mb-12">
            <img src="{{ asset('assets/img/001_HC.svg') }}" class="mx-auto w-36" alt="Logo" />
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
                    <label for="email" class="block mb-1 text-sm font-medium text-title">Email Address</label>
                    <div class="relative">
                        <input
                            type="email" id="email"
                            wire:model.defer="email"
                            class="bg-gray-100 border border-borderColor text-sm rounded-input block w-full py-2 px-2.5  h-[38px] placeholder:text-gray-400"
                            placeholder="email@example.com"
                            autocomplete="email"
                            required
                        />
                        <span class="absolute top-[10px] right-[10px]">
                            <i class="ti ti-mail text-gray-400"></i>
                        </span>
                    </div>
                    @error('email') <p class="text-primary text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-title mt-6">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            wire:model.defer="password"
                            class="bg-gray-100 border border-borderColor text-sm rounded-input block w-full py-2 px-2.5  h-[38px] placeholder:text-gray-400"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        />
                        <span class="ti ti-eye-off absolute top-[10px] right-[10px] cursor-pointer toggle-password"></span>
                    </div>
                    @error('password') <p class="text-primary text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="flex py-3 items-center justify-between text-sm">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            wire:model="remember"
                            class="size-4 bg-white border border-borderColor rounded text-primary mr-1 "
                        />
                        <span>Remember Me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary-600 hover:underline">Forgot Password?</a>
                    @endif
                </div>

                <!-- Login Button -->
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="btn w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold">
                    Sign In
                </button>
            </form>
        </div>

        <!-- Footer -->
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
