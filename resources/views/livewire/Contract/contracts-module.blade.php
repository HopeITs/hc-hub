@php use App\Enums\SalaryPer; @endphp
<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts', 2)"
            :crumbs="[
                ['label' => trans_choice('general.employee_management', 2)],
                ['label' => trans_choice('general.contracts', 2)],
            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button :href="route('contracts.create')">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading>@lang('general.employee')</x-table.heading>
            <x-table.heading>{{ trans_choice('general.positions', 1) }}</x-table.heading>
            <x-table.heading>{{ trans_choice('general.contract-type', 1) }}</x-table.heading>
            <x-table.heading>{{ trans_choice('general.department', 1) }}</x-table.heading>
            <x-table.heading>{{ trans_choice('general.job-level', 1) }}</x-table.heading>
            <x-table.heading data-sort="period">@lang('general.period')</x-table.heading>
            <x-table.heading>@lang('general.yearly-leave-days')</x-table.heading>
            <x-table.heading>@lang('general.salary-per')</x-table.heading>
            <x-table.heading>@lang('general.weekly-hours')</x-table.heading>
            <x-table.heading>@lang('general.status')</x-table.heading>
            <x-table.heading/>
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>

                    <x-table.cell>
                        {{ $item->employee?->getTranslation('name', $locale) ?? '—' }}
                    </x-table.cell>

                    <x-table.cell>
                        {{ $item->position?->requiredPosition?->title ?? '#'.$item->position_id }}
                    </x-table.cell>

                    <x-table.cell>
                        {{ $item->contractType?->getTranslation('name', $locale) ?? '—' }}
                    </x-table.cell>

                    <x-table.cell>
                        {{ $item->department?->name ?? '—' }}
                    </x-table.cell>

                    <x-table.cell>
                        {{ $item->jobLevel?->getTranslation('name', $locale) ?? '—' }}
                    </x-table.cell>

                    <x-table.cell>
                        <x-period :start="$item->start_date" :end="$item->end_date"/>
                    </x-table.cell>

                    <x-table.cell>{{ $item->yearly_leave_days ?? '—' }}</x-table.cell>

                    <x-table.cell>
                        @php($per = \App\Enums\SalaryPer::getLocalizedName(
                        $item->salary_per instanceof \BackedEnum
                            ? $item->salary_per->value
                            : (string) $item->salary_per
                        ))
                        <x-badge :color="$per['color']">{{ $per['value'] }}</x-badge>
                    </x-table.cell>

                    <x-table.cell>{{ $item->weekly_hours ?? '—' }}</x-table.cell>

                    <x-table.cell>
                        <x-badge :color="$item->is_active ? 'green' : 'red'">
                            {{ $item->is_active ? __('general.active') : __('general.inactive') }}
                        </x-badge>
                    </x-table.cell>

                    <x-table.cell>
                        <x-table.edit-btn :href="route('contracts.edit', $item->id)"/>
                        <x-table.delete-btn :modelId="$item->id"/>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $items->links() }}
        </x-slot>
    </x-table>
</div>
