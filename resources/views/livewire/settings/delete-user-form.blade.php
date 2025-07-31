<section class="pt-5 mt-4 border-t border-gray-200 dark:border-gray-700">
    <div x-data="{ open: false }">

        {{--  Section Header --}}
        <div class="space-y-1 mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                {{ __('general.profile.delete-account') }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('general.profile.delete-account-text') }}
            </p>
        </div>

        {{--  Trigger Button using Submit Component --}}
        <x-settings.submit-button
            type="button"
            @click="open = true"
            class="bg-[#E63946] hover:bg-[#d92f3c]"
        >
            <i class="ti ti-trash text-base"></i>
            {{ __('general.profile.delete-account') }}
        </x-settings.submit-button>

        {{--  Modal --}}
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @keydown.escape.window="open = false">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-lg p-6 relative"
                @click.away="open = false">
                {{-- Close Icon --}}
                <button
                    type="button"
                    @click="open = false"
                    class="absolute top-4 left-4 text-gray-400 hover:text-gray-600">
                    <i class="ti ti-x text-xl"></i>
                </button>

                {{--  Form --}}
                <form wire:submit.prevent="deleteUser" class="space-y-6 mt-2">

                    {{-- Modal Title --}}
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-1">
                            {{ __('general.profile.delete-account-confirm') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('general.profile.delete-account-confirm-text') }}
                        </p>
                    </div>

                    {{--  Password Input using password-input component --}}
                    <x-settings.password-input
                        id="password"
                        wire:model.defer="password"
                        label="{{ __('general.profile.Password') }}"
                        autocomplete="current-password"
                        :error="$errors->get('password')"/>

                    {{--  Footer Buttons --}}
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="open = false"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg shadow transition-all focus:ring-2 focus:ring-gray-300 focus:ring-offset-1">
                            {{ __('general.profile.cancel') }}
                        </button>

                        <x-settings.submit-button class="bg-[#E63946] hover:bg-[#d92f3c]">
                            <i class="ti ti-trash text-base"></i>
                            {{ __('general.profile.delete-account') }}
                        </x-settings.submit-button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</section>
