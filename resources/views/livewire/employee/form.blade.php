<div>
    <livewire:layout.breadcrumbs :title="__('general.add-new')" :crumbs="[
        ['label' => trans_choice('general.employee-management', 2)],
        ['label' => trans_choice('general.employee', 2), 'url' => route('employees.index')],
        ['label' => __('general.add-new')],
    ]" />

    <x-tab.form activeTab="information">
        <x-tab.list>
            <x-tab.item :title="__('general.information')"  activeTab="information" :active="$activeTab === 'information'">
                <x-icons.user class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.education')"  :disabled="!isset($infoForm->id)" activeTab="education" :active="$activeTab === 'education'">
                <x-icons.education class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.courses')" activeTab="courses" :disabled="!isset($infoForm->id)" :active="$activeTab === 'courses'">
                <x-icons.courses class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.experience')" activeTab="experience" :disabled="!isset($infoForm->id)" :active="$activeTab === 'experience'">
                <x-icons.experience class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.relatives')" activeTab="relatives" :disabled="!isset($infoForm->id)" :active="$activeTab === 'relatives'">
                <x-icons.relatives class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.badges')" activeTab="badges" :disabled="!isset($infoForm->id)" :active="$activeTab === 'badges'">
                <x-icons.badges class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.checklist')" activeTab="checklist" :disabled="!isset($infoForm->id)" :active="$activeTab === 'checklist'">
                <x-icons.checklist class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.attachments')" activeTab="attachments" :disabled="!isset($infoForm->id)" :active="$activeTab === 'attachments'">
                <x-icons.attachment class="w-5 h-5 mr-2" />
            </x-tab.item>
            <x-tab.item :title="__('general.items')" activeTab="items" :disabled="!isset($infoForm->id)" :active="$activeTab === 'items'">
                <x-icons.items class="w-5 h-5 mr-2" />
            </x-tab.item>
        </x-tab.list>

        <div class="flex-grow w-full">
            <x-tab.container activeTab="information">
                @if($activeTab === 'information')
                    @include('livewire.employee.tabs.personal-info-tab')
                @elseif($activeTab === 'education')
                    @include('livewire.employee.tabs.education-fields')
                @elseif($activeTab === 'courses')
                    @include('livewire.employee.tabs.courses-fields')
                @elseif($activeTab === 'experience')
                    @include('livewire.employee.tabs.experience-fields')
                @elseif($activeTab === 'relatives')
                    @include('livewire.employee.tabs.relatives-fields')
                @elseif($activeTab === 'badges')
                    @include('livewire.employee.tabs.badges-fields')
                @elseif($activeTab === 'checklist')
                    @include('livewire.employee.tabs.checklist-fields')
                @elseif($activeTab === 'attachments')
                    @include('livewire.employee.tabs.attachments-fields')
                @elseif($activeTab === 'items')
                    @include('livewire.employee.tabs.items-fields')
                @else
                    @include('livewire.employee.tabs.personal-info-tab')
                @endif
            </x-tab.container>
        </div>
    </x-tab.form>
</div>
