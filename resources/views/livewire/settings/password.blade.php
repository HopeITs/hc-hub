<x-slot:title>
    إعدادات الحساب
</x-slot:title>

        <div class="grid md:grid-cols-4 pl-30">

            {{-- Sidebar Tabs --}}
            <aside class="bg-gray-50 dark:bg-gray-900 p-6 border-e border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-8">
                    {{ __('general.profile.settings') }}
                </h3>

                <x-settings.tab-navigation
                    :tabs="[
                        ['label' => __('general.profile.profile'), 'route' => 'settings.profile'],
                        ['label' => __('general.profile.Password'), 'route' => 'settings.password'],
                    ]"
                />
            </aside>

            {{-- Form Content --}}
            <div class="col-span-3 p-10 space-y-8">
                <form wire:submit.prevent="updatePassword" class="space-y-6">

                    {{-- Current Password --}}
                    <x-forms.password-input
                        id="current_password"
                        label="{{ __('general.profile.current-password') }}"
                        model="current_password"
                        autocomplete="current-password"
                    />

                    {{-- New Password --}}
                    <x-forms.password-input
                        id="password"
                        label="{{ __('general.profile.new-password') }}"
                        model="password"
                        autocomplete="new-password"
                    />

                    {{-- Confirm Password --}}
                    <x-forms.password-input
                        id="password_confirmation"
                        label="{{ __('auth.confirm-password') }}"
                        model="password_confirmation"
                        autocomplete="new-password"
                    />

                    {{-- Submit --}}
                    <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4 pt-6">
                        <x-primary-button class="w-full sm:w-44 h-11 text-base">
                            {{ __('general.profile.save') }}
                        </x-primary-button>

                        <x-action-message on="password-updated" class="text-sm text-green-600 dark:text-green-400">
                            {{ __('general.profile.got-saved') }}
                        </x-action-message>
                    </div>

                </form>
            </div>
        </div>
