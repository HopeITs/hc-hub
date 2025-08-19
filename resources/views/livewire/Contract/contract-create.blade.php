<div>
    <livewire:layout.breadcrumbs :title="__('general.add_new')" :crumbs="[
        ['label' => trans_choice('general.employee_management', 2)],
        ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index')],
        ['label' => __('general.add_new')],
    ]" />

    <x-tab.form activeTab="identity">
        <x-tab.list>
            <x-tab.item :title="__('general.identity-org')" activeTab="identity">
                <!-- user icon -->
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zM3 18a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            </x-tab.item>

            <x-tab.item :title="__('general.terms-status')" activeTab="terms">
                <!-- calendar icon -->
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1z"/>
                    <path d="M18 9H2v7a2 2 0 002 2h12a2 2 0 002-2V9z"/>
                </svg>
            </x-tab.item>

            <x-tab.item :title="__('general.compensation-schedule')" activeTab="comp">
                <!-- currency icon -->
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 100 12h3a1 1 0 110 2h-3a8 8 0 110-16h3a1 1 0 110 2h-3z"/>
                </svg>
            </x-tab.item>
        </x-tab.list>

        <div class="flex-grow w-full">
            <x-tab.container active-tab="identity">
                @include('livewire.contract.partials.identity-fields')
            </x-tab.container>

            <x-tab.container active-tab="terms">
                @include('livewire.contract.partials.terms-fields')
            </x-tab.container>

            <x-tab.container active-tab="comp">
                @include('livewire.contract.partials.pay-fields')

            </x-tab.container>
        </div>
    </x-tab.form>
</div>
