<section class="min-h-screen rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>
        {{-- logo --}}
        <div class="text-center mb-12">
            <x-app-logo class="mx-auto w-36"></x-app-logo>
        </div>

        {{-- login form --}}
        <x-auth-card>
            <div class="text-center mb-12">
                <h2 class="text-[24px] font-semibold text-title mb-2">{{__('auth.sign-in')}}</h2>
                <p class="text-sm text-secondary-700">{{__('auth.sign-in-text')}}</p>
            </div>

            @if (session('status'))
                <div class="text-green-600 text-sm text-center mb-4">{{ session('status') }}</div>
            @endif

            <form wire:submit.prevent="login" class="space-y-4">

                <div>
                    <x-input-label for="email" value="{{__('auth.email-address')}}"/>
                    <x-text-input
                        id="email"
                        type="email"
                        wire:model.defer="email"
                        placeholder="email@example.com" />

                    <x-input-error field="email"/>
                </div>


                <div>
                    <x-input-label for="password" value="{{__('auth.password')}}" class="mt-6"/>
                    <x-text-input
                        id="password"
                        type="password"
                        wire:model.defer="password"
                        placeholder="••••••••" />

                    <x-input-error field="password"/>

                </div>

                <div class="flex py-3 items-center justify-between text-sm">
                    <x-checkbox wire:model.defer="remember">
                        {{__('auth.remember-me')}}
                    </x-checkbox>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary-600 hover:underline">
                            {{__('auth.forgot-password')}}
                        </a>
                    @endif
                </div>

                <x-primary-button>
                    {{__('auth.sign-in')}}
                </x-primary-button>

            </form>
        </x-auth-card>

        <div class="text-center text-gray-500 text-[13px] mt-6">
            &copy; 2025 - {{__('auth.copyright')}}
        </div>
    </x-auth-card-wrapper>
</section>

<script>
    function togglePasswordVisibility(id) {
        const input = document.getElementById(id);
        const icon = document.getElementById(id + '_icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ti-eye-off');
            icon.classList.add('ti-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('ti-eye');
            icon.classList.add('ti-eye-off');
        }
    }
</script>

