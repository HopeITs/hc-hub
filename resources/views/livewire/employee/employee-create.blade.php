<div>
    <livewire:layout.breadcrumbs :title="__('general.add_new')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.add_new')],
    ]" />

    <form wire:submit.prevent="save" class="mt-4">
        <div x-data="{ activeTab: 'information' }" class="flex flex-col md:flex-row gap-8">
            <ul class="flex-none w-full md:w-48 text-sm font-medium text-gray-500 space-y-2">
                <li>
                    <button type="button" @click="activeTab = 'information'" :class="{ 'bg-blue-600 text-white': activeTab === 'information', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'information' }" class="w-full flex items-center p-3 rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-semibold">@lang('general.information')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'education'" :class="{ 'bg-blue-600 text-white': activeTab === 'education', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'education' }" class="w-full flex items-center p-3 rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 16c1.255 0 2.443-.29 3.5-.804V4.804zM14.5 4c1.255 0 2.443.29 3.5.804v10A7.969 7.969 0 0114.5 16c-1.255 0-2.443-.29-3.5-.804V4.804A7.968 7.968 0 0114.5 4z"></path>
                        </svg>
                        <span class="font-semibold">@lang('general.education')</span>
                    </button>
                </li>
            </ul>

            <div class="flex-grow w-full">
                <div x-show="activeTab === 'information'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.info-form')
                </div>
                <div x-show="activeTab === 'education'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.education-fields')
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
        </div>
    </form>
</div>
