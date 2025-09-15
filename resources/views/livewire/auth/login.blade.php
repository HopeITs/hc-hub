<section class="min-h-[86.7dvh] rtl text-right bg-gray-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>

        <div class="text-center mb-6">
            <x-app-logo class="mx-auto w-36" />
        </div>

        <x-auth-card class="w-96 space-y-6">

            <div class="text-center space-y-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('auth.sign-in') }}</h2>
                <p class="text-sm text-gray-500">{{ __('auth.sign-in-text') }}</p>
            </div>

            <x-auth-session-status class="text-center text-green-600 text-sm mb-2" :status="session('status')" />

            <form wire:submit.prevent="login" class="space-y-5">

                <div>
                    <x-forms.input-label for="email" :value="__('auth.email-address')" />
                    <x-forms.input
                        id="email"
                        type="email"
                        model="email"
                        update="live"            {{-- IMPORTANT: trigger updatedEmail() as you type --}}
                        placeholder="email@example.com"
                        autocomplete="email"
                    />
                    <x-forms.input-error field="email" />
                </div>

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

                @if(count($roleOptions) > 0)
                    <div>
                        <x-forms.input-label for="role_id" :value="__('Login as role')" />
                        <select id="role_id"
                                wire:model="selectedRoleId"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="">{{ __('Choose a role') }}</option>
                            @foreach($roleOptions as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ __('This will be your active role for this session.') }}
                        </p>
                    </div>
                @endif

                <div class="flex items-center justify-between text-sm text-gray-600">
                    <x-forms.input type="checkbox" id="remember" model="remember">
                        <span>{{ __('auth.remember-me') }}</span>
                    </x-forms.input>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary-600 hover:underline">
                            {{ __('auth.forgot-password') }}
                        </a>
                    @endif
                </div>
                <x-primary-button class="w-full">
                    {{ __('auth.sign-in') }}
                </x-primary-button>

            </form>

        </x-auth-card>

    </x-auth-card-wrapper>
</section>
