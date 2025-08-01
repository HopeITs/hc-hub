<x-slot:title>
    إعدادات الحساب
</x-slot:title>

<div class="grid md:grid-cols-4 pl-30 ">

    {{-- Tabs Sidebar --}}
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

        <form wire:submit.prevent="updateProfileInformation" class="space-y-6">

            {{-- Name Fields --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Arabic Name --}}
                <div class="space-y-2">
                    <x-forms.input-label
                        for="name_ar"
                        class="text-base font-medium"
                        :value="__('general.profile.name-ar')"/>
                    <x-forms.input
                        id="name_ar"
                        type="text"
                        model="name.ar"
                        autocomplete="name"/>
                    <x-forms.input-error field="name.ar"/>
                </div>

                {{-- English Name --}}
                <div class="space-y-2">
                    <x-forms.input-label
                        for="name_en"
                        class="text-base font-medium"
                        :value="__('general.profile.name-en')"/>
                    <x-forms.input
                        id="name_en"
                        type="text"
                        model="name.en"
                        autocomplete="name"/>
                    <x-forms.input-error field="name.en"/>
                </div>
            </div>

            {{-- Email --}}
            <div class="space-y-2">
                <x-forms.input-label
                    for="email"
                    class="text-base font-medium"
                    :value="__('general.profile.email')"/>
                <x-forms.input
                    id="email"
                    type="email"
                    model="email"
                    autocomplete="email"/>
                <x-forms.input-error field="email"/>
            </div>

            {{-- Email Verification Alert --}}
            <x-settings.email-verification-alert/>

            {{-- Submit Button --}}
            <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4 pt-6">
                <x-primary-button class="w-full sm:w-44 h-11 text-base">
                    {{ __('general.profile.save') }}
                </x-primary-button>

                <x-action-message
                    on="profile-updated"
                    class="text-sm text-green-600 dark:text-green-400">
                    {{ __('general.profile.got-saved') }}
                </x-action-message>
            </div>
        </form>
    </div>
</div>

