<section class="w-full bg-gray-50 dark:bg-gray-900 py-5 px-4 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
{{--    <livewire:layout.breadcrumbs--}}
{{--        :title="__('general.profile.update-password')"--}}
{{--        :crumbs="[--}}
{{--            ['label' => __('general.dashboard'), 'url' => route('dashboard')],--}}
{{--            ['label' => __('general.profile.settings')],--}}
{{--        ]"--}}
{{--    />--}}

    <x-settings.card>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

            {{-- Sidebar Tabs --}}
            <x-settings.tab-navigation
                :tabs="[
                    ['label' => __('general.profile.profile'), 'route' => 'settings.profile'],
                    ['label' => __('general.profile.Password'), 'route' => 'settings.password'],
                ]"
            />

            {{-- Form Section --}}
            <x-settings.form-section>

                <form wire:submit="updatePassword" class="space-y-6">

                    {{-- Current Password --}}
                    <x-settings.password-input
                        id="current_password"
                        wire:model.defer="current_password"
                        label="{{ __('general.profile.current-password') }}"
                        autocomplete="current-password"
                        :error="$errors->get('current_password')"
                    />

                    {{-- New Password --}}
                    <x-settings.password-input
                        id="password"
                        wire:model.defer="password"
                        label="{{ __('general.profile.new-password') }}"
                        autocomplete="new-password"
                        :error="$errors->get('password')"
                    />

                    {{-- Confirm Password --}}
                    <x-settings.password-input
                        id="password_confirmation"
                        wire:model.defer="password_confirmation"
                        label="{{ __('auth.confirm-password') }}"
                        autocomplete="new-password"
                        :error="$errors->get('password_confirmation')"
                    />

                    {{-- Submit Button --}}
                    <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                        <x-settings.submit-button>
                            {{ __('general.profile.save') }}
                        </x-settings.submit-button>

                        <x-action-message on="password-updated" class="text-sm text-green-600 dark:text-green-400">
                            {{ __('general.profile.got-saved') }}
                        </x-action-message>
                    </div>

                </form>

            </x-settings.form-section>
        </div>

    </x-settings.card>
</section>
