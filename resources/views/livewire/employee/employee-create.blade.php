<div>
    <livewire:layout.breadcrumbs :title="__('general.add_new')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.add_new')],
    ]"/>


        <x-tab.form activeTab="information">
            <x-tab.list>
                <x-tab.item :title="__('general.information')" activeTab="information">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.education')" activeTab="education">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.courses')" activeTab="courses">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.experience')" activeTab="experience">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.relatives')" activeTab="relatives">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.badges')" activeTab="badges">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
                <x-tab.item :title="__('general.checklist')" activeTab="checklist">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                              clip-rule="evenodd"></path>
                    </svg>
                </x-tab.item>
            </x-tab.list>

        <div class="flex-grow w-full">
            <x-tab.container active-tab="information">
                @include('livewire.employee.partials.info-form')
            </x-tab.container>
            <x-tab.container active-tab="education">
                @include('livewire.employee.partials.education-fields')
            </x-tab.container>
            <x-tab.container active-tab="courses">
                @include('livewire.employee.partials.courses-fields')
            </x-tab.container>
            <x-tab.container active-tab="experience">
                @include('livewire.employee.partials.experience-fields')
            </x-tab.container>
            <x-tab.container active-tab="relatives">
                @include('livewire.employee.partials.relatives-fields')
            </x-tab.container>
            <x-tab.container active-tab="badges">
                @include('livewire.employee.partials.badges-fields')
            </x-tab.container>
            <x-tab.container active-tab="checklist">
                @include('livewire.employee.partials.checklist-fields')
            </x-tab.container>

        </div>
    </x-tab.form>



</div>

