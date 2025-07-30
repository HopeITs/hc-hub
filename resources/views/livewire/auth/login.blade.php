<div class="flex flex-col gap-6">
    <x-auth-header :title="__('auth.sign-in')" :description="__('auth.sign-in-text')"/>

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')"/>

    <form wire:submit="login" class=" flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('auth.email-address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->

        <flux:input
            wire:model="password"
            :label="__('auth.password')"
            type="password"
            required
            autocomplete="current-password"
            :placeholder="__('auth.password')"
            viewable
        />


        <!-- Remember Me -->
        <div class="relative">
            <flux:checkbox wire:model="remember" :label="__('auth.remember-me')"/>
            @if (Route::has('password.request'))
                <flux:link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('auth.forgot-password') }}
                </flux:link>
            @endif
        </div>
        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('auth.sign-in') }}</flux:button>
        </div>
    </form>


</div>
