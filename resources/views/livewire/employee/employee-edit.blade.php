<div>
    <livewire:layout.breadcrumbs :title="__('general.edit')" :crumbs="[
        ['label' => trans_choice('general.employee-management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.edit')],
    ]"/>

    <form wire:submit.prevent="saveInformation" class="mt-4">
        <x-tab.form activeTab="information">
            <x-tab.list>
                <x-tab.item :title="__('general.information')" activeTab="information"
                            activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.users class="w-5 h-5 mr-2"/>
                </x-tab.item>

                <x-tab.item :title="__('general.education')" activeTab="education" activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.education class="w-5 h-5 mr-2"/>
                </x-tab.item>

                <x-tab.item :title="__('general.courses')" activeTab="courses" activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.courses class="w-5 h-5 mr-2"/>
                </x-tab.item>

                <x-tab.item :title="__('general.experience')" activeTab="experience"
                            activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.experience class="w-5 h-5 mr-2"/>
                </x-tab.item>

                <x-tab.item :title="__('general.relatives')" activeTab="relatives" activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.relatives class="w-5 h-5 mr-2"/>
                </x-tab.item>

                <x-tab.item :title="__('general.badges')" activeTab="badges" activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.badges class="w-5 h-5 mr-2"/>
                </x-tab.item>

                <x-tab.item :title="__('general.checklist')" activeTab="checklist" activeClass="bg-blue-600 text-white"
                            inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.checklist class="w-5 h-5 mr-2"/>
                </x-tab.item>
            </x-tab.list>

            <div class="flex-grow w-full">
                <x-tab.container activeTab="information">
                    @include('livewire.employee.tabs.personal-info-tab')
                </x-tab.container>

                <x-tab.container activeTab="education">
                    @include('livewire.employee.tabs.education-fields')
                </x-tab.container>

                <x-tab.container activeTab="courses">
                    @include('livewire.employee.tabs.courses-fields')
                </x-tab.container>

                <x-tab.container activeTab="experience">
                    @include('livewire.employee.tabs.experience-fields')
                </x-tab.container>

                <x-tab.container activeTab="relatives">
                    @include('livewire.employee.tabs.relatives-fields')
                </x-tab.container>

                <x-tab.container activeTab="badges">
                    @include('livewire.employee.tabs.badges-fields')
                </x-tab.container>

                <x-tab.container activeTab="checklist">
                    @include('livewire.employee.tabs.checklist-fields')
                </x-tab.container>
            </div>
        </x-tab.form>

        @if ($errors->any())
            <div class="mt-4 text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </form>
</div>
