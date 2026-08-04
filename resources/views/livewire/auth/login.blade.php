<section class="min-h-[86.7dvh]  rtl:text-right bg-neutral-100 flex items-center justify-center px-4">
    <x-auth-card-wrapper>

        <div class="text-center mb-6">
            <x-app-logo class="mx-auto " />
        </div>

        <x-auth-card class="w-96 space-y-6">

            <div class="text-center space-y-1">
                <h2 class="text-h3 font-bold text-neutral-900">{{ __('auth.sign-in') }}</h2>
                <p class="text-body text-secondary-medium">{{ __('auth.sign-in-text') }}</p>
            </div>

            <x-auth-session-status class="text-center text-green-600 text-body mb-2" :status="session('status')" />

            <form
                x-data
                x-on:submit.prevent="
                    $wire.set('email', document.getElementById('email').value, false);
                    $wire.set('password', document.getElementById('password').value, false);
                    $wire.login()
                "
                class="space-y-4"
            >

                <div>
                    <x-forms.input-label for="email" required :value="__('auth.email-address')" />
                    <x-forms.input
                        id="email"
                        type="email"
                        wire:model.live.debounce.500ms="email"
                        placeholder="email@example.com"
                        autocomplete="email"
                    />

                    <x-forms.input-error field="email" />
                </div>

                <div>
                    <x-forms.input-label for="password" required :value="__('auth.password')" />
                    <x-forms.password-input
                        id="password"
                        model="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        :disabled="$employeeInactive"
                    />

                    <x-forms.input-error field="password" />
                </div>

{{--                @if(count($roleOptions) > 0)--}}
{{--                    <div>--}}
{{--                        <x-forms.input-label for="role_id" :value="__('Login as role')" />--}}
{{--                        <select id="role_id"--}}
{{--                                wire:model="selectedRoleId"--}}
{{--                                class="mt-1 block w-full rounded-control border-neutral-300 focus:border-primary focus:ring-primary">--}}
{{--                            <option value="">{{ __('Choose a role') }}</option>--}}
{{--                            @foreach($roleOptions as $opt)--}}
{{--                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                        <p class="text-small text-secondary-medium mt-1">--}}
{{--                            {{ __('This will be your active role for this session.') }}--}}
{{--                        </p>--}}
{{--                    </div>--}}
{{--                @endif--}}

                <div class="flex items-center justify-between text-body text-secondary-medium">
                    <x-forms.checkbox-input type="checkbox" id="remember" wire:model="remember">
                    {{ __('auth.remember-me') }}
                    </x-forms.checkbox-input>

                    @if (request()->has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary hover:underline">
                            {{ __('auth.forgot-password') }}
                        </a>
                    @endif
                </div>
                @if($employeeInactive)
                    <div class="text-center text-primary text-body font-semibold">
                        {{ $inactiveMessage }}
                    </div>
                @endif
                @if(!$employeeInactive)
                    <x-primary-button class="w-full">
                        {{ __('auth.sign-in') }}
                    </x-primary-button>
                @endif


            </form>

        </x-auth-card>

    </x-auth-card-wrapper>
</section>
