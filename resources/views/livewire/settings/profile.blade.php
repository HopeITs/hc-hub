<x-slot:title>
    إعدادات الحساب
</x-slot:title>

<section class="w-full bg-gray-50 dark:bg-gray-900 py-5 px-4 sm:px-6 lg:px-8">

    <livewire:layouts.breadcrumbs
        :title="__('general.profile.profile')"
        :crumbs="[
            ['label' => __('general.dashboard'), 'url' => route('dashboard')],
            ['label' => __('general.profile.settings')],
        ]"/>
    <x-settings.card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <x-settings.tab-navigation
                :tabs="[
                        ['label' => __('general.profile.profile'), 'route' => 'settings.profile'],
                        ['label' => __('general.profile.Password'), 'route' => 'settings.password'],
                       ]"/>
            <x-settings.form-section>
                <form wire:submit="updateProfileInformation" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- English Name --}}
                        <x-settings.text-input
                            wire:model="name.ar"
                            label="{{ __('general.profile.name-ar') }}"
                            type="text"
                            required
                            autocomplete="name"/>

                        {{-- Arabic Name --}}
                        <x-settings.text-input
                            wire:model="name.en"
                            label="{{ __('general.profile.name-en') }}"
                            type="text"
                            required
                            autocomplete="name"/>
                    </div>


                    {{-- Email Field --}}
                    <x-settings.text-input
                        wire:model="email"
                        label="{{ __('general.profile.email') }}"
                        type="email"
                        required
                        autocomplete="email"/>

                    {{-- Email Verification Notice --}}
                    <x-settings.email-verification-alert />

                    {{-- Save Button + Message --}}
                    <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                        <x-settings.submit-button>
                            {{ __('general.profile.save') }}
                        </x-settings.submit-button>

                        <x-action-message on="profile-updated" class="text-sm text-green-600 dark:text-green-400">
                            {{ __('general.profile.got-saved') }}
                        </x-action-message>

                    </div>
                </form>

                {{-- Delete Section --}}
                <div>
                    <livewire:settings.delete-user-form/>
                </div>

            </x-settings.form-section>
        </div>
    </x-settings.card>
</section>
