<div>

    @php($locale = app()->getLocale())
    {{-- <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="trans_choice('general.city', 2)" :crumbs="[
            ['label' => __('general.project-basics')],
            ['label' => trans_choice('general.city', 2), 'route' => 'cities.index'],
        ]" />
    </div> --}}
    <div class="flex justify-between items-center mb-6">

        <livewire:layout.breadcrumbs :title="trans_choice('general.city', 2)" :crumbs="[
            ['label' => __('general.project-basics')],
            ['label' => trans_choice('general.city', 2), 'route' => 'cities.index'],
        ]" />

        {{-- @activerole('HR', 'super-admin')
            <div class="flex items-center gap-2">
                <x-primary-button wire:click="$dispatchTo('basics.city-table', 'exportExcel')"
                    class="!bg-green-600 hover:!bg-green-700 focus:!bg-green-700 !ring-green-500 flex items-center gap-2"
                    :showSpinner="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M9 13l6 6"></path>
                        <path d="M15 13l-6 6"></path>
                    </svg>
                    @lang('general.export-excel')
                </x-primary-button>
            </div>
        @endactiverole --}}
        @activerole('HR','super-admin')
            <div class="flex items-center gap-2">
              <x-primary-button wire:click="$dispatchTo('basics.city-table', 'exportExcel')" :showSpinner="true">
                    @lang('general.export-excel')
                </x-primary-button>
            </div>
        @endactiverole

    </div>
    <livewire:basics.city-table />
</div>
