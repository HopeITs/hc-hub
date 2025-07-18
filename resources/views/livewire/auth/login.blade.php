<section class="min-h-screen bg-[linear-gradient(115.43deg,_#FFFFFF_0.45%,_#FFF3ED_100%)] flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded shadow-md h-screen flex flex-col justify-between p-6">

        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="{{ asset('assets/img/logo.svg') }}" class="mx-auto w-32" alt="Logo" />
        </div>

        <!-- Login Form -->
        <div>
            <!-- Heading -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-semibold text-title mb-2">Sign In</h2>
                <p class="text-sm text-gray-500">Please enter your details to sign in</p>
            </div>

            <!-- Session Message -->
            @if (session('status'))
                <div class="text-green-600 text-sm text-center mb-4">{{ session('status') }}</div>
            @endif

            <form wire:submit.prevent="login" class="space-y-4">

                <!-- Email -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-title">Email Address</label>
                    <div class="relative">
                        <input
                            type="email"
                            wire:model.defer="email"
                            class="bg-white border border-borderColor text-sm rounded-input block w-full py-2 px-2.5 h-[38px] placeholder:text-gray-400"
                            placeholder="email@example.com"
                            autocomplete="email"
                            required
                        />
                        <span class="absolute top-[10px] right-[10px]">
                            <i class="ti ti-mail text-gray-400"></i>
                        </span>
                    </div>
                    @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-title">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            wire:model.defer="password"
                            class="bg-white border border-borderColor text-sm rounded-input block w-full py-2 px-2.5 h-[38px] placeholder:text-gray-400"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        />
                        <span class="ti ti-eye-off absolute top-[10px] right-[10px] cursor-pointer toggle-password"></span>
                    </div>
                    @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            wire:model="remember"
                            class="size-4 bg-white border border-borderColor rounded text-primary me-2"
                        />
                        <span>Remember Me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary hover:underline">Forgot Password?</a>
                    @endif
                </div>

                <!-- Login Button -->
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="btn btn-primary w-full bg-primary text-white"
                >
                    Sign In
                </button>
            </form>

            <!-- Register -->
            @if (Route::has('register'))
                <div class="text-center mt-4 text-sm text-dark">
                    Don’t have an account?
                    <a href="{{ route('register') }}" class="text-primary hover-a">Create Account</a>
                </div>
            @endif

            <!-- Divider -->
            <div class="login-or relative text-center my-4 py-2 before:border-t before:border-borderColor before:w-full before:absolute before:left-0 before:top-[17px]">
                <span class="bg-white relative inline-block px-3 z-10">Or</span>
            </div>

            <!-- Social Buttons -->
            <div class="flex items-center justify-center gap-3 mt-2">
                <a href="#" class="p-2 btn bg-info flex justify-center items-center rounded">
                    <img src="{{ asset('assets/img/icons/facebook-logo.svg') }}" alt="Facebook" class="h-5 w-5" />
                </a>
                <a href="#" class="p-2 btn bg-outline-light border flex justify-center items-center rounded">
                    <img src="{{ asset('assets/img/icons/google-logo.svg') }}" alt="Google" class="h-5 w-5" />
                </a>
                <a href="#" class="p-2 btn bg-dark flex justify-center items-center rounded">
                    <img src="{{ asset('assets/img/icons/apple-logo.svg') }}" alt="Apple" class="h-5 w-5" />
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-xs mt-8">
            &copy; 2024 Smarthr. All rights reserved.
        </div>
    </div>
</section>
