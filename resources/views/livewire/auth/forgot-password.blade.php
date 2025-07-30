 <div class="flex flex-col gap-6">
    <x-auth-header :title="__('auth.forgot-password')" :description="__('auth.forgot-password-text')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('auth.email-address')"
            type="email"
            class="text-secondary-dark"
            required
            autofocus
            placeholder="email@example.com"
        />

        <flux:button variant="primary" type="submit" class="w-full">{{ __('auth.submit') }}</flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm ">
        <span>{{ __('auth.back-to') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('auth.sign-in') }}</flux:link>
    </div>
</div>
