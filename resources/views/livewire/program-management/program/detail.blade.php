<div class="space-y-8" x-data="{ activeTab: 'details' }">
    <livewire:layout.breadcrumbs :title="__('general.programs-management')" :crumbs="[
        ['label' => __('general.programs-management')],
        ['label' => trans_choice('general.program', 2), 'url' => route('programs.index')],
        ['label' => $program->name],
    ]" />

    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div class="w-full">
            <div class="flex justify-between">
                <div class="flex items-center  gap-3">

                    <h1  class="text-3xl font-bold text-gray-800" tooltip>{{ $program->name }}
                    </h1>

                    <span
                        class="bg-primary-100 text-primary-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $program->code .'-'. $program->slug }}</span>
                </div>
                <div class="flex h-fit  space-x-2 ">
                    <x-forms.toggle-input class="!me-2 text-xl p-2" :active="$program->is_active" :model-id="$program->id" wire-event="confirmToggleItemStatus" />
                    <x-table.edit-btn class="!p-0 me-2" :href="route('programs.edit', ['program' => $program->slug])" />
                    <x-table.delete-btn class="!p-0" :modelId="$program->id" />
                </div>
            </div>

            <p class="text-md text-gray-500 mt-2">
                {{ $program->getTranslation('description', app()->getLocale()) }}
            </p>
        </div>


    </div>

    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <a href="#" @click.prevent="activeTab = 'details'"
                :class="{
                    'border-primary text-primary': activeTab === 'details',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'details'
                }"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                @lang('general.project-info')
            </a>
            <a href="#" @click.prevent="activeTab = 'centers'"
                :class="{
                    'border-primary text-primary': activeTab === 'centers',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'centers'
                }"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                {{ trans_choice('general.center', 2) }}
            </a>
            <a href="#" @click.prevent="activeTab = 'employees'"
                :class="{
                    'border-primary text-primary': activeTab === 'employees',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'employees'
                }"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                {{ trans_choice('general.employee', 2) }}
            </a>
        </nav>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div x-show="activeTab === 'details'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 text-sm">
                <div class="flex items-start gap-3">
                    <x-icon name="arrows-pointing-in" class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <span class="font-semibold text-gray-800">@lang('general.slug'):</span>
                        <p class="text-gray-600">{{ $program->slug }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3"></div>
                <div class="flex items-start gap-3">
                    <x-icon name="calendar" class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <span class="font-semibold text-gray-800">@lang('general.start-date'):</span>
                        <p class="text-gray-600">{{ $program->start_date?->format('d M, Y') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <x-icon name="calendar" class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <span class="font-semibold text-gray-800">@lang('general.end-date'):</span>
                        <p class="text-gray-600">{{ $program->end_date?->format('d M, Y') ?? __('general.active') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'centers'" x-cloak>
            @if ($program->centers->isNotEmpty())
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-medium">@lang('general.name')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.city')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.created_at')</th>
                                <th class="px-6 py-3 font-medium text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                            @foreach ($program->centers as $center)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{-- CORRECTED: Access Center name directly --}}
                                        {{ $center->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $center->city?->getTranslation('name', app()->getLocale()) ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $center->created_at->format('d M, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <x-table.edit-btn :href="route('centers.index', ['edit' => $center->id])" class="mx-auto" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">@lang('general.no-centers')</p>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'employees'" x-cloak>
            @if ($employees && $employees->isNotEmpty())
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-medium">@lang('general.employee-name')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.position')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.contract-start')</th>
                                <th class="px-6 py-3 font-medium">@lang('general.contract-end')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                            @foreach ($employees as $employee)
                                @foreach ($employee->contracts as $contract)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ $employee->getTranslation('name', app()->getLocale()) ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $contract->position?->requiredPosition?->title ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $contract->start_date?->format('d M, Y') ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ $contract->end_date?->format('d M, Y') ?? __('general.active') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">@lang('general.no-employees')</p>
                </div>
            @endif
        </div>
    </div>
</div>
