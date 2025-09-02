@php use App\Enums\SalaryPer; @endphp
<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts', 2)"
            :crumbs="[
                ['label' => trans_choice('general.employee-management', 2)],
                ['label' => trans_choice('general.contracts', 2)],
            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button :href="route('contracts.create')">
                @lang('general.add-new')
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
                <tr wire:key="contract-row-{{ $item->id }}">
                    <x-table.cell>{{ $item->id }}</x-table.cell>

                    {{-- Employee --}}
                    <x-table.cell>
                        {{ $item->employee?->getTranslation('name', $locale) ?? '—' }}
                    </x-table.cell>

                    {{-- Position (show title if present, fallback to requiredPosition->title or #id) --}}
                    <x-table.cell>
                        {{ $item->position?->title
                            ?? $item->position?->requiredPosition?->title
                            ?? '#'.$item->position_id }}
                    </x-table.cell>

                    {{-- Contract Type --}}
                    <x-table.cell>
                        {{ $item->contractType?->getTranslation('name', $locale) ?? '—' }}
                    </x-table.cell>

                    {{-- Department via position --}}
                    <x-table.cell>
                        {{ $item->position?->department?->name ?? '—' }}
                    </x-table.cell>

                    {{-- Job Level via position --}}
                    <x-table.cell>
                        {{ $item->position?->jobLevel?->getTranslation('name', $locale)
                            ?? $item->position?->jobLevel?->name
                            ?? '—' }}
                    </x-table.cell>

                    {{-- Period --}}
                    <x-table.cell>
                        <x-period :start="$item->start_date" :end="$item->end_date"/>
                    </x-table.cell>

                    {{-- Yearly leave --}}
                    <x-table.cell>{{ $item->yearly_leave_days ?? '—' }}</x-table.cell>

                    {{-- Salary per --}}
                    <x-table.cell>
                        @php($per = SalaryPer::getLocalizedName(
                            $item->salary_per instanceof \BackedEnum
                                ? $item->salary_per->value
                                : (string) $item->salary_per
                        ))
                        <x-badge :color="$per['color']">{{ $per['value'] }}</x-badge>
                    </x-table.cell>

                    {{-- Weekly hours --}}
                    <x-table.cell>{{ $item->weekly_hours ?? '—' }}</x-table.cell>

                    {{-- Status --}}
                    <x-table.cell>
                        <x-badge :color="$item->is_active ? 'green' : 'red'">
                            {{ $item->is_active ? __('general.active') : __('general.inactive') }}
                        </x-badge>
                    </x-table.cell>

                    {{-- Actions --}}
                    <x-table.cell>
                        <a href="{{ route('contracts.show', $item->id) }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                           title="{{ __('general.view') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
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
