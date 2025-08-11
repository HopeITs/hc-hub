<div>
    <livewire:layout.breadcrumbs :title="__('general.edit')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.edit')],
    ]" />

    <form wire:submit.prevent="save" class="mt-4">
        <div x-data="{ activeTab: 'information' }" class="flex flex-col md:flex-row gap-8">
            <ul class="flex-none w-full md:w-48 text-sm font-medium text-gray-500 space-y-2">
                <li>
                    <button type="button" @click="activeTab = 'information'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'information', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'information' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.users class="w-5 h-5 mr-2" />
                        <span class="font-semibold">@lang('general.information')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'education'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'education', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'education' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.education />
                        <span class="font-semibold">@lang('general.education')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'courses'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'courses', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'courses' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.courses />
                        <span class="font-semibold">@lang('general.courses')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'experience'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'experience', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'experience' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.experience />
                        <span class="font-semibold">@lang('general.experience')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'relatives'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'relatives', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'relatives' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.relatives />
                        <span class="font-semibold">@lang('general.relatives')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'badges'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'badges', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'badges' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.badges />
                        <span class="font-semibold">@lang('general.badges')</span>
                    </button>
                </li>
                <li>
                    <button type="button" @click="activeTab = 'checklist'"
                        :class="{ 'bg-blue-600 text-white': activeTab === 'checklist', 'bg-gray-50 hover:bg-gray-100': activeTab !== 'checklist' }"
                        class="w-full flex items-center p-3 rounded-lg">
                        <x-icons.checklist />
                        <span class="font-semibold">@lang('general.checklist')</span>
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
                <div x-show="activeTab === 'courses'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.courses-fields')
                </div>
                <div x-show="activeTab === 'experience'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.experience-fields')
                </div>
                <div x-show="activeTab === 'relatives'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.relatives-fields')
                </div>
                <div x-show="activeTab === 'badges'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.badges-fields')
                </div>
                <div x-show="activeTab === 'checklist'" x-cloak style="display: none;" class="p-4 border rounded-md">
                    @include('livewire.employee.partials.checklist-fields')
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('employees.index')">@lang('general.cancel')</x-secondary-button>
        </div>

        @if ($errors->any())
            <div class="mt-4 text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </form>
</div>
