<div class="space-y-4">
    @php($locale = $locale ?? app()->getLocale())

    <livewire:layout.breadcrumbs
        :title="trans_choice('general.contracts', 1)"
        :crumbs="[
            ['label' => trans_choice('general.employee_management', 2)],
            ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index', [], false) ?? null],
            ['label' => '#'.$contract->id],
        ]"
    />

    <div class="grid md:grid-cols-2 gap-4">
        <div class="rounded-2xl border p-4 bg-white dark:bg-gray-900">
            <h3 class="font-semibold mb-3">{{ __('general.employee') }}</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="font-medium inline">{{ __('general.name') }}:</dt>
                    <dd class="inline ms-1">
                        {{ $contract->employee?->getTranslation('name', $locale) ?? '—' }}
                    </dd>
                </div>
                <div><dt class="font-medium inline">{{ trans_choice('general.positions',1) }}:</dt>
                    <dd class="inline ms-1">
                        {{ $contract->position?->requiredPosition?->title ?? '#'.$contract->position_id }}
                    </dd>
                </div>
                <div><dt class="font-medium inline">{{  trans_choice('general.center',1) }}:</dt>
                    <dd class="inline ms-1">
                        {{ $contract->position?->center?->getTranslation('name', $locale) ?? '—' }}
                    </dd>
                </div>
                <div><dt class="font-medium inline">{{ trans_choice('general.department',1) }}:</dt>
                    <dd class="inline ms-1">{{ $contract->department?->name ?? '—' }}</dd>
                </div>
                <div><dt class="font-medium inline">{{  trans_choice('general.positions',1) }}:</dt>
                    <dd class="inline ms-1">
                        {{ $contract->jobLevel?->getTranslation('name', $locale) ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border p-4 bg-white dark:bg-gray-900">
            <h3 class="font-semibold mb-3">{{ __('general.contract-details') }}</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="font-medium inline">{{  trans_choice('general.contract-type',1) }}:</dt>
                    <dd class="inline ms-1">
                        {{ $contract->contractType?->getTranslation('name', $locale) ?? '—' }}
                    </dd>
                </div>
                <div><dt class="font-medium inline">{{ __('general.period') }}:</dt>
                    <dd class="inline ms-1">
                        <x-period :start="$contract->start_date" :end="$contract->end_date"/>
                    </dd>
                </div>
                <div><dt class="font-medium inline">{{ __('general.yearly-leave-days') }}:</dt>
                    <dd class="inline ms-1">{{ $contract->yearly_leave_days ?? '—' }}</dd>
                </div>
                <div><dt class="font-medium inline">{{ __('general.salary-per') }}:</dt>
                    <dd class="inline ms-1">
                        @php($per = \App\Enums\SalaryPer::getLocalizedName(
                            $contract->salary_per instanceof \BackedEnum
                                ? $contract->salary_per->value
                                : (string) $contract->salary_per
                        ))
                        <x-badge :color="$per['color']">{{ $per['value'] }}</x-badge>
                        <span class="ms-2">{{ number_format((float)$contract->salary, 2) }}</span>
                    </dd>
                </div>
                <div><dt class="font-medium inline">{{ __('general.weekly-hours') }}:</dt>
                    <dd class="inline ms-1">{{ $contract->weekly_hours ?? '—' }}</dd>
                </div>
                <div><dt class="font-medium inline">{{ __('general.status') }}:</dt>
                    <dd class="inline ms-1">
                        <x-badge :color="$contract->is_active ? 'green' : 'red'">
                            {{ $contract->is_active ? __('general.active') : __('general.inactive') }}
                        </x-badge>
                    </dd>
                </div>
                @if($contract->terminated_at)
                    <div><dt class="font-medium inline">{{ __('general.terminated_at') }}:</dt>
                        <dd class="inline ms-1">{{ $contract->terminated_at?->toDateString() }}</dd>
                    </div>
                    <div><dt class="font-medium inline">{{ __('general.reason_of_termination') }}:</dt>
                        <dd class="inline ms-1">{{ $contract->reason_of_termination ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <div class="mt-6 flex gap-2">
        <x-primary-button :href="route('contracts.edit', $contract->id)">
            {{ __('general.edit') }}
        </x-primary-button>
        <x-secondary-button :href="route('contracts.index')">
            {{ __('general.back') }}
        </x-secondary-button>
    </div>
</div>
