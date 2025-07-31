<x-slot:title>
    إعدادات الحساب
</x-slot:title>

<section class="w-full bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-10">
    <x-settings.card class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-0 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-4">

            {{-- Tabs Sidebar --}}
            <div class="bg-gray-100 dark:bg-gray-800 p-6">
                <x-settings.tab-navigation
                    :tabs="[
                        ['label' => __('general.profile.profile'), 'route' => 'settings.profile'],
                        ['label' => __('general.profile.Password'), 'route' => 'settings.password'],
                    ]"
                />
            </div>

            {{-- Main Form Area --}}
            <div class="col-span-3 p-8 bg-white dark:bg-gray-900 space-y-6">
                <form wire:submit="updateProfileInformation" class="space-y-6">

                    {{-- Name Fields --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-settings.text-input
                            wire:model="name.ar"
                            label="{{ __('general.profile.name-ar') }}"
                            type="text"
                            required
                            autocomplete="name" />

                        <x-settings.text-input
                            wire:model="name.en"
                            label="{{ __('general.profile.name-en') }}"
                            type="text"
                            required
                            autocomplete="name" />
                    </div>

                    {{-- Email Field --}}
                    <x-settings.text-input
                        wire:model="email"
                        label="{{ __('general.profile.email') }}"
                        type="email"
                        required
                        autocomplete="email" />

                    {{-- Email Verification Info --}}
                    <x-settings.email-verification-alert />

                    {{-- Save Button --}}
                    <div class="flex items-center justify-start gap-4 pt-2">
                        <x-settings.submit-button>
                            {{ __('general.profile.save') }}
                        </x-settings.submit-button>

                        <x-action-message on="profile-updated" class="text-sm text-green-600 dark:text-green-400">
                            {{ __('general.profile.got-saved') }}
                        </x-action-message>
                    </div>
                </form>
            </div>
        </div>
    </x-settings.card>
</section>
