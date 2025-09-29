<div>
    @php
        use App\Models\Center;$center = null;
        if (request()->has('center')) {
            $center = Center::with('city')->find(request('center'));
        }
    @endphp

    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.positions')"
            :crumbs="array_filter([
                ['label' => __('general.programs-management')],
                ['label' => __('general.positions'), 'route' => route('positions.index')],
                $center ? ['label' => $center->name . ' (' . $center->city?->getTranslation('name', app()->getLocale()) . ')'] : null,
            ])"
            :showButton="true"
        />
        <div class="flex items-center gap-2">
            <x-primary-button :href="route('positions.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:position.position-table
        :center-id="request()->get('center')"
        :city-id="request()->get('city')"
    />
</div>
