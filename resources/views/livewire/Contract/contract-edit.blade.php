<div>
    <livewire:layout.breadcrumbs :title="__('general.edit')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index')],
        ['label' => __('general.edit')],
    ]" />

    <form wire:submit.prevent="save" class="mt-4">
        <x-tab.form activeTab="identity">
            <x-tab.list>
                <x-tab.item
                    :title="__('general.identity-org')"
                    activeTab="identity"
                    activeClass="bg-blue-600 text-white"
                    inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.users class="w-5 h-5 mr-2" />
                </x-tab.item>

                <x-tab.item
                    :title="__('general.terms-status')"
                    activeTab="terms"
                    activeClass="bg-blue-600 text-white"
                    inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.calendar class="w-5 h-5 mr-2" />
                </x-tab.item>

                <x-tab.item
                    :title="__('general.compensation-schedule')"
                    activeTab="comp"
                    activeClass="bg-blue-600 text-white"
                    inactiveClass="bg-gray-50 hover:bg-gray-100">
                    <x-icons.currency-dollar class="w-5 h-5 mr-2" />
                </x-tab.item>
            </x-tab.list>

            <div class="flex-grow w-full">
                <x-tab.container activeTab="identity">
                    @include('livewire.contract.partials.identity-fields')
                </x-tab.container>

                <x-tab.container activeTab="terms">
                    @include('livewire.contract.partials.terms-fields')
                </x-tab.container>

                <x-tab.container activeTab="comp">
                    @include('livewire.contract.partials.compensation-fields')
                </x-tab.container>
            </div>
        </x-tab.form>

        <div class="flex justify-end space-x-2 pt-4 mt-6 border-t">
            <x-primary-button type="submit">@lang('general.save')</x-primary-button>
            <x-secondary-button :href="route('contracts.index')">@lang('general.cancel')</x-secondary-button>
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
