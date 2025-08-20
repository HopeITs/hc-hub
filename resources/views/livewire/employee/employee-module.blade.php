<div>
    <div class="flex justify-between">
        <livewire:layout.breadcrumbs :title="trans_choice('general.employee', 2)" :crumbs="[
            ['label' => trans_choice('general.employee_management', 2)],
            ['label' => trans_choice('general.employee', 2)],
        ]" :showButton="true" />
        <div>
            <x-primary-button :href="route('employees.personal-info-tab')">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading>@lang('general.name')</x-table.heading>
            <x-table.heading>@lang('general.photo')</x-table.heading>
            <x-table.heading>@lang('general.years_of_service')</x-table.heading>
            <x-table.heading>@lang('general.direct_manager')</x-table.heading>
            <x-table.heading>@lang('general.tech_manager')</x-table.heading>
            <x-table.heading>@lang('general.actual_job')</x-table.heading>
            <x-table.heading>@lang('general.work_email')</x-table.heading>
            <x-table.heading>@lang('general.status')</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->getTranslation('name', app()->getLocale()) }}</x-table.cell>
                    <x-table.cell>
                        <img src="{{ $item->photo ? asset('storage/' . $item->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($item->name) . '&color=7F9CF5&background=EBF4FF' }}"
                            alt="{{ $item->name }}" class="h-12 w-12 rounded-full object-cover">
                    </x-table.cell>
                    <x-table.cell>{{ $item->years_of_service }}</x-table.cell>
                    <x-table.cell>{{ $item->directManager?->getTranslation('name', app()->getLocale()) ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->techManager?->getTranslation('name', app()->getLocale()) ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->actual_job }}</x-table.cell>
                    <x-table.cell>{{ $item->account->email }}</x-table.cell>
                    <x-table.cell>
                        <x-badge :color="$item->is_active ? 'green' : 'red'">
                            {{ $item->is_active ? __('general.active') : __('general.inactive') }}
                        </x-badge>
                    </x-table.cell>
                    <x-table.cell>
                        <x-table.edit-btn :href="route('employees.edit', $item->id)" />
                        <x-table.delete-btn :modelId="$item->id" />
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data />
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $items->links() }}
        </x-slot>
    </x-table>
</div>
