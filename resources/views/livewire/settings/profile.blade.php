<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('general.my-profile')" :subheading="__('general.profile.manage-settings')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input class="text-secondary-dark outline-secondary-light" wire:model="name.en" :label="__('general.profile.name-en')" type="text" required autofocus autocomplete="name" />
            <flux:input class="text-secondary-dark"  wire:model="name.ar" :label="__('general.profile.name-ar')" type="text" required autofocus autocomplete="name" />
            <div>
                <flux:input class="text-secondary-dark" wire:model="email" :label="__('general.profile.email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button  variant="primary" type="submit" class="w-full bg-secondary-medium">{{ __('general.profile.save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('general.profile.got-saved') }}
                </x-action-message>
            </div>
        </form>

{{--        <livewire:settings.delete-user-form />--}}
    </x-settings.layout>
</section>
